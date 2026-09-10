<?php
declare(strict_types=1);
namespace OCA\Legislativo\Db;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;
/** @method int getId() @method void setType(string $v) @method string getType() @method void setNumber(int $v) @method int getNumber() @method void setYear(int $v) @method int getYear() @method void setTitle(string $v) @method string getTitle() @method void setEmenta(?string $v) @method ?string getEmenta() @method void setStatus(string $v) @method string getStatus() @method void setPublishedAt(?\DateTime $v) @method ?\DateTime getPublishedAt() @method void setCreatedBy(string $v) @method string getCreatedBy() @method void setCreatedAt(\DateTime $v) @method \DateTime getCreatedAt() @method void setUpdatedAt(\DateTime $v) @method \DateTime getUpdatedAt() @method void setSearchText(?string $v) @method ?string getSearchText() */
class Norm extends Entity {
	protected string $type=''; protected int $number=0; protected int $year=0; protected string $title=''; protected ?string $ementa=null; protected string $status='draft'; protected ?\DateTime $publishedAt=null; protected string $createdBy=''; protected ?\DateTime $createdAt=null; protected ?\DateTime $updatedAt=null; protected ?string $searchText=null;
	public function __construct(){foreach(['id','number','year']as$f)$this->addType($f,Types::INTEGER);foreach(['publishedAt','createdAt','updatedAt']as$f)$this->addType($f,Types::DATETIME);}
	public function jsonSerialize():array{return['id'=>$this->getId(),'type'=>$this->getType(),'number'=>$this->getNumber(),'year'=>$this->getYear(),'title'=>$this->getTitle(),'ementa'=>$this->getEmenta(),'status'=>$this->getStatus(),'publishedAt'=>$this->getPublishedAt()?->format(DATE_ATOM),'createdBy'=>$this->getCreatedBy(),'createdAt'=>$this->getCreatedAt()->format(DATE_ATOM),'updatedAt'=>$this->getUpdatedAt()->format(DATE_ATOM)];}
}
