<?php
declare(strict_types=1);
namespace OCA\Legislativo\Controller;
use OCA\Legislativo\Exception\ValidationException;
use OCA\Legislativo\Service\NormService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
class PublicNormController extends Controller {
	public function __construct(string $appName,IRequest $request,private NormService $norms){parent::__construct($appName,$request);}
	#[PublicPage]#[NoCSRFRequired] public function index():DataResponse{return new DataResponse(['data'=>$this->norms->search($this->request->getParams(),true)]);}
	#[PublicPage]#[NoCSRFRequired] public function show(int $id):DataResponse{try{return new DataResponse(['data'=>$this->norms->get($id,true)]);}catch(DoesNotExistException|ValidationException){return new DataResponse(['error'=>'not_found','message'=>'Norma publicada não encontrada.'],Http::STATUS_NOT_FOUND);}}
}
