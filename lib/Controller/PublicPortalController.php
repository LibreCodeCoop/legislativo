<?php

declare(strict_types=1);

namespace OCA\Legislativo\Controller;

use OCA\Legislativo\Service\PublicPortalService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

class PublicPortalController extends Controller {
	public function __construct(string $appName, IRequest $request, private PublicPortalService $portal) { parent::__construct($appName, $request); }

	#[PublicPage]
	#[NoCSRFRequired]
	public function page(): TemplateResponse { return $this->portalPage(); }

	#[PublicPage]
	#[NoCSRFRequired]
	public function parliamentarianPage(int $id): TemplateResponse { return $this->portalPage($id); }

	#[PublicPage]
	#[NoCSRFRequired]
	public function matters(): DataResponse { return new DataResponse(['data' => $this->portal->listMatters($this->request->getParams())]); }

	#[PublicPage]
	#[NoCSRFRequired]
	public function matter(int $id): DataResponse { try { return new DataResponse(['data' => $this->portal->matter($id)]); } catch (DoesNotExistException) { return new DataResponse(['error' => 'not_found', 'message' => 'Matéria não encontrada.'], Http::STATUS_NOT_FOUND); } }

	#[PublicPage]
	#[NoCSRFRequired]
	public function sessions(): DataResponse { return new DataResponse(['data' => $this->portal->sessions($this->request->getParams())]); }

	#[PublicPage]
	#[NoCSRFRequired]
	public function search(): DataResponse { return new DataResponse(['data' => $this->portal->globalSearch((string)$this->request->getParam('query', ''), (int)$this->request->getParam('limit', 20))]); }

	#[PublicPage]
	#[NoCSRFRequired]
	public function parliamentarians(): DataResponse { return new DataResponse(['data' => $this->portal->parliamentarians((string)$this->request->getParam('query', ''))]); }

	#[PublicPage]
	#[NoCSRFRequired]
	public function parliamentarian(int $id): DataResponse { try { return new DataResponse(['data' => $this->portal->parliamentarian($id)]); } catch (DoesNotExistException) { return new DataResponse(['error' => 'not_found', 'message' => 'Parlamentar não encontrado.'], Http::STATUS_NOT_FOUND); } }

	private function portalPage(?int $parliamentarianId = null): TemplateResponse { Util::addStyle('legislativo', 'portal'); Util::addScript('legislativo', 'portal'); return new TemplateResponse('legislativo', 'portal', ['parliamentarianId' => $parliamentarianId], 'guest'); }
}
