<?php

declare(strict_types=1);

namespace OCA\Legislativo\Service;

use OCA\Legislativo\Db\AgendaItem;
use OCA\Legislativo\Db\AgendaItemMapper;
use OCA\Legislativo\Db\Attendance;
use OCA\Legislativo\Db\AttendanceMapper;
use OCA\Legislativo\Db\LegislativeSession;
use OCA\Legislativo\Db\LegislativeSessionMapper;
use OCA\Legislativo\Db\Matter;
use OCA\Legislativo\Db\MatterMapper;
use OCA\Legislativo\Db\Vote;
use OCA\Legislativo\Db\VoteMapper;
use OCA\Legislativo\Db\SpeakerEntry;
use OCA\Legislativo\Db\SpeakerEntryMapper;
use OCA\Legislativo\Exception\ValidationException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IConfig;
use OCP\IUserManager;

class VotingService {
	private const SESSION_STATUSES = ['scheduled','open','closed'];
	private const VOTE_TYPES = ['nominal','secret','symbolic'];
	private const QUORUM_TYPES = ['simple','absolute','two_thirds'];
	private const CHOICES = ['yes','no','abstain','obstruction'];

	public function __construct(
		private LegislativeSessionMapper $sessions,
		private AgendaItemMapper $agenda,
		private AttendanceMapper $attendance,
		private VoteMapper $votes,
		private MatterMapper $matters,
		private AuthorizationService $authorization,
		private AuditService $audit,
		private IUserManager $userManager,
		private IConfig $config,
		private SpeakerEntryMapper $speakers,
	) {
	}

	/** @return list<array<string,mixed>> */
	public function list(): array {
		$this->authorization->getUserId();
		return array_map(fn (LegislativeSession $session): array => $this->summary($session), $this->sessions->findAllSessions());
	}

	/** @return array<string,mixed> */
	public function get(int $id): array {
		$uid = $this->authorization->getUserId(); $session = $this->sessions->find($id);
		$attendance = $this->attendance->findBySession($id);
		return [
			'session' => $this->summary($session),
			'attendance' => array_map(static fn (Attendance $entry): array => $entry->jsonSerialize(), $attendance),
			'agenda' => array_map(fn (AgendaItem $item): array => $this->agendaData($item, $session), $this->agenda->findBySession($id)),
			'speakers' => array_map(static fn(SpeakerEntry $entry):array=>$entry->jsonSerialize(),$this->speakers->findBySession($id)),
			'me' => ['uid' => $uid, 'present' => $this->isPresent($id, $uid)],
		];
	}

	/** @return array<string,mixed> */
	public function publicGet(int $id):array{
		$session=$this->sessions->find($id);
		$sessionData=array_intersect_key($this->summary($session),array_flip(['id','type','number','year','scheduledAt','status','totalSeats','openedAt','closedAt','timerLabel','timerStatus','timerStartedAt','timerDuration','serverTime','presentCount','presenceRequired','agendaCount']));
		$agenda=array_map(function(AgendaItem $item)use($session):array{$full=$this->agendaData($item,$session);$data=array_intersect_key($full,array_flip(['id','position','status','voteType','quorumType','openedAt','closedAt','result','tally']));$data['matter']=array_intersect_key($full['matter'],array_flip(['id','type','number','year','subject','quorum','procedure']));$data['votes']=array_map(static fn(array $vote):array=>array_intersect_key($vote,array_flip(['voterUid','choice','castAt'])),$full['votes']);return$data;},$this->agenda->findBySession($id));
		$speakers=array_map(static fn(SpeakerEntry $entry):array=>array_intersect_key($entry->jsonSerialize(),array_flip(['id','displayName','topic','position','status','allottedSeconds','startedAt','endedAt'])),$this->speakers->findBySession($id));
		return['session'=>$sessionData,'attendance'=>array_map(static fn(Attendance $a):array=>['displayName'=>$a->getDisplayName(),'present'=>$a->getPresent()],$this->attendance->findBySession($id)),'agenda'=>$agenda,'speakers'=>$speakers];
	}

