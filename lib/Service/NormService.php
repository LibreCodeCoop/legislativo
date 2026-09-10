<?php

declare(strict_types=1);

namespace OCA\Legislativo\Service;

use OCA\Legislativo\Db\Norm;
use OCA\Legislativo\Db\NormMapper;
use OCA\Legislativo\Db\NormRelationMapper;
use OCA\Legislativo\Db\NormVersion;
use OCA\Legislativo\Db\NormVersionMapper;
use OCA\Legislativo\Exception\ValidationException;

class NormService {
	private const RELATIONS = ['amends', 'repeals', 'regulates', 'references'];
	public function __construct(private NormMapper $norms, private NormVersionMapper $versions, private NormRelationMapper $relations, private AuthorizationService $authorization, private AuditService $audit, private TextNormalizer $textNormalizer) {}

	/** @return list<array<string,mixed>> */
	public function search(array $filters, bool $public = false): array { return array_map(fn (Norm $norm): array => $this->summary($norm, $public), $this->norms->search($filters, (int)($filters['limit'] ?? 50), (int)($filters['offset'] ?? 0), $public)); }

	/** @return array<string,mixed> */
	public function get(int $id, bool $public = false): array {
		$norm = $this->norms->find($id);
		if ($public && $norm->getStatus() !== 'published') throw new ValidationException('Norma não publicada.');
		$versions = $this->versions->findByNorm($id); $relations = [];
		foreach ($this->relations->findByNorm($id) as $relation) { $otherId = $relation->getSourceNormId() === $id ? $relation->getTargetNormId() : $relation->getSourceNormId(); try { $other = $this->norms->find($otherId); if (!$public || $other->getStatus() === 'published') $relations[] = ['relation' => $relation->jsonSerialize(), 'norm' => $this->summary($other, true)]; } catch (\Throwable) {} }
		return ['norm' => $this->summary($norm, $public), 'versions' => array_map(fn (NormVersion $version): array => $public ? $this->publicVersion($version) : $version->jsonSerialize(), $public ? array_values(array_filter($versions, fn (NormVersion $version): bool => $version->getValidFrom() <= new \DateTime('today') && ($version->getValidUntil() === null || $version->getValidUntil() >= new \DateTime('today')))) : $versions), 'relations' => $relations];
	}

	public function save(array $data, ?int $id = null): Norm {
		$uid = $this->authorization->requireWrite(); $type = trim((string)($data['type'] ?? '')); $number = (int)($data['number'] ?? 0); $year = (int)($data['year'] ?? 0); $title = trim((string)($data['title'] ?? '')); $status = trim((string)($data['status'] ?? 'draft'));
		if ($type === '' || mb_strlen($type) > 64 || $number < 1 || $year < 1800 || $year > 2200 || $title === '' || mb_strlen($title) > 512 || !in_array($status, ['draft', 'published', 'revoked'], true)) throw new ValidationException('Tipo, numeração, ano, título ou situação da norma inválidos.');
		$now = $this->now(); $before = null;
		if ($id !== null) { $norm = $this->norms->find($id); $before = $norm->jsonSerialize(); } else { $norm = new Norm(); $norm->setCreatedBy($uid); $norm->setCreatedAt($now); }
		$norm->setType($type); $norm->setNumber($number); $norm->setYear($year); $norm->setTitle($title); $norm->setEmenta($this->optional($data['ementa'] ?? null, 10000)); $norm->setStatus($status); $norm->setPublishedAt($status === 'published' ? ($norm->getPublishedAt() ?? $now) : null); $norm->setUpdatedAt($now); $this->refreshSearchText($norm);
		$saved = $norm->getId() ? $this->norms->update($norm) : $this->norms->insert($norm); $this->audit->record($uid, 'norm', $saved->getId(), $id ? 'norm.update' : 'norm.create', $before, $saved->jsonSerialize()); return $saved;
	}

