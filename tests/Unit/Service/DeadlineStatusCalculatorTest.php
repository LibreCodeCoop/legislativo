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

	protected function setUp(): void {
		$this->calculator = new DeadlineStatusCalculator();
	}

	public function testOverdueStatus(): void {
		$now = new \DateTimeImmutable('2026-08-05 12:00:00');
		$dueAt = new \DateTime('2026-08-04');

		$this->assertSame('overdue', $this->calculator->classify($dueAt, null, $now));
	}

	public function testDueTodayStatus(): void {
		$now = new \DateTimeImmutable('2026-08-05 12:00:00');
		$dueAt = new \DateTime('2026-08-05');

		$this->assertSame('due_today', $this->calculator->classify($dueAt, null, $now));
	}

	public function testUpcomingStatus(): void {
		$now = new \DateTimeImmutable('2026-08-05 12:00:00');
		$dueAt = new \DateTime('2026-08-06');

		$this->assertSame('upcoming', $this->calculator->classify($dueAt, null, $now));
	}

	public function testCompletedStatusWhenAnswered(): void {
		$now = new \DateTimeImmutable('2026-08-05 12:00:00');
		$dueAt = new \DateTime('2026-08-04');
		$answeredAt = new \DateTime('2026-08-03');

		$this->assertSame('completed', $this->calculator->classify($dueAt, $answeredAt, $now));
	}

	public function testNoDeadlineStatus(): void {
		$now = new \DateTimeImmutable('2026-08-05 12:00:00');

		$this->assertSame('no_deadline', $this->calculator->classify(null, null, $now));
	}

	public function testDaysRemainingWithFutureDate(): void {
		$now = new \DateTimeImmutable('2026-08-05 12:00:00');
		$dueAt = new \DateTime('2026-08-10');

		$this->assertSame(5, $this->calculator->daysRemaining($dueAt, $now));
	}

	public function testDaysRemainingWithPastDate(): void {
		$now = new \DateTimeImmutable('2026-08-05 12:00:00');
		$dueAt = new \DateTime('2026-08-03');

		$this->assertSame(-2, $this->calculator->daysRemaining($dueAt, $now));
	}

	public function testDaysRemainingWithNoDueDate(): void {
		$now = new \DateTimeImmutable('2026-08-05 12:00:00');

		$this->assertNull($this->calculator->daysRemaining(null, $now));
	}
}
