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

	public function testCalculateSimpleDays(): void {
		$start = new \DateTimeImmutable('2026-08-04');
		$result = $this->calculator->calculate($start, 5, false);

		$this->assertSame('2026-08-09', $result->format('Y-m-d'));
	}

	public function testCalculateBusinessDaysSkipsWeekends(): void {
		// Segunda-feira 2026-08-03
		$start = new \DateTimeImmutable('2026-08-03');
		$result = $this->calculator->calculate($start, 5, true);

		// 5 dias úteis a partir de segunda = segunda seguinte
		$this->assertSame('2026-08-10', $result->format('Y-m-d'));
	}

	public function testCalculateWithNonWorkingPeriods(): void {
		$start = new \DateTimeImmutable('2026-08-01');
		$periods = [
			['startsOn' => '2026-08-04', 'endsOn' => '2026-08-04', 'active' => true],
		];
		$result = $this->calculator->calculate($start, 2, true, $periods);

		// Dia 4 é feriado, então pula
		$this->assertSame('2026-08-05', $result->format('Y-m-d'));
	}

	public function testCalculateZeroDays(): void {
		$start = new \DateTimeImmutable('2026-08-04');
		$result = $this->calculator->calculate($start, 0, false);

		$this->assertSame('2026-08-04', $result->format('Y-m-d'));
	}

	public function testCalculateNegativeDaysThrows(): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->calculator->calculate(new \DateTimeImmutable('2026-08-04'), -1, false);
	}

	public function testCalculateIgnoresInactivePeriods(): void {
		$start = new \DateTimeImmutable('2026-08-03');
		$periods = [
			['startsOn' => '2026-08-04', 'endsOn' => '2026-08-04', 'active' => false],
		];
		$result = $this->calculator->calculate($start, 2, true, $periods);

		// Período inativo não deve ser excluído
		$this->assertSame('2026-08-05', $result->format('Y-m-d'));
	}
}
