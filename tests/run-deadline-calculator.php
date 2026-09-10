<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
require_once __DIR__ . '/../lib/Service/DeadlineCalculator.php';

use OCA\Legislativo\Service\DeadlineCalculator;

$calculator = new DeadlineCalculator();
$cases = [
	['2026-08-07', 3, false, '2026-08-10'],
	['2026-08-07', 2, true, '2026-08-11'],
	['2026-08-07', 0, true, '2026-08-07'],
];

foreach ($cases as [$start, $days, $businessDays, $expected]) {
	$actual = $calculator
		->calculate(new DateTimeImmutable($start . ' 12:00:00 UTC'), $days, $businessDays)
		->format('Y-m-d');
	if ($actual !== $expected) {
		fwrite(STDERR, "Esperado {$expected}; obtido {$actual}.\n");
		exit(1);
	}
}

$withHoliday = $calculator->calculate(
	new DateTimeImmutable('2026-08-07 12:00:00 UTC'),
	2,
	true,
	[['startsOn' => '2026-08-10', 'endsOn' => '2026-08-10', 'active' => true]],
)->format('Y-m-d');
if ($withHoliday !== '2026-08-12') {
	fwrite(STDERR, "Calendário legislativo não foi aplicado ao prazo.\n");
	exit(1);
}

fwrite(STDOUT, "DeadlineCalculator: 4 casos válidos.\n");