	public function requestSpeech(int $sessionId,string $topic,int $seconds=300):SpeakerEntry{
		$uid=$this->authorization->getUserId();$session=$this->sessions->find($sessionId);if($session->getStatus()!=='open')throw new ValidationException('A inscrição só é permitida durante sessão aberta.');if(!$this->isPresent($sessionId,$uid))throw new ValidationException('Somente parlamentares presentes podem se inscrever.');
		foreach($this->speakers->findBySession($sessionId)as $entry)if($entry->getUserUid()===$uid&&in_array($entry->getStatus(),['waiting','speaking'],true))throw new ValidationException('Você já está inscrito para falar.');
		$user=$this->userManager->get($uid);$entry=new SpeakerEntry();$entry->setSessionId($sessionId);$entry->setUserUid($uid);$entry->setDisplayName($user?->getDisplayName()??$uid);$entry->setTopic(($topic=trim($topic))===''?null:substr($topic,0,512));$entry->setPosition($this->speakers->nextPosition($sessionId));$entry->setStatus('waiting');$entry->setAllottedSeconds(max(30,min($seconds,3600)));$entry->setRequestedAt($this->now());$saved=$this->speakers->insert($entry);$this->audit->record($uid,'session',$sessionId,'speaker.request',null,$saved->jsonSerialize());return $saved;
	}

	public function transitionSpeaker(int $sessionId,int $speakerId,string $action):SpeakerEntry{
		$uid=$this->authorization->requirePresident();$session=$this->sessions->find($sessionId);$entry=$this->speakers->find($speakerId);if($entry->getSessionId()!==$sessionId||$session->getStatus()!=='open')throw new ValidationException('Inscrição ou sessão inválida.');$before=$entry->jsonSerialize();
		if($action==='start'){if($entry->getStatus()!=='waiting')throw new ValidationException('A inscrição não está aguardando chamada.');foreach($this->speakers->findBySession($sessionId)as $other)if($other->getStatus()==='speaking')throw new ValidationException('Já existe um orador com a palavra.');$entry->setStatus('speaking');$entry->setStartedAt($this->now());$this->applyTimer($session,'start',$entry->getAllottedSeconds(),'Orador: '.$entry->getDisplayName());}
		elseif($action==='finish'){if($entry->getStatus()!=='speaking')throw new ValidationException('Este orador não está com a palavra.');$entry->setStatus('done');$entry->setEndedAt($this->now());$this->applyTimer($session,'stop',null,null);}else throw new ValidationException('Ação de orador inválida.');
		$saved=$this->speakers->update($entry);$this->audit->record($uid,'session',$sessionId,'speaker.'.$action,$before,$saved->jsonSerialize());return $saved;
	}

	public function timer(int $sessionId,string $action,?int $duration,?string $label):LegislativeSession{
		$uid=$this->authorization->requirePresident();$session=$this->sessions->find($sessionId);if($session->getStatus()!=='open')throw new ValidationException('O cronômetro requer uma sessão aberta.');$before=$session->jsonSerialize();$this->applyTimer($session,$action,$duration,$label);$this->audit->record($uid,'session',$sessionId,'timer.'.$action,$before,$session->jsonSerialize());return $session;
	}

	private function applyTimer(LegislativeSession $session,string $action,?int $duration,?string $label):void{
		if($action==='start'){if($duration===null||$duration<1||$duration>7200)throw new ValidationException('Informe duração entre 1 e 7200 segundos.');$session->setTimerStatus('running');$session->setTimerDuration($duration);$session->setTimerLabel(substr(trim((string)$label),0,255));$session->setTimerStartedAt($this->now());}
		elseif($action==='stop'){if($session->getTimerStatus()==='running'&&$session->getTimerStartedAt()!==null&&$session->getTimerDuration()!==null){$elapsed=max(0,time()-$session->getTimerStartedAt()->getTimestamp());$session->setTimerDuration(max(0,$session->getTimerDuration()-$elapsed));$session->setTimerStartedAt($this->now());}$session->setTimerStatus('stopped');}
		elseif($action==='reset'){$session->setTimerStatus('idle');$session->setTimerDuration(null);$session->setTimerLabel(null);$session->setTimerStartedAt(null);}else throw new ValidationException('Ação de cronômetro inválida.');$session->setUpdatedAt($this->now());$this->sessions->update($session);
	}

