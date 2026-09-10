<?php

declare(strict_types=1);

namespace OCA\Legislativo\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version4000Date20260804190000 extends SimpleMigrationStep {
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if ($schema->hasTable('leg_document_flows')) return null;
		$table = $schema->createTable('leg_document_flows');
		$table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'unsigned' => true]);
		$table->setPrimaryKey(['id']);
		$table->addColumn('matter_id', Types::BIGINT, ['unsigned' => true]);
		$table->addColumn('protocol_id', Types::BIGINT, ['unsigned' => true]);
		$table->addColumn('source_file_id', Types::BIGINT, ['unsigned' => true]);
		$table->addColumn('source_path', Types::STRING, ['length' => 1024]);
		$table->addColumn('pdf_file_id', Types::BIGINT, ['unsigned' => true]);
		$table->addColumn('pdf_path', Types::STRING, ['length' => 1024]);
		$table->addColumn('status', Types::STRING, ['length' => 32, 'default' => 'prepared']);
		$table->addColumn('libresign_uuid', Types::STRING, ['length' => 64, 'notnull' => false]);
		$table->addColumn('signers', Types::JSON, ['notnull' => false]);
		$table->addColumn('checksum', Types::STRING, ['length' => 64]);
		$table->addColumn('error_message', Types::TEXT, ['notnull' => false]);
		$table->addColumn('created_by', Types::STRING, ['length' => 64]);
		$table->addColumn('created_at', Types::DATETIME, []);
		$table->addColumn('updated_at', Types::DATETIME, []);
		$table->addIndex(['matter_id', 'created_at'], 'leg_doc_flow_matter_idx');
		$table->addIndex(['status'], 'leg_doc_flow_status_idx');
		return $schema;
	}
}
