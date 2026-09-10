<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Tests\Unit\Service;

use OCA\Legislativo\Service\TextNormalizer;
use PHPUnit\Framework\TestCase;

class TextNormalizerTest extends TestCase {
	private TextNormalizer $normalizer;

	protected function setUp(): void {
		$this->normalizer = new TextNormalizer();
	}

	public function testRemovesAccentsAndHtml(): void {
		self::assertStringContainsString('transparencia legislativa', $this->normalizer->document(['<p>Transparência legislativa</p>']));
	}

	public function testSingularAndPluralProduceSameSearchToken(): void {
		self::assertSame($this->normalizer->queryTokens('comissão'), $this->normalizer->queryTokens('comissões'));
		self::assertSame($this->normalizer->queryTokens('cidadão'), $this->normalizer->queryTokens('cidadãos'));
		self::assertSame($this->normalizer->queryTokens('municipal'), $this->normalizer->queryTokens('municipais'));
		self::assertSame($this->normalizer->queryTokens('lei'), $this->normalizer->queryTokens('leis'));
	}

	public function testDocumentContainsSearchStems(): void {
		$document = $this->normalizer->document(['Comissões Municipais']);
		foreach ($this->normalizer->queryTokens('comissão municipal') as $token) {
			self::assertStringContainsString($token, $document);
		}
	}
}
