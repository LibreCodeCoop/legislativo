<?php

declare(strict_types=1);

namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<LegislativeSession> */
class LegislativeSessionMapper extends QBMapper {
	public function __construct(IDBConnection $db) { parent::__construct($db, 'leg_sessions', LegislativeSession::class); }
	public function find(int $id): LegislativeSession { $qb=$this->db->getQueryBuilder(); $qb->select('*')->from($this->getTableName())->where($qb->expr()->eq('id',$qb->createNamedParameter($id,IQueryBuilder::PARAM_INT))); return $this->findEntity($qb); }
	/** @return LegislativeSession[] */ public function findAllSessions(): array { $qb=$this->db->getQueryBuilder(); $qb->select('*')->from($this->getTableName())->orderBy('scheduled_at','DESC')->setMaxResults(100); return $this->findEntities($qb); }
}
