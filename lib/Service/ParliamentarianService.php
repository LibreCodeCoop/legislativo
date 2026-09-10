<?php

declare(strict_types=1);

namespace OCA\Legislativo\Service;

use OCA\Legislativo\Db\Parliamentarian;
use OCA\Legislativo\Db\ParliamentarianMapper;
use OCA\Legislativo\Exception\ValidationException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUserManager;

class ParliamentarianService {
	public function __construct(private ParliamentarianMapper $mapper,private AuthorizationService $authorization,private AuditService $audit,private IUserManager $users){}
	/** @return list<array<string,mixed>> */ public function list(bool $onlyActive=false):array{$this->authorization->getUserId();return array_map(static fn(Parliamentarian $p):array=>$p->jsonSerialize(),$this->mapper->findAllProfiles($onlyActive));}
	/** @param array<string,mixed> $data */ public function save(array $data):Parliamentarian{
		$operator=$this->authorization->requireWrite();$uid=trim((string)($data['userUid']??''));$user=$this->users->get($uid);if($user===null)throw new ValidationException('Usuário Nextcloud não encontrado.');
		try{$profile=$this->mapper->findByUser($uid);$before=$profile->jsonSerialize();}catch(DoesNotExistException){$profile=new Parliamentarian();$profile->setUserUid($uid);$profile->setCreatedAt($this->now());$before=null;}
		$start=$this->date((string)($data['termStart']??''),'Início do mandato');$end=$this->date((string)($data['termEnd']??''),'Fim do mandato');if($end<$start)throw new ValidationException('O fim do mandato deve ser posterior ao início.');
		$party=trim((string)($data['party']??''));$role=trim((string)($data['role']??'vereador'));$seat=(int)($data['seatNumber']??0);
		$profile->setDisplayName($user->getDisplayName());$profile->setParty($party===''?null:substr($party,0,32));$profile->setRole($role===''?'vereador':substr($role,0,64));$profile->setSeatNumber($seat>0?$seat:null);$profile->setTermStart($start);$profile->setTermEnd($end);$profile->setActive(filter_var($data['active']??true,FILTER_VALIDATE_BOOL));$profile->setUpdatedAt($this->now());
		$saved=$profile->getId()?$this->mapper->update($profile):$this->mapper->insert($profile);$this->audit->record($operator,'parliamentarian',$saved->getId(),'parliamentarian.save',$before,$saved->jsonSerialize());return $saved;
	}
	private function date(string $value,string $label):\DateTime{try{$date=new \DateTime($value,new \DateTimeZone('UTC'));if($value===''||$date->format('Y-m-d')!==$value)throw new \Exception();return $date;}catch(\Throwable){throw new ValidationException($label.' inválido.');}}
	private function now():\DateTime{return new \DateTime('now',new \DateTimeZone('UTC'));}
}
