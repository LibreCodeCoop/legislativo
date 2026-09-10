<?php

declare(strict_types=1);

namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method int getId()
 * @method void setMatterId(int $value)
 * @method int getMatterId()
 * @method void setProtocolId(int $value)
 * @method int getProtocolId()
 * @method void setSourceFileId(int $value)
 * @method int getSourceFileId()
 * @method void setSourcePath(string $value)
 * @method string getSourcePath()
 * @method void setPdfFileId(int $value)
 * @method int getPdfFileId()
 * @method void setPdfPath(string $value)
 * @method string getPdfPath()
 * @method void setStatus(string $value)
 * @method string getStatus()
 * @method void setLibresignUuid(?string $value)
 * @method ?string getLibresignUuid()
 * @method void setSigners(?array $value)
 * @method ?array getSigners()
 * @method void setChecksum(string $value)
 * @method string getChecksum()
 * @method void setErrorMessage(?string $value)
 * @method ?string getErrorMessage()
 * @method void setCreatedBy(string $value)
 * @method string getCreatedBy()
 * @method void setCreatedAt(\DateTime $value)
 * @method \DateTime getCreatedAt()
 * @method void setUpdatedAt(\DateTime $value)
 * @method \DateTime getUpdatedAt()
 */
class DocumentFlow extends Entity {
	protected int $matterId = 0;
	protected int $protocolId = 0;
	protected int $sourceFileId = 0;
	protected string $sourcePath = '';
	protected int $pdfFileId = 0;
	protected string $pdfPath = '';
	protected string $status = 'prepared';
	protected ?string $libresignUuid = null;
	protected ?array $signers = null;
	protected string $checksum = '';
	protected ?string $errorMessage = null;
	protected string $createdBy = '';
	protected ?\DateTime $createdAt = null;
	protected ?\DateTime $updatedAt = null;

	public function __construct() {
		$this->addType('id', Types::INTEGER);
		$this->addType('matterId', Types::INTEGER);
		$this->addType('protocolId', Types::INTEGER);
		$this->addType('sourceFileId', Types::INTEGER);
		$this->addType('pdfFileId', Types::INTEGER);
		$this->addType('signers', Types::JSON);
		$this->addType('createdAt', Types::DATETIME);
		$this->addType('updatedAt', Types::DATETIME);
	}

	/** @return array<string, mixed> */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(), 'matterId' => $this->getMatterId(), 'protocolId' => $this->getProtocolId(),
			'sourceFileId' => $this->getSourceFileId(), 'sourcePath' => $this->getSourcePath(),
			'pdfFileId' => $this->getPdfFileId(), 'pdfPath' => $this->getPdfPath(), 'status' => $this->getStatus(),
			'libresignUuid' => $this->getLibresignUuid(), 'signers' => $this->getSigners() ?? [],
			'checksum' => $this->getChecksum(), 'errorMessage' => $this->getErrorMessage(), 'createdBy' => $this->getCreatedBy(),
			'createdAt' => $this->getCreatedAt()->format(DATE_ATOM), 'updatedAt' => $this->getUpdatedAt()->format(DATE_ATOM),
		];
	}
}
