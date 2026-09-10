<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Controller;

use OCA\Legislativo\Exception\AuthorizationException;
use OCA\Legislativo\Exception\ValidationException;
use OCA\Legislativo\Service\AuditQueryService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class AuditController extends Controller {
	public function __construct(string $appName, IRequest $request, private AuditQueryService $auditQuery) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	public function index(): DataResponse {
		try {
			return new DataResponse(['data' => $this->auditQuery->search($this->request->getParams())]);
		} catch (ValidationException $exception) {
			return new DataResponse(['error' => 'validation_error', 'message' => $exception->getMessage(), 'fields' => $exception->getErrors()], Http::STATUS_UNPROCESSABLE_ENTITY);
		} catch (AuthorizationException $exception) {
			return new DataResponse(['error' => 'forbidden', 'message' => $exception->getMessage()], Http::STATUS_FORBIDDEN);
		}
	}
}
