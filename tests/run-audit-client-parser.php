<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
require_once __DIR__ . '/../lib/Service/AuditClientParser.php';

use OCA\Legislativo\Service\AuditClientParser;

$parser = new AuditClientParser();
$cases = [
	['Mozilla/5.0 (X11; Linux x86_64; rv:153.0) Gecko/20100101 Firefox/153.0', ['Firefox 153.0', 'Linux', 'Computador']],
	['Mozilla/5.0 (Linux; Android 15; Pixel) AppleWebKit/537.36 Chrome/140.0.0.0 Mobile Safari/537.36', ['Chrome 140.0.0.0', 'Android 15', 'Celular']],
	['Mozilla/5.0 (iPad; CPU OS 18_0 like Mac OS X) Version/18.0 Mobile Safari/604.1', ['Safari 18.0', 'iPadOS 18.0', 'Tablet']],
];

foreach ($cases as [$userAgent, $expected]) {
	$actual = $parser->parse($userAgent);
	if ([$actual['browser'], $actual['operatingSystem'], $actual['device']] !== $expected) {
		fwrite(STDERR, 'Cliente inesperado: ' . json_encode($actual, JSON_UNESCAPED_UNICODE) . "\n");
		exit(1);
	}
}

fwrite(STDOUT, "AuditClientParser: 3 casos válidos.\n");
