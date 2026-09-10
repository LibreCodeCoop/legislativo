<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Service;

class DeadlineCalculator {
	/**
	 * Calcula o prazo excluindo o dia inicial. Feriados e recessos serão
	 * incorporados quando o calendário legislativo estiver disponível.
	 */
	public function calculate(\DateTimeImmutable $start, int $days, bool $businessDays, array $nonWorkingPeriods = []): \DateTimeImmutable {
		if ($days < 0) {
			throw new \InvalidArgumentException('O prazo não pode ser negativo.');
		}

		$result = $start;
		$remaining = $days;
		while ($remaining > 0) {
			$result = $result->modify('+1 day');
			$weekday = (int)$result->format('N');
			if (!$businessDays || ($weekday <= 5 && !$this->isExcluded($result, $nonWorkingPeriods))) {
				$remaining--;
			}
		}

		return $result;
	}

	private function isExcluded(\DateTimeImmutable $date, array $periods): bool {
		$current = $date->format('Y-m-d');
		foreach ($periods as $period) {
			if (!($period['active'] ?? true)) continue;
			if ($current >= (string)($period['startsOn'] ?? '') && $current <= (string)($period['endsOn'] ?? '')) return true;
		}
		return false;
	}
}
