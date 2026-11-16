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

class Version10000Date20261116100000 extends SimpleMigrationStep {
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if ($schema->hasTable('leg_official_documents')) {
			return null;
		}

		$table = $schema->createTable('leg_official_documents');
		$table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'unsigned' => true]);
		$table->setPrimaryKey(['id']);
		$table->addColumn('kind', Types::STRING, ['length' => 32]);
		$table->addColumn('number', Types::INTEGER, ['unsigned' => true, 'notnull' => false]);
		$table->addColumn('year', Types::INTEGER, ['unsigned' => true]);
		$table->addColumn('subject', Types::STRING, ['length' => 512]);
		$table->addColumn('body', Types::TEXT, []);
		$table->addColumn('recipients', Types::JSON, []);
		$table->addColumn('status', Types::STRING, ['length' => 32, 'default' => 'draft']);
		$table->addColumn('created_by', Types::STRING, ['length' => 64]);
		$table->addColumn('issued_by', Types::STRING, ['length' => 64, 'notnull' => false]);
		$table->addColumn('issued_at', Types::DATETIME, ['notnull' => false]);
		$table->addColumn('created_at', Types::DATETIME, []);
		$table->addColumn('updated_at', Types::DATETIME, []);
		$table->addUniqueIndex(['kind', 'year', 'number'], 'leg_offdoc_number_uk');
		$table->addIndex(['status', 'updated_at'], 'leg_offdoc_status_idx');
		$table->addIndex(['kind', 'year'], 'leg_offdoc_kind_year_idx');

		return $schema;
	}
}