	/** @param array<string,mixed> $data */
	public function create(array $data): LegislativeSession {
		$uid = $this->authorization->requireWrite();
		$type = trim((string)($data['type'] ?? '')); if ($type === '' || mb_strlen($type) > 64) throw new ValidationException('Informe um tipo de sessão válido.');
		$number=(int)($data['number']??0); $year=(int)($data['year']??0); $seats=(int)($data['totalSeats']??11);
		if ($number<1 || $year<1900 || $year>2200 || $seats<1 || $seats>999) throw new ValidationException('Numeração, ano ou quantidade de cadeiras inválida.');
		try { $scheduled = new \DateTime((string)($data['scheduledAt']??''), new \DateTimeZone('America/Sao_Paulo')); }
		catch (\Throwable) { throw new ValidationException('Informe uma data e hora válidas.'); }
		$scheduled->setTimezone(new \DateTimeZone('UTC'));
		$now=$this->now(); $session=new LegislativeSession(); $session->setType($type); $session->setNumber($number); $session->setYear($year);
		$session->setScheduledAt($scheduled); $session->setStatus('scheduled'); $session->setTotalSeats($seats); $session->setChairUid($uid);
		$session->setNotes($this->optional($data,'notes',4000)); $session->setCreatedAt($now); $session->setUpdatedAt($now);
		$inserted=$this->sessions->insert($session); $this->audit->record($uid,'session',$inserted->getId(),'session.create',null,$inserted->jsonSerialize()); return $inserted;
	}

	/** @param array<string,mixed> $data */
	public function setAttendance(int $sessionId, array $data): Attendance {
		$operator=$this->authorization->requireWrite(); $session=$this->sessions->find($sessionId);
		if ($session->getStatus()==='closed') throw new ValidationException('A sessão já foi encerrada.');
		$uid=trim((string)($data['userUid']??'')); if ($uid==='') throw new ValidationException('Informe o usuário do parlamentar.');
		$user=$this->userManager->get($uid); if ($user===null) throw new ValidationException('Usuário do Nextcloud não encontrado: '.$uid);
		$present=filter_var($data['present']??true,FILTER_VALIDATE_BOOL); $before=null;
		try { $entry=$this->attendance->findByUser($sessionId,$uid); $before=$entry->jsonSerialize(); }
		catch (DoesNotExistException) { $entry=new Attendance(); $entry->setSessionId($sessionId); $entry->setUserUid($uid); }
		$entry->setDisplayName($user->getDisplayName()); $entry->setPresent($present); $entry->setCheckedAt($this->now());
		$saved=$entry->getId() ? $this->attendance->update($entry) : $this->attendance->insert($entry);
		$this->audit->record($operator,'session',$sessionId,'attendance.update',$before,$saved->jsonSerialize()); return $saved;
	}

	/** @param array<string,mixed> $data */
	public function addAgenda(int $sessionId, array $data): AgendaItem {
		$uid=$this->authorization->requireWrite(); $session=$this->sessions->find($sessionId);
		if ($session->getStatus()!=='scheduled') throw new ValidationException('A pauta só pode ser alterada antes da abertura da sessão.');
		$matterId=(int)($data['matterId']??0); $matter=$this->matters->find($matterId);
		$voteType=(string)($data['voteType']??'nominal'); $quorumType=(string)($data['quorumType']??'simple');
		if (!in_array($voteType,self::VOTE_TYPES,true) || !in_array($quorumType,self::QUORUM_TYPES,true)) throw new ValidationException('Modalidade de voto ou quórum inválido.');
		$item=new AgendaItem(); $item->setSessionId($sessionId); $item->setMatterId($matterId); $item->setPosition($this->agenda->nextPosition($sessionId));
		$item->setStatus('pending'); $item->setVoteType($voteType); $item->setQuorumType($quorumType); $item->setCreatedAt($this->now());
		$inserted=$this->agenda->insert($item); $before=$matter->jsonSerialize(); $matter->setStatus('agenda'); $matter->setUpdatedAt($this->now()); $this->matters->update($matter);
		$this->audit->record($uid,'session',$sessionId,'agenda.add',null,$inserted->jsonSerialize());
		$this->audit->record($uid,'matter',$matterId,'matter.agenda',$before,$matter->jsonSerialize()); return $inserted;
	}

