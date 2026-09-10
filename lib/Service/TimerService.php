<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Legislativo\Service;

use OCA\Legislativo\Db\LegislativeSession;
use OCA\Legislativo\Db\LegislativeSessionMapper;
use OCA\Legislativo\Exception\ValidationException;

/**
 * Gerencia o cronômetro sincronizado das sessões legislativas.
 */
class TimerService {
	public function __construct(
		private LegislativeSessionMapper $sessions,
		private AuthorizationService $authorization,
		private AuditService $audit,
	) {
	}

	/**
	 * Controla o cronômetro (start/stop/reset).
	 */
	public function timer(int $sessionId, string $action, ?int $duration, ?string $label): LegislativeSession {
		$uid = $this->authorization->requirePresident();
		$session = $this->sessions->find($sessionId);

		if ($session->getStatus() !== 'open') {
			throw new ValidationException('O cronômetro requer uma sessão aberta.');
		}

		$before = $session->jsonSerialize();
		$this->applyTimer($session, $action, $duration, $label);
		$this->audit->record($uid, 'session', $sessionId, 'timer.' . $action, $before, $session->jsonSerialize());

		return $session;
	}

	/**
	 * Aplica transição no cronômetro.
	 *
	 * Método público para uso pelo SpeakerService.
	 */
	public function applyTimer(LegislativeSession $session, string $action, ?int $duration, ?string $label): void {
		if ($action === 'start') {
			if ($duration === null || $duration < 1 || $duration > 7200) {
				throw new ValidationException('Informe duração entre 1 e 7200 segundos.');
			}

			$session->setTimerStatus('running');
			$session->setTimerDuration($duration);
			$session->setTimerLabel(substr(trim((string) $label), 0, 255));
			$session->setTimerStartedAt($this->now());
		} elseif ($action === 'stop') {
			if ($session->getTimerStatus() === 'running'
				&& $session->getTimerStartedAt() !== null
				&& $session->getTimerDuration() !== null
			) {
				$elapsed = max(0, time() - $session->getTimerStartedAt()->getTimestamp());
				$session->setTimerDuration(max(0, $session->getTimerDuration() - $elapsed));
				$session->setTimerStartedAt($this->now());
			}

			$session->setTimerStatus('stopped');
		} elseif ($action === 'reset') {
			$session->setTimerStatus('idle');
			$session->setTimerDuration(null);
			$session->setTimerLabel(null);
			$session->setTimerStartedAt(null);
		} else {
			throw new ValidationException('Ação de cronômetro inválida.');
		}

		$session->setUpdatedAt($this->now());
		$this->sessions->update($session);
	}

	private function now(): \DateTime {
		return new \DateTime('now', new \DateTimeZone('UTC'));
	}
}
