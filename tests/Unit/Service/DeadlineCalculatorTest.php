<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Tests\Unit\Service;

use OCA\Legislativo\Service\DeadlineCalculator;
use PHPUnit\Framework\TestCase;

class DeadlineCalculatorTest extends TestCase {
	private DeadlineCalculator $calculator;

	protected function setUp(): void {
		$this->calculator = new DeadlineCalculator();
	}

	public function testCountsCalendarDaysExcludingStartDate(): void {
		$start = new \DateTimeImmutable('2026-08-07 12:00:00 UTC');
		$result = $this->calculator->calculate($start, 3, false);

		self::assertSame('2026-08-10', $result->format('Y-m-d'));
	}

	public function testSkipsWeekendForBusinessDays(): void {
		$start = new \DateTimeImmutable('2026-08-07 12:00:00 UTC');
		$result = $this->calculator->calculate($start, 2, true);

		self::assertSame('2026-08-11', $result->format('Y-m-d'));
	}

	public function testZeroDaysKeepsStartDate(): void {
		$start = new \DateTimeImmutable('2026-08-07 12:00:00 UTC');

		self::assertSame($start, $this->calculator->calculate($start, 0, true));
	}

	public function testSkipsConfiguredHolidayAndRecess(): void {
		$start = new \DateTimeImmutable('2026-08-07 12:00:00 UTC');
		$periods = [['startsOn' => '2026-08-10', 'endsOn' => '2026-08-10', 'active' => true]];
		$result = $this->calculator->calculate($start, 2, true, $periods);

		self::assertSame('2026-08-12', $result->format('Y-m-d'));
	}
}
