<?php
declare(strict_types=1);
namespace OCA\Legislativo\Db;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;
/** @method int getId() @method void setSourceNormId(int $v) @method int getSourceNormId() @method void setTargetNormId(int $v) @method int getTargetNormId() @method void setRelationType(string $v) @method string getRelationType() @method void setNotes(?string $v) @method ?string getNotes() @method void setCreatedBy(string $v) @method string getCreatedBy() @method void setCreatedAt(\DateTime $v) @method \DateTime getCreatedAt() */
class NormRelation extends Entity { protected int $sourceNormId=0; protected int $targetNormId=0; protected string $relationType='amends'; protected ?string $notes=null; protected string $createdBy=''; protected ?\DateTime $createdAt=null; public function __construct(){foreach(['id','sourceNormId','targetNormId']as$f)$this->addType($f,Types::INTEGER);$this->addType('createdAt',Types::DATETIME);} public function jsonSerialize():array{return['id'=>$this->getId(),'sourceNormId'=>$this->getSourceNormId(),'targetNormId'=>$this->getTargetNormId(),'relationType'=>$this->getRelationType(),'notes'=>$this->getNotes(),'createdBy'=>$this->getCreatedBy(),'createdAt'=>$this->getCreatedAt()->format(DATE_ATOM)];} }
