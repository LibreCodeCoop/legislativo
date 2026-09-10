<?php

declare(strict_types=1);

namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<Attachment> */
class AttachmentMapper extends QBMapper {
	public function __construct(IDBConnection $db) { parent::__construct($db, 'leg_attachments', Attachment::class); }

	/** @return Attachment[] */
	public function findByMatter(int $matterId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName())
			->where($qb->expr()->eq('matter_id', $qb->createNamedParameter($matterId, IQueryBuilder::PARAM_INT)))
			->orderBy('created_at', 'ASC');
		return $this->findEntities($qb);
	}
}
