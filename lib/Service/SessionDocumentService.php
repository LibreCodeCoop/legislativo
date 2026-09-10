<?php

declare(strict_types=1);

namespace OCA\Legislativo\Service;

use OCA\Legislativo\Db\LegislativeSessionMapper;
use OCA\Legislativo\Exception\ValidationException;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;

class SessionDocumentService{
	public function __construct(private VotingService $voting,private LegislativeSessionMapper $sessions,private IRootFolder $root,private AuthorizationService $authorization,private AuditService $audit){}
	/** @return array{fileId:int,path:string,type:string} */
	public function generate(int $sessionId,string $type):array{
		$uid=$this->authorization->requireWrite();if(!in_array($type,['agenda','minutes'],true))throw new ValidationException('Tipo de documento inválido.');$data=$this->voting->get($sessionId);$session=$this->sessions->find($sessionId);if($type==='minutes'&&$session->getStatus()!=='closed')throw new ValidationException('A ata só pode ser gerada após o encerramento da sessão.');
		$lines=$this->buildLines($data,$type);$temp=sys_get_temp_dir().'/legislativo-session-'.bin2hex(random_bytes(6));if(!mkdir($temp,0700,true)&&!is_dir($temp))throw new ValidationException('Falha ao criar área temporária.');
		try{$ps=$temp.'/document.ps';$pdf=$temp.'/document.pdf';file_put_contents($ps,$this->postScript($lines));$this->run(['/usr/bin/gs','-q','-dBATCH','-dNOPAUSE','-dSAFER','-sDEVICE=pdfwrite','-sOutputFile='.$pdf,$ps]);$content=file_get_contents($pdf);if($content===false||!str_starts_with($content,'%PDF-'))throw new ValidationException('Falha ao produzir o PDF.');$file=$this->save($uid,$session->getYear(),$type.'-'.$session->getNumber().'-'.$session->getYear().'.pdf',$content);$relative=$this->relative($file->getPath(),$uid);if($type==='agenda')$session->setAgendaFileId($file->getId());else{$session->setMinutesFileId($file->getId());$session->setMinutesLibresignUuid(null);$session->setMinutesSignatureStatus('not_requested');$session->setMinutesSigners(null);}$session->setUpdatedAt(new \DateTime('now',new \DateTimeZone('UTC')));$this->sessions->update($session);$result=['fileId'=>$file->getId(),'path'=>'/'.$relative,'type'=>$type];$this->audit->record($uid,'session',$sessionId,'session.document.'.$type,null,$result);return$result;}finally{foreach(glob($temp.'/*')?:[]as$f)@unlink($f);@rmdir($temp);}
	}
	/** @param array<string,mixed> $data @return list<string> */
	private function buildLines(array $data,string $type):array{
		$s=$data['session'];$statuses=['scheduled'=>'agendada','open'=>'aberta','closed'=>'encerrada'];$results=['approved'=>'aprovada','rejected'=>'rejeitada','no_quorum'=>'sem quorum'];$voteTypes=['nominal'=>'nominal','secret'=>'secreta','symbolic'=>'simbolica'];$quorums=['simple'=>'maioria simples','absolute'=>'maioria absoluta','two_thirds'=>'dois tercos'];
		$local=static function(?string$value):string{if(!$value)return'-';return(new \DateTimeImmutable($value))->setTimezone(new \DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i:s');};
		$lines=[($type==='agenda'?'ORDEM DO DIA':'ATA DA SESSAO'),strtoupper($s['type']).' No '.$s['number'].'/'.$s['year'],'Data: '.$local($s['scheduledAt']),'Situacao: '.($statuses[$s['status']]??$s['status']),'Presencas: '.$s['presentCount'].' de '.$s['totalSeats'],''];
		$lines[]='PRESENTES';foreach($data['attendance']as$a)if($a['present'])$lines[]='- '.$a['displayName'];$lines[]='';$lines[]='PAUTA E RESULTADOS';
		foreach($data['agenda']as$item){$m=$item['matter'];$lines[]=$item['position'].'. '.$m['type'].' No '.$m['number'].'/'.$m['year'].' - '.$m['subject'];$lines[]='   Modalidade: '.($voteTypes[$item['voteType']]??$item['voteType']).' | Quorum: '.($quorums[$item['quorumType']]??$item['quorumType']).' | Resultado: '.($results[$item['result']??'']??'pendente');if(isset($item['tally']['yes']))$lines[]='   Sim: '.$item['tally']['yes'].' | Nao: '.$item['tally']['no'].' | Abstencao: '.$item['tally']['abstain'].' | Obstrucao: '.$item['tally']['obstruction'];}
		if($type==='minutes'){$lines[]='';$lines[]='ORADORES';foreach($data['speakers']as$sp)$lines[]='- '.$sp['displayName'].' - '.($sp['topic']??'sem tema').' ('.($sp['status']==='done'?'concluido':$sp['status']).')';$lines[]='';$lines[]='Sessao aberta em: '.$local($s['openedAt']);$lines[]='Sessao encerrada em: '.$local($s['closedAt']);if(!empty($s['minutesHtml'])){$lines[]='';$lines[]='REGISTRO DA ATA';$marked=preg_replace('/<\/?(?:p|h2|h3|li|blockquote|br)[^>]*>/i',"\n",(string)$s['minutesHtml'])??'';$plain=html_entity_decode(strip_tags($marked),ENT_QUOTES|ENT_HTML5,'UTF-8');foreach(preg_split('/\R+/',trim($plain))?:[]as$paragraph)if(trim($paragraph)!=='')$lines[]=trim($paragraph);}}
		return$lines;
	}
	/** @param list<string> $lines */ private function postScript(array $lines):string{$ps="%!PS-Adobe-3.0\n/Helvetica findfont 10 scalefont setfont\n/y 790 def\n/line { y 45 lt { showpage /Helvetica findfont 10 scalefont setfont /y 790 def } if 45 y moveto show /y y 14 sub def } bind def\n";foreach($lines as$line)foreach(explode("\n",wordwrap($this->ascii($line),95,"\n",true))as$wrapped)$ps.='('.$this->escape($wrapped).") line\n";return$ps."showpage\n%%EOF\n";}
	private function ascii(string $value):string{return iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value)?:$value;}private function escape(string $v):string{return str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$v);}
	/** @param list<string> $command */private function run(array $command):void{$pipes=[];$p=proc_open($command,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($p))throw new ValidationException('Ghostscript indisponível.');fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($p)!==0)throw new ValidationException('Falha ao gerar PDF: '.trim((string)$err?:$out));}
	private function save(string $uid,int $year,string $name,string $content):File{$folder=$this->root->getUserFolder($uid);foreach(['Documentos Legislativos','Sessoes',(string)$year]as$part){$folder=$folder->nodeExists($part)?$folder->get($part):$folder->newFolder($part);if(!$folder instanceof Folder)throw new ValidationException('Destino documental inválido.');}$base=pathinfo($name,PATHINFO_FILENAME);$candidate=$name;$i=2;while($folder->nodeExists($candidate))$candidate=$base.'-'.$i++.'.pdf';$file=$folder->newFile($candidate);$file->putContent($content);return$file;}
	private function relative(string $path,string $uid):string{foreach(['/'.$uid.'/files/','/files/'.$uid.'/']as$marker){$p=strpos($path,$marker);if($p!==false)return substr($path,$p+strlen($marker));}return ltrim($path,'/');}
}
