<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Tests\Unit\Service;

use OCA\Legislativo\Service\AuditClientParser;
use PHPUnit\Framework\TestCase;

class AuditClientParserTest extends TestCase {
	private AuditClientParser $parser;

	protected function setUp(): void {
		$this->parser = new AuditClientParser();
	}

	public function testParsesFirefoxOnLinuxDesktop(): void {
		$result = $this->parser->parse('Mozilla/5.0 (X11; Linux x86_64; rv:153.0) Gecko/20100101 Firefox/153.0');
		self::assertSame('Firefox 153.0', $result['browser']);
		self::assertSame('Linux', $result['operatingSystem']);
		self::assertSame('Computador', $result['device']);
	}

	public function testParsesChromeOnAndroidPhone(): void {
		$result = $this->parser->parse('Mozilla/5.0 (Linux; Android 15; Pixel) AppleWebKit/537.36 Chrome/140.0.0.0 Mobile Safari/537.36');
		self::assertSame('Chrome 140.0.0.0', $result['browser']);
		self::assertSame('Android 15', $result['operatingSystem']);
		self::assertSame('Celular', $result['device']);
	}

	public function testHandlesMissingUserAgent(): void {
		self::assertSame([
			'browser' => 'Não informado',
			'operatingSystem' => 'Não informado',
			'device' => 'Não informado',
		], $this->parser->parse(null));
	}
}
