<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Service;

class AuditClientParser {
	/** @return array{browser: string, operatingSystem: string, device: string} */
	public function parse(?string $userAgent): array {
		$userAgent = trim((string)$userAgent);
		if ($userAgent === '') {
			return ['browser' => 'Não informado', 'operatingSystem' => 'Não informado', 'device' => 'Não informado'];
		}

		$browser = $this->firstMatch($userAgent, [
			'/Edg\/([\d.]+)/' => 'Microsoft Edge $1',
			'/Firefox\/([\d.]+)/' => 'Firefox $1',
			'/OPR\/([\d.]+)/' => 'Opera $1',
			'/Chrome\/([\d.]+)/' => 'Chrome $1',
			'/Version\/([\d.]+).*Safari\//' => 'Safari $1',
		]) ?? 'Outro navegador';
		$operatingSystem = $this->firstMatch($userAgent, [
			'/Android\s+([\d.]+)/i' => 'Android $1',
			'/iPhone OS ([\d_]+)/i' => 'iOS $1',
			'/iPad.*OS ([\d_]+)/i' => 'iPadOS $1',
			'/Windows NT 10\.0/i' => 'Windows 10/11',
			'/Mac OS X ([\d_]+)/i' => 'macOS $1',
			'/Linux/i' => 'Linux',
		]) ?? 'Outro sistema';
		$operatingSystem = str_replace('_', '.', $operatingSystem);

		$device = preg_match('/bot|crawler|spider/i', $userAgent) ? 'Robô'
			: (preg_match('/iPad|Tablet/i', $userAgent) ? 'Tablet'
				: (preg_match('/Mobile|Android|iPhone/i', $userAgent) ? 'Celular' : 'Computador'));

		return ['browser' => $browser, 'operatingSystem' => $operatingSystem, 'device' => $device];
	}

	/** @param array<string, string> $patterns */
	private function firstMatch(string $value, array $patterns): ?string {
		foreach ($patterns as $pattern => $label) {
			if (preg_match($pattern, $value, $matches) === 1) {
				return str_replace('$1', $matches[1] ?? '', $label);
			}
		}
		return null;
	}
}
