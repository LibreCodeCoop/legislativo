<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Service;

use OCA\Legislativo\Db\MatterMapper;
use OCA\Legislativo\Db\Proceeding;
use OCA\Legislativo\Db\ProceedingMapper;
use OCA\Legislativo\Exception\ValidationException;
use OCP\IDBConnection;

class DeadlineService {
	private const STATES = ['open', 'overdue', 'due_today', 'upcoming', 'completed', 'no_deadline'];
	private \DateTimeZone $utc;

	public function __construct(
		private IDBConnection $db,
		private ProceedingMapper $proceedingMapper,
		private MatterMapper $matterMapper,
		private AuthorizationService $authorization,
		private AuditService $auditService,
		private DeadlineStatusCalculator $statusCalculator,
	) {
		$this->utc = new \DateTimeZone('UTC');
	}

	/** @param array<string, mixed> $filters
	 * @return list<array<string, mixed>>
	 */
	public function search(array $filters): array {
		$this->authorization->getUserId();
		$state = trim((string)($filters['state'] ?? 'open'));
		if (!in_array($state, self::STATES, true)) {
			throw new ValidationException('Situação de prazo inválida.', ['state' => 'Selecione uma situação reconhecida.']);
		}
		$filters['state'] = $state;
		$filters['dueFrom'] = $this->parseDate($filters, 'dueFrom');
		$filters['dueTo'] = $this->parseDate($filters, 'dueTo');
		if ($filters['dueFrom'] !== null && $filters['dueTo'] !== null && $filters['dueTo'] < $filters['dueFrom']) {
			throw new ValidationException('Período de vencimento inválido.', ['dueTo' => 'A data final deve ser igual ou posterior à inicial.']);
		}

		$now = new \DateTimeImmutable('now', $this->utc);
		$limit = (int)($filters['limit'] ?? 100);
		$offset = (int)($filters['offset'] ?? 0);
		$matters = [];

		return array_map(function (Proceeding $proceeding) use ($now, &$matters): array {
			$matterId = $proceeding->getMatterId();
			$matter = $matters[$matterId] ??= $this->matterMapper->find($matterId);
			return [
				...$proceeding->jsonSerialize(),
				'deadlineState' => $this->statusCalculator->classify($proceeding->getDueAt(), $proceeding->getAnsweredAt(), $now),
				'daysRemaining' => $this->statusCalculator->daysRemaining($proceeding->getDueAt(), $now),
				'matter' => [
					'id' => $matter->getId(),
					'type' => $matter->getType(),
					'number' => $matter->getNumber(),
					'year' => $matter->getYear(),
					'subject' => $matter->getSubject(),
				],
			];
		}, $this->proceedingMapper->searchDeadlines($filters, $now, $limit, $offset));
	}

	/** @param array<string, mixed> $data */
	public function complete(int $id, array $data): Proceeding {
		$uid = $this->authorization->requireWrite();
		$proceeding = $this->proceedingMapper->find($id);
		if ($proceeding->getAnsweredAt() !== null) {
			throw new ValidationException('Esta tramitação já foi concluída.');
		}
		$result = trim((string)($data['result'] ?? ''));
		if ($result === '' || mb_strlen($result) > 255) {
			throw new ValidationException('Informe um resultado com até 255 caracteres.', ['result' => 'O resultado é obrigatório.']);
		}

		$before = $proceeding->jsonSerialize();
		$proceeding->setResult($result);
		$proceeding->setAnsweredAt(new \DateTime('now', $this->utc));
		if (array_key_exists('notes', $data)) {
			$notes = trim((string)$data['notes']);
			if (mb_strlen($notes) > 4000) {
				throw new ValidationException('Observações excedem o tamanho permitido.', ['notes' => 'Máximo de 4000 caracteres.']);
			}
			$proceeding->setNotes($notes === '' ? null : $notes);
		}

		$this->db->beginTransaction();
		try {
			$updated = $this->proceedingMapper->update($proceeding);
			$this->auditService->record($uid, 'matter', $updated->getMatterId(), 'proceeding.complete', $before, $updated->jsonSerialize());
			$this->db->commit();
			return $updated;
		} catch (\Throwable $exception) {
			$this->db->rollBack();
			throw $exception;
		}
	}

	/** @param array<string, mixed> $data */
	private function parseDate(array $data, string $field): ?\DateTimeImmutable {
		$value = trim((string)($data[$field] ?? ''));
		if ($value === '') {
			return null;
		}
		$date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, $this->utc);
		$errors = \DateTimeImmutable::getLastErrors();
		if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
			throw new ValidationException('Data inválida.', [$field => 'Use o formato AAAA-MM-DD.']);
		}
		return $date;
	}
}
