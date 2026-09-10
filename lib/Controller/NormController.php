<?php
declare(strict_types=1);
namespace OCA\Legislativo\Controller;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCA\Legislativo\Exception\AuthorizationException;
use OCA\Legislativo\Exception\ValidationException;
use OCA\Legislativo\Service\NormService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
class NormController extends Controller {
	public function __construct(string $appName,IRequest $request,private NormService $norms){parent::__construct($appName,$request);}
	#[NoAdminRequired] public function index():DataResponse{return$this->respond(fn()=>['data'=>$this->norms->search($this->request->getParams())]);}
	#[NoAdminRequired] public function show(int $id):DataResponse{return$this->respond(fn()=>['data'=>$this->norms->get($id)]);}
	#[NoAdminRequired] public function create():DataResponse{return$this->respond(fn()=>['data'=>$this->norms->save($this->request->getParams())->jsonSerialize()],Http::STATUS_CREATED);}
	#[NoAdminRequired] public function update(int $id):DataResponse{return$this->respond(fn()=>['data'=>$this->norms->save($this->request->getParams(),$id)->jsonSerialize()]);}
	#[NoAdminRequired] public function version(int $id):DataResponse{return$this->respond(fn()=>['data'=>$this->norms->saveVersion($id,$this->request->getParams())->jsonSerialize()],Http::STATUS_CREATED);}
	#[NoAdminRequired] public function updateVersion(int $versionId):DataResponse{return$this->respond(fn()=>['data'=>$this->norms->updateVersion($versionId,$this->request->getParams())->jsonSerialize()]);}
	#[NoAdminRequired] public function relation(int $id):DataResponse{return$this->respond(fn()=>['data'=>$this->norms->relate($id,$this->request->getParams())],Http::STATUS_CREATED);}
	#[NoAdminRequired] public function import():DataResponse{return$this->respond(fn()=>['data'=>$this->norms->importCsv((string)$this->request->getParam('csv',''),filter_var($this->request->getParam('dryRun',false),FILTER_VALIDATE_BOOL))],Http::STATUS_CREATED);}
	private function respond(callable $operation,int $status=Http::STATUS_OK):DataResponse{try{return new DataResponse($operation(),$status);}catch(ValidationException $e){return new DataResponse(['error'=>'validation_error','message'=>$e->getMessage(),'fields'=>$e->getErrors()],Http::STATUS_UNPROCESSABLE_ENTITY);}catch(AuthorizationException $e){return new DataResponse(['error'=>'forbidden','message'=>$e->getMessage()],Http::STATUS_FORBIDDEN);}catch(DoesNotExistException){return new DataResponse(['error'=>'not_found','message'=>'Norma não encontrada.'],Http::STATUS_NOT_FOUND);}catch(UniqueConstraintViolationException){return new DataResponse(['error'=>'duplicate','message'=>'Norma, versão ou relação já cadastrada.'],Http::STATUS_CONFLICT);}}
}