	public function saveVersion(int $normId, array $data): NormVersion {
		$uid = $this->authorization->requireWrite(); $norm = $this->norms->find($normId); $label = trim((string)($data['label'] ?? '')); $body = (string)($data['bodyHtml'] ?? ''); $from = $this->date($data['validFrom'] ?? ''); $until = trim((string)($data['validUntil'] ?? '')) === '' ? null : $this->date($data['validUntil']);
		if ($label === '' || mb_strlen($label) > 64 || $body === '') throw new ValidationException('Informe rótulo, início de vigência e texto da versão.'); if ($until && $until < $from) throw new ValidationException('O fim da vigência não pode anteceder o início.');
		$this->assertNoOverlap($normId, $from, $until); $clean = $this->sanitize($body); $version = new NormVersion(); $version->setNormId($norm->getId()); $version->setLabel($label); $version->setValidFrom($from); $version->setValidUntil($until); $version->setBodyHtml($clean); $version->setChecksum(hash('sha256', $clean)); $version->setCreatedBy($uid); $version->setCreatedAt($this->now()); $saved = $this->versions->insert($version); $this->refreshSearchText($norm); $norm->setUpdatedAt($this->now()); $this->norms->update($norm); $this->audit->record($uid, 'norm', $normId, 'norm.version.create', null, $saved->jsonSerialize()); return $saved;
	}

	public function updateVersion(int $versionId, array $data): NormVersion { $uid = $this->authorization->requireWrite(); $version = $this->versions->find($versionId); $from = $this->date($data['validFrom'] ?? $version->getValidFrom()->format('Y-m-d')); $until = trim((string)($data['validUntil'] ?? '')) === '' ? null : $this->date($data['validUntil']); if ($until && $until < $from) throw new ValidationException('O fim da vigência não pode anteceder o início.'); $this->assertNoOverlap($version->getNormId(), $from, $until, $versionId); $before = $version->jsonSerialize(); $body = $this->sanitize((string)($data['bodyHtml'] ?? $version->getBodyHtml())); $version->setLabel(trim((string)($data['label'] ?? $version->getLabel()))); $version->setValidFrom($from); $version->setValidUntil($until); $version->setBodyHtml($body); $version->setChecksum(hash('sha256', $body)); $saved = $this->versions->update($version); $norm = $this->norms->find($version->getNormId()); $this->refreshSearchText($norm); $norm->setUpdatedAt($this->now()); $this->norms->update($norm); $this->audit->record($uid, 'norm', $version->getNormId(), 'norm.version.update', $before, $saved->jsonSerialize()); return $saved; }

	public function relate(int $sourceId, array $data): array {
		$uid = $this->authorization->requireWrite(); $targetId = (int)($data['targetNormId'] ?? 0); $type = trim((string)($data['relationType'] ?? 'amends')); if ($targetId < 1 || $targetId === $sourceId || !in_array($type, self::RELATIONS, true)) throw new ValidationException('Relação entre normas inválida.'); $this->norms->find($sourceId); $this->norms->find($targetId); $relation = new \OCA\Legislativo\Db\NormRelation(); $relation->setSourceNormId($sourceId); $relation->setTargetNormId($targetId); $relation->setRelationType($type); $relation->setNotes($this->optional($data['notes'] ?? null, 1000)); $relation->setCreatedBy($uid); $relation->setCreatedAt($this->now()); $saved = $this->relations->insert($relation); $this->audit->record($uid, 'norm', $sourceId, 'norm.relation.create', null, $saved->jsonSerialize()); return $saved->jsonSerialize();
	}

