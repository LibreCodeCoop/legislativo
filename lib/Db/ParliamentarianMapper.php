<?php

declare(strict_types=1);

namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<Parliamentarian> */
class ParliamentarianMapper extends QBMapper {
	public function __construct(IDBConnection $db){parent::__construct($db,'leg_parliamentarians',Parliamentarian::class);}
	/** @return Parliamentarian[] */ public function findAllProfiles(bool $onlyActive=false):array{$qb=$this->db->getQueryBuilder();$qb->select('*')->from($this->getTableName());if($onlyActive)$qb->where($qb->expr()->eq('active',$qb->createNamedParameter(true,IQueryBuilder::PARAM_BOOL)));$qb->orderBy('display_name','ASC');return $this->findEntities($qb);}
	public function find(int $id):Parliamentarian{$qb=$this->db->getQueryBuilder();$qb->select('*')->from($this->getTableName())->where($qb->expr()->eq('id',$qb->createNamedParameter($id,IQueryBuilder::PARAM_INT)));return $this->findEntity($qb);}
	public function findByUser(string $uid):Parliamentarian{$qb=$this->db->getQueryBuilder();$qb->select('*')->from($this->getTableName())->where($qb->expr()->eq('user_uid',$qb->createNamedParameter($uid)));return $this->findEntity($qb);}
}
