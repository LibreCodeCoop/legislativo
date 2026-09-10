<?php
declare(strict_types=1);
namespace OCA\Legislativo\Db;
use OCA\Legislativo\Service\TextNormalizer;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
/** @template-extends QBMapper<Norm> */
class NormMapper extends QBMapper {
	public function __construct(IDBConnection $db,private TextNormalizer $textNormalizer){parent::__construct($db,'leg_norms',Norm::class);}
	public function find(int $id):Norm{$qb=$this->db->getQueryBuilder();$qb->select('*')->from($this->getTableName())->where($qb->expr()->eq('id',$qb->createNamedParameter($id,IQueryBuilder::PARAM_INT)));return$this->findEntity($qb);}
	/** @return list<Norm> */
	public function search(array $filters, int $limit = 50, int $offset = 0, bool $publishedOnly = false): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName());
		$query = trim((string)($filters['query'] ?? ''));
		if ($query !== '') {
			$mode = (string)($filters['queryMode'] ?? 'all');
			if ($mode === 'phrase') {
				$term = $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($this->textNormalizer->normalize($query)) . '%');
				$qb->andWhere($qb->expr()->ilike('search_text', $term));
			} else {
				$conditions = [];
				foreach ($this->textNormalizer->queryTokens($query) as $token) {
					$term = $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($token) . '%');
					$condition = $qb->expr()->ilike('search_text', $term);
					if ($mode === 'any') $conditions[] = $condition; else $qb->andWhere($condition);
				}
				if ($conditions !== []) $qb->andWhere($qb->expr()->orX(...$conditions));
			}
		}
		foreach (['type', 'status'] as $field) {
			$value = trim((string)($filters[$field] ?? ''));
			if ($value !== '' && (!$publishedOnly || $field !== 'status')) $qb->andWhere($qb->expr()->eq($field, $qb->createNamedParameter($value)));
		}
		if ($publishedOnly) $qb->andWhere($qb->expr()->eq('status', $qb->createNamedParameter('published')));
		foreach (['number', 'year'] as $field) {
			$value = (int)($filters[$field] ?? 0);
			if ($value > 0) $qb->andWhere($qb->expr()->eq($field, $qb->createNamedParameter($value, IQueryBuilder::PARAM_INT)));
			$from = (int)($filters[$field . 'From'] ?? 0);
			$to = (int)($filters[$field . 'To'] ?? 0);
			if ($from > 0) $qb->andWhere($qb->expr()->gte($field, $qb->createNamedParameter($from, IQueryBuilder::PARAM_INT)));
			if ($to > 0) $qb->andWhere($qb->expr()->lte($field, $qb->createNamedParameter($to, IQueryBuilder::PARAM_INT)));
		}
		$sortFields = ['year' => 'year', 'number' => 'number', 'title' => 'title', 'publishedAt' => 'published_at', 'updatedAt' => 'updated_at'];
		$sort = $sortFields[(string)($filters['sort'] ?? '')] ?? 'year';
		$direction = strtolower((string)($filters['direction'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
		$qb->orderBy($sort, $direction)->addOrderBy($sort === 'number' ? 'year' : 'number', $direction)
			->setMaxResults(max(1, min($limit, 100)))->setFirstResult(max(0, $offset));
		return $this->findEntities($qb);
	}
}
