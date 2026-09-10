<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Service;

use OCA\Legislativo\Exception\AuthorizationException;
use OCP\IGroupManager;
use OCP\IUserSession;

class AuthorizationService {
	public const OPERATORS_GROUP = 'legislativo_operadores';
	public const PRESIDENTS_GROUP = 'legislativo_presidentes';

	public function __construct(
		private IUserSession $userSession,
		private IGroupManager $groupManager,
	) {
	}

	public function getUserId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new AuthorizationException('É necessário estar autenticado.');
		}

		return $user->getUID();
	}

	public function canWrite(): bool {
		$uid = $this->getUserId();
		return $this->groupManager->isAdmin($uid)
			|| $this->groupManager->isInGroup($uid, self::OPERATORS_GROUP);
	}

	public function requireWrite(): string {
		$uid = $this->getUserId();
		if (!$this->canWrite()) {
			throw new AuthorizationException(
				'Somente administradores ou integrantes do grupo ' . self::OPERATORS_GROUP . ' podem alterar registros legislativos.',
			);
		}

		return $uid;
	}

	public function canPreside(): bool {
		$uid = $this->getUserId();
		return $this->groupManager->isAdmin($uid)
			|| $this->groupManager->isInGroup($uid, self::PRESIDENTS_GROUP);
	}

	public function requirePresident(): string {
		$uid = $this->getUserId();
		if (!$this->canPreside()) {
			throw new AuthorizationException(
				'Somente administradores ou integrantes do grupo ' . self::PRESIDENTS_GROUP . ' podem conduzir a sessão.',
			);
		}

		return $uid;
	}
}
