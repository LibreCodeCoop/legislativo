<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Db;

use OCA\Legislativo\Service\TextNormalizer;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<Matter> */
class MatterMapper extends QBMapper {
	public function __construct(IDBConnection $db, private TextNormalizer $textNormalizer) {
		parent::__construct($db, 'leg_matters', Matter::class);
	}

	public function find(int $id): Matter {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

		return $this->findEntity($qb);
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return Matter[]
	 */
	public function search(array $filters, int $limit = 50, int $offset = 0): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName());

		$query = trim((string)($filters['query'] ?? ''));
		if ($query !== '') {
			$mode = (string)($filters['queryMode'] ?? 'all');
			if ($mode === 'phrase') {
				$term = $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($this->textNormalizer->normalize($query)) . '%');
				$qb->andWhere($qb->expr()->ilike('search_text', $term));
			} else {
				$conditions = [];
				foreach ($this->textNormalizer->queryTokens($query) as $token) {
					$term = $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($token) . '%');
					$condition = $qb->expr()->ilike('search_text', $term);
					if ($mode === 'any') {
						$conditions[] = $condition;
					} else {
						$qb->andWhere($condition);
					}
				}
				if ($conditions !== []) {
					$qb->andWhere($qb->expr()->orX(...$conditions));
				}
			}
		}

		foreach (['type', 'status'] as $field) {
			$value = trim((string)($filters[$field] ?? ''));
			if ($value !== '') {
				$qb->andWhere($qb->expr()->eq($field, $qb->createNamedParameter($value)));
			}
		}
		$authorUid = trim((string)($filters['authorUid'] ?? ''));
		if ($authorUid !== '') {
			$qb->andWhere($qb->expr()->eq('author_uid', $qb->createNamedParameter($authorUid)));
		}

		foreach (['number', 'year'] as $field) {
			$value = (int)($filters[$field] ?? 0);
			if ($value > 0) {
				$qb->andWhere($qb->expr()->eq($field, $qb->createNamedParameter($value, IQueryBuilder::PARAM_INT)));
			}
		}
		foreach (['number' => 'number', 'year' => 'year'] as $parameter => $field) {
			$from = (int)($filters[$parameter . 'From'] ?? 0);
			$to = (int)($filters[$parameter . 'To'] ?? 0);
			if ($from > 0) {
				$qb->andWhere($qb->expr()->gte($field, $qb->createNamedParameter($from, IQueryBuilder::PARAM_INT)));
			}
			if ($to > 0) {
				$qb->andWhere($qb->expr()->lte($field, $qb->createNamedParameter($to, IQueryBuilder::PARAM_INT)));
			}
		}

		$sortFields = ['year' => 'year', 'number' => 'number', 'subject' => 'subject', 'presentedAt' => 'presented_at', 'updatedAt' => 'updated_at'];
		$sort = $sortFields[(string)($filters['sort'] ?? '')] ?? 'year';
		$direction = strtolower((string)($filters['direction'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
		$qb->orderBy($sort, $direction)
			->addOrderBy($sort === 'number' ? 'year' : 'number', $direction)
			->setMaxResults(max(1, min($limit, 100)))
			->setFirstResult(max(0, $offset));

		return $this->findEntities($qb);
	}
}
