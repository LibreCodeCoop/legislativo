<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Legislativo\Tests\Unit\Service;

use OCA\Legislativo\Service\AuditClientParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AuditClientParserTest extends TestCase {
	private AuditClientParser $parser;

	protected function setUp(): void {
		$this->parser = new AuditClientParser();
	}

	public static function userAgentProvider(): array {
		return [
			'Firefox desktop' => [
				'Mozilla/5.0 (X11; Linux x86_64; rv:153.0) Gecko/20100101 Firefox/153.0',
				['Firefox 153.0', 'Linux', 'Computador'],
			],
			'Chrome Android' => [
				'Mozilla/5.0 (Linux; Android 15; Pixel) AppleWebKit/537.36 Chrome/140.0.0.0 Mobile Safari/537.36',
				['Chrome 140.0.0.0', 'Android 15', 'Celular'],
			],
			'Safari iPad' => [
				'Mozilla/5.0 (iPad; CPU OS 18_0 like Mac OS X) Version/18.0 Mobile Safari/604.1',
				['Safari 18.0', 'iPadOS 18.0', 'Tablet'],
			],
		];
	}

	#[DataProvider('userAgentProvider')]
	public function testParseUserAgent(string $userAgent, array $expected): void {
		$actual = $this->parser->parse($userAgent);

		$this->assertSame($expected[0], $actual['browser']);
		$this->assertSame($expected[1], $actual['operatingSystem']);
		$this->assertSame($expected[2], $actual['device']);
	}
}
