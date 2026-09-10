<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Migration;

use Closure;
use OCA\Legislativo\Service\TextNormalizer;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version9000Date20260806200000 extends SimpleMigrationStep {
	public function __construct(private IDBConnection $db) {
	}

	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$changed = false;
		foreach (['leg_matters', 'leg_norms'] as $tableName) {
			$table = $schema->getTable($tableName);
			if (!$table->hasColumn('search_text')) {
				$table->addColumn('search_text', Types::TEXT, ['notnull' => false]);
				$changed = true;
			}
		}
		return $changed ? $schema : null;
	}

	#[\Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$normalizer = new TextNormalizer();
		$matterRows = $this->db->getQueryBuilder()->select('*')->from('leg_matters')->executeQuery()->fetchAll();
		foreach ($matterRows as $row) {
			$document = $normalizer->document([
				$row['type'], $row['number'], $row['year'], $row['subject'], $row['body'], $row['author_uid'],
				$row['status'], $row['theme'], $row['quorum'], $row['procedure'], $row['notes'], $row['presented_at'],
			]);
			$this->updateSearchText('leg_matters', (int)$row['id'], $document);
		}

		$normRows = $this->db->getQueryBuilder()->select('*')->from('leg_norms')->executeQuery()->fetchAll();
		foreach ($normRows as $row) {
			$versionQuery = $this->db->getQueryBuilder();
			$versions = $versionQuery->select('body_html')->from('leg_norm_versions')
				->where($versionQuery->expr()->eq('norm_id', $versionQuery->createNamedParameter((int)$row['id'], IQueryBuilder::PARAM_INT)))
				->executeQuery()->fetchAll(\PDO::FETCH_COLUMN);
			$document = $normalizer->document([$row['type'], $row['number'], $row['year'], $row['title'], $row['ementa'], $row['status'], ...$versions]);
			$this->updateSearchText('leg_norms', (int)$row['id'], $document);
		}
		$output->info(count($matterRows) . ' matérias e ' . count($normRows) . ' normas indexadas para busca tolerante.');
	}

	private function updateSearchText(string $table, int $id, string $document): void {
		$qb = $this->db->getQueryBuilder();
		$qb->update($table)
			->set('search_text', $qb->createNamedParameter($document))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->executeStatement();
	}
}
