<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Legislativo\Service;

use OCA\Legislativo\Db\Attachment;
use OCA\Legislativo\Db\AttachmentMapper;
use OCA\Legislativo\Db\AuditEntry;
use OCA\Legislativo\Db\AuditEntryMapper;
use OCA\Legislativo\Db\CalendarEntry;
use OCA\Legislativo\Db\CalendarEntryMapper;
use OCA\Legislativo\Db\DocumentFlow;
use OCA\Legislativo\Db\DocumentFlowMapper;
use OCA\Legislativo\Db\Matter;
use OCA\Legislativo\Db\MatterMapper;
use OCA\Legislativo\Db\Proceeding;
use OCA\Legislativo\Db\ProceedingMapper;
use OCA\Legislativo\Db\Protocol;
use OCA\Legislativo\Db\ProtocolMapper;
use OCA\Legislativo\Db\Submission;
use OCA\Legislativo\Db\SubmissionMapper;
use OCA\Legislativo\Exception\ValidationException;
use OCP\IDBConnection;

class MatterService {
	private \DateTimeZone $utc;

	public function __construct(
		private IDBConnection $db,
		private MatterMapper $matterMapper,
		private ProtocolMapper $protocolMapper,
		private ProceedingMapper $proceedingMapper,
		private AttachmentMapper $attachmentMapper,
		private SubmissionMapper $submissionMapper,
		private CalendarEntryMapper $calendarMapper,
		private DocumentFlowMapper $documentFlowMapper,
		private AuditEntryMapper $auditMapper,
		private AuthorizationService $authorization,
		private AuditService $auditService,
		private DeadlineCalculator $deadlineCalculator,
		private TextNormalizer $textNormalizer,
		private MatterValidator $validator,
	) {
		$this->utc = new \DateTimeZone('UTC');
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return array<int, array<string, mixed>>
	 */
	public function search(array $filters): array {
		$this->authorization->getUserId();
		$limit = (int) ($filters['limit'] ?? 50);
		$offset = (int) ($filters['offset'] ?? 0);

		return array_map(
			static fn (Matter $matter): array => $matter->jsonSerialize(),
			$this->matterMapper->search($filters, $limit, $offset),
		);
	}

	/** @return array<string, mixed> */
	public function get(int $id): array {
		$this->authorization->getUserId();
		$matter = $this->matterMapper->find($id);

		return [
			'matter' => $matter->jsonSerialize(),
			'protocols' => array_map(
				static fn (Protocol $protocol): array => $protocol->jsonSerialize(),
				$this->protocolMapper->findByMatter($id),
			),
			'proceedings' => array_map(
				static fn (Proceeding $proceeding): array => $proceeding->jsonSerialize(),
				$this->proceedingMapper->findByMatter($id),
			),
			'attachments' => array_map(
				static fn (Attachment $attachment): array => $attachment->jsonSerialize(),
				$this->attachmentMapper->findByMatter($id),
			),
			'submissions' => array_map(
				static fn (Submission $submission): array => $submission->jsonSerialize(),
				$this->submissionMapper->findByMatter($id),
			),
			'documentFlows' => array_map(
				static fn (DocumentFlow $flow): array => $flow->jsonSerialize(),
				$this->documentFlowMapper->findByMatter($id),
			),
			'audit' => array_map(
				static fn (AuditEntry $entry): array => $entry->jsonSerialize(),
				$this->auditMapper->findForMatter($id),
			),
		];
	}

	/** @param array<string, mixed> $data */
	public function create(array $data): Matter {
		$uid = $this->authorization->requireWrite();
		$values = $this->validator->validateMatter($data, false);
		$now = $this->now();

		$matter = new Matter();
		$this->applyMatterValues($matter, $values);
		$matter->setAuthorUid($values['authorUid'] ?? $uid);
		$this->refreshSearchText($matter);
		$matter->setCreatedAt($now);
		$matter->setUpdatedAt($now);

		return $this->transactional(function () use ($matter, $uid): Matter {
			$inserted = $this->matterMapper->insert($matter);
			$this->auditService->record($uid, 'matter', $inserted->getId(), 'matter.create', null, $inserted->jsonSerialize());
			return $inserted;
		});
	}

	/** @param array<string, mixed> $data */
	public function update(int $id, array $data): Matter {
		$uid = $this->authorization->requireWrite();
		$matter = $this->matterMapper->find($id);
		$before = $matter->jsonSerialize();
		$values = $this->validator->validateMatter($data, true);
		$this->applyMatterValues($matter, $values);
		$this->refreshSearchText($matter);
		$matter->setUpdatedAt($this->now());

		return $this->transactional(function () use ($matter, $uid, $before): Matter {
			$updated = $this->matterMapper->update($matter);
			$this->auditService->record($uid, 'matter', $updated->getId(), 'matter.update', $before, $updated->jsonSerialize());
			return $updated;
		});
	}

	/** @param array<string, mixed> $data */
	public function protocol(int $matterId, array $data): Protocol {
		$uid = $this->authorization->requireWrite();
		$matter = $this->matterMapper->find($matterId);
		$validated = $this->validator->validateProtocol($data);
		$now = $this->now();

		$protocol = new Protocol();
		$protocol->setMatterId($matterId);
		$protocol->setNumber((int) ($data['number'] ?? $this->protocolMapper->nextNumber($validated['year'])));
		$protocol->setYear($validated['year']);
		$protocol->setSender($validated['sender']);
		$protocol->setSubject($this->validator->optionalString($data, 'subject', 512) ?? $matter->getSubject());
		$protocol->setStatus('received');
		$protocol->setFileId($validated['fileId']);

		$submissionId = $validated['submissionId'];
		if ($submissionId !== null) {
			$submission = $this->submissionMapper->find($submissionId);
			if ($submission->getMatterId() !== $matterId || $submission->getStatus() !== 'accepted') {
				throw new ValidationException('A entrada de triagem precisa estar aceita e vinculada a esta matéria.');
			}
			$protocol->setSubmissionId($submissionId);
		}

		$protocol->setReceivedAt($now);
		$protocol->setCreatedAt($now);
		$protocol->setUpdatedAt($now);

		return $this->transactional(function () use ($protocol, $matter, $uid, $submissionId): Protocol {
			$inserted = $this->protocolMapper->insert($protocol);

			if ($submissionId !== null) {
				$submission = $this->submissionMapper->find($submissionId);
				$submission->setStatus('protocolled');
				$this->submissionMapper->update($submission);
			}

			$before = $matter->jsonSerialize();
			$matter->setStatus('protocolled');
			$matter->setUpdatedAt($this->now());
			$this->matterMapper->update($matter);
			$this->auditService->record($uid, 'matter', $matter->getId(), 'protocol.create', $before, [
				'matter' => $matter->jsonSerialize(),
				'protocol' => $inserted->jsonSerialize(),
			]);

			return $inserted;
		});
	}

	/** @param array<string, mixed> $data */
	public function proceed(int $matterId, array $data): Proceeding {
		$uid = $this->authorization->requireWrite();
		$matter = $this->matterMapper->find($matterId);
		$validated = $this->validator->validateProceeding($data);

		$sent = new \DateTimeImmutable('now', $this->utc);
		$periods = array_map(
			static fn (CalendarEntry $entry): array => $entry->jsonSerialize(),
			$this->calendarMapper->findAll(true),
		);
		$due = $validated['deadlineDays'] > 0
			? $this->deadlineCalculator->calculate($sent, $validated['deadlineDays'], $validated['businessDays'], $periods)
			: null;

		$proceeding = new Proceeding();
		$proceeding->setMatterId($matterId);
		$proceeding->setSenderUid($uid);
		$proceeding->setRecipient($validated['recipient']);
		$proceeding->setObjective($validated['objective']);
		$proceeding->setResult(null);
		$proceeding->setSentAt(\DateTime::createFromImmutable($sent));
		$proceeding->setDueAt($due === null ? null : \DateTime::createFromImmutable($due));
		$proceeding->setAnsweredAt(null);
		$proceeding->setDeadlineDays($validated['deadlineDays'] > 0 ? $validated['deadlineDays'] : null);
		$proceeding->setBusinessDays($validated['businessDays']);
		$proceeding->setNotes($validated['notes']);
		$proceeding->setCreatedAt($this->now());

		return $this->transactional(function () use ($proceeding, $matter, $uid): Proceeding {
			$inserted = $this->proceedingMapper->insert($proceeding);
			$before = $matter->jsonSerialize();
			$matter->setStatus('processing');
			$matter->setUpdatedAt($this->now());
			$this->matterMapper->update($matter);
			$this->auditService->record($uid, 'matter', $matter->getId(), 'proceeding.create', $before, [
				'matter' => $matter->jsonSerialize(),
				'proceeding' => $inserted->jsonSerialize(),
			]);
			return $inserted;
		});
	}

	/** @return list<Proceeding> */
	public function proceedBatch(int $matterId, array $data): array {
		$uid = $this->authorization->requireWrite();
		$matter = $this->matterMapper->find($matterId);
		$recipients = $this->validator->validateBatchRecipients($data);
		$validated = $this->validator->validateProceeding($data);

		$sent = new \DateTimeImmutable('now', $this->utc);
		$periods = array_map(
			static fn (CalendarEntry $entry): array => $entry->jsonSerialize(),
			$this->calendarMapper->findAll(true),
		);
		$due = $validated['deadlineDays'] > 0
			? $this->deadlineCalculator->calculate($sent, $validated['deadlineDays'], $validated['businessDays'], $periods)
			: null;

		$proceedings = [];
		foreach ($recipients as $recipient) {
			$proceeding = new Proceeding();
			$proceeding->setMatterId($matterId);
			$proceeding->setSenderUid($uid);
			$proceeding->setRecipient($recipient);
			$proceeding->setObjective($validated['objective']);
			$proceeding->setResult(null);
			$proceeding->setSentAt(\DateTime::createFromImmutable($sent));
			$proceeding->setDueAt($due === null ? null : \DateTime::createFromImmutable($due));
			$proceeding->setAnsweredAt(null);
			$proceeding->setDeadlineDays($validated['deadlineDays'] > 0 ? $validated['deadlineDays'] : null);
			$proceeding->setBusinessDays($validated['businessDays']);
			$proceeding->setNotes($validated['notes']);
			$proceeding->setCreatedAt($this->now());
			$proceedings[] = $proceeding;
		}

		return $this->transactional(function () use ($proceedings, $matter, $uid): array {
			$saved = [];
			foreach ($proceedings as $proceeding) {
				$saved[] = $this->proceedingMapper->insert($proceeding);
			}

			$before = $matter->jsonSerialize();
			$matter->setStatus('processing');
			$matter->setUpdatedAt($this->now());
			$this->matterMapper->update($matter);
			$this->auditService->record($uid, 'matter', $matter->getId(), 'proceeding.batch.create', $before, [
				'matter' => $matter->jsonSerialize(),
				'proceedings' => array_map(static fn (Proceeding $item): array => $item->jsonSerialize(), $saved),
			]);

			return $saved;
		});
	}

	/** @param array<string, mixed> $values */
	private function applyMatterValues(Matter $matter, array $values): void {
		$setters = [
			'type' => 'setType',
			'number' => 'setNumber',
			'year' => 'setYear',
			'subject' => 'setSubject',
			'body' => 'setBody',
			'authorUid' => 'setAuthorUid',
			'status' => 'setStatus',
			'theme' => 'setTheme',
			'quorum' => 'setQuorum',
			'procedure' => 'setProcedure',
			'notes' => 'setNotes',
			'presentedAt' => 'setPresentedAt',
			'fileId' => 'setFileId',
		];

		foreach ($setters as $field => $setter) {
			if (array_key_exists($field, $values)) {
				$matter->{$setter}($values[$field]);
			}
		}
	}

	private function transactional(callable $operation): mixed {
		$this->db->beginTransaction();
		try {
			$result = $operation();
			$this->db->commit();
			return $result;
		} catch (\Throwable $exception) {
			$this->db->rollBack();
			throw $exception;
		}
	}

	private function now(): \DateTime {
		return new \DateTime('now', $this->utc);
	}

	private function refreshSearchText(Matter $matter): void {
		$matter->setSearchText($this->textNormalizer->document([
			$matter->getType(),
			$matter->getNumber(),
			$matter->getYear(),
			$matter->getSubject(),
			$matter->getBody(),
			$matter->getAuthorUid(),
			$matter->getStatus(),
			$matter->getTheme(),
			$matter->getQuorum(),
			$matter->getProcedure(),
			$matter->getNotes(),
			$matter->getPresentedAt()?->format('Y-m-d'),
		]));
	}
}
