<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Service;

use OCA\Legislativo\Db\OfficialDocument;
use OCA\Legislativo\Db\OfficialDocumentMapper;
use OCA\Legislativo\Exception\ValidationException;
use OCP\IDBConnection;

class OfficialDocumentService {
	public function __construct(
		private IDBConnection $db,
		private OfficialDocumentMapper $documents,
		private OfficialDocumentValidator $validator,
		private AuthorizationService $authorization,
		private AuditService $audit,
	) {
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return array<int, array<string, mixed>>
	 */
	public function search(array $filters): array {
		$this->authorization->requireWrite();

		return array_map(static fn (OfficialDocument $document): array => $document->jsonSerialize(), $this->documents->search($filters));
	}

	/** @return array<string, mixed> */
	public function get(int $id): array {
		$this->authorization->requireWrite();

		return $this->documents->find($id)->jsonSerialize();
	}

	/** @param array<string, mixed> $data */
	public function create(array $data): OfficialDocument {
		$uid = $this->authorization->requireWrite();
		$values = $this->validator->validate($data);
		$now = new \DateTime('now', new \DateTimeZone('UTC'));
		$document = new OfficialDocument();
		$this->apply($document, $values);
		$document->setStatus('draft');
		$document->setCreatedBy($uid);
		$document->setCreatedAt($now);
		$document->setUpdatedAt($now);
		$inserted = $this->documents->insert($document);
		$this->audit->record($uid, 'official_document', $inserted->getId(), 'official_document.create', null, $inserted->jsonSerialize());

		return $inserted;
	}

	/** @param array<string, mixed> $data */
	public function update(int $id, array $data): OfficialDocument {
		$uid = $this->authorization->requireWrite();
		$document = $this->documents->find($id);
		if ($document->getStatus() !== 'draft') {
			throw new ValidationException('Somente rascunhos podem ser alterados.');
		}
		$before = $document->jsonSerialize();
		$complete = array_merge($before, $data);
		$values = $this->validator->validate($complete);
		$this->apply($document, $values);
		$document->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
		$updated = $this->documents->update($document);
		$this->audit->record($uid, 'official_document', $id, 'official_document.update', $before, $updated->jsonSerialize());

		return $updated;
	}

	public function issue(int $id): OfficialDocument {
		$uid = $this->authorization->requireWrite();
		$document = $this->documents->find($id);
		if ($document->getStatus() !== 'draft') {
			throw new ValidationException('O documento já foi emitido.');
		}
		$before = $document->jsonSerialize();
		$now = new \DateTime('now', new \DateTimeZone('UTC'));

		$this->db->beginTransaction();
		try {
			$document->setNumber($this->documents->nextNumber($document->getKind(), $document->getYear()));
			$document->setStatus('issued');
			$document->setIssuedBy($uid);
			$document->setIssuedAt($now);
			$document->setUpdatedAt($now);
			$updated = $this->documents->update($document);
			$this->audit->record($uid, 'official_document', $id, 'official_document.issue', $before, $updated->jsonSerialize());
			$this->db->commit();
		} catch (\Throwable $exception) {
			$this->db->rollBack();
			throw $exception;
		}

		return $updated;
	}

	/** @param array<string, mixed> $values */
	private function apply(OfficialDocument $document, array $values): void {
		if (isset($values['kind'])) {
			$document->setKind($values['kind']);
		}
		if (isset($values['year'])) {
			$document->setYear($values['year']);
		}
		if (isset($values['subject'])) {
			$document->setSubject($values['subject']);
		}
		if (isset($values['body'])) {
			$document->setBody($values['body']);
		}
		if (isset($values['recipients'])) {
			$document->setRecipients($values['recipients']);
		}
	}
}
