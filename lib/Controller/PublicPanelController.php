<?php

declare(strict_types=1);

namespace OCA\Legislativo\Controller;

use OCA\Legislativo\Service\VotingService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

class PublicPanelController extends Controller{
	public function __construct(string $appName,IRequest $request,private VotingService $voting){parent::__construct($appName,$request);}
	#[PublicPage]#[NoCSRFRequired]public function page(int $id):TemplateResponse{Util::addStyle('legislativo','panel');Util::addScript('legislativo','panel');return new TemplateResponse('legislativo','panel',['sessionId'=>$id],'guest');}
	#[PublicPage]#[NoCSRFRequired]public function data(int $id):DataResponse{try{$data=$this->voting->publicGet($id);$state=$data;unset($state['session']['serverTime']);$eventId=hash('sha256',json_encode($state,JSON_THROW_ON_ERROR));if(hash_equals($eventId,(string)$this->request->getParam('since',''))){$response=new DataResponse(null,Http::STATUS_NO_CONTENT);$response->addHeader('Cache-Control','no-store');return$response;}$data['eventId']=$eventId;$response=new DataResponse(['data'=>$data]);$response->addHeader('Cache-Control','no-store');return$response;}catch(DoesNotExistException){return new DataResponse(['error'=>'not_found','message'=>'Sessão não encontrada.'],Http::STATUS_NOT_FOUND);}}
}
