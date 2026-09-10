<?php

declare(strict_types=1);

namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

class LegislativeSession extends Entity {
	protected string $type = ''; protected int $number = 0; protected int $year = 0;
	protected ?\DateTime $scheduledAt = null; protected string $status = 'scheduled'; protected int $totalSeats = 11;
	protected ?string $chairUid = null; protected ?\DateTime $openedAt = null; protected ?\DateTime $closedAt = null;
	protected ?string $notes = null; protected ?int $agendaFileId = null; protected ?int $minutesFileId = null;
	protected ?string $minutesHtml = null; protected int $minutesRevision = 0; protected ?string $minutesUpdatedBy = null; protected ?\DateTime $minutesUpdatedAt = null;
	protected ?string $minutesLibresignUuid = null; protected string $minutesSignatureStatus = 'not_requested'; protected ?array $minutesSigners = null;
	protected ?string $timerLabel = null; protected string $timerStatus = 'idle'; protected ?\DateTime $timerStartedAt = null; protected ?int $timerDuration = null;
	protected ?\DateTime $createdAt = null; protected ?\DateTime $updatedAt = null;
	public function __construct() { foreach (['id','number','year','totalSeats','agendaFileId','minutesFileId','minutesRevision','timerDuration'] as $f) $this->addType($f, Types::INTEGER); foreach (['scheduledAt','openedAt','closedAt','minutesUpdatedAt','timerStartedAt','createdAt','updatedAt'] as $f) $this->addType($f, Types::DATETIME); $this->addType('minutesSigners', Types::JSON); }
	public function jsonSerialize(): array { return ['id'=>$this->getId(),'type'=>$this->getType(),'number'=>$this->getNumber(),'year'=>$this->getYear(),'scheduledAt'=>$this->getScheduledAt()->format(DATE_ATOM),'status'=>$this->getStatus(),'totalSeats'=>$this->getTotalSeats(),'chairUid'=>$this->getChairUid(),'openedAt'=>$this->getOpenedAt()?->format(DATE_ATOM),'closedAt'=>$this->getClosedAt()?->format(DATE_ATOM),'notes'=>$this->getNotes(),'agendaFileId'=>$this->getAgendaFileId(),'minutesFileId'=>$this->getMinutesFileId(),'minutesHtml'=>$this->getMinutesHtml(),'minutesRevision'=>$this->getMinutesRevision(),'minutesUpdatedBy'=>$this->getMinutesUpdatedBy(),'minutesUpdatedAt'=>$this->getMinutesUpdatedAt()?->format(DATE_ATOM),'minutesLibresignUuid'=>$this->getMinutesLibresignUuid(),'minutesSignatureStatus'=>$this->getMinutesSignatureStatus(),'minutesSigners'=>$this->getMinutesSigners(),'timerLabel'=>$this->getTimerLabel(),'timerStatus'=>$this->getTimerStatus(),'timerStartedAt'=>$this->getTimerStartedAt()?->format(DATE_ATOM),'timerDuration'=>$this->getTimerDuration(),'serverTime'=>(new \DateTimeImmutable('now',new \DateTimeZone('UTC')))->format(DATE_ATOM),'createdAt'=>$this->getCreatedAt()->format(DATE_ATOM),'updatedAt'=>$this->getUpdatedAt()->format(DATE_ATOM)]; }
}
