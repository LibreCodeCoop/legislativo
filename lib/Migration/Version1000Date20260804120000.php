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

class Version1000Date20260804120000 extends SimpleMigrationStep {
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$changed = false;

		if (!$schema->hasTable('leg_matters')) {
			$changed = true;
			$table = $schema->createTable('leg_matters');
			$this->addId($table);
			$table->addColumn('type', Types::STRING, ['length' => 64]);
			$table->addColumn('number', Types::INTEGER, ['unsigned' => true]);
			$table->addColumn('year', Types::SMALLINT, ['unsigned' => true]);
			$table->addColumn('subject', Types::STRING, ['length' => 512]);
			$table->addColumn('body', Types::TEXT, ['notnull' => false]);
			$table->addColumn('author_uid', Types::STRING, ['length' => 64, 'notnull' => false]);
			$table->addColumn('status', Types::STRING, ['length' => 32, 'default' => 'draft']);
			$table->addColumn('quorum', Types::STRING, ['length' => 64, 'notnull' => false]);
			$table->addColumn('procedure', Types::STRING, ['length' => 64, 'notnull' => false]);
			$table->addColumn('file_id', Types::BIGINT, ['unsigned' => true, 'notnull' => false]);
			$this->addTimestamps($table);
			$table->addUniqueIndex(['type', 'number', 'year'], 'leg_matters_number_uk');
			$table->addIndex(['status'], 'leg_matters_status_idx');
		}

		if (!$schema->hasTable('leg_protocols')) {
			$changed = true;
			$table = $schema->createTable('leg_protocols');
			$this->addId($table);
			$table->addColumn('matter_id', Types::BIGINT, ['unsigned' => true, 'notnull' => false]);
			$table->addColumn('number', Types::INTEGER, ['unsigned' => true]);
			$table->addColumn('year', Types::SMALLINT, ['unsigned' => true]);
			$table->addColumn('sender', Types::STRING, ['length' => 255]);
			$table->addColumn('subject', Types::STRING, ['length' => 512]);
			$table->addColumn('status', Types::STRING, ['length' => 32, 'default' => 'received']);
			$table->addColumn('file_id', Types::BIGINT, ['unsigned' => true, 'notnull' => false]);
			$this->addTimestamps($table);
			$table->addUniqueIndex(['number', 'year'], 'leg_protocol_number_uk');
			$table->addIndex(['matter_id'], 'leg_protocol_matter_idx');
		}

		if (!$schema->hasTable('leg_proceedings')) {
			$changed = true;
			$table = $schema->createTable('leg_proceedings');
			$this->addId($table);
			$table->addColumn('matter_id', Types::BIGINT, ['unsigned' => true]);
			$table->addColumn('sender_uid', Types::STRING, ['length' => 64]);
			$table->addColumn('recipient', Types::STRING, ['length' => 255]);
			$table->addColumn('objective', Types::STRING, ['length' => 512]);
			$table->addColumn('result', Types::STRING, ['length' => 255, 'notnull' => false]);
			$table->addColumn('sent_at', Types::DATETIME, []);
			$table->addColumn('due_at', Types::DATETIME, ['notnull' => false]);
			$table->addColumn('answered_at', Types::DATETIME, ['notnull' => false]);
			$table->addColumn('notes', Types::TEXT, ['notnull' => false]);
			$table->addIndex(['matter_id'], 'leg_proceed_matter_idx');
			$table->addIndex(['due_at'], 'leg_proceed_due_idx');
		}

		if (!$schema->hasTable('leg_sessions')) {
			$changed = true;
			$table = $schema->createTable('leg_sessions');
			$this->addId($table);
			$table->addColumn('type', Types::STRING, ['length' => 64]);
			$table->addColumn('number', Types::INTEGER, ['unsigned' => true]);
			$table->addColumn('year', Types::SMALLINT, ['unsigned' => true]);
			$table->addColumn('scheduled_at', Types::DATETIME, []);
			$table->addColumn('status', Types::STRING, ['length' => 32, 'default' => 'scheduled']);
			$table->addColumn('agenda_file_id', Types::BIGINT, ['unsigned' => true, 'notnull' => false]);
			$table->addColumn('minutes_file_id', Types::BIGINT, ['unsigned' => true, 'notnull' => false]);
			$this->addTimestamps($table);
			$table->addUniqueIndex(['type', 'number', 'year'], 'leg_sessions_number_uk');
		}

