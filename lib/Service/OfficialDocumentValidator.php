<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Service;

use OCA\Legislativo\Exception\ValidationException;

class OfficialDocumentValidator {
	public const KINDS = ['memorandum', 'official_letter', 'circular'];

	/**
	 * @param array<string, mixed> $data
	 * @return array{kind?: string, year?: int, subject?: string, body?: string, recipients?: list<string>}
	 */
	public function validate(array $data, bool $partial = false): array {
		$values = [];
		if (!$partial || array_key_exists('kind', $data)) {
			$kind = trim((string) ($data['kind'] ?? ''));
			if (!in_array($kind, self::KINDS, true)) {
				throw new ValidationException('Tipo de documento inválido.', ['kind' => 'Selecione memorando, ofício ou circular.']);
			}
			$values['kind'] = $kind;
		}
		if (!$partial || array_key_exists('year', $data)) {
			$year = (int) ($data['year'] ?? date('Y'));
			if ($year < 1900 || $year > 2200) {
				throw new ValidationException('Ano inválido.', ['year' => 'Informe um ano entre 1900 e 2200.']);
			}
			$values['year'] = $year;
		}
		if (!$partial || array_key_exists('subject', $data)) {
			$values['subject'] = $this->requiredString($data, 'subject', 'Assunto', 512);
		}
		if (!$partial || array_key_exists('body', $data)) {
			$values['body'] = $this->requiredString($data, 'body', 'Conteúdo', 200000);
		}
		if (!$partial || array_key_exists('recipients', $data)) {
			$kind = $values['kind'] ?? trim((string) ($data['kind'] ?? ''));
			$values['recipients'] = $this->recipients($data['recipients'] ?? [], $kind);
		}

		return $values;
	}

	/** @return list<string> */
	public function recipients(mixed $raw, string $kind): array {
		if (is_string($raw)) {
			$raw = preg_split('/[;\r\n]+/', $raw) ?: [];
		}
		if (!is_array($raw)) {
			throw new ValidationException('Destinatários inválidos.', ['recipients' => 'Informe uma lista de destinatários.']);
		}
		$recipients = [];
		foreach ($raw as $entry) {
			$value = trim((string) $entry);
			if ($value !== '') {
				if (mb_strlen($value) > 255) {
					throw new ValidationException('Destinatário excede o tamanho permitido.', ['recipients' => 'Máximo de 255 caracteres por destinatário.']);
				}
				$recipients[] = $value;
			}
		}
		$recipients = array_values(array_unique($recipients));
		$minimum = $kind === 'circular' ? 2 : 1;
		if (count($recipients) < $minimum || count($recipients) > 100) {
			$message = $kind === 'circular' ? 'A circular exige de 2 a 100 destinatários.' : 'Informe de 1 a 100 destinatários.';
			throw new ValidationException($message, ['recipients' => $message]);
		}

		return $recipients;
	}

	/** @param array<string, mixed> $data */
	private function requiredString(array $data, string $field, string $label, int $max): string {
		$value = trim((string) ($data[$field] ?? ''));
		if ($value === '') {
			throw new ValidationException($label . ' é obrigatório.', [$field => $label . ' é obrigatório.']);
		}
		if (mb_strlen($value) > $max) {
			throw new ValidationException($label . ' excede o tamanho permitido.', [$field => 'Máximo de ' . $max . ' caracteres.']);
		}

		return $value;
	}
}
