<?php

declare(strict_types=1);

namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

class Parliamentarian extends Entity {
	protected string $userUid=''; protected string $displayName=''; protected ?string $party=null; protected string $role='vereador'; protected ?int $seatNumber=null;
	protected ?\DateTime $termStart=null; protected ?\DateTime $termEnd=null; protected bool $active=true; protected ?\DateTime $createdAt=null; protected ?\DateTime $updatedAt=null;
	public function __construct(){foreach(['id','seatNumber'] as $f)$this->addType($f,Types::INTEGER);$this->addType('active',Types::BOOLEAN);foreach(['termStart','termEnd'] as $f)$this->addType($f,Types::DATE);foreach(['createdAt','updatedAt'] as $f)$this->addType($f,Types::DATETIME);}
	public function jsonSerialize():array{return['id'=>$this->getId(),'userUid'=>$this->getUserUid(),'displayName'=>$this->getDisplayName(),'party'=>$this->getParty(),'role'=>$this->getRole(),'seatNumber'=>$this->getSeatNumber(),'termStart'=>$this->getTermStart()->format('Y-m-d'),'termEnd'=>$this->getTermEnd()->format('Y-m-d'),'active'=>$this->getActive(),'createdAt'=>$this->getCreatedAt()->format(DATE_ATOM),'updatedAt'=>$this->getUpdatedAt()->format(DATE_ATOM)];}
}
