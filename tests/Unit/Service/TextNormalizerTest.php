<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Legislativo\Tests\Unit\Service;

use OCA\Legislativo\Service\TextNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TextNormalizerTest extends TestCase {
	private TextNormalizer $normalizer;

	protected function setUp(): void {
		$this->normalizer = new TextNormalizer();
	}

	public static function singularPluralProvider(): array {
		return [
			'comissão/comissões' => ['comissão', 'comissões'],
			'cidadão/cidadãos' => ['cidadão', 'cidadãos'],
			'municipal/municipais' => ['municipal', 'municipais'],
			'lei/leis' => ['lei', 'leis'],
			'projeto/projetos' => ['projeto', 'projetos'],
		];
	}

	#[DataProvider('singularPluralProvider')]
	public function testQueryTokensEquivalentForSingularAndPlural(string $singular, string $plural): void {
		$this->assertSame(
			$this->normalizer->queryTokens($singular),
			$this->normalizer->queryTokens($plural),
			"Variações não equivalentes: $singular / $plural.",
		);
	}

	public function testDocumentContainsAllQueryTokens(): void {
		$document = $this->normalizer->document(['<p>Comissões de Transparência Municipais</p>']);

		foreach ($this->normalizer->queryTokens('comissao transparencia municipal') as $token) {
			$this->assertStringContainsString($token, $document, "Token $token ausente do documento normalizado.");
		}
	}

	public function testNormalizeStripsHtmlEntities(): void {
		$result = $this->normalizer->normalize('&lt;script&gt;alert(1)&lt;/script&gt;');

		$this->assertStringNotContainsString('<script>', $result);
		$this->assertStringNotContainsString('&lt;', $result);
	}

	public function testNormalizeLowercasesAndTrims(): void {
		$result = $this->normalizer->normalize('  Município de Conchal/SP  ');

		$this->assertSame('municipio de conchal sp', $result);
	}

	public function testDocumentWithEmptyValues(): void {
		$document = $this->normalizer->document(['test', null, '', 42]);

		$this->assertStringContainsString('test', $document);
		$this->assertStringContainsString('42', $document);
	}

	public function testQueryTokensEmptyString(): void {
		$this->assertSame([], $this->normalizer->queryTokens(''));
	}

	public function testQueryTokensDeduplicates(): void {
		$tokens = $this->normalizer->queryTokens('lei lei lei');
		$this->assertCount(1, $tokens);
	}
}
