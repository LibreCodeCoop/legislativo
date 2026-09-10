<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
require_once __DIR__ . '/../../../lib/base.php';

use OCA\Legislativo\Service\MatterService;
use OCA\Legislativo\Service\NormService;
use OCP\IDBConnection;

$server = \OC::$server;
$admin = $server->getUserManager()->get('admin');
if ($admin === null) {
	fwrite(STDERR, "Usuário admin não encontrado.\n");
	exit(1);
}
$server->getUserSession()->setUser($admin);

/** @var IDBConnection $db */
$db = $server->get(IDBConnection::class);
/** @var MatterService $matters */
$matters = $server->get(MatterService::class);
/** @var NormService $norms */
$norms = $server->get(NormService::class);

$db->beginTransaction();
try {
	$matter = $matters->create([
		'type' => 'Projeto de Lei',
		'number' => 999999,
		'year' => 2026,
		'subject' => 'Comissões de inovação pública',
	]);
	$matterIds = array_column($matters->search(['query' => 'comissao inovacoes admin']), 'id');
	if (!in_array($matter->getId(), $matterIds, true)) {
		throw new RuntimeException('A matéria recém-criada não foi encontrada por acento, plural e autor.');
	}
	$anyIds = array_column($matters->search(['query' => 'inexistente comissões', 'queryMode' => 'any']), 'id');
	$allIds = array_column($matters->search(['query' => 'inexistente comissões', 'queryMode' => 'all']), 'id');
	$phraseIds = array_column($matters->search(['query' => 'comissões de inovação', 'queryMode' => 'phrase']), 'id');
	if (!in_array($matter->getId(), $anyIds, true) || in_array($matter->getId(), $allIds, true) || !in_array($matter->getId(), $phraseIds, true)) {
		throw new RuntimeException('Os modos E, OU ou frase exata não produziram o resultado esperado.');
	}
	$rangeIds = array_column($matters->search(['numberFrom' => 999999, 'numberTo' => 999999, 'yearFrom' => 2026, 'yearTo' => 2026]), 'id');
	if (!in_array($matter->getId(), $rangeIds, true)) {
		throw new RuntimeException('A matéria não foi encontrada pelos intervalos numérico e anual.');
	}

	$norm = $norms->save([
		'type' => 'Lei',
		'number' => 999999,
		'year' => 2026,
		'title' => 'Política de serviços digitais',
		'status' => 'published',
	]);
	$norms->saveVersion($norm->getId(), [
		'label' => 'Texto original',
		'validFrom' => '2026-01-01',
		'bodyHtml' => '<p>Os cidadãos terão informações acessíveis.</p>',
	]);
	$normIds = array_column($norms->search(['query' => 'cidadao informacao acessiveis'], true), 'id');
	if (!in_array($norm->getId(), $normIds, true)) {
		throw new RuntimeException('A norma não foi encontrada pelo conteúdo de sua versão.');
	}
} finally {
	$db->rollBack();
}

if ($matters->search(['number' => 999999, 'year' => 2026]) !== [] || $norms->search(['number' => 999999, 'year' => 2026]) !== []) {
	fwrite(STDERR, "O rollback não removeu a massa temporária da busca.\n");
	exit(1);
}

fwrite(STDOUT, "Busca textual: criação, indexação de versões e rollback validados.\n");
