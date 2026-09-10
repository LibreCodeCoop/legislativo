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
 * @method void setType(string $value)
 * @method string getType()
 * @method void setNumber(int $value)
 * @method int getNumber()
 * @method void setYear(int $value)
 * @method int getYear()
 * @method void setSubject(string $value)
 * @method string getSubject()
 * @method void setBody(?string $value)
 * @method ?string getBody()
 * @method void setAuthorUid(?string $value)
 * @method ?string getAuthorUid()
 * @method void setStatus(string $value)
 * @method string getStatus()
 * @method void setTheme(?string $value)
 * @method ?string getTheme()
 * @method void setQuorum(?string $value)
 * @method ?string getQuorum()
 * @method void setProcedure(?string $value)
 * @method ?string getProcedure()
 * @method void setNotes(?string $value)
 * @method ?string getNotes()
 * @method void setPresentedAt(?\DateTime $value)
 * @method ?\DateTime getPresentedAt()
 * @method void setFileId(?int $value)
 * @method ?int getFileId()
 * @method void setCreatedAt(\DateTime $value)
 * @method \DateTime getCreatedAt()
 * @method void setUpdatedAt(\DateTime $value)
 * @method \DateTime getUpdatedAt()
 * @method void setSearchText(?string $value)
 * @method ?string getSearchText()
 */
class Matter extends Entity {
	protected string $type = '';
	protected int $number = 0;
	protected int $year = 0;
	protected string $subject = '';
	protected ?string $body = null;
	protected ?string $authorUid = null;
	protected string $status = 'draft';
	protected ?string $theme = null;
	protected ?string $quorum = null;
	protected ?string $procedure = null;
	protected ?string $notes = null;
	protected ?\DateTime $presentedAt = null;
	protected ?int $fileId = null;
	protected ?\DateTime $createdAt = null;
	protected ?\DateTime $updatedAt = null;
	protected ?string $searchText = null;

	public function __construct() {
		$this->addType('id', Types::INTEGER);
		$this->addType('number', Types::INTEGER);
		$this->addType('year', Types::INTEGER);
		$this->addType('presentedAt', Types::DATE);
		$this->addType('fileId', Types::INTEGER);
		$this->addType('createdAt', Types::DATETIME);
		$this->addType('updatedAt', Types::DATETIME);
	}

	/** @return array<string, mixed> */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'type' => $this->getType(),
			'number' => $this->getNumber(),
			'year' => $this->getYear(),
			'subject' => $this->getSubject(),
			'body' => $this->getBody(),
			'authorUid' => $this->getAuthorUid(),
			'status' => $this->getStatus(),
			'theme' => $this->getTheme(),
			'quorum' => $this->getQuorum(),
			'procedure' => $this->getProcedure(),
			'notes' => $this->getNotes(),
			'presentedAt' => $this->getPresentedAt()?->format('Y-m-d'),
			'fileId' => $this->getFileId(),
			'createdAt' => $this->getCreatedAt()->format(DATE_ATOM),
			'updatedAt' => $this->getUpdatedAt()->format(DATE_ATOM),
		];
	}
}
