<?php

declare(strict_types=1);

namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<Vote> */
class VoteMapper extends QBMapper {
	public function __construct(IDBConnection $db) { parent::__construct($db,'leg_votes',Vote::class); }
	/** @return Vote[] */ public function findForBallot(int $sessionId,int $matterId): array { $qb=$this->db->getQueryBuilder(); $qb->select('*')->from($this->getTableName())->where($qb->expr()->eq('session_id',$qb->createNamedParameter($sessionId,IQueryBuilder::PARAM_INT)))->andWhere($qb->expr()->eq('matter_id',$qb->createNamedParameter($matterId,IQueryBuilder::PARAM_INT)))->orderBy('cast_at','ASC'); return $this->findEntities($qb); }
}
