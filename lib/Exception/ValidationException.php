<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Exception;

class ValidationException extends \InvalidArgumentException {
	/** @param array<string, string> $errors */
	public function __construct(
		string $message,
		private array $errors = [],
	) {
		parent::__construct($message);
	}

	/** @return array<string, string> */
	public function getErrors(): array {
		return $this->errors;
	}
}
