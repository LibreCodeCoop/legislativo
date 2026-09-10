<?php

declare(strict_types=1);

namespace OCA\Legislativo\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version5000Date20260804210000 extends SimpleMigrationStep {
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure(); $changed = false;
		$sessions = $schema->getTable('leg_sessions');
		foreach ([
			'total_seats' => [Types::SMALLINT, ['unsigned' => true, 'default' => 11]],
			'chair_uid' => [Types::STRING, ['length' => 64, 'notnull' => false]],
			'opened_at' => [Types::DATETIME, ['notnull' => false]],
			'closed_at' => [Types::DATETIME, ['notnull' => false]],
			'notes' => [Types::TEXT, ['notnull' => false]],
		] as $name => [$type, $options]) if (!$sessions->hasColumn($name)) { $sessions->addColumn($name, $type, $options); $changed = true; }

		if (!$schema->hasTable('leg_session_agenda')) {
			$table = $schema->createTable('leg_session_agenda'); $this->addId($table);
			$table->addColumn('session_id', Types::BIGINT, ['unsigned' => true]);
			$table->addColumn('matter_id', Types::BIGINT, ['unsigned' => true]);
			$table->addColumn('position', Types::INTEGER, ['unsigned' => true]);
			$table->addColumn('status', Types::STRING, ['length' => 32, 'default' => 'pending']);
			$table->addColumn('vote_type', Types::STRING, ['length' => 16, 'default' => 'nominal']);
			$table->addColumn('quorum_type', Types::STRING, ['length' => 24, 'default' => 'simple']);
			$table->addColumn('opened_at', Types::DATETIME, ['notnull' => false]);
			$table->addColumn('closed_at', Types::DATETIME, ['notnull' => false]);
			$table->addColumn('result', Types::STRING, ['length' => 32, 'notnull' => false]);
			$table->addColumn('created_at', Types::DATETIME, []);
			$table->addUniqueIndex(['session_id', 'matter_id'], 'leg_agenda_session_matter_uk');
			$table->addUniqueIndex(['session_id', 'position'], 'leg_agenda_position_uk');
			$table->addIndex(['session_id', 'status'], 'leg_agenda_status_idx'); $changed = true;
		}

		if (!$schema->hasTable('leg_attendance')) {
			$table = $schema->createTable('leg_attendance'); $this->addId($table);
			$table->addColumn('session_id', Types::BIGINT, ['unsigned' => true]);
			$table->addColumn('user_uid', Types::STRING, ['length' => 64]);
			$table->addColumn('display_name', Types::STRING, ['length' => 255]);
			$table->addColumn('present', Types::BOOLEAN, ['default' => true]);
			$table->addColumn('checked_at', Types::DATETIME, []);
			$table->addUniqueIndex(['session_id', 'user_uid'], 'leg_attendance_session_user_uk');
			$table->addIndex(['session_id', 'present'], 'leg_attendance_present_idx'); $changed = true;
		}
		return $changed ? $schema : null;
	}

	private function addId(object $table): void {
		$table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'unsigned' => true]); $table->setPrimaryKey(['id']);
	}
}
