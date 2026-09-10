<?php

declare(strict_types=1);

namespace OCA\Legislativo\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCA\Legislativo\Exception\AuthorizationException;
use OCA\Legislativo\Exception\ValidationException;
use OCA\Legislativo\Service\AttachmentService;
use OCA\Legislativo\Service\CalendarService;
use OCA\Legislativo\Service\DeadlineService;
use OCA\Legislativo\Service\TriageService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class WorkflowController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private AttachmentService $attachments,
		private TriageService $triage,
		private CalendarService $calendarService,
		private DeadlineService $deadlineService,
	) { parent::__construct($appName, $request); }

	#[NoAdminRequired]
	public function attach(int $id): DataResponse {
		return $this->respond(fn () => ['data' => $this->attachments->add($id, (string)$this->request->getParam('filePath', ''))->jsonSerialize()], Http::STATUS_CREATED);
	}

	#[NoAdminRequired]
	public function submissions(): DataResponse {
		return $this->respond(fn () => ['data' => $this->triage->list((string)$this->request->getParam('status', 'pending'))]);
	}

	#[NoAdminRequired]
	public function submit(): DataResponse {
		return $this->respond(fn () => ['data' => $this->triage->submit($this->request->getParams())->jsonSerialize()], Http::STATUS_CREATED);
	}

	#[NoAdminRequired]
	public function decide(int $id): DataResponse {
		return $this->respond(fn () => ['data' => $this->triage->decide($id, $this->request->getParams())->jsonSerialize()]);
	}

	#[NoAdminRequired]
	public function calendar(): DataResponse {
		return $this->respond(fn () => ['data' => $this->calendarService->list()]);
	}

	#[NoAdminRequired]
	public function createCalendar(): DataResponse {
		return $this->respond(fn () => ['data' => $this->calendarService->create($this->request->getParams())->jsonSerialize()], Http::STATUS_CREATED);
	}

	#[NoAdminRequired]
	public function toggleCalendar(int $id): DataResponse {
		$active = filter_var($this->request->getParam('active', true), FILTER_VALIDATE_BOOL);
		return $this->respond(fn () => ['data' => $this->calendarService->toggle($id, $active)->jsonSerialize()]);
	}

	#[NoAdminRequired]
	public function deadlines(): DataResponse {
		return $this->respond(fn () => ['data' => $this->deadlineService->search($this->request->getParams())]);
	}

	#[NoAdminRequired]
	public function completeProceeding(int $id): DataResponse {
		return $this->respond(fn () => ['data' => $this->deadlineService->complete($id, $this->request->getParams())->jsonSerialize()]);
	}

	private function respond(callable $operation, int $status = Http::STATUS_OK): DataResponse {
		try { return new DataResponse($operation(), $status); }
		catch (ValidationException $e) { return new DataResponse(['error' => 'validation_error', 'message' => $e->getMessage(), 'fields' => $e->getErrors()], Http::STATUS_UNPROCESSABLE_ENTITY); }
		catch (AuthorizationException $e) { return new DataResponse(['error' => 'forbidden', 'message' => $e->getMessage()], Http::STATUS_FORBIDDEN); }
		catch (DoesNotExistException) { return new DataResponse(['error' => 'not_found', 'message' => 'Registro não encontrado.'], Http::STATUS_NOT_FOUND); }
		catch (UniqueConstraintViolationException) { return new DataResponse(['error' => 'duplicate', 'message' => 'Este vínculo já existe.'], Http::STATUS_CONFLICT); }
	}
}
