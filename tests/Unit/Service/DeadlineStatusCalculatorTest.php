<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Tests\Unit\Service;

use OCA\Legislativo\Service\DeadlineStatusCalculator;
use PHPUnit\Framework\TestCase;

class DeadlineStatusCalculatorTest extends TestCase {
	private DeadlineStatusCalculator $calculator;
	private \DateTimeImmutable $now;

	protected function setUp(): void {
		$this->calculator = new DeadlineStatusCalculator();
		$this->now = new \DateTimeImmutable('2026-08-06 16:00:00 UTC');
	}

	public function testClassifiesCompletedBeforeDeadline(): void {
		self::assertSame('completed', $this->calculator->classify(
			new \DateTimeImmutable('2026-08-10 12:00:00 UTC'),
			new \DateTimeImmutable('2026-08-05 12:00:00 UTC'),
			$this->now,
		));
	}

	public function testClassifiesOpenDeadlineStates(): void {
		self::assertSame('no_deadline', $this->calculator->classify(null, null, $this->now));
		self::assertSame('overdue', $this->calculator->classify(new \DateTimeImmutable('2026-08-05 12:00:00 UTC'), null, $this->now));
		self::assertSame('due_today', $this->calculator->classify(new \DateTimeImmutable('2026-08-06 09:00:00 UTC'), null, $this->now));
		self::assertSame('upcoming', $this->calculator->classify(new \DateTimeImmutable('2026-08-07 12:00:00 UTC'), null, $this->now));
	}

	public function testCalculatesSignedCalendarDaysRemaining(): void {
		self::assertSame(-2, $this->calculator->daysRemaining(new \DateTimeImmutable('2026-08-04 12:00:00 UTC'), $this->now));
		self::assertSame(0, $this->calculator->daysRemaining(new \DateTimeImmutable('2026-08-06 09:00:00 UTC'), $this->now));
		self::assertSame(3, $this->calculator->daysRemaining(new \DateTimeImmutable('2026-08-09 12:00:00 UTC'), $this->now));
		self::assertNull($this->calculator->daysRemaining(null, $this->now));
	}
}
