<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCA\Legislativo\Exception\AuthorizationException;
use OCA\Legislativo\Exception\ValidationException;
use OCA\Legislativo\Service\OfficialDocumentService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class OfficialDocumentController extends Controller {
	public function __construct(string $appName, IRequest $request, private OfficialDocumentService $documents) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	public function index(): DataResponse {
		return $this->respond(fn (): array => ['data' => $this->documents->search($this->request->getParams())]);
	}

	#[NoAdminRequired]
	public function show(int $id): DataResponse {
		return $this->respond(fn (): array => ['data' => $this->documents->get($id)]);
	}

	#[NoAdminRequired]
	public function create(): DataResponse {
		return $this->respond(fn (): array => ['data' => $this->documents->create($this->request->getParams())->jsonSerialize()], Http::STATUS_CREATED);
	}

	#[NoAdminRequired]
	public function update(int $id): DataResponse {
		return $this->respond(fn (): array => ['data' => $this->documents->update($id, $this->request->getParams())->jsonSerialize()]);
	}

	#[NoAdminRequired]
	public function issue(int $id): DataResponse {
		return $this->respond(fn (): array => ['data' => $this->documents->issue($id)->jsonSerialize()]);
	}

	private function respond(callable $operation, int $status = Http::STATUS_OK): DataResponse {
		try {
			return new DataResponse($operation(), $status);
		} catch (ValidationException $exception) {
			return new DataResponse(['error' => 'validation_error', 'message' => $exception->getMessage(), 'fields' => $exception->getErrors()], Http::STATUS_UNPROCESSABLE_ENTITY);
		} catch (AuthorizationException $exception) {
			return new DataResponse(['error' => 'forbidden', 'message' => $exception->getMessage()], Http::STATUS_FORBIDDEN);
		} catch (DoesNotExistException) {
			return new DataResponse(['error' => 'not_found', 'message' => 'Documento não encontrado.'], Http::STATUS_NOT_FOUND);
		} catch (UniqueConstraintViolationException) {
			return new DataResponse(['error' => 'duplicate', 'message' => 'Conflito na numeração. Tente emitir novamente.'], Http::STATUS_CONFLICT);
		}
	}
}
