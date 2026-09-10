<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
require_once __DIR__ . '/../../../lib/base.php';

use OCA\Legislativo\Db\ProceedingMapper;
use OCA\Legislativo\Service\DeadlineService;
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
/** @var ProceedingMapper $mapper */
$mapper = $server->get(ProceedingMapper::class);
/** @var DeadlineService $service */
$service = $server->get(DeadlineService::class);

$open = $mapper->searchDeadlines(['state' => 'open'], new DateTimeImmutable('now', new DateTimeZone('UTC')), 1);
if ($open === []) {
	fwrite(STDERR, "Nenhuma tramitação aberta disponível para o teste.\n");
	exit(1);
}

$id = $open[0]->getId();
$originalResult = $open[0]->getResult();
$originalAnsweredAt = $open[0]->getAnsweredAt()?->format(DATE_ATOM);
$db->beginTransaction();
try {
	$updated = $service->complete($id, ['result' => 'Validação transacional']);
	if ($updated->getAnsweredAt() === null || $updated->getResult() !== 'Validação transacional') {
		throw new RuntimeException('A conclusão não foi persistida dentro da transação.');
	}
	if ($mapper->find($id)->getAnsweredAt() === null) {
		throw new RuntimeException('A leitura após a atualização não encontrou a conclusão.');
	}
} finally {
	$db->rollBack();
}

$restored = $mapper->find($id);
if ($restored->getAnsweredAt()?->format(DATE_ATOM) !== $originalAnsweredAt || $restored->getResult() !== $originalResult) {
	fwrite(STDERR, "O rollback não restaurou a tramitação {$id}.\n");
	exit(1);
}

fwrite(STDOUT, "DeadlineService: conclusão e rollback validados na tramitação {$id}.\n");
