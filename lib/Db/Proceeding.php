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
 * @method void setMatterId(int $value)
 * @method int getMatterId()
 * @method void setSenderUid(string $value)
 * @method string getSenderUid()
 * @method void setRecipient(string $value)
 * @method string getRecipient()
 * @method void setObjective(string $value)
 * @method string getObjective()
 * @method void setResult(?string $value)
 * @method ?string getResult()
 * @method void setSentAt(\DateTime $value)
 * @method \DateTime getSentAt()
 * @method void setDueAt(?\DateTime $value)
 * @method ?\DateTime getDueAt()
 * @method void setAnsweredAt(?\DateTime $value)
 * @method ?\DateTime getAnsweredAt()
 * @method void setDeadlineDays(?int $value)
 * @method ?int getDeadlineDays()
 * @method void setBusinessDays(bool $value)
 * @method bool getBusinessDays()
 * @method void setNotes(?string $value)
 * @method ?string getNotes()
 * @method void setCreatedAt(\DateTime $value)
 * @method \DateTime getCreatedAt()
 */
class Proceeding extends Entity {
	protected int $matterId = 0;
	protected string $senderUid = '';
	protected string $recipient = '';
	protected string $objective = '';
	protected ?string $result = null;
	protected ?\DateTime $sentAt = null;
	protected ?\DateTime $dueAt = null;
	protected ?\DateTime $answeredAt = null;
	protected ?int $deadlineDays = null;
	protected bool $businessDays = true;
	protected ?string $notes = null;
	protected ?\DateTime $createdAt = null;

	public function __construct() {
		$this->addType('id', Types::INTEGER);
		$this->addType('matterId', Types::INTEGER);
		$this->addType('sentAt', Types::DATETIME);
		$this->addType('dueAt', Types::DATETIME);
		$this->addType('answeredAt', Types::DATETIME);
		$this->addType('deadlineDays', Types::INTEGER);
		$this->addType('businessDays', Types::BOOLEAN);
		$this->addType('createdAt', Types::DATETIME);
	}

	/** @return array<string, mixed> */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'matterId' => $this->getMatterId(),
			'senderUid' => $this->getSenderUid(),
			'recipient' => $this->getRecipient(),
			'objective' => $this->getObjective(),
			'result' => $this->getResult(),
			'sentAt' => $this->getSentAt()->format(DATE_ATOM),
			'dueAt' => $this->getDueAt()?->format(DATE_ATOM),
			'answeredAt' => $this->getAnsweredAt()?->format(DATE_ATOM),
			'deadlineDays' => $this->getDeadlineDays(),
			'businessDays' => $this->getBusinessDays(),
			'notes' => $this->getNotes(),
			'createdAt' => $this->getCreatedAt()->format(DATE_ATOM),
		];
	}
}
