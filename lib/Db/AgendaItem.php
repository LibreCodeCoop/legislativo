<?php

declare(strict_types=1);

namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

class AgendaItem extends Entity {
	protected int $sessionId=0; protected int $matterId=0; protected int $position=0; protected string $status='pending';
	protected string $voteType='nominal'; protected string $quorumType='simple'; protected ?\DateTime $openedAt=null; protected ?\DateTime $closedAt=null;
	protected ?string $result=null; protected ?\DateTime $createdAt=null;
	public function __construct() { foreach (['id','sessionId','matterId','position'] as $f) $this->addType($f,Types::INTEGER); foreach (['openedAt','closedAt','createdAt'] as $f) $this->addType($f,Types::DATETIME); }
	public function jsonSerialize(): array { return ['id'=>$this->getId(),'sessionId'=>$this->getSessionId(),'matterId'=>$this->getMatterId(),'position'=>$this->getPosition(),'status'=>$this->getStatus(),'voteType'=>$this->getVoteType(),'quorumType'=>$this->getQuorumType(),'openedAt'=>$this->getOpenedAt()?->format(DATE_ATOM),'closedAt'=>$this->getClosedAt()?->format(DATE_ATOM),'result'=>$this->getResult(),'createdAt'=>$this->getCreatedAt()->format(DATE_ATOM)]; }
}
