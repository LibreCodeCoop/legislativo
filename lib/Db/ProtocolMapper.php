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

/** @template-extends QBMapper<Protocol> */
class ProtocolMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'leg_protocols', Protocol::class);
	}

	public function find(int $id): Protocol {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		return $this->findEntity($qb);
	}

	/** @return Protocol[] */
	public function findByMatter(int $matterId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('matter_id', $qb->createNamedParameter($matterId, IQueryBuilder::PARAM_INT)))
			->orderBy('received_at', 'ASC');

		return $this->findEntities($qb);
	}

	public function nextNumber(int $year): int {
		$qb = $this->db->getQueryBuilder();
		$qb->select($qb->createFunction('MAX(number)'))
			->from($this->getTableName())
			->where($qb->expr()->eq('year', $qb->createNamedParameter($year, IQueryBuilder::PARAM_INT)));

		return ((int)$qb->executeQuery()->fetchOne()) + 1;
	}
}
