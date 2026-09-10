<?php

declare(strict_types=1);

namespace OCA\Legislativo\Service;

use OCA\Legislativo\Db\CalendarEntry;
use OCA\Legislativo\Db\CalendarEntryMapper;
use OCA\Legislativo\Exception\ValidationException;

class CalendarService {
	private const KINDS = ['holiday', 'optional', 'recess'];
	private \DateTimeZone $utc;

	public function __construct(
		private CalendarEntryMapper $mapper,
		private AuthorizationService $authorization,
		private AuditService $auditService,
	) { $this->utc = new \DateTimeZone('UTC'); }

	public function list(): array {
		$this->authorization->getUserId();
		return array_map(static fn (CalendarEntry $entry): array => $entry->jsonSerialize(), $this->mapper->findAll());
	}

	public function create(array $data): CalendarEntry {
		$uid = $this->authorization->requireWrite();
		$name = trim((string)($data['name'] ?? ''));
		$kind = (string)($data['kind'] ?? '');
		if ($name === '' || mb_strlen($name) > 255) throw new ValidationException('Informe um nome válido para o período.');
		if (!in_array($kind, self::KINDS, true)) throw new ValidationException('Tipo de período inválido.');
		$starts = $this->date((string)($data['startsOn'] ?? ''));
		$ends = $this->date((string)($data['endsOn'] ?? $data['startsOn'] ?? ''));
		if ($ends < $starts) throw new ValidationException('A data final não pode ser anterior à inicial.');
		$now = new \DateTime('now', $this->utc);
		$entry = new CalendarEntry();
		$entry->setName($name); $entry->setKind($kind); $entry->setStartsOn($starts); $entry->setEndsOn($ends);
		$entry->setActive(true); $entry->setCreatedBy($uid); $entry->setCreatedAt($now); $entry->setUpdatedAt($now);
		$inserted = $this->mapper->insert($entry);
		$this->auditService->record($uid, 'calendar', $inserted->getId(), 'calendar.create', null, $inserted->jsonSerialize());
		return $inserted;
	}

	public function toggle(int $id, bool $active): CalendarEntry {
		$uid = $this->authorization->requireWrite();
		$entry = $this->mapper->find($id);
		$before = $entry->jsonSerialize();
		$entry->setActive($active); $entry->setUpdatedAt(new \DateTime('now', $this->utc));
		$updated = $this->mapper->update($entry);
		$this->auditService->record($uid, 'calendar', $id, 'calendar.toggle', $before, $updated->jsonSerialize());
		return $updated;
	}

	private function date(string $value): \DateTime {
		$date = \DateTime::createFromFormat('!Y-m-d', $value, $this->utc);
		$errors = \DateTime::getLastErrors();
		if ($date === false || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))) throw new ValidationException('Informe datas válidas.');
		return $date;
	}
}
