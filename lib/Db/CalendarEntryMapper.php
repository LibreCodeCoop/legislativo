<?php

declare(strict_types=1);

namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<CalendarEntry> */
class CalendarEntryMapper extends QBMapper {
	public function __construct(IDBConnection $db) { parent::__construct($db, 'leg_calendar', CalendarEntry::class); }

	public function find(int $id): CalendarEntry {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		return $this->findEntity($qb);
	}

	/** @return CalendarEntry[] */
	public function findAll(bool $activeOnly = false): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName());
		if ($activeOnly) $qb->where($qb->expr()->eq('active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)));
		$qb->orderBy('starts_on', 'ASC');
		return $this->findEntities($qb);
	}
}
