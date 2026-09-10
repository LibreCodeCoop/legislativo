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
use OCA\Legislativo\Service\MatterService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class MatterController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private MatterService $matterService,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	public function index(): DataResponse {
		return $this->respond(fn (): array => [
			'data' => $this->matterService->search($this->request->getParams()),
		]);
	}

	#[NoAdminRequired]
	public function show(int $id): DataResponse {
		return $this->respond(fn (): array => ['data' => $this->matterService->get($id)]);
	}

	#[NoAdminRequired]
	public function create(): DataResponse {
		return $this->respond(
			fn (): array => ['data' => $this->matterService->create($this->request->getParams())->jsonSerialize()],
			Http::STATUS_CREATED,
		);
	}

	#[NoAdminRequired]
	public function update(int $id): DataResponse {
		return $this->respond(
			fn (): array => ['data' => $this->matterService->update($id, $this->request->getParams())->jsonSerialize()],
		);
	}

	#[NoAdminRequired]
	public function protocol(int $id): DataResponse {
		return $this->respond(
			fn (): array => ['data' => $this->matterService->protocol($id, $this->request->getParams())->jsonSerialize()],
			Http::STATUS_CREATED,
		);
	}

	#[NoAdminRequired]
	public function proceed(int $id): DataResponse {
		return $this->respond(
			fn (): array => ['data' => $this->matterService->proceed($id, $this->request->getParams())->jsonSerialize()],
			Http::STATUS_CREATED,
		);
	}

	#[NoAdminRequired]
	public function proceedBatch(int $id): DataResponse {
		return $this->respond(fn (): array => ['data' => array_map(static fn ($item): array => $item->jsonSerialize(), $this->matterService->proceedBatch($id, $this->request->getParams()))], Http::STATUS_CREATED);
	}

	private function respond(callable $operation, int $successStatus = Http::STATUS_OK): DataResponse {
		try {
			return new DataResponse($operation(), $successStatus);
		} catch (ValidationException $exception) {
			return new DataResponse([
				'error' => 'validation_error',
				'message' => $exception->getMessage(),
				'fields' => $exception->getErrors(),
			], Http::STATUS_UNPROCESSABLE_ENTITY);
		} catch (AuthorizationException $exception) {
			return new DataResponse([
				'error' => 'forbidden',
				'message' => $exception->getMessage(),
			], Http::STATUS_FORBIDDEN);
		} catch (DoesNotExistException) {
			return new DataResponse([
				'error' => 'not_found',
				'message' => 'Matéria não encontrada.',
			], Http::STATUS_NOT_FOUND);
		} catch (UniqueConstraintViolationException) {
			return new DataResponse([
				'error' => 'duplicate',
				'message' => 'Já existe um registro com essa numeração.',
			], Http::STATUS_CONFLICT);
		}
	}
}
