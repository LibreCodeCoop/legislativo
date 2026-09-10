<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
require_once __DIR__ . '/../../../lib/base.php';

use OCA\Legislativo\Service\PublicPortalService;

/** @var PublicPortalService $portal */
$portal = \OC::$server->get(PublicPortalService::class);
$profiles = $portal->parliamentarians();
if ($profiles === []) {
	fwrite(STDERR, "Nenhum perfil parlamentar ativo disponível.\n");
	exit(1);
}
foreach ($profiles as $profile) {
	if (array_intersect(['userUid', 'createdAt', 'updatedAt'], array_keys($profile)) !== []) {
		fwrite(STDERR, "O perfil público expôs campos internos.\n");
		exit(1);
	}
}

$detail = $portal->parliamentarian((int)$profiles[0]['id']);
if (($detail['profile']['displayName'] ?? '') === '' || !is_array($detail['matters'] ?? null)) {
	fwrite(STDERR, "O detalhe público do parlamentar está incompleto.\n");
	exit(1);
}

$results = $portal->globalSearch('transparencia');
$kinds = array_unique(array_column($results, 'kind'));
if (!in_array('matter', $kinds, true) || !in_array('norm', $kinds, true)) {
	fwrite(STDERR, "A busca global não reuniu matérias e normas.\n");
	exit(1);
}

fwrite(STDOUT, 'Portal público: ' . count($profiles) . " perfis sanitizados e busca global validados.\n");
