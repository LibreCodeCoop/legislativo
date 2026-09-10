<?php

declare(strict_types=1);

namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method int getId()
 * @method void setMatterId(int $value)
 * @method int getMatterId()
 * @method void setSender(string $value)
 * @method string getSender()
 * @method void setSubject(string $value)
 * @method string getSubject()
 * @method void setNotes(?string $value)
 * @method ?string getNotes()
 * @method void setStatus(string $value)
 * @method string getStatus()
 * @method void setFileId(?int $value)
 * @method ?int getFileId()
 * @method void setFilePath(?string $value)
 * @method ?string getFilePath()
 * @method void setSubmittedAt(\DateTime $value)
 * @method \DateTime getSubmittedAt()
 * @method void setReviewedAt(?\DateTime $value)
 * @method ?\DateTime getReviewedAt()
 * @method void setReviewedBy(?string $value)
 * @method ?string getReviewedBy()
 * @method void setReviewNotes(?string $value)
 * @method ?string getReviewNotes()
 */
class Submission extends Entity {
	protected int $matterId = 0;
	protected string $sender = '';
	protected string $subject = '';
	protected ?string $notes = null;
	protected string $status = 'pending';
	protected ?int $fileId = null;
	protected ?string $filePath = null;
	protected ?\DateTime $submittedAt = null;
	protected ?\DateTime $reviewedAt = null;
	protected ?string $reviewedBy = null;
	protected ?string $reviewNotes = null;

	public function __construct() {
		foreach (['id', 'matterId', 'fileId'] as $field) $this->addType($field, Types::INTEGER);
		foreach (['submittedAt', 'reviewedAt'] as $field) $this->addType($field, Types::DATETIME);
	}

	public function jsonSerialize(): array {
		return ['id' => $this->getId(), 'matterId' => $this->getMatterId(), 'sender' => $this->getSender(),
			'subject' => $this->getSubject(), 'notes' => $this->getNotes(), 'status' => $this->getStatus(),
			'fileId' => $this->getFileId(), 'filePath' => $this->getFilePath(),
			'submittedAt' => $this->getSubmittedAt()->format(DATE_ATOM),
			'reviewedAt' => $this->getReviewedAt()?->format(DATE_ATOM), 'reviewedBy' => $this->getReviewedBy(),
			'reviewNotes' => $this->getReviewNotes()];
	}
}
