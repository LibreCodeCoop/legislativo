<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Migration;

use OCA\Legislativo\Service\AuthorizationService;
use OCP\IGroupManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class InstallOperatorGroup implements IRepairStep {
	public function __construct(private IGroupManager $groupManager) {
	}

	public function getName(): string {
		return 'Criar os grupos funcionais da Gestão Legislativa';
	}

	public function run(IOutput $output): void {
		foreach ([AuthorizationService::OPERATORS_GROUP, AuthorizationService::PRESIDENTS_GROUP] as $groupId) {
			if ($this->groupManager->get($groupId) === null) {
				$this->groupManager->createGroup($groupId);
				$output->info('Grupo ' . $groupId . ' criado.');
			}
		}
	}
}
