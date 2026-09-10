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
 * @method void setNumber(int $value)
 * @method int getNumber()
 * @method void setYear(int $value)
 * @method int getYear()
 * @method void setSender(string $value)
 * @method string getSender()
 * @method void setSubject(string $value)
 * @method string getSubject()
 * @method void setStatus(string $value)
 * @method string getStatus()
 * @method void setFileId(?int $value)
 * @method ?int getFileId()
 * @method void setSubmissionId(?int $value)
 * @method ?int getSubmissionId()
 * @method void setReceivedAt(\DateTime $value)
 * @method \DateTime getReceivedAt()
 * @method void setCreatedAt(\DateTime $value)
 * @method \DateTime getCreatedAt()
 * @method void setUpdatedAt(\DateTime $value)
 * @method \DateTime getUpdatedAt()
 */
class Protocol extends Entity {
	protected int $matterId = 0;
	protected int $number = 0;
	protected int $year = 0;
	protected string $sender = '';
	protected string $subject = '';
	protected string $status = 'received';
	protected ?int $fileId = null;
	protected ?int $submissionId = null;
	protected ?\DateTime $receivedAt = null;
	protected ?\DateTime $createdAt = null;
	protected ?\DateTime $updatedAt = null;

	public function __construct() {
		$this->addType('id', Types::INTEGER);
		$this->addType('matterId', Types::INTEGER);
		$this->addType('number', Types::INTEGER);
		$this->addType('year', Types::INTEGER);
		$this->addType('fileId', Types::INTEGER);
		$this->addType('submissionId', Types::INTEGER);
		$this->addType('receivedAt', Types::DATETIME);
		$this->addType('createdAt', Types::DATETIME);
		$this->addType('updatedAt', Types::DATETIME);
	}

	/** @return array<string, mixed> */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'matterId' => $this->getMatterId(),
			'number' => $this->getNumber(),
			'year' => $this->getYear(),
			'sender' => $this->getSender(),
			'subject' => $this->getSubject(),
			'status' => $this->getStatus(),
			'fileId' => $this->getFileId(),
			'submissionId' => $this->getSubmissionId(),
			'receivedAt' => $this->getReceivedAt()->format(DATE_ATOM),
			'createdAt' => $this->getCreatedAt()->format(DATE_ATOM),
			'updatedAt' => $this->getUpdatedAt()->format(DATE_ATOM),
		];
	}
}
