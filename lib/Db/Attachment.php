<?php

declare(strict_types=1);

namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method int getId()
 * @method void setMatterId(int $value)
 * @method int getMatterId()
 * @method void setFileId(int $value)
 * @method int getFileId()
 * @method void setFilePath(string $value)
 * @method string getFilePath()
 * @method void setFileName(string $value)
 * @method string getFileName()
 * @method void setMimeType(string $value)
 * @method string getMimeType()
 * @method void setFileSize(int $value)
 * @method int getFileSize()
 * @method void setAddedBy(string $value)
 * @method string getAddedBy()
 * @method void setCreatedAt(\DateTime $value)
 * @method \DateTime getCreatedAt()
 */
class Attachment extends Entity {
	protected int $matterId = 0;
	protected int $fileId = 0;
	protected string $filePath = '';
	protected string $fileName = '';
	protected string $mimeType = '';
	protected int $fileSize = 0;
	protected string $addedBy = '';
	protected ?\DateTime $createdAt = null;

	public function __construct() {
		foreach (['id', 'matterId', 'fileId', 'fileSize'] as $field) $this->addType($field, Types::INTEGER);
		$this->addType('createdAt', Types::DATETIME);
	}

	public function jsonSerialize(): array {
		return ['id' => $this->getId(), 'matterId' => $this->getMatterId(), 'fileId' => $this->getFileId(),
			'filePath' => $this->getFilePath(), 'fileName' => $this->getFileName(), 'mimeType' => $this->getMimeType(),
			'fileSize' => $this->getFileSize(), 'addedBy' => $this->getAddedBy(), 'createdAt' => $this->getCreatedAt()->format(DATE_ATOM)];
	}
}
