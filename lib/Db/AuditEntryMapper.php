<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<AuditEntry> */
class AuditEntryMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'leg_audit_log', AuditEntry::class);
	}

	/** @return AuditEntry[] */
	public function findForMatter(int $matterId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('entity_type', $qb->createNamedParameter('matter')))
			->andWhere($qb->expr()->eq('entity_id', $qb->createNamedParameter($matterId, IQueryBuilder::PARAM_INT)))
			->orderBy('created_at', 'ASC');

		return $this->findEntities($qb);
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return AuditEntry[]
	 */
	public function search(array $filters, int $limit = 100, int $offset = 0): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName());

		foreach (['userUid' => 'user_uid', 'action' => 'action'] as $filter => $column) {
			$value = trim((string)($filters[$filter] ?? ''));
			if ($value !== '') {
				$term = $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($value) . '%');
				$qb->andWhere($qb->expr()->ilike($column, $term));
			}
		}
		$entityType = trim((string)($filters['entityType'] ?? ''));
		if ($entityType !== '') {
			$qb->andWhere($qb->expr()->eq('entity_type', $qb->createNamedParameter($entityType)));
		}
		$entityId = (int)($filters['entityId'] ?? 0);
		if ($entityId > 0) {
			$qb->andWhere($qb->expr()->eq('entity_id', $qb->createNamedParameter($entityId, IQueryBuilder::PARAM_INT)));
		}
		if (($filters['from'] ?? null) instanceof \DateTimeImmutable) {
			$qb->andWhere($qb->expr()->gte('created_at', $qb->createNamedParameter($filters['from'], IQueryBuilder::PARAM_DATETIME_IMMUTABLE)));
		}
		if (($filters['to'] ?? null) instanceof \DateTimeImmutable) {
			$qb->andWhere($qb->expr()->lt('created_at', $qb->createNamedParameter($filters['to']->modify('+1 day'), IQueryBuilder::PARAM_DATETIME_IMMUTABLE)));
		}

		$qb->orderBy('created_at', 'DESC')
			->setMaxResults(max(1, min($limit, 200)))
			->setFirstResult(max(0, $offset));

		return $this->findEntities($qb);
	}
}
