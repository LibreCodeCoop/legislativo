<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Service;

use OCA\Legislativo\Db\AuditEntry;
use OCA\Legislativo\Db\Attachment;
use OCA\Legislativo\Db\AttachmentMapper;
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
	private const STATUSES = [
		'draft',
		'received',
		'protocolled',
		'processing',
		'agenda',
		'approved',
		'rejected',
		'archived',
	];

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
	) {
		$this->utc = new \DateTimeZone('UTC');
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return array<int, array<string, mixed>>
	 */
	public function search(array $filters): array {
		$this->authorization->getUserId();
		$limit = (int)($filters['limit'] ?? 50);
		$offset = (int)($filters['offset'] ?? 0);

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
		$values = $this->validateMatter($data, false);
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
		$values = $this->validateMatter($data, true);
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
		$sender = $this->requiredString($data, 'sender', 'Remetente', 255);
		$year = (int)($data['year'] ?? (int)date('Y'));
		if ($year < 1900 || $year > 2200) {
			throw new ValidationException('Ano de protocolo inválido.', ['year' => 'Informe um ano entre 1900 e 2200.']);
		}

		$now = $this->now();
		$protocol = new Protocol();
		$protocol->setMatterId($matterId);
		$protocol->setNumber((int)($data['number'] ?? $this->protocolMapper->nextNumber($year)));
		$protocol->setYear($year);
		$protocol->setSender($sender);
		$protocol->setSubject($this->optionalString($data, 'subject', 512) ?? $matter->getSubject());
		$protocol->setStatus('received');
		$protocol->setFileId($this->optionalPositiveInt($data, 'fileId'));
		$submissionId = $this->optionalPositiveInt($data, 'submissionId');
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
		$recipient = $this->requiredString($data, 'recipient', 'Destinatário', 255);
		$objective = $this->requiredString($data, 'objective', 'Objetivo', 512);
		$deadlineDays = (int)($data['deadlineDays'] ?? 0);
		if ($deadlineDays < 0 || $deadlineDays > 3650) {
			throw new ValidationException('Prazo inválido.', ['deadlineDays' => 'Informe um prazo entre 0 e 3650 dias.']);
		}

		$businessDays = filter_var($data['businessDays'] ?? true, FILTER_VALIDATE_BOOL);
		$sent = new \DateTimeImmutable('now', $this->utc);
		$periods = array_map(static fn (CalendarEntry $entry): array => $entry->jsonSerialize(), $this->calendarMapper->findAll(true));
		$due = $deadlineDays > 0
			? $this->deadlineCalculator->calculate($sent, $deadlineDays, $businessDays, $periods)
			: null;

		$proceeding = new Proceeding();
		$proceeding->setMatterId($matterId);
		$proceeding->setSenderUid($uid);
		$proceeding->setRecipient($recipient);
		$proceeding->setObjective($objective);
		$proceeding->setResult(null);
		$proceeding->setSentAt(\DateTime::createFromImmutable($sent));
		$proceeding->setDueAt($due === null ? null : \DateTime::createFromImmutable($due));
		$proceeding->setAnsweredAt(null);
		$proceeding->setDeadlineDays($deadlineDays > 0 ? $deadlineDays : null);
		$proceeding->setBusinessDays($businessDays);
		$proceeding->setNotes($this->optionalString($data, 'notes', 4000));
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
		$uid = $this->authorization->requireWrite(); $matter = $this->matterMapper->find($matterId); $raw = $data['recipients'] ?? [];
		if (is_string($raw)) $raw = preg_split('/[;\r\n]+/', $raw) ?: [];
		if (!is_array($raw)) throw new ValidationException('Informe os destinatários da tramitação.');
		$recipients = [];
		foreach ($raw as $entry) { $recipient = is_array($entry) ? ($entry['recipient'] ?? '') : $entry; $recipient = trim((string)$recipient); if ($recipient !== '') $recipients[] = $recipient; }
		$recipients = array_values(array_unique($recipients)); if ($recipients === [] || count($recipients) > 50) throw new ValidationException('Informe entre 1 e 50 destinatários.');
		$objective = $this->requiredString($data, 'objective', 'Objetivo', 512); $deadlineDays = (int)($data['deadlineDays'] ?? 0); if ($deadlineDays < 0 || $deadlineDays > 3650) throw new ValidationException('Prazo inválido.'); $businessDays = filter_var($data['businessDays'] ?? true, FILTER_VALIDATE_BOOL); $sent = new \DateTimeImmutable('now', $this->utc); $periods = array_map(static fn (CalendarEntry $entry): array => $entry->jsonSerialize(), $this->calendarMapper->findAll(true)); $due = $deadlineDays > 0 ? $this->deadlineCalculator->calculate($sent, $deadlineDays, $businessDays, $periods) : null; $notes = $this->optionalString($data, 'notes', 4000); $proceedings = [];
		foreach ($recipients as $recipient) { $proceeding = new Proceeding(); $proceeding->setMatterId($matterId); $proceeding->setSenderUid($uid); $proceeding->setRecipient($this->requiredString(['recipient' => $recipient], 'recipient', 'Destinatário', 255)); $proceeding->setObjective($objective); $proceeding->setResult(null); $proceeding->setSentAt(\DateTime::createFromImmutable($sent)); $proceeding->setDueAt($due === null ? null : \DateTime::createFromImmutable($due)); $proceeding->setAnsweredAt(null); $proceeding->setDeadlineDays($deadlineDays > 0 ? $deadlineDays : null); $proceeding->setBusinessDays($businessDays); $proceeding->setNotes($notes); $proceeding->setCreatedAt($this->now()); $proceedings[] = $proceeding; }
		return $this->transactional(function () use ($proceedings, $matter, $uid): array { $saved = []; foreach ($proceedings as $proceeding) $saved[] = $this->proceedingMapper->insert($proceeding); $before = $matter->jsonSerialize(); $matter->setStatus('processing'); $matter->setUpdatedAt($this->now()); $this->matterMapper->update($matter); $this->auditService->record($uid, 'matter', $matter->getId(), 'proceeding.batch.create', $before, ['matter' => $matter->jsonSerialize(), 'proceedings' => array_map(static fn (Proceeding $item): array => $item->jsonSerialize(), $saved)]); return $saved; });
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	private function validateMatter(array $data, bool $partial): array {
		$values = [];
		if (!$partial || array_key_exists('type', $data)) {
			$values['type'] = $this->requiredString($data, 'type', 'Tipo', 64);
		}
		if (!$partial || array_key_exists('number', $data)) {
			$number = (int)($data['number'] ?? 0);
			if ($number <= 0) {
				throw new ValidationException('Número inválido.', ['number' => 'Informe um número maior que zero.']);
			}
			$values['number'] = $number;
		}
		if (!$partial || array_key_exists('year', $data)) {
			$year = (int)($data['year'] ?? 0);
			if ($year < 1900 || $year > 2200) {
				throw new ValidationException('Ano inválido.', ['year' => 'Informe um ano entre 1900 e 2200.']);
			}
			$values['year'] = $year;
		}
		if (!$partial || array_key_exists('subject', $data)) {
			$values['subject'] = $this->requiredString($data, 'subject', 'Assunto', 512);
		}

		foreach (['body' => 200000, 'authorUid' => 64, 'theme' => 255, 'quorum' => 64, 'procedure' => 64, 'notes' => 4000] as $field => $max) {
			if (array_key_exists($field, $data)) {
				$values[$field] = $this->optionalString($data, $field, $max);
			}
		}

		if (array_key_exists('status', $data) || !$partial) {
			$status = trim((string)($data['status'] ?? 'draft'));
			if (!in_array($status, self::STATUSES, true)) {
				throw new ValidationException('Situação inválida.', ['status' => 'Selecione uma situação reconhecida.']);
			}
			$values['status'] = $status;
		}

		if (array_key_exists('presentedAt', $data)) {
			$values['presentedAt'] = $this->parseDate((string)$data['presentedAt'], 'presentedAt');
		} elseif (!$partial) {
			$values['presentedAt'] = new \DateTime('today', $this->utc);
		}

		if (array_key_exists('fileId', $data)) {
			$values['fileId'] = $this->optionalPositiveInt($data, 'fileId');
		}

		return $values;
	}

	/** @param array<string, mixed> $values */
	private function applyMatterValues(Matter $matter, array $values): void {
		$setters = [
			'type' => 'setType', 'number' => 'setNumber', 'year' => 'setYear',
			'subject' => 'setSubject', 'body' => 'setBody', 'authorUid' => 'setAuthorUid',
			'status' => 'setStatus', 'theme' => 'setTheme', 'quorum' => 'setQuorum',
			'procedure' => 'setProcedure', 'notes' => 'setNotes',
			'presentedAt' => 'setPresentedAt', 'fileId' => 'setFileId',
		];
		foreach ($setters as $field => $setter) {
			if (array_key_exists($field, $values)) {
				$matter->{$setter}($values[$field]);
			}
		}
	}

	/**
	 * @param array<string, mixed>|null $before
	 * @param array<string, mixed>|null $after
	 */
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

	/** @param array<string, mixed> $data */
	private function requiredString(array $data, string $field, string $label, int $max): string {
		$value = trim((string)($data[$field] ?? ''));
		if ($value === '') {
			throw new ValidationException($label . ' é obrigatório.', [$field => $label . ' é obrigatório.']);
		}
		if (mb_strlen($value) > $max) {
			throw new ValidationException($label . ' excede o tamanho permitido.', [$field => 'Máximo de ' . $max . ' caracteres.']);
		}
		return $value;
	}

	/** @param array<string, mixed> $data */
	private function optionalString(array $data, string $field, int $max): ?string {
		$value = trim((string)($data[$field] ?? ''));
		if ($value === '') {
			return null;
		}
		if (mb_strlen($value) > $max) {
			throw new ValidationException('Campo excede o tamanho permitido.', [$field => 'Máximo de ' . $max . ' caracteres.']);
		}
		return $value;
	}

	/** @param array<string, mixed> $data */
	private function optionalPositiveInt(array $data, string $field): ?int {
		if (!isset($data[$field]) || $data[$field] === '') {
			return null;
		}
		$value = (int)$data[$field];
		if ($value <= 0) {
			throw new ValidationException('Identificador inválido.', [$field => 'Informe um identificador maior que zero.']);
		}
		return $value;
	}

	private function parseDate(string $value, string $field): ?\DateTime {
		if (trim($value) === '') {
			return null;
		}
		$date = \DateTime::createFromFormat('!Y-m-d', $value, $this->utc);
		$errors = \DateTime::getLastErrors();
		if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
			throw new ValidationException('Data inválida.', [$field => 'Use o formato AAAA-MM-DD.']);
		}
		return $date;
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
