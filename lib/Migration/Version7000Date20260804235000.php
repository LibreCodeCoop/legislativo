<?php

declare(strict_types=1);

namespace OCA\Legislativo\Migration;

use Closure;
use OCA\Legislativo\Service\AuthorizationService;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IGroupManager;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version7000Date20260804235000 extends SimpleMigrationStep {
	public function __construct(private IGroupManager $groupManager) {
	}

	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$table = $schema->getTable('leg_sessions');
		$changed = false;
		$columns = [
			'minutes_html' => [Types::TEXT, ['notnull' => false]],
			'minutes_revision' => [Types::INTEGER, ['unsigned' => true, 'default' => 0]],
			'minutes_updated_by' => [Types::STRING, ['length' => 64, 'notnull' => false]],
			'minutes_updated_at' => [Types::DATETIME, ['notnull' => false]],
			'minutes_libresign_uuid' => [Types::STRING, ['length' => 64, 'notnull' => false]],
			'minutes_signature_status' => [Types::STRING, ['length' => 32, 'default' => 'not_requested']],
			'minutes_signers' => [Types::JSON, ['notnull' => false]],
		];
		foreach ($columns as $name => [$type, $definition]) {
			if (!$table->hasColumn($name)) {
				$table->addColumn($name, $type, $definition);
				$changed = true;
			}
		}
		return $changed ? $schema : null;
	}

	#[\Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$groupId = AuthorizationService::PRESIDENTS_GROUP;
		if ($this->groupManager->get($groupId) === null) {
			$this->groupManager->createGroup($groupId);
			$output->info('Grupo ' . $groupId . ' criado.');
		}
	}
}
