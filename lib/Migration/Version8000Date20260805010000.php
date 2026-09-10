<?php

declare(strict_types=1);

namespace OCA\Legislativo\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version8000Date20260805010000 extends SimpleMigrationStep {
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */ $schema = $schemaClosure(); $changed = false;
		if (!$schema->hasTable('leg_norms')) {
			$table = $schema->createTable('leg_norms'); $this->id($table);
			$table->addColumn('type', Types::STRING, ['length' => 64]); $table->addColumn('number', Types::INTEGER, ['unsigned' => true]); $table->addColumn('year', Types::SMALLINT, ['unsigned' => true]);
			$table->addColumn('title', Types::STRING, ['length' => 512]); $table->addColumn('ementa', Types::TEXT, ['notnull' => false]); $table->addColumn('status', Types::STRING, ['length' => 16, 'default' => 'draft']);
			$table->addColumn('published_at', Types::DATETIME, ['notnull' => false]); $table->addColumn('created_by', Types::STRING, ['length' => 64]); $table->addColumn('created_at', Types::DATETIME, []); $table->addColumn('updated_at', Types::DATETIME, []);
			$table->addUniqueIndex(['type', 'number', 'year'], 'leg_norms_identity_uk'); $table->addIndex(['status', 'year'], 'leg_norms_status_year_idx'); $changed = true;
		}
		if (!$schema->hasTable('leg_norm_versions')) {
			$table = $schema->createTable('leg_norm_versions'); $this->id($table);
			$table->addColumn('norm_id', Types::BIGINT, ['unsigned' => true]); $table->addColumn('label', Types::STRING, ['length' => 64]); $table->addColumn('valid_from', Types::DATE, []); $table->addColumn('valid_until', Types::DATE, ['notnull' => false]); $table->addColumn('body_html', Types::TEXT, []); $table->addColumn('checksum', Types::STRING, ['length' => 64]); $table->addColumn('created_by', Types::STRING, ['length' => 64]); $table->addColumn('created_at', Types::DATETIME, []);
			$table->addIndex(['norm_id', 'valid_from'], 'leg_norm_version_date_idx'); $table->addUniqueIndex(['norm_id', 'label'], 'leg_norm_version_label_uk'); $changed = true;
		}
		if (!$schema->hasTable('leg_norm_relations')) {
			$table = $schema->createTable('leg_norm_relations'); $this->id($table);
			$table->addColumn('source_norm_id', Types::BIGINT, ['unsigned' => true]); $table->addColumn('target_norm_id', Types::BIGINT, ['unsigned' => true]); $table->addColumn('relation_type', Types::STRING, ['length' => 32]); $table->addColumn('notes', Types::STRING, ['length' => 1000, 'notnull' => false]); $table->addColumn('created_by', Types::STRING, ['length' => 64]); $table->addColumn('created_at', Types::DATETIME, []);
			$table->addUniqueIndex(['source_norm_id', 'target_norm_id', 'relation_type'], 'leg_norm_relation_uk'); $table->addIndex(['target_norm_id'], 'leg_norm_relation_target_idx'); $changed = true;
		}
		return $changed ? $schema : null;
	}
	private function id(object $table): void { $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'unsigned' => true]); $table->setPrimaryKey(['id']); }
}
