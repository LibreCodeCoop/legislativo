<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
require_once __DIR__ . '/../lib/Service/DeadlineStatusCalculator.php';

use OCA\Legislativo\Service\DeadlineStatusCalculator;

$calculator = new DeadlineStatusCalculator();
$now = new DateTimeImmutable('2026-08-06 16:00:00 UTC');
$cases = [
	[null, null, 'no_deadline'],
	['2026-08-05 12:00:00 UTC', null, 'overdue'],
	['2026-08-06 09:00:00 UTC', null, 'due_today'],
	['2026-08-07 12:00:00 UTC', null, 'upcoming'],
	['2026-08-07 12:00:00 UTC', '2026-08-06 10:00:00 UTC', 'completed'],
];

foreach ($cases as [$dueAt, $answeredAt, $expected]) {
	$actual = $calculator->classify(
		$dueAt === null ? null : new DateTimeImmutable($dueAt),
		$answeredAt === null ? null : new DateTimeImmutable($answeredAt),
		$now,
	);
	if ($actual !== $expected) {
		fwrite(STDERR, "Esperado {$expected}; obtido {$actual}.\n");
		exit(1);
	}
}

if ($calculator->daysRemaining(new DateTimeImmutable('2026-08-09 12:00:00 UTC'), $now) !== 3) {
	fwrite(STDERR, "Contagem de dias restantes inválida.\n");
	exit(1);
}

fwrite(STDOUT, "DeadlineStatusCalculator: 6 casos válidos.\n");