	/** CSV columns: type,number,year,title,ementa,status,body_html,version_label,valid_from,valid_until */
	public function importCsv(string $csv, bool $dryRun = false): array {
		$uid = $this->authorization->requireWrite(); $csv = trim($csv); if ($csv === '') throw new ValidationException('Envie um arquivo CSV com as normas.'); if (strlen($csv) > 25 * 1024 * 1024) throw new ValidationException('O CSV excede o limite de 25 MB.');
		$lines = preg_split('/\r\n|\n|\r/', $csv) ?: []; $first = (string)($lines[0] ?? ''); $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ','; $headers = array_map(fn ($v) => strtolower(trim((string)$v)), str_getcsv(preg_replace('/^\xEF\xBB\xBF/', '', $first), $delimiter));
		$required = ['type', 'number', 'year', 'title']; foreach ($required as $column) if (!in_array($column, $headers, true)) throw new ValidationException('CSV sem coluna obrigatória: ' . $column);
		$result = ['dryRun' => $dryRun, 'total' => 0, 'imported' => 0, 'skipped' => 0, 'errors' => []];
		foreach (array_slice($lines, 1) as $lineNumber => $line) { if (trim($line) === '') continue; $result['total']++; $rowNumber = $lineNumber + 2; $values = str_getcsv($line, $delimiter); $row = []; foreach ($headers as $index => $header) $row[$header] = trim((string)($values[$index] ?? '')); try { $data = ['type' => $row['type'] ?? '', 'number' => $row['number'] ?? '', 'year' => $row['year'] ?? '', 'title' => $row['title'] ?? '', 'ementa' => $row['ementa'] ?? '', 'status' => ($row['status'] ?? '') ?: 'draft']; $type = trim((string)$data['type']); $number = (int)$data['number']; $year = (int)$data['year']; if ($type === '' || $number < 1 || $year < 1800 || $year > 2200 || trim((string)$data['title']) === '') throw new ValidationException('tipo, número, ano e título são obrigatórios.'); if ($this->norms->search(['type' => $type, 'number' => $number, 'year' => $year], 1) !== []) { $result['skipped']++; $result['errors'][] = ['line' => $rowNumber, 'message' => 'Norma já cadastrada.']; continue; } if ($dryRun) { $result['imported']++; continue; } $norm = $this->save($data); $body = trim((string)($row['body_html'] ?? '')); if ($body !== '') $this->saveVersion($norm->getId(), ['label' => ($row['version_label'] ?? '') ?: 'Texto importado', 'validFrom' => ($row['valid_from'] ?? '') ?: $year . '-01-01', 'validUntil' => $row['valid_until'] ?? '', 'bodyHtml' => $body]); $result['imported']++; } catch (\Throwable $e) { $result['errors'][] = ['line' => $rowNumber, 'message' => $e instanceof ValidationException ? $e->getMessage() : 'Falha ao importar a linha.']; } if ($result['total'] >= 1000) break; }
		if (!$dryRun) $this->audit->record($uid, 'norm', $result['imported'] > 0 ? 1 : 0, 'norm.import', null, $result); return $result;
	}

	private function summary(Norm $norm, bool $public): array { $data = $norm->jsonSerialize(); if ($public) $data['createdBy'] = null; return $data; }
	private function publicVersion(NormVersion $version): array { return ['id' => $version->getId(), 'label' => $version->getLabel(), 'validFrom' => $version->getValidFrom()->format('Y-m-d'), 'validUntil' => $version->getValidUntil()?->format('Y-m-d'), 'bodyHtml' => $version->getBodyHtml(), 'checksum' => $version->getChecksum()]; }
	private function date(mixed $value): \DateTime { try { return new \DateTime((string)$value); } catch (\Throwable) { throw new ValidationException('Data de vigência inválida.'); } }
	private function now(): \DateTime { return new \DateTime('now', new \DateTimeZone('UTC')); }
	private function optional(mixed $value, int $max): ?string { $value = trim((string)$value); if ($value === '') return null; if (mb_strlen($value) > $max) throw new ValidationException('Texto excede o limite permitido.'); return $value; }
	private function sanitize(string $html): string { $allowed = '<p><br><strong><em><u><h2><h3><ol><ul><li><blockquote><table><thead><tbody><tr><th><td>'; $clean = strip_tags($html, $allowed); return trim((string)preg_replace('/<(?!\/)([a-z0-9]+)[^>]*>/i', '<$1>', $clean)); }
	private function assertNoOverlap(int $normId, \DateTime $from, ?\DateTime $until, ?int $ignoreId = null): void { foreach ($this->versions->findByNorm($normId) as $existing) { if ($ignoreId !== null && $existing->getId() === $ignoreId) continue; $existingUntil = $existing->getValidUntil(); if (($until === null || $existing->getValidFrom() <= $until) && ($existingUntil === null || $from <= $existingUntil)) throw new ValidationException('A vigência da versão se sobrepõe a outra versão da mesma norma.'); } }
	private function refreshSearchText(Norm $norm): void { $bodies = []; if ($norm->getId()) foreach ($this->versions->findByNorm($norm->getId()) as $version) $bodies[] = $version->getBodyHtml(); $norm->setSearchText($this->textNormalizer->document([$norm->getType(), $norm->getNumber(), $norm->getYear(), $norm->getTitle(), $norm->getEmenta(), $norm->getStatus(), ...$bodies])); }
}
