<?php

declare(strict_types=1);

namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

class SpeakerEntry extends Entity {
	protected int $sessionId=0;protected string $userUid='';protected string $displayName='';protected ?string $topic=null;protected int $position=0;protected string $status='waiting';protected int $allottedSeconds=300;protected ?\DateTime $requestedAt=null;protected ?\DateTime $startedAt=null;protected ?\DateTime $endedAt=null;
	public function __construct(){foreach(['id','sessionId','position','allottedSeconds'] as $f)$this->addType($f,Types::INTEGER);foreach(['requestedAt','startedAt','endedAt'] as $f)$this->addType($f,Types::DATETIME);}
	public function jsonSerialize():array{return['id'=>$this->getId(),'sessionId'=>$this->getSessionId(),'userUid'=>$this->getUserUid(),'displayName'=>$this->getDisplayName(),'topic'=>$this->getTopic(),'position'=>$this->getPosition(),'status'=>$this->getStatus(),'allottedSeconds'=>$this->getAllottedSeconds(),'requestedAt'=>$this->getRequestedAt()->format(DATE_ATOM),'startedAt'=>$this->getStartedAt()?->format(DATE_ATOM),'endedAt'=>$this->getEndedAt()?->format(DATE_ATOM)];}
}
