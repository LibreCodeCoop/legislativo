<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
require_once __DIR__ . '/../lib/Service/TextNormalizer.php';

use OCA\Legislativo\Service\TextNormalizer;

$normalizer = new TextNormalizer();
$pairs = [['comissão', 'comissões'], ['cidadão', 'cidadãos'], ['municipal', 'municipais'], ['lei', 'leis'], ['projeto', 'projetos']];
foreach ($pairs as [$singular, $plural]) {
	if ($normalizer->queryTokens($singular) !== $normalizer->queryTokens($plural)) {
		fwrite(STDERR, "Variações não equivalentes: {$singular} / {$plural}.\n");
		exit(1);
	}
}
$document = $normalizer->document(['<p>Comissões de Transparência Municipais</p>']);
foreach ($normalizer->queryTokens('comissao transparencia municipal') as $token) {
	if (!str_contains($document, $token)) {
		fwrite(STDERR, "Token {$token} ausente do documento normalizado.\n");
		exit(1);
	}
}

fwrite(STDOUT, "TextNormalizer: 8 casos válidos.\n");
