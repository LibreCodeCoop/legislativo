<?php

declare(strict_types=1);

namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<Attendance> */
class AttendanceMapper extends QBMapper {
	public function __construct(IDBConnection $db) { parent::__construct($db,'leg_attendance',Attendance::class); }
	/** @return Attendance[] */ public function findBySession(int $sessionId): array { $qb=$this->db->getQueryBuilder(); $qb->select('*')->from($this->getTableName())->where($qb->expr()->eq('session_id',$qb->createNamedParameter($sessionId,IQueryBuilder::PARAM_INT)))->orderBy('display_name','ASC'); return $this->findEntities($qb); }
	public function findByUser(int $sessionId,string $uid): Attendance { $qb=$this->db->getQueryBuilder(); $qb->select('*')->from($this->getTableName())->where($qb->expr()->eq('session_id',$qb->createNamedParameter($sessionId,IQueryBuilder::PARAM_INT)))->andWhere($qb->expr()->eq('user_uid',$qb->createNamedParameter($uid))); return $this->findEntity($qb); }
}
