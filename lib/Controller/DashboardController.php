<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Controller;

use OCA\Legislativo\AppInfo\Application;
use OCA\Legislativo\Service\AuthorizationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

class DashboardController extends Controller {
	private const MODULES = [
		[
			'id' => 'processo',
			'name' => 'Processo legislativo',
			'description' => 'Protocolo, matérias, tramitações, prazos, pautas, atas e arquivo.',
			'status' => 'functional',
		],
		[
			'id' => 'votacao',
			'name' => 'Votação eletrônica',
			'description' => 'Sessões, presença, quórum, discussão e votos em tempo real.',
			'status' => 'functional',
		],
		[
			'id' => 'legislacao',
			'name' => 'Legislação consolidada',
			'description' => 'Normas relacionadas, versões históricas, índice e pesquisa textual.',
			'status' => 'functional',
		],
		[
			'id' => 'portal',
			'name' => 'Portal institucional',
			'description' => 'Transparência ativa e publicação dos dados legislativos.',
			'status' => 'functional',
		],
	];

	public function __construct(
		string $appName,
		IRequest $request,
		private AuthorizationService $authorization,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function index(): TemplateResponse {
		Util::addStyle(Application::APP_ID, 'dashboard');
		Util::addScript(Application::APP_ID, 'dashboard');

		return new TemplateResponse(Application::APP_ID, 'dashboard', [
			'modules' => self::MODULES,
			'version' => '0.16.0',
			'canWrite' => $this->authorization->canWrite(),
			'canPreside' => $this->authorization->canPreside(),
		]);
	}

	#[NoAdminRequired]
	public function health(): DataResponse {
		return new DataResponse([
			'ok' => true,
			'app' => Application::APP_ID,
			'version' => '0.16.0',
			'modules' => self::MODULES,
		]);
	}
}
