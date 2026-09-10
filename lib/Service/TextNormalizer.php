<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Legislativo\Service;

class TextNormalizer {
	/** @param iterable<mixed> $values */
	public function document(iterable $values): string {
		$parts = [];
		foreach ($values as $value) {
			if ($value !== null && $value !== '') {
				$parts[] = is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}
		}
		$normalized = $this->normalize(implode(' ', $parts));
		$stems = array_map(fn (string $token): string => $this->stem($token), $this->split($normalized));
		return trim($normalized . ' ' . implode(' ', array_unique($stems)));
	}

	/** @return list<string> */
	public function queryTokens(string $query): array {
		$tokens = array_map(fn (string $token): string => $this->stem($token), $this->split($this->normalize($query)));
		return array_values(array_unique(array_filter($tokens, static fn (string $token): bool => $token !== '')));
	}

	public function normalize(string $value): string {
		$value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
		if ($ascii !== false) {
			$value = $ascii;
		}
		$value = mb_strtolower($value, 'UTF-8');
		return trim((string)preg_replace('/[^a-z0-9]+/u', ' ', $value));
	}

	/** @return list<string> */
	private function split(string $value): array {
		return array_values(array_filter(preg_split('/\s+/', $value) ?: []));
	}

	private function stem(string $word): string {
		$length = strlen($word);
		if ($length <= 3) {
			return $word;
		}
		if ($word === 'leis') {
			return 'lei';
		}
		if (str_ends_with($word, 'oes') || str_ends_with($word, 'aes') || str_ends_with($word, 'aos')) {
			return substr($word, 0, -3);
		}
		if (str_ends_with($word, 'ao')) {
			return substr($word, 0, -2);
		}
		if (str_ends_with($word, 'ais')) {
			return substr($word, 0, -3) . 'al';
		}
		if (str_ends_with($word, 'eis')) {
			return substr($word, 0, -3) . 'el';
		}
		if (str_ends_with($word, 'ns')) {
			return substr($word, 0, -2) . 'm';
		}
		if (str_ends_with($word, 's')) {
			return substr($word, 0, -1);
		}
		return $word;
	}
}