		if (!$schema->hasTable('leg_votes')) {
			$changed = true;
			$table = $schema->createTable('leg_votes');
			$this->addId($table);
			$table->addColumn('session_id', Types::BIGINT, ['unsigned' => true]);
			$table->addColumn('matter_id', Types::BIGINT, ['unsigned' => true]);
			$table->addColumn('voter_uid', Types::STRING, ['length' => 64]);
			$table->addColumn('choice', Types::STRING, ['length' => 16]);
			$table->addColumn('secret', Types::BOOLEAN, ['default' => false]);
			$table->addColumn('cast_at', Types::DATETIME, []);
			$table->addUniqueIndex(['session_id', 'matter_id', 'voter_uid'], 'leg_votes_cast_uk');
			$table->addIndex(['session_id', 'matter_id'], 'leg_votes_result_idx');
		}

		if (!$schema->hasTable('leg_laws')) {
			$changed = true;
			$table = $schema->createTable('leg_laws');
			$this->addId($table);
			$table->addColumn('type', Types::STRING, ['length' => 64]);
			$table->addColumn('number', Types::INTEGER, ['unsigned' => true]);
			$table->addColumn('year', Types::SMALLINT, ['unsigned' => true]);
			$table->addColumn('subject', Types::STRING, ['length' => 512]);
			$table->addColumn('html', Types::TEXT, []);
			$table->addColumn('effective_from', Types::DATE, ['notnull' => false]);
			$table->addColumn('effective_to', Types::DATE, ['notnull' => false]);
			$table->addColumn('supersedes_id', Types::BIGINT, ['unsigned' => true, 'notnull' => false]);
			$table->addColumn('source_file_id', Types::BIGINT, ['unsigned' => true, 'notnull' => false]);
			$this->addTimestamps($table);
			$table->addUniqueIndex(['type', 'number', 'year', 'effective_from'], 'leg_laws_version_uk');
			$table->addIndex(['supersedes_id'], 'leg_laws_previous_idx');
		}

		if (!$schema->hasTable('leg_audit_log')) {
			$changed = true;
			$table = $schema->createTable('leg_audit_log');
			$this->addId($table);
			$table->addColumn('user_uid', Types::STRING, ['length' => 64]);
			$table->addColumn('module', Types::STRING, ['length' => 64]);
			$table->addColumn('entity_type', Types::STRING, ['length' => 64]);
			$table->addColumn('entity_id', Types::BIGINT, ['unsigned' => true]);
			$table->addColumn('action', Types::STRING, ['length' => 64]);
			$table->addColumn('before_data', Types::JSON, ['notnull' => false]);
			$table->addColumn('after_data', Types::JSON, ['notnull' => false]);
			$table->addColumn('ip_address', Types::STRING, ['length' => 64, 'notnull' => false]);
			$table->addColumn('user_agent', Types::STRING, ['length' => 512, 'notnull' => false]);
			$table->addColumn('created_at', Types::DATETIME, []);
			$table->addIndex(['entity_type', 'entity_id'], 'leg_audit_entity_idx');
			$table->addIndex(['created_at'], 'leg_audit_created_idx');
		}

		return $changed ? $schema : null;
	}

	private function addId(object $table): void {
		$table->addColumn('id', Types::BIGINT, [
			'autoincrement' => true,
			'unsigned' => true,
		]);
		$table->setPrimaryKey(['id']);
	}

	private function addTimestamps(object $table): void {
		$table->addColumn('created_at', Types::DATETIME, []);
		$table->addColumn('updated_at', Types::DATETIME, []);
	}
}
