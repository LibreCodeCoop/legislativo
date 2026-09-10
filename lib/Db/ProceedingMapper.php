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

/** @template-extends QBMapper<Proceeding> */
class ProceedingMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'leg_proceedings', Proceeding::class);
	}

	/** @return Proceeding[] */
	public function findByMatter(int $matterId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('matter_id', $qb->createNamedParameter($matterId, IQueryBuilder::PARAM_INT)))
			->orderBy('sent_at', 'ASC');

		return $this->findEntities($qb);
	}

	public function find(int $id): Proceeding {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

		return $this->findEntity($qb);
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return Proceeding[]
	 */
	public function searchDeadlines(array $filters, \DateTimeImmutable $now, int $limit = 100, int $offset = 0): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('p.*')
			->from($this->getTableName(), 'p')
			->innerJoin('p', 'leg_matters', 'm', $qb->expr()->eq('m.id', 'p.matter_id'));

		$state = (string)($filters['state'] ?? 'open');
		$startOfDay = $now->setTime(0, 0);
		$startOfTomorrow = $startOfDay->modify('+1 day');
		if ($state === 'completed') {
			$qb->andWhere($qb->expr()->isNotNull('p.answered_at'));
		} else {
			$qb->andWhere($qb->expr()->isNull('p.answered_at'));
			if ($state === 'overdue') {
				$qb->andWhere($qb->expr()->isNotNull('p.due_at'))
					->andWhere($qb->expr()->lt('p.due_at', $qb->createNamedParameter($startOfDay, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)));
			} elseif ($state === 'due_today') {
				$qb->andWhere($qb->expr()->gte('p.due_at', $qb->createNamedParameter($startOfDay, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
					->andWhere($qb->expr()->lt('p.due_at', $qb->createNamedParameter($startOfTomorrow, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)));
			} elseif ($state === 'upcoming') {
				$qb->andWhere($qb->expr()->gte('p.due_at', $qb->createNamedParameter($startOfTomorrow, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)));
			} elseif ($state === 'no_deadline') {
				$qb->andWhere($qb->expr()->isNull('p.due_at'));
			}
		}

		$recipient = trim((string)($filters['recipient'] ?? ''));
		if ($recipient !== '') {
			$term = $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($recipient) . '%');
			$qb->andWhere($qb->expr()->ilike('p.recipient', $term));
		}
		$type = trim((string)($filters['type'] ?? ''));
		if ($type !== '') {
			$qb->andWhere($qb->expr()->eq('m.type', $qb->createNamedParameter($type)));
		}
		if (($filters['dueFrom'] ?? null) instanceof \DateTimeImmutable) {
			$qb->andWhere($qb->expr()->gte('p.due_at', $qb->createNamedParameter($filters['dueFrom'], IQueryBuilder::PARAM_DATETIME_IMMUTABLE)));
		}
		if (($filters['dueTo'] ?? null) instanceof \DateTimeImmutable) {
			$qb->andWhere($qb->expr()->lt('p.due_at', $qb->createNamedParameter($filters['dueTo']->modify('+1 day'), IQueryBuilder::PARAM_DATETIME_IMMUTABLE)));
		}

		$qb->orderBy('p.due_at', 'ASC')
			->addOrderBy('p.sent_at', 'DESC')
			->setMaxResults(max(1, min($limit, 100)))
			->setFirstResult(max(0, $offset));

		return $this->findEntities($qb);
	}
}
