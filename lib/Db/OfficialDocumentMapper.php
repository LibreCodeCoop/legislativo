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

/** @template-extends QBMapper<OfficialDocument> */
class OfficialDocumentMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'leg_official_documents', OfficialDocument::class);
	}

	public function find(int $id): OfficialDocument {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

		return $this->findEntity($qb);
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return OfficialDocument[]
	 */
	public function search(array $filters): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName());

		foreach (['kind', 'status'] as $field) {
			$value = trim((string) ($filters[$field] ?? ''));
			if ($value !== '') {
				$qb->andWhere($qb->expr()->eq($field, $qb->createNamedParameter($value)));
			}
		}
		$year = (int) ($filters['year'] ?? 0);
		if ($year > 0) {
			$qb->andWhere($qb->expr()->eq('year', $qb->createNamedParameter($year, IQueryBuilder::PARAM_INT)));
		}
		$query = trim((string) ($filters['query'] ?? ''));
		if ($query !== '') {
			$term = $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($query) . '%');
			$qb->andWhere($qb->expr()->orX(
				$qb->expr()->ilike('subject', $term),
				$qb->expr()->ilike('body', $term),
			));
		}

		$limit = max(1, min((int) ($filters['limit'] ?? 100), 100));
		$qb->orderBy('year', 'DESC')->addOrderBy('number', 'DESC')->addOrderBy('updated_at', 'DESC')->setMaxResults($limit);

		return $this->findEntities($qb);
	}

	public function nextNumber(string $kind, int $year): int {
		$qb = $this->db->getQueryBuilder();
		$maximum = $qb->selectAlias($qb->func()->max('number'), 'maximum')
			->from($this->getTableName())
			->where($qb->expr()->eq('kind', $qb->createNamedParameter($kind)))
			->andWhere($qb->expr()->eq('year', $qb->createNamedParameter($year, IQueryBuilder::PARAM_INT)))
			->executeQuery()
			->fetchOne();

		return ((int) $maximum) + 1;
	}
}
