<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Service;

class DeadlineStatusCalculator {
	public function classify(?\DateTimeInterface $dueAt, ?\DateTimeInterface $answeredAt, \DateTimeImmutable $now): string {
		if ($answeredAt !== null) {
			return 'completed';
		}
		if ($dueAt === null) {
			return 'no_deadline';
		}

		$due = \DateTimeImmutable::createFromInterface($dueAt)->setTimezone($now->getTimezone());
		if ($due->format('Y-m-d') === $now->format('Y-m-d')) {
			return 'due_today';
		}
		if ($due < $now) {
			return 'overdue';
		}

		return 'upcoming';
	}

	public function daysRemaining(?\DateTimeInterface $dueAt, \DateTimeImmutable $now): ?int {
		if ($dueAt === null) {
			return null;
		}

		$dueDate = \DateTimeImmutable::createFromInterface($dueAt)->setTimezone($now->getTimezone())->setTime(0, 0);
		$today = $now->setTime(0, 0);
		return (int)$today->diff($dueDate)->format('%r%a');
	}
}
