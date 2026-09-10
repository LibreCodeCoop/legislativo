<?php

declare(strict_types=1);

namespace OCA\Legislativo\Service;

use OCA\Legislativo\Db\Attachment;
use OCA\Legislativo\Db\AttachmentMapper;
use OCA\Legislativo\Db\MatterMapper;
use OCA\Legislativo\Exception\ValidationException;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;

class AttachmentService {
	public function __construct(
		private IDBConnection $db,
		private IRootFolder $rootFolder,
		private MatterMapper $matterMapper,
		private AttachmentMapper $attachmentMapper,
		private AuthorizationService $authorization,
		private AuditService $auditService,
	) {
	}

	public function add(int $matterId, string $filePath): Attachment {
		$uid = $this->authorization->requireWrite();
		$this->matterMapper->find($matterId);
		$path = ltrim(trim($filePath), '/');
		if ($path === '') throw new ValidationException('Selecione um arquivo do Nextcloud.', ['filePath' => 'Arquivo obrigatório.']);
		try {
			$node = $this->rootFolder->getUserFolder($uid)->get($path);
		} catch (\Throwable) {
			throw new ValidationException('O arquivo selecionado não foi encontrado ou não está acessível.');
		}
		if (!$node instanceof File) throw new ValidationException('Selecione um arquivo, não uma pasta.');

		$attachment = new Attachment();
		$attachment->setMatterId($matterId);
		$attachment->setFileId($node->getId());
		$attachment->setFilePath('/' . $path);
		$attachment->setFileName($node->getName());
		$attachment->setMimeType($node->getMimeType());
		$attachment->setFileSize($node->getSize());
		$attachment->setAddedBy($uid);
		$attachment->setCreatedAt(new \DateTime('now', new \DateTimeZone('UTC')));

		$this->db->beginTransaction();
		try {
			$inserted = $this->attachmentMapper->insert($attachment);
			$this->auditService->record($uid, 'matter', $matterId, 'attachment.create', null, $inserted->jsonSerialize());
			$this->db->commit();
			return $inserted;
		} catch (\Throwable $exception) {
			$this->db->rollBack();
			throw $exception;
		}
	}
}
