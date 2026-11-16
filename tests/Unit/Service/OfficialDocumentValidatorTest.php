<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Tests\Unit\Service;

use OCA\Legislativo\Exception\ValidationException;
use OCA\Legislativo\Service\OfficialDocumentValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OfficialDocumentValidatorTest extends TestCase {
	private OfficialDocumentValidator $validator;

	protected function setUp(): void {
		$this->validator = new OfficialDocumentValidator();
	}

	/** @return array<string, array{string}> */
	public static function kindProvider(): array {
		return [
			'memorando' => ['memorandum'],
			'ofício' => ['official_letter'],
			'circular' => ['circular'],
		];
	}

	#[DataProvider('kindProvider')]
	public function testAcceptsSupportedKinds(string $kind): void {
		$result = $this->validator->validate([
			'kind' => $kind,
			'year' => 2026,
			'subject' => 'Assunto institucional',
			'body' => 'Conteúdo do documento.',
			'recipients' => $kind === 'circular' ? ['Setor A', 'Setor B'] : ['Destinatário'],
		]);

		$this->assertSame($kind, $result['kind']);
		$this->assertSame(2026, $result['year']);
	}

	public function testNormalizesRecipientsFromTextAndRemovesDuplicates(): void {
		$result = $this->validator->validate([
			'kind' => 'circular',
			'year' => 2026,
			'subject' => 'Expediente',
			'body' => 'Comunicado geral.',
			'recipients' => "Secretaria;Plenário\nSecretaria",
		]);

		$this->assertSame(['Secretaria', 'Plenário'], $result['recipients']);
	}

	public function testRejectsUnsupportedKind(): void {
		$this->expectException(ValidationException::class);

		$this->validator->validate([
			'kind' => 'email',
			'year' => 2026,
			'subject' => 'Assunto',
			'body' => 'Conteúdo',
			'recipients' => ['Destino'],
		]);
	}

	public function testCircularRequiresAtLeastTwoRecipients(): void {
		$this->expectException(ValidationException::class);

		$this->validator->validate([
			'kind' => 'circular',
			'year' => 2026,
			'subject' => 'Assunto',
			'body' => 'Conteúdo',
			'recipients' => ['Destino único'],
		]);
	}

	public function testRejectsMissingBody(): void {
		$this->expectException(ValidationException::class);

		$this->validator->validate([
			'kind' => 'memorandum',
			'year' => 2026,
			'subject' => 'Assunto',
			'body' => '',
			'recipients' => ['Destino'],
		]);
	}

	public function testRejectsInvalidYear(): void {
		$this->expectException(ValidationException::class);

		$this->validator->validate([
			'kind' => 'official_letter',
			'year' => 1800,
			'subject' => 'Assunto',
			'body' => 'Conteúdo',
			'recipients' => ['Destino'],
		]);
	}
}
