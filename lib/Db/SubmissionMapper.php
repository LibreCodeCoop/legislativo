<?php

declare(strict_types=1);

namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<Submission> */
class SubmissionMapper extends QBMapper {
	public function __construct(IDBConnection $db) { parent::__construct($db, 'leg_submissions', Submission::class); }

	public function find(int $id): Submission {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		return $this->findEntity($qb);
	}

	/** @return Submission[] */
	public function findByMatter(int $matterId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName())
			->where($qb->expr()->eq('matter_id', $qb->createNamedParameter($matterId, IQueryBuilder::PARAM_INT)))
			->orderBy('submitted_at', 'ASC');
		return $this->findEntities($qb);
	}

	/** @return Submission[] */
	public function findQueue(string $status = 'pending'): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName());
		if ($status !== '') $qb->where($qb->expr()->eq('status', $qb->createNamedParameter($status)));
		$qb->orderBy('submitted_at', 'ASC')->setMaxResults(100);
		return $this->findEntities($qb);
	}
}