	public function transition(int $sessionId, string $action): LegislativeSession {
		$uid=$this->authorization->requirePresident(); $session=$this->sessions->find($sessionId); $before=$session->jsonSerialize();
		if ($action==='open') {
			if ($session->getStatus()!=='scheduled') throw new ValidationException('Somente uma sessão agendada pode ser aberta.');
			$present=$this->presentCount($sessionId); $required=$this->presenceRequired($session->getTotalSeats());
			if ($present<$required) throw new ValidationException("Quórum insuficiente: $present presentes; são necessários $required.");
			$session->setStatus('open'); $session->setOpenedAt($this->now());
		} elseif ($action==='close') {
			if ($session->getStatus()!=='open') throw new ValidationException('Somente uma sessão aberta pode ser encerrada.');
			foreach ($this->agenda->findBySession($sessionId) as $item) if ($item->getStatus()==='voting') throw new ValidationException('Encerre a votação em andamento antes de fechar a sessão.');
			$session->setStatus('closed'); $session->setClosedAt($this->now());
		} else throw new ValidationException('Ação de sessão inválida.');
		$session->setUpdatedAt($this->now()); $updated=$this->sessions->update($session); $this->audit->record($uid,'session',$sessionId,'session.'.$action,$before,$updated->jsonSerialize()); return $updated;
	}

	public function transitionBallot(int $sessionId, int $itemId, string $action): AgendaItem {
		$uid=$this->authorization->requirePresident(); $session=$this->sessions->find($sessionId); $item=$this->agenda->find($itemId);
		if ($item->getSessionId()!==$sessionId || $session->getStatus()!=='open') throw new ValidationException('Item ou sessão inválida para votação.');
		$before=$item->jsonSerialize();
		if ($action==='open') {
			if ($item->getStatus()!=='pending') throw new ValidationException('Este item não está aguardando votação.');
			foreach ($this->agenda->findBySession($sessionId) as $other) if ($other->getStatus()==='voting') throw new ValidationException('Já existe uma votação aberta nesta sessão.');
			$item->setStatus('voting'); $item->setOpenedAt($this->now());
		} elseif ($action==='close') {
			if ($item->getStatus()!=='voting') throw new ValidationException('Este item não está em votação.');
			$result=$this->calculateResult($session,$item); $item->setStatus('closed'); $item->setResult($result['result']); $item->setClosedAt($this->now());
			$matter=$this->matters->find($item->getMatterId()); $matterBefore=$matter->jsonSerialize();
			$matter->setStatus(match($result['result']){'approved'=>'approved','rejected'=>'rejected',default=>'agenda'}); $matter->setUpdatedAt($this->now()); $this->matters->update($matter);
			$this->audit->record($uid,'matter',$matter->getId(),'vote.result',$matterBefore,['matter'=>$matter->jsonSerialize(),'result'=>$result]);
		} else throw new ValidationException('Ação de votação inválida.');
		$updated=$this->agenda->update($item); $this->audit->record($uid,'session',$sessionId,'ballot.'.$action,$before,$this->agendaData($updated,$session)); return $updated;
	}

