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
 * @method void setKind(string $value)
 * @method string getKind()
 * @method void setNumber(?int $value)
 * @method ?int getNumber()
 * @method void setYear(int $value)
 * @method int getYear()
 * @method void setSubject(string $value)
 * @method string getSubject()
 * @method void setBody(string $value)
 * @method string getBody()
 * @method void setRecipients(array $value)
 * @method array getRecipients()
 * @method void setStatus(string $value)
 * @method string getStatus()
 * @method void setCreatedBy(string $value)
 * @method string getCreatedBy()
 * @method void setIssuedBy(?string $value)
 * @method ?string getIssuedBy()
 * @method void setIssuedAt(?\DateTime $value)
 * @method ?\DateTime getIssuedAt()
 * @method void setCreatedAt(\DateTime $value)
 * @method \DateTime getCreatedAt()
 * @method void setUpdatedAt(\DateTime $value)
 * @method \DateTime getUpdatedAt()
 */
class OfficialDocument extends Entity {
	protected string $kind = '';
	protected ?int $number = null;
	protected int $year = 0;
	protected string $subject = '';
	protected string $body = '';
	/** @var list<string> */
	protected array $recipients = [];
	protected string $status = 'draft';
	protected string $createdBy = '';
	protected ?string $issuedBy = null;
	protected ?\DateTime $issuedAt = null;
	protected ?\DateTime $createdAt = null;
	protected ?\DateTime $updatedAt = null;

	public function __construct() {
		foreach (['id', 'number', 'year'] as $field) {
			$this->addType($field, Types::INTEGER);
		}
		$this->addType('recipients', Types::JSON);
		foreach (['issuedAt', 'createdAt', 'updatedAt'] as $field) {
			$this->addType($field, Types::DATETIME);
		}
	}

	/** @return array<string, mixed> */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'kind' => $this->getKind(),
			'number' => $this->getNumber(),
			'year' => $this->getYear(),
			'subject' => $this->getSubject(),
			'body' => $this->getBody(),
			'recipients' => $this->getRecipients(),
			'status' => $this->getStatus(),
			'createdBy' => $this->getCreatedBy(),
			'issuedBy' => $this->getIssuedBy(),
			'issuedAt' => $this->getIssuedAt()?->format(DATE_ATOM),
			'createdAt' => $this->getCreatedAt()->format(DATE_ATOM),
			'updatedAt' => $this->getUpdatedAt()->format(DATE_ATOM),
		];
	}
}
