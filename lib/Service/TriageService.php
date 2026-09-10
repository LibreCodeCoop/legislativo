<?php

declare(strict_types=1);

namespace OCA\Legislativo\Service;

use OCA\Legislativo\Db\MatterMapper;
use OCA\Legislativo\Db\Submission;
use OCA\Legislativo\Db\SubmissionMapper;
use OCA\Legislativo\Exception\ValidationException;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;

class TriageService {
	public function __construct(
		private IDBConnection $db,
		private IRootFolder $rootFolder,
		private MatterMapper $matterMapper,
		private SubmissionMapper $submissionMapper,
		private AuthorizationService $authorization,
		private AuditService $auditService,
	) {
	}

	public function list(string $status): array {
		$this->authorization->getUserId();
		return array_map(static fn (Submission $item): array => $item->jsonSerialize(), $this->submissionMapper->findQueue($status));
	}

	public function submit(array $data): Submission {
		$uid = $this->authorization->requireWrite();
		$matterId = (int)($data['matterId'] ?? 0);
		$this->matterMapper->find($matterId);
		$sender = $this->required($data, 'sender', 255);
		$subject = $this->required($data, 'subject', 512);
		[$fileId, $filePath] = $this->resolveOptionalFile($uid, (string)($data['filePath'] ?? ''));

		$item = new Submission();
		$item->setMatterId($matterId);
		$item->setSender($sender);
		$item->setSubject($subject);
		$item->setNotes($this->optional($data, 'notes', 4000));
		$item->setStatus('pending');
		$item->setFileId($fileId);
		$item->setFilePath($filePath);
		$item->setSubmittedAt(new \DateTime('now', new \DateTimeZone('UTC')));

		$this->db->beginTransaction();
		try {
			$inserted = $this->submissionMapper->insert($item);
			$this->auditService->record($uid, 'matter', $matterId, 'submission.create', null, $inserted->jsonSerialize());
			$this->db->commit();
			return $inserted;
		} catch (\Throwable $exception) {
			$this->db->rollBack();
			throw $exception;
		}
	}

	public function decide(int $id, array $data): Submission {
		$uid = $this->authorization->requireWrite();
		$item = $this->submissionMapper->find($id);
		if ($item->getStatus() !== 'pending') throw new ValidationException('Esta entrada já foi analisada.');
		$decision = (string)($data['decision'] ?? '');
		if (!in_array($decision, ['accepted', 'rejected'], true)) throw new ValidationException('Decisão inválida.');
		$before = $item->jsonSerialize();
		$item->setStatus($decision);
		$item->setReviewedAt(new \DateTime('now', new \DateTimeZone('UTC')));
		$item->setReviewedBy($uid);
		$item->setReviewNotes($this->optional($data, 'reviewNotes', 4000));

		$this->db->beginTransaction();
		try {
			$updated = $this->submissionMapper->update($item);
			$this->auditService->record($uid, 'matter', $item->getMatterId(), 'submission.' . $decision, $before, $updated->jsonSerialize());
			$this->db->commit();
			return $updated;
		} catch (\Throwable $exception) {
			$this->db->rollBack();
			throw $exception;
		}
	}

	private function resolveOptionalFile(string $uid, string $value): array {
		$path = ltrim(trim($value), '/');
		if ($path === '') return [null, null];
		try { $node = $this->rootFolder->getUserFolder($uid)->get($path); } catch (\Throwable) {
			throw new ValidationException('O arquivo da triagem não foi encontrado.');
		}
		if (!$node instanceof File) throw new ValidationException('Selecione um arquivo para a triagem.');
		return [$node->getId(), '/' . $path];
	}

	private function required(array $data, string $field, int $max): string {
		$value = trim((string)($data[$field] ?? ''));
		if ($value === '' || mb_strlen($value) > $max) throw new ValidationException('Preencha corretamente o campo ' . $field . '.');
		return $value;
	}

	private function optional(array $data, string $field, int $max): ?string {
		$value = trim((string)($data[$field] ?? ''));
		if ($value === '') return null;
		if (mb_strlen($value) > $max) throw new ValidationException('O campo ' . $field . ' excede o tamanho permitido.');
		return $value;
	}
}
