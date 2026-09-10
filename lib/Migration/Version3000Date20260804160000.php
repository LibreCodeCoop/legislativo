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

class Version3000Date20260804160000 extends SimpleMigrationStep {
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$changed = false;

		if (!$schema->hasTable('leg_attachments')) {
			$changed = true;
			$table = $schema->createTable('leg_attachments');
			$this->addId($table);
			$table->addColumn('matter_id', Types::BIGINT, ['unsigned' => true]);
			$table->addColumn('file_id', Types::BIGINT, ['unsigned' => true]);
			$table->addColumn('file_path', Types::STRING, ['length' => 1024]);
			$table->addColumn('file_name', Types::STRING, ['length' => 255]);
			$table->addColumn('mime_type', Types::STRING, ['length' => 255]);
			$table->addColumn('file_size', Types::BIGINT, ['unsigned' => true, 'default' => 0]);
			$table->addColumn('added_by', Types::STRING, ['length' => 64]);
			$table->addColumn('created_at', Types::DATETIME, []);
			$table->addUniqueIndex(['matter_id', 'file_id'], 'leg_attach_matter_file_uk');
			$table->addIndex(['matter_id'], 'leg_attach_matter_idx');
		}

		if (!$schema->hasTable('leg_submissions')) {
			$changed = true;
			$table = $schema->createTable('leg_submissions');
			$this->addId($table);
			$table->addColumn('matter_id', Types::BIGINT, ['unsigned' => true]);
			$table->addColumn('sender', Types::STRING, ['length' => 255]);
			$table->addColumn('subject', Types::STRING, ['length' => 512]);
			$table->addColumn('notes', Types::TEXT, ['notnull' => false]);
			$table->addColumn('status', Types::STRING, ['length' => 32, 'default' => 'pending']);
			$table->addColumn('file_id', Types::BIGINT, ['unsigned' => true, 'notnull' => false]);
			$table->addColumn('file_path', Types::STRING, ['length' => 1024, 'notnull' => false]);
			$table->addColumn('submitted_at', Types::DATETIME, []);
			$table->addColumn('reviewed_at', Types::DATETIME, ['notnull' => false]);
			$table->addColumn('reviewed_by', Types::STRING, ['length' => 64, 'notnull' => false]);
			$table->addColumn('review_notes', Types::TEXT, ['notnull' => false]);
			$table->addIndex(['status', 'submitted_at'], 'leg_submission_queue_idx');
			$table->addIndex(['matter_id'], 'leg_submission_matter_idx');
		}

		if (!$schema->hasTable('leg_calendar')) {
			$changed = true;
			$table = $schema->createTable('leg_calendar');
			$this->addId($table);
			$table->addColumn('name', Types::STRING, ['length' => 255]);
			$table->addColumn('kind', Types::STRING, ['length' => 32]);
			$table->addColumn('starts_on', Types::DATE, []);
			$table->addColumn('ends_on', Types::DATE, []);
			$table->addColumn('active', Types::BOOLEAN, ['default' => true]);
			$table->addColumn('created_by', Types::STRING, ['length' => 64]);
			$table->addColumn('created_at', Types::DATETIME, []);
			$table->addColumn('updated_at', Types::DATETIME, []);
			$table->addIndex(['starts_on', 'ends_on', 'active'], 'leg_calendar_period_idx');
		}

		$protocols = $schema->getTable('leg_protocols');
		if (!$protocols->hasColumn('submission_id')) {
			$protocols->addColumn('submission_id', Types::BIGINT, ['unsigned' => true, 'notnull' => false]);
			$protocols->addIndex(['submission_id'], 'leg_protocol_submission_idx');
			$changed = true;
		}

		return $changed ? $schema : null;
	}

	private function addId(object $table): void {
		$table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'unsigned' => true]);
		$table->setPrimaryKey(['id']);
	}
}
