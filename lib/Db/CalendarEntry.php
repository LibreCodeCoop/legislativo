<?php

declare(strict_types=1);

namespace OCA\Legislativo\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method int getId()
 * @method void setName(string $value)
 * @method string getName()
 * @method void setKind(string $value)
 * @method string getKind()
 * @method void setStartsOn(\DateTime $value)
 * @method \DateTime getStartsOn()
 * @method void setEndsOn(\DateTime $value)
 * @method \DateTime getEndsOn()
 * @method void setActive(bool $value)
 * @method bool getActive()
 * @method void setCreatedBy(string $value)
 * @method string getCreatedBy()
 * @method void setCreatedAt(\DateTime $value)
 * @method \DateTime getCreatedAt()
 * @method void setUpdatedAt(\DateTime $value)
 * @method \DateTime getUpdatedAt()
 */
class CalendarEntry extends Entity {
	protected string $name = '';
	protected string $kind = '';
	protected ?\DateTime $startsOn = null;
	protected ?\DateTime $endsOn = null;
	protected bool $active = true;
	protected string $createdBy = '';
	protected ?\DateTime $createdAt = null;
	protected ?\DateTime $updatedAt = null;

	public function __construct() {
		$this->addType('id', Types::INTEGER);
		$this->addType('active', Types::BOOLEAN);
		foreach (['startsOn', 'endsOn'] as $field) $this->addType($field, Types::DATE);
		foreach (['createdAt', 'updatedAt'] as $field) $this->addType($field, Types::DATETIME);
	}

	public function jsonSerialize(): array {
		return ['id' => $this->getId(), 'name' => $this->getName(), 'kind' => $this->getKind(),
			'startsOn' => $this->getStartsOn()->format('Y-m-d'), 'endsOn' => $this->getEndsOn()->format('Y-m-d'),
			'active' => $this->getActive(), 'createdBy' => $this->getCreatedBy(),
			'createdAt' => $this->getCreatedAt()->format(DATE_ATOM), 'updatedAt' => $this->getUpdatedAt()->format(DATE_ATOM)];
	}
}
