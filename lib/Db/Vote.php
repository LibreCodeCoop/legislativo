<?php

declare(strict_types=1);

namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

class Vote extends Entity {
	protected int $sessionId=0; protected int $matterId=0; protected string $voterUid=''; protected string $choice=''; protected bool $secret=false; protected ?\DateTime $castAt=null;
	public function __construct() { foreach (['id','sessionId','matterId'] as $f) $this->addType($f,Types::INTEGER); $this->addType('secret',Types::BOOLEAN); $this->addType('castAt',Types::DATETIME); }
	public function jsonSerialize(): array { return ['id'=>$this->getId(),'sessionId'=>$this->getSessionId(),'matterId'=>$this->getMatterId(),'voterUid'=>$this->getVoterUid(),'choice'=>$this->getChoice(),'secret'=>$this->getSecret(),'castAt'=>$this->getCastAt()->format(DATE_ATOM)]; }
}
