<?php

declare(strict_types=1);

namespace OCA\Legislativo\Controller;

use OCA\Legislativo\Exception\AuthorizationException;
use OCA\Legislativo\Exception\ValidationException;
use OCA\Legislativo\Service\DocumentFlowService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class DocumentController extends Controller {
	public function __construct(string $appName, IRequest $request, private DocumentFlowService $documents) { parent::__construct($appName, $request); }

	#[NoAdminRequired]
	public function prepare(int $id): DataResponse {
		return $this->respond(fn () => ['data' => $this->documents->prepare($id, (int)$this->request->getParam('protocolId', 0), (string)$this->request->getParam('filePath', ''))->jsonSerialize()], Http::STATUS_CREATED);
	}

	#[NoAdminRequired]
	public function requestSignature(int $id, int $flowId): DataResponse {
		return $this->respond(fn () => ['data' => $this->documents->requestSignature($id, $flowId, $this->request->getParam('signers', []))->jsonSerialize()]);
	}

	private function respond(callable $operation, int $status = Http::STATUS_OK): DataResponse {
		try { return new DataResponse($operation(), $status); }
		catch (ValidationException $e) { return new DataResponse(['error' => 'validation_error', 'message' => $e->getMessage(), 'fields' => $e->getErrors()], Http::STATUS_UNPROCESSABLE_ENTITY); }
		catch (AuthorizationException $e) { return new DataResponse(['error' => 'forbidden', 'message' => $e->getMessage()], Http::STATUS_FORBIDDEN); }
		catch (DoesNotExistException) { return new DataResponse(['error' => 'not_found', 'message' => 'Registro não encontrado.'], Http::STATUS_NOT_FOUND); }
		catch (\Throwable $e) { return new DataResponse(['error' => 'document_error', 'message' => 'Falha no fluxo documental: ' . $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR); }
	}
}
