<?php
declare(strict_types=1);
namespace OCA\Legislativo\Db;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
/** @template-extends QBMapper<NormRelation> */
class NormRelationMapper extends QBMapper { public function __construct(IDBConnection $db){parent::__construct($db,'leg_norm_relations',NormRelation::class);} /** @return list<NormRelation> */ public function findByNorm(int $normId):array{$qb=$this->db->getQueryBuilder();$qb->select('*')->from($this->getTableName())->where($qb->expr()->orX($qb->expr()->eq('source_norm_id',$qb->createNamedParameter($normId,IQueryBuilder::PARAM_INT)),$qb->expr()->eq('target_norm_id',$qb->createNamedParameter($normId,IQueryBuilder::PARAM_INT))))->orderBy('created_at','ASC');return$this->findEntities($qb);} }
