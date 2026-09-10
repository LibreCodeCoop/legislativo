<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Legislativo\Service;

use OCA\Legislativo\Exception\ValidationException;

/**
 * Validação isolada de matérias legislativas.
 *
 * Extraído de MatterService para reduzir acoplamento e facilitar testes unitários.
 */
class MatterValidator {
	private const STATUSES = [
		'draft',
		'received',
		'protocolled',
		'processing',
		'agenda',
		'approved',
		'rejected',
		'archived',
	];

	private \DateTimeZone $utc;

	public function __construct() {
		$this->utc = new \DateTimeZone('UTC');
	}

	/**
	 * Valida os dados de criação ou atualização de matéria.
	 *
	 * @param array<string, mixed> $data
	 * @param bool $partial true para atualização (campos opcionais), false para criação
	 * @return array<string, mixed> valores normalizados
	 */
	public function validateMatter(array $data, bool $partial): array {
		$values = [];

		if (!$partial || array_key_exists('type', $data)) {
			$values['type'] = $this->requiredString($data, 'type', 'Tipo', 64);
		}

		if (!$partial || array_key_exists('number', $data)) {
			$number = (int) ($data['number'] ?? 0);
			if ($number <= 0) {
				throw new ValidationException('Número inválido.', ['number' => 'Informe um número maior que zero.']);
			}
			$values['number'] = $number;
		}

		if (!$partial || array_key_exists('year', $data)) {
			$year = (int) ($data['year'] ?? 0);
			if ($year < 1900 || $year > 2200) {
				throw new ValidationException('Ano inválido.', ['year' => 'Informe um ano entre 1900 e 2200.']);
			}
			$values['year'] = $year;
		}

		if (!$partial || array_key_exists('subject', $data)) {
			$values['subject'] = $this->requiredString($data, 'subject', 'Assunto', 512);
		}

		foreach (['body' => 200000, 'authorUid' => 64, 'theme' => 255, 'quorum' => 64, 'procedure' => 64, 'notes' => 4000] as $field => $max) {
			if (array_key_exists($field, $data)) {
				$values[$field] = $this->optionalString($data, $field, $max);
			}
		}

		if (array_key_exists('status', $data) || !$partial) {
			$status = trim((string) ($data['status'] ?? 'draft'));
			if (!in_array($status, self::STATUSES, true)) {
				throw new ValidationException('Situação inválida.', ['status' => 'Selecione uma situação reconhecida.']);
			}
			$values['status'] = $status;
		}

		if (array_key_exists('presentedAt', $data)) {
			$values['presentedAt'] = $this->parseDate((string) $data['presentedAt'], 'presentedAt');
		} elseif (!$partial) {
			$values['presentedAt'] = new \DateTime('today', $this->utc);
		}

		if (array_key_exists('fileId', $data)) {
			$values['fileId'] = $this->optionalPositiveInt($data, 'fileId');
		}

		return $values;
	}

	/**
	 * Valida os dados de protocolo.
	 *
	 * @param array<string, mixed> $data
	 * @return array{sender: string, year: int, submissionId: int|null, fileId: int|null}
	 */
	public function validateProtocol(array $data): array {
		$sender = $this->requiredString($data, 'sender', 'Remetente', 255);
		$year = (int) ($data['year'] ?? (int) date('Y'));

		if ($year < 1900 || $year > 2200) {
			throw new ValidationException('Ano de protocolo inválido.', ['year' => 'Informe um ano entre 1900 e 2200.']);
		}

		return [
			'sender' => $sender,
			'year' => $year,
			'submissionId' => $this->optionalPositiveInt($data, 'submissionId'),
			'fileId' => $this->optionalPositiveInt($data, 'fileId'),
		];
	}

