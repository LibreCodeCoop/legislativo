<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version2000Date20260804143000 extends SimpleMigrationStep {
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$changed = false;

		$matters = $schema->getTable('leg_matters');
		foreach ([
			'theme' => [Types::STRING, ['length' => 255, 'notnull' => false]],
			'notes' => [Types::TEXT, ['notnull' => false]],
			'presented_at' => [Types::DATE, ['notnull' => false]],
		] as $name => [$type, $definition]) {
			if (!$matters->hasColumn($name)) {
				$matters->addColumn($name, $type, $definition);
				$changed = true;
			}
		}

		$protocols = $schema->getTable('leg_protocols');
		if (!$protocols->hasColumn('received_at')) {
			$protocols->addColumn('received_at', Types::DATETIME, ['notnull' => false]);
			$changed = true;
		}

		$proceedings = $schema->getTable('leg_proceedings');
		foreach ([
			'deadline_days' => [Types::SMALLINT, ['unsigned' => true, 'notnull' => false]],
			'business_days' => [Types::BOOLEAN, ['default' => true]],
			'created_at' => [Types::DATETIME, ['notnull' => false]],
		] as $name => [$type, $definition]) {
			if (!$proceedings->hasColumn($name)) {
				$proceedings->addColumn($name, $type, $definition);
				$changed = true;
			}
		}

		$audit = $schema->getTable('leg_audit_log');
		if (!$audit->hasColumn('request_id')) {
			$audit->addColumn('request_id', Types::STRING, ['length' => 64, 'notnull' => false]);
			$changed = true;
		}

		return $changed ? $schema : null;
	}
}