	public function cast(int $sessionId, int $itemId, string $choice): Vote {
		$uid=$this->authorization->getUserId(); $session=$this->sessions->find($sessionId); $item=$this->agenda->find($itemId);
		if ($session->getStatus()!=='open' || $item->getSessionId()!==$sessionId || $item->getStatus()!=='voting') throw new ValidationException('Não há votação aberta para este item.');
		if (!$this->isPresent($sessionId,$uid)) throw new ValidationException('Somente parlamentares com presença registrada podem votar.');
		if (!in_array($choice,self::CHOICES,true)) throw new ValidationException('Opção de voto inválida.');
		$secret=$item->getVoteType()==='secret'; $voterKey=$secret ? hash_hmac('sha256',$sessionId.'|'.$item->getMatterId().'|'.$uid,$this->config->getSystemValueString('secret')) : $uid;
		foreach ($this->votes->findForBallot($sessionId,$item->getMatterId()) as $existing) {
			if (hash_equals($existing->getVoterUid(),$voterKey)) throw new ValidationException('Seu voto já foi computado nesta matéria.');
		}
		$vote=new Vote(); $vote->setSessionId($sessionId); $vote->setMatterId($item->getMatterId()); $vote->setVoterUid($voterKey); $vote->setChoice($choice); $vote->setSecret($secret); $vote->setCastAt($this->now());
		$inserted=$this->votes->insert($vote);
		$this->audit->record($uid,'session',$sessionId,'vote.cast',null,$secret?['receipt'=>hash('sha256',(string)$inserted->getId()),'secret'=>true]:$inserted->jsonSerialize()); return $inserted;
	}

	private function summary(LegislativeSession $session): array { $data=$session->jsonSerialize(); $data['presentCount']=$this->presentCount($session->getId()); $data['presenceRequired']=$this->presenceRequired($session->getTotalSeats()); $data['agendaCount']=count($this->agenda->findBySession($session->getId())); return $data; }
	private function agendaData(AgendaItem $item, LegislativeSession $session): array {
		$data=$item->jsonSerialize(); $matter=$this->matters->find($item->getMatterId()); $data['matter']=$matter->jsonSerialize(); $ballots=$this->votes->findForBallot($session->getId(),$item->getMatterId());
		$tally=$this->tally($ballots); $data['tally']=$item->getVoteType()==='secret' && $item->getStatus()!=='closed' ? ['total'=>count($ballots)] : $tally;
		$data['votes']=$item->getVoteType()==='nominal' ? array_map(static fn(Vote $v):array=>$v->jsonSerialize(),$ballots) : []; return $data;
	}
	/** @param Vote[] $votes */ private function tally(array $votes): array { $r=['yes'=>0,'no'=>0,'abstain'=>0,'obstruction'=>0,'total'=>count($votes)]; foreach($votes as $vote) $r[$vote->getChoice()]++; return $r; }
	private function calculateResult(LegislativeSession $session, AgendaItem $item): array { $t=$this->tally($this->votes->findForBallot($session->getId(),$item->getMatterId())); $present=$this->presentCount($session->getId()); $minimum=$this->presenceRequired($session->getTotalSeats()); if($present<$minimum) return ['result'=>'no_quorum','tally'=>$t]; $approved=match($item->getQuorumType()){'absolute'=>$t['yes']>=$minimum,'two_thirds'=>$t['yes']>=(int)ceil($session->getTotalSeats()*2/3),default=>$t['yes']>$t['no']}; return ['result'=>$approved?'approved':'rejected','tally'=>$t]; }
	private function presentCount(int $sessionId): int { return count(array_filter($this->attendance->findBySession($sessionId),static fn(Attendance $a):bool=>$a->getPresent())); }
	private function isPresent(int $sessionId,string $uid): bool { try { return $this->attendance->findByUser($sessionId,$uid)->getPresent(); } catch(DoesNotExistException) { return false; } }
	private function presenceRequired(int $seats): int { return intdiv($seats,2)+1; }
	private function now(): \DateTime { return new \DateTime('now',new \DateTimeZone('UTC')); }
	/** @param array<string,mixed> $data */ private function optional(array $data,string $key,int $max): ?string { $v=trim((string)($data[$key]??'')); if($v==='') return null; if(mb_strlen($v)>$max) throw new ValidationException('Campo excede o tamanho permitido.'); return $v; }
}
