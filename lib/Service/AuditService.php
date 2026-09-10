<?php

declare(strict_types=1);

namespace OCA\Legislativo\Service;

use OCA\Legislativo\Db\AuditEntry;
use OCA\Legislativo\Db\AuditEntryMapper;
use OCP\IRequest;

class AuditService {
	public function __construct(private AuditEntryMapper $auditMapper, private IRequest $request) {
	}

	public function record(string $uid, string $entityType, int $entityId, string $action, ?array $before, ?array $after): void {
		$entry = new AuditEntry();
		$entry->setUserUid($uid);
		$entry->setModule('processo_legislativo');
		$entry->setEntityType($entityType);
		$entry->setEntityId($entityId);
		$entry->setAction($action);
		$entry->setBeforeData($before);
		$entry->setAfterData($after);
		$entry->setIpAddress(substr($this->request->getRemoteAddress(), 0, 64));
		$entry->setUserAgent(substr($this->request->getHeader('User-Agent'), 0, 512));
		$entry->setRequestId(substr($this->request->getId(), 0, 64));
		$entry->setCreatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
		$this->auditMapper->insert($entry);
	}
}
