<?php

declare(strict_types=1);

namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<SpeakerEntry> */
class SpeakerEntryMapper extends QBMapper {
	public function __construct(IDBConnection $db){parent::__construct($db,'leg_speaker_queue',SpeakerEntry::class);}
	public function find(int $id):SpeakerEntry{$qb=$this->db->getQueryBuilder();$qb->select('*')->from($this->getTableName())->where($qb->expr()->eq('id',$qb->createNamedParameter($id,IQueryBuilder::PARAM_INT)));return $this->findEntity($qb);}
	/** @return SpeakerEntry[] */ public function findBySession(int $sessionId):array{$qb=$this->db->getQueryBuilder();$qb->select('*')->from($this->getTableName())->where($qb->expr()->eq('session_id',$qb->createNamedParameter($sessionId,IQueryBuilder::PARAM_INT)))->orderBy('position','ASC');return $this->findEntities($qb);}
	public function nextPosition(int $sessionId):int{$qb=$this->db->getQueryBuilder();$qb->select($qb->createFunction('MAX(position)'))->from($this->getTableName())->where($qb->expr()->eq('session_id',$qb->createNamedParameter($sessionId,IQueryBuilder::PARAM_INT)));return((int)$qb->executeQuery()->fetchOne())+1;}
}
