<?php

declare(strict_types=1);

namespace OCA\Legislativo\Controller;

use OCA\Legislativo\Exception\AuthorizationException;use OCA\Legislativo\Exception\ValidationException;use OCA\Legislativo\Service\ParliamentarianService;use OCP\AppFramework\Controller;use OCP\AppFramework\Http;use OCP\AppFramework\Http\Attribute\NoAdminRequired;use OCP\AppFramework\Http\DataResponse;use OCP\IRequest;
class ParliamentarianController extends Controller{
	public function __construct(string $appName,IRequest $request,private ParliamentarianService $service){parent::__construct($appName,$request);}
	#[NoAdminRequired]public function index():DataResponse{return $this->respond(fn()=>['data'=>$this->service->list(filter_var($this->request->getParam('active',false),FILTER_VALIDATE_BOOL))]);}
	#[NoAdminRequired]public function save():DataResponse{return $this->respond(fn()=>['data'=>$this->service->save($this->request->getParams())->jsonSerialize()]);}
	private function respond(callable $fn):DataResponse{try{return new DataResponse($fn());}catch(ValidationException $e){return new DataResponse(['error'=>'validation_error','message'=>$e->getMessage()],Http::STATUS_UNPROCESSABLE_ENTITY);}catch(AuthorizationException $e){return new DataResponse(['error'=>'forbidden','message'=>$e->getMessage()],Http::STATUS_FORBIDDEN);}}
}
