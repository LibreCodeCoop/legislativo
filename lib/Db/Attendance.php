<?php

declare(strict_types=1);

namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

class Attendance extends Entity {
	protected int $sessionId=0; protected string $userUid=''; protected string $displayName=''; protected bool $present=true; protected ?\DateTime $checkedAt=null;
	public function __construct() { $this->addType('id',Types::INTEGER); $this->addType('sessionId',Types::INTEGER); $this->addType('present',Types::BOOLEAN); $this->addType('checkedAt',Types::DATETIME); }
	public function jsonSerialize(): array { return ['id'=>$this->getId(),'sessionId'=>$this->getSessionId(),'userUid'=>$this->getUserUid(),'displayName'=>$this->getDisplayName(),'present'=>$this->getPresent(),'checkedAt'=>$this->getCheckedAt()->format(DATE_ATOM)]; }
}
