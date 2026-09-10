<?php
declare(strict_types=1);
namespace OCA\Legislativo\Db;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
/** @template-extends QBMapper<NormVersion> */
class NormVersionMapper extends QBMapper { public function __construct(IDBConnection $db){parent::__construct($db,'leg_norm_versions',NormVersion::class);} public function find(int $id):NormVersion{$qb=$this->db->getQueryBuilder();$qb->select('*')->from($this->getTableName())->where($qb->expr()->eq('id',$qb->createNamedParameter($id,IQueryBuilder::PARAM_INT)));return$this->findEntity($qb);} /** @return list<NormVersion> */ public function findByNorm(int $normId):array{$qb=$this->db->getQueryBuilder();$qb->select('*')->from($this->getTableName())->where($qb->expr()->eq('norm_id',$qb->createNamedParameter($normId,IQueryBuilder::PARAM_INT)))->orderBy('valid_from','DESC');return$this->findEntities($qb);} public function findCurrent(int $normId,?\DateTimeImmutable $date=null):?NormVersion{$date??=new \DateTimeImmutable('today');$qb=$this->db->getQueryBuilder();$qb->select('*')->from($this->getTableName())->where($qb->expr()->eq('norm_id',$qb->createNamedParameter($normId,IQueryBuilder::PARAM_INT)))->andWhere($qb->expr()->lte('valid_from',$qb->createNamedParameter($date->format('Y-m-d'))))->andWhere($qb->expr()->orX($qb->expr()->isNull('valid_until'),$qb->expr()->gte('valid_until',$qb->createNamedParameter($date->format('Y-m-d')))))->orderBy('valid_from','DESC')->setMaxResults(1);$rows=$this->findEntities($qb);return$rows[0]??null;} }
