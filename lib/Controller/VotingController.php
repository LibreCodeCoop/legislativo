<?php

declare(strict_types=1);

namespace OCA\Legislativo\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCA\Legislativo\Exception\AuthorizationException;
use OCA\Legislativo\Exception\ValidationException;
use OCA\Legislativo\Service\VotingService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class VotingController extends Controller {
	public function __construct(string $appName,IRequest $request,private VotingService $voting){parent::__construct($appName,$request);}
	#[NoAdminRequired] public function index(): DataResponse { return $this->respond(fn()=>['data'=>$this->voting->list()]); }
	#[NoAdminRequired] public function show(int $id): DataResponse { return $this->respond(fn()=>['data'=>$this->voting->get($id)]); }
	#[NoAdminRequired] public function create(): DataResponse { return $this->respond(fn()=>['data'=>$this->voting->create($this->request->getParams())->jsonSerialize()],Http::STATUS_CREATED); }
	#[NoAdminRequired] public function attendance(int $id): DataResponse { return $this->respond(fn()=>['data'=>$this->voting->setAttendance($id,$this->request->getParams())->jsonSerialize()]); }
	#[NoAdminRequired] public function addAgenda(int $id): DataResponse { return $this->respond(fn()=>['data'=>$this->voting->addAgenda($id,$this->request->getParams())->jsonSerialize()],Http::STATUS_CREATED); }
	#[NoAdminRequired] public function transition(int $id): DataResponse { return $this->respond(fn()=>['data'=>$this->voting->transition($id,(string)$this->request->getParam('action',''))->jsonSerialize()]); }
	#[NoAdminRequired] public function transitionBallot(int $id,int $itemId): DataResponse { return $this->respond(fn()=>['data'=>$this->voting->transitionBallot($id,$itemId,(string)$this->request->getParam('action',''))->jsonSerialize()]); }
	#[NoAdminRequired] public function cast(int $id,int $itemId): DataResponse { return $this->respond(fn()=>['data'=>$this->voting->cast($id,$itemId,(string)$this->request->getParam('choice',''))->jsonSerialize()],Http::STATUS_CREATED); }
	#[NoAdminRequired] public function requestSpeech(int $id):DataResponse{return $this->respond(fn()=>['data'=>$this->voting->requestSpeech($id,(string)$this->request->getParam('topic',''),(int)$this->request->getParam('seconds',300))->jsonSerialize()],Http::STATUS_CREATED);}
	#[NoAdminRequired] public function transitionSpeaker(int $id,int $speakerId):DataResponse{return $this->respond(fn()=>['data'=>$this->voting->transitionSpeaker($id,$speakerId,(string)$this->request->getParam('action',''))->jsonSerialize()]);}
	#[NoAdminRequired] public function timer(int $id):DataResponse{return $this->respond(fn()=>['data'=>$this->voting->timer($id,(string)$this->request->getParam('action',''),$this->request->getParam('duration')===null?null:(int)$this->request->getParam('duration'),$this->request->getParam('label')===null?null:(string)$this->request->getParam('label'))->jsonSerialize()]);}
	private function respond(callable $fn,int $status=Http::STATUS_OK): DataResponse { try{return new DataResponse($fn(),$status);} catch(ValidationException $e){return new DataResponse(['error'=>'validation_error','message'=>$e->getMessage()],Http::STATUS_UNPROCESSABLE_ENTITY);} catch(AuthorizationException $e){return new DataResponse(['error'=>'forbidden','message'=>$e->getMessage()],Http::STATUS_FORBIDDEN);} catch(DoesNotExistException){return new DataResponse(['error'=>'not_found','message'=>'Registro não encontrado.'],Http::STATUS_NOT_FOUND);} catch(UniqueConstraintViolationException|\OC\DB\Exceptions\DbalException){return new DataResponse(['error'=>'duplicate','message'=>'Registro duplicado ou voto já computado.'],Http::STATUS_CONFLICT);} }
}