	/**
	 * Valida os dados de tramitação.
	 *
	 * @param array<string, mixed> $data
	 * @return array{recipient: string, objective: string, deadlineDays: int, businessDays: bool, notes: string|null}
	 */
	public function validateProceeding(array $data): array {
		$recipient = $this->requiredString($data, 'recipient', 'Destinatário', 255);
		$objective = $this->requiredString($data, 'objective', 'Objetivo', 512);
		$deadlineDays = (int) ($data['deadlineDays'] ?? 0);

		if ($deadlineDays < 0 || $deadlineDays > 3650) {
			throw new ValidationException('Prazo inválido.', ['deadlineDays' => 'Informe um prazo entre 0 e 3650 dias.']);
		}

		$businessDays = filter_var($data['businessDays'] ?? true, FILTER_VALIDATE_BOOL);
		$notes = $this->optionalString($data, 'notes', 4000);

		return [
			'recipient' => $recipient,
			'objective' => $objective,
			'deadlineDays' => $deadlineDays,
			'businessDays' => $businessDays,
			'notes' => $notes,
		];
	}

	/**
	 * Normaliza e valida a lista de destinatários para tramitação em lote.
	 *
	 * @param array<string, mixed> $data
	 * @return list<string> destinatários únicos (1 a 50)
	 */
	public function validateBatchRecipients(array $data): array {
		$raw = $data['recipients'] ?? [];

		if (is_string($raw)) {
			$raw = preg_split('/[;\r\n]+/', $raw) ?: [];
		}

		if (!is_array($raw)) {
			throw new ValidationException('Informe os destinatários da tramitação.');
		}

		$recipients = [];
		foreach ($raw as $entry) {
			$recipient = is_array($entry) ? ($entry['recipient'] ?? '') : $entry;
			$recipient = trim((string) $recipient);
			if ($recipient !== '') {
				$recipients[] = $recipient;
			}
		}

		$recipients = array_values(array_unique($recipients));

		if ($recipients === [] || count($recipients) > 50) {
			throw new ValidationException('Informe entre 1 e 50 destinatários.');
		}

		return $recipients;
	}

	/**
	 * Valida uma string obrigatória.
	 *
	 * @param array<string, mixed> $data
	 */
	public function requiredString(array $data, string $field, string $label, int $max): string {
		$value = trim((string) ($data[$field] ?? ''));

		if ($value === '') {
			throw new ValidationException($label . ' é obrigatório.', [$field => $label . ' é obrigatório.']);
		}

		if (mb_strlen($value) > $max) {
			throw new ValidationException($label . ' excede o tamanho permitido.', [$field => 'Máximo de ' . $max . ' caracteres.']);
		}

		return $value;
	}

	/**
	 * Valida uma string opcional.
	 *
	 * @param array<string, mixed> $data
	 */
	public function optionalString(array $data, string $field, int $max): ?string {
		$value = trim((string) ($data[$field] ?? ''));

		if ($value === '') {
			return null;
		}

		if (mb_strlen($value) > $max) {
			throw new ValidationException('Campo excede o tamanho permitido.', [$field => 'Máximo de ' . $max . ' caracteres.']);
		}

		return $value;
	}

	/**
	 * Valida um inteiro positivo opcional.
	 *
	 * @param array<string, mixed> $data
	 */
	public function optionalPositiveInt(array $data, string $field): ?int {
		if (!isset($data[$field]) || $data[$field] === '') {
			return null;
		}

		$value = (int) $data[$field];

		if ($value <= 0) {
			throw new ValidationException('Identificador inválido.', [$field => 'Informe um identificador maior que zero.']);
		}

		return $value;
	}

	/**
	 * Converte string no formato AAAA-MM-DD para DateTime.
	 */
	public function parseDate(string $value, string $field): ?\DateTime {
		if (trim($value) === '') {
			return null;
		}

		$date = \DateTime::createFromFormat('!Y-m-d', $value, $this->utc);
		$errors = \DateTime::getLastErrors();

		if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
			throw new ValidationException('Data inválida.', [$field => 'Use o formato AAAA-MM-DD.']);
		}

		return $date;
	}
}
