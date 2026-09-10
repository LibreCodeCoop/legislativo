<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Service;

use OCA\Legislativo\Db\AuditEntry;
use OCA\Legislativo\Db\AuditEntryMapper;
use OCA\Legislativo\Exception\ValidationException;

class AuditQueryService {
	private const ENTITY_TYPES = ['matter', 'session', 'norm', 'calendar', 'parliamentarian'];
	private \DateTimeZone $utc;

	public function __construct(
		private AuditEntryMapper $auditMapper,
		private AuthorizationService $authorization,
		private AuditClientParser $clientParser,
	) {
		$this->utc = new \DateTimeZone('UTC');
	}

	/** @param array<string, mixed> $filters
	 * @return list<array<string, mixed>>
	 */
	public function search(array $filters): array {
		$this->authorization->requireWrite();
		$entityType = trim((string)($filters['entityType'] ?? ''));
		if ($entityType !== '' && !in_array($entityType, self::ENTITY_TYPES, true)) {
			throw new ValidationException('Tipo de entidade inválido.', ['entityType' => 'Selecione um módulo reconhecido.']);
		}
		$filters['entityType'] = $entityType;
		$filters['from'] = $this->parseDate($filters, 'from');
		$filters['to'] = $this->parseDate($filters, 'to');
		if ($filters['from'] !== null && $filters['to'] !== null && $filters['to'] < $filters['from']) {
			throw new ValidationException('Período de auditoria inválido.', ['to' => 'A data final deve ser igual ou posterior à inicial.']);
		}

		return array_map(function (AuditEntry $entry): array {
			$data = $entry->jsonSerialize();
			$data['client'] = $this->clientParser->parse($entry->getUserAgent());
			return $data;
		}, $this->auditMapper->search(
			$filters,
			(int)($filters['limit'] ?? 100),
			(int)($filters['offset'] ?? 0),
		));
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
