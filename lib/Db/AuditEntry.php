<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method int getId()
 * @method void setUserUid(string $value)
 * @method string getUserUid()
 * @method void setModule(string $value)
 * @method string getModule()
 * @method void setEntityType(string $value)
 * @method string getEntityType()
 * @method void setEntityId(int $value)
 * @method int getEntityId()
 * @method void setAction(string $value)
 * @method string getAction()
 * @method void setBeforeData(?array $value)
 * @method ?array getBeforeData()
 * @method void setAfterData(?array $value)
 * @method ?array getAfterData()
 * @method void setIpAddress(?string $value)
 * @method ?string getIpAddress()
 * @method void setUserAgent(?string $value)
 * @method ?string getUserAgent()
 * @method void setRequestId(?string $value)
 * @method ?string getRequestId()
 * @method void setCreatedAt(\DateTime $value)
 * @method \DateTime getCreatedAt()
 */
class AuditEntry extends Entity {
	protected string $userUid = '';
	protected string $module = '';
	protected string $entityType = '';
	protected int $entityId = 0;
	protected string $action = '';
	protected ?array $beforeData = null;
	protected ?array $afterData = null;
	protected ?string $ipAddress = null;
	protected ?string $userAgent = null;
	protected ?string $requestId = null;
	protected ?\DateTime $createdAt = null;

	public function __construct() {
		$this->addType('id', Types::INTEGER);
		$this->addType('entityId', Types::INTEGER);
		$this->addType('beforeData', Types::JSON);
		$this->addType('afterData', Types::JSON);
		$this->addType('createdAt', Types::DATETIME);
	}

	/** @return array<string, mixed> */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'userUid' => $this->getUserUid(),
			'module' => $this->getModule(),
			'entityType' => $this->getEntityType(),
			'entityId' => $this->getEntityId(),
			'action' => $this->getAction(),
			'before' => $this->getBeforeData(),
			'after' => $this->getAfterData(),
			'ipAddress' => $this->getIpAddress(),
			'userAgent' => $this->getUserAgent(),
			'requestId' => $this->getRequestId(),
			'createdAt' => $this->getCreatedAt()->format(DATE_ATOM),
		];
	}
}
