<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Legislativo\Service;

use OCA\Legislativo\Db\Attendance;
use OCA\Legislativo\Db\AttendanceMapper;
use OCA\Legislativo\Db\LegislativeSession;
use OCA\Legislativo\Db\LegislativeSessionMapper;
use OCA\Legislativo\Db\SpeakerEntry;
use OCA\Legislativo\Db\SpeakerEntryMapper;
use OCA\Legislativo\Exception\ValidationException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUserManager;

/**
 * Gerencia inscrições e transições de oradores em sessões legislativas.
 */
class SpeakerService {
	public function __construct(
		private LegislativeSessionMapper $sessions,
		private AttendanceMapper $attendance,
		private SpeakerEntryMapper $speakers,
		private AuthorizationService $authorization,
		private AuditService $audit,
		private IUserManager $userManager,
		private TimerService $timerService,
	) {
	}

	/**
	 * Lista os oradores de uma sessão.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function findBySession(int $sessionId): array {
		return array_map(
			static fn (SpeakerEntry $entry): array => $entry->jsonSerialize(),
			$this->speakers->findBySession($sessionId),
		);
	}

	/**
	 * Registra inscrição para uso da palavra.
	 */
	public function requestSpeech(int $sessionId, string $topic, int $seconds = 300): SpeakerEntry {
		$uid = $this->authorization->getUserId();
		$session = $this->sessions->find($sessionId);

		if ($session->getStatus() !== 'open') {
			throw new ValidationException('A inscrição só é permitida durante sessão aberta.');
		}

		if (!$this->isPresent($sessionId, $uid)) {
			throw new ValidationException('Somente parlamentares presentes podem se inscrever.');
		}

		foreach ($this->speakers->findBySession($sessionId) as $entry) {
			if ($entry->getUserUid() === $uid && in_array($entry->getStatus(), ['waiting', 'speaking'], true)) {
				throw new ValidationException('Você já está inscrito para falar.');
			}
		}

		$user = $this->userManager->get($uid);
		$entry = new SpeakerEntry();
		$entry->setSessionId($sessionId);
		$entry->setUserUid($uid);
		$entry->setDisplayName($user?->getDisplayName() ?? $uid);
		$entry->setTopic(($topic = trim($topic)) === '' ? null : substr($topic, 0, 512));
		$entry->setPosition($this->speakers->nextPosition($sessionId));
		$entry->setStatus('waiting');
		$entry->setAllottedSeconds(max(30, min($seconds, 3600)));
		$entry->setRequestedAt($this->now());

		$saved = $this->speakers->insert($entry);
		$this->audit->record($uid, 'session', $sessionId, 'speaker.request', null, $saved->jsonSerialize());

		return $saved;
	}

	/**
	 * Transiciona estado de um orador (start/finish).
	 */
	public function transition(int $sessionId, int $speakerId, string $action): SpeakerEntry {
		$uid = $this->authorization->requirePresident();
		$session = $this->sessions->find($sessionId);
		$entry = $this->speakers->find($speakerId);

		if ($entry->getSessionId() !== $sessionId || $session->getStatus() !== 'open') {
			throw new ValidationException('Inscrição ou sessão inválida.');
		}

		$before = $entry->jsonSerialize();

		if ($action === 'start') {
			if ($entry->getStatus() !== 'waiting') {
				throw new ValidationException('A inscrição não está aguardando chamada.');
			}

			foreach ($this->speakers->findBySession($sessionId) as $other) {
				if ($other->getStatus() === 'speaking') {
					throw new ValidationException('Já existe um orador com a palavra.');
				}
			}

			$entry->setStatus('speaking');
			$entry->setStartedAt($this->now());
			$this->timerService->applyTimer($session, 'start', $entry->getAllottedSeconds(), 'Orador: ' . $entry->getDisplayName());
		} elseif ($action === 'finish') {
			if ($entry->getStatus() !== 'speaking') {
				throw new ValidationException('Este orador não está com a palavra.');
			}

			$entry->setStatus('done');
			$entry->setEndedAt($this->now());
			$this->timerService->applyTimer($session, 'stop', null, null);
		} else {
			throw new ValidationException('Ação de orador inválida.');
		}

		$saved = $this->speakers->update($entry);
		$this->audit->record($uid, 'session', $sessionId, 'speaker.' . $action, $before, $saved->jsonSerialize());

		return $saved;
	}

	private function isPresent(int $sessionId, string $uid): bool {
		try {
			return $this->attendance->findByUser($sessionId, $uid)->getPresent();
		} catch (DoesNotExistException) {
			return false;
		}
	}

	private function now(): \DateTime {
		return new \DateTime('now', new \DateTimeZone('UTC'));
	}
}
