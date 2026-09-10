<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Legislativo\Tests\Unit\Service;

use OCA\Legislativo\Exception\ValidationException;
use OCA\Legislativo\Service\MatterValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MatterValidatorTest extends TestCase {
	private MatterValidator $validator;

	protected function setUp(): void {
		$this->validator = new MatterValidator();
	}

	// --- validateMatter ---

	public function testValidateMatterFullCreation(): void {
		$data = [
			'type' => 'PL',
			'number' => '123',
			'year' => '2026',
			'subject' => 'Projeto de lei teste',
		];

		$result = $this->validator->validateMatter($data, false);

		$this->assertSame('PL', $result['type']);
		$this->assertSame(123, $result['number']);
		$this->assertSame(2026, $result['year']);
		$this->assertSame('Projeto de lei teste', $result['subject']);
		$this->assertSame('draft', $result['status']);
		$this->assertInstanceOf(\DateTime::class, $result['presentedAt']);
	}

	public function testValidateMatterPartialUpdate(): void {
		$data = ['subject' => 'Novo assunto'];

		$result = $this->validator->validateMatter($data, true);

		$this->assertSame('Novo assunto', $result['subject']);
		$this->assertArrayNotHasKey('type', $result);
		$this->assertArrayNotHasKey('number', $result);
	}

	public function testValidateMatterMissingTypeThrows(): void {
		$this->expectException(ValidationException::class);

		$this->validator->validateMatter(['number' => 1, 'year' => 2026, 'subject' => 'Teste'], false);
	}

	public function testValidateMatterInvalidNumberThrows(): void {
		$this->expectException(ValidationException::class);

		$this->validator->validateMatter(['type' => 'PL', 'number' => 0, 'year' => 2026, 'subject' => 'Teste'], false);
	}

	public function testValidateMatterInvalidYearThrows(): void {
		$this->expectException(ValidationException::class);

		$this->validator->validateMatter(['type' => 'PL', 'number' => 1, 'year' => 1800, 'subject' => 'Teste'], false);
	}

	public function testValidateMatterInvalidStatusThrows(): void {
		$this->expectException(ValidationException::class);

		$this->validator->validateMatter([
			'type' => 'PL', 'number' => 1, 'year' => 2026,
			'subject' => 'Teste', 'status' => 'invalid_status',
		], false);
	}

	public static function validStatusProvider(): array {
		return array_map(fn (string $s): array => [$s], [
			'draft', 'received', 'protocolled', 'processing',
			'agenda', 'approved', 'rejected', 'archived',
		]);
	}

	#[DataProvider('validStatusProvider')]
	public function testValidateMatterAcceptsValidStatuses(string $status): void {
		$result = $this->validator->validateMatter([
			'type' => 'PL', 'number' => 1, 'year' => 2026,
			'subject' => 'Teste', 'status' => $status,
		], false);

		$this->assertSame($status, $result['status']);
	}

	// --- validateProtocol ---

	public function testValidateProtocol(): void {
		$result = $this->validator->validateProtocol(['sender' => 'João Silva', 'year' => '2026']);

		$this->assertSame('João Silva', $result['sender']);
		$this->assertSame(2026, $result['year']);
		$this->assertNull($result['submissionId']);
		$this->assertNull($result['fileId']);
	}

	public function testValidateProtocolMissingSenderThrows(): void {
		$this->expectException(ValidationException::class);

		$this->validator->validateProtocol([]);
	}

	// --- validateProceeding ---

	public function testValidateProceeding(): void {
		$result = $this->validator->validateProceeding([
			'recipient' => 'Comissão de Finanças',
			'objective' => 'Parecer técnico',
			'deadlineDays' => 15,
		]);

		$this->assertSame('Comissão de Finanças', $result['recipient']);
		$this->assertSame('Parecer técnico', $result['objective']);
		$this->assertSame(15, $result['deadlineDays']);
		$this->assertTrue($result['businessDays']);
		$this->assertNull($result['notes']);
	}

	public function testValidateProceedingExcessiveDeadlineThrows(): void {
		$this->expectException(ValidationException::class);

		$this->validator->validateProceeding([
			'recipient' => 'Teste',
			'objective' => 'Teste',
			'deadlineDays' => 5000,
		]);
	}

	// --- validateBatchRecipients ---

	public function testValidateBatchRecipientsFromString(): void {
		$result = $this->validator->validateBatchRecipients([
			'recipients' => "João;Maria;Pedro",
		]);

		$this->assertCount(3, $result);
		$this->assertSame(['João', 'Maria', 'Pedro'], $result);
	}

	public function testValidateBatchRecipientsDeduplicates(): void {
		$result = $this->validator->validateBatchRecipients([
			'recipients' => ['João', 'Maria', 'João'],
		]);

		$this->assertCount(2, $result);
	}

	public function testValidateBatchRecipientsEmptyThrows(): void {
		$this->expectException(ValidationException::class);

		$this->validator->validateBatchRecipients(['recipients' => []]);
	}

	public function testValidateBatchRecipientsOverFiftyThrows(): void {
		$this->expectException(ValidationException::class);

		$recipients = array_map(fn (int $i): string => "Dest $i", range(1, 51));
		$this->validator->validateBatchRecipients(['recipients' => $recipients]);
	}

	// --- requiredString / optionalString ---

	public function testRequiredStringTrimsWhitespace(): void {
		$result = $this->validator->requiredString(['name' => '  João  '], 'name', 'Nome', 255);

		$this->assertSame('João', $result);
	}

	public function testOptionalStringReturnsNullForEmpty(): void {
		$this->assertNull($this->validator->optionalString(['name' => ''], 'name', 255));
		$this->assertNull($this->validator->optionalString([], 'name', 255));
	}

	// --- optionalPositiveInt ---

	public function testOptionalPositiveIntReturnsNullForMissing(): void {
		$this->assertNull($this->validator->optionalPositiveInt([], 'id'));
	}

	public function testOptionalPositiveIntReturnsValue(): void {
		$this->assertSame(42, $this->validator->optionalPositiveInt(['id' => '42'], 'id'));
	}

	public function testOptionalPositiveIntZeroThrows(): void {
		$this->expectException(ValidationException::class);

		$this->validator->optionalPositiveInt(['id' => '0'], 'id');
	}

	// --- parseDate ---

	public function testParseDateValidFormat(): void {
		$result = $this->validator->parseDate('2026-08-04', 'date');

		$this->assertInstanceOf(\DateTime::class, $result);
		$this->assertSame('2026-08-04', $result->format('Y-m-d'));
	}

	public function testParseDateEmptyReturnsNull(): void {
		$this->assertNull($this->validator->parseDate('', 'date'));
	}

	public function testParseDateInvalidFormatThrows(): void {
		$this->expectException(ValidationException::class);

		$this->validator->parseDate('04/08/2026', 'date');
	}
}
