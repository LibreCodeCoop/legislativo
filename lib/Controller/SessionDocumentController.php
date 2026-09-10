<?php

declare(strict_types=1);

namespace OCA\Legislativo\Controller;

use OCA\Legislativo\Exception\AuthorizationException;use OCA\Legislativo\Exception\ValidationException;use OCA\Legislativo\Service\SessionDocumentService;use OCP\AppFramework\Controller;use OCP\AppFramework\Http;use OCP\AppFramework\Http\Attribute\NoAdminRequired;use OCP\AppFramework\Http\DataResponse;use OCP\IRequest;
class SessionDocumentController extends Controller{
	public function __construct(string $appName,IRequest $request,private SessionDocumentService $documents,private \OCA\Legislativo\Service\SessionMinutesService $minutes){parent::__construct($appName,$request);}
	#[NoAdminRequired]public function generate(int $id):DataResponse{return $this->respond(fn()=>['data'=>$this->documents->generate($id,(string)$this->request->getParam('type',''))],Http::STATUS_CREATED);}
	#[NoAdminRequired]public function saveMinutes(int $id):DataResponse{return $this->respond(fn()=>['data'=>$this->minutes->save($id,(string)$this->request->getParam('html',''))->jsonSerialize()]);}
	#[NoAdminRequired]public function requestMinutesSignature(int $id):DataResponse{return $this->respond(fn()=>['data'=>$this->minutes->requestSignature($id,$this->request->getParam('signers',[]))->jsonSerialize()]);}
	private function respond(callable $operation,int $status=Http::STATUS_OK):DataResponse{try{return new DataResponse($operation(),$status);}catch(ValidationException $e){return new DataResponse(['error'=>'validation_error','message'=>$e->getMessage()],Http::STATUS_UNPROCESSABLE_ENTITY);}catch(AuthorizationException $e){return new DataResponse(['error'=>'forbidden','message'=>$e->getMessage()],Http::STATUS_FORBIDDEN);}catch(\OCP\AppFramework\Db\DoesNotExistException){return new DataResponse(['error'=>'not_found','message'=>'Sessão não encontrada.'],Http::STATUS_NOT_FOUND);}}
}
