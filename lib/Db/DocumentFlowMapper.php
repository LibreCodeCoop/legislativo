<?php

declare(strict_types=1);

namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<DocumentFlow> */
class DocumentFlowMapper extends QBMapper {
	public function __construct(IDBConnection $db) { parent::__construct($db, 'leg_document_flows', DocumentFlow::class); }

	public function find(int $id): DocumentFlow {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		return $this->findEntity($qb);
	}

	/** @return DocumentFlow[] */
	public function findByMatter(int $matterId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName())
			->where($qb->expr()->eq('matter_id', $qb->createNamedParameter($matterId, IQueryBuilder::PARAM_INT)))
			->orderBy('created_at', 'DESC');
		return $this->findEntities($qb);
	}
}
