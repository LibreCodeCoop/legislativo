<?php

declare(strict_types=1);

namespace OCA\Legislativo\Service;

use OCA\Legislativo\Db\LegislativeSession;
use OCA\Legislativo\Db\LegislativeSessionMapper;
use OCA\Legislativo\Exception\ValidationException;

class SessionMinutesService {
	private const ALLOWED_TAGS = ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'h2', 'h3', 'blockquote'];

	public function __construct(
		private LegislativeSessionMapper $sessions,
		private AuthorizationService $authorization,
		private AuditService $audit,
		private LibreSignGateway $libreSign,
	) {
	}

	public function save(int $sessionId, string $html): LegislativeSession {
		$uid = $this->authorization->requireWrite();
		$session = $this->sessions->find($sessionId);
		if ($session->getStatus() !== 'closed') throw new ValidationException('A minuta da ata só pode ser editada após o encerramento da sessão.');
		if (strlen($html) > 100000) throw new ValidationException('A minuta excede o limite de 100.000 caracteres.');
		$clean = $this->sanitize($html);
		$before = $session->jsonSerialize();
		$session->setMinutesHtml($clean === '' ? null : $clean);
		$session->setMinutesRevision($session->getMinutesRevision() + 1);
		$session->setMinutesUpdatedBy($uid);
		$session->setMinutesUpdatedAt($this->now());
		$session->setUpdatedAt($this->now());
		if ($session->getMinutesSignatureStatus() === 'requested') $session->setMinutesSignatureStatus('draft_changed');
		$updated = $this->sessions->update($session);
		$this->audit->record($uid, 'session', $sessionId, 'minutes.draft.saved', $before, $updated->jsonSerialize());
		return $updated;
	}

	/** @param array<int|string,mixed>|string $rawSigners */
	public function requestSignature(int $sessionId, array|string $rawSigners): LegislativeSession {
		$uid = $this->authorization->requireWrite();
		$session = $this->sessions->find($sessionId);
		if ($session->getStatus() !== 'closed' || $session->getMinutesFileId() === null) throw new ValidationException('Gere o PDF da ata encerrada antes de solicitar assinaturas.');
		if ($session->getMinutesSignatureStatus() === 'requested') throw new ValidationException('Esta versão da ata já foi encaminhada ao LibreSign.');
		$signers = $this->parseSigners($rawSigners);
		$result = $this->libreSign->request($session->getMinutesFileId(), 'ata-' . $session->getNumber() . '-' . $session->getYear(), $signers);
		$before = $session->jsonSerialize();
		$session->setMinutesLibresignUuid($result['uuid']);
		$session->setMinutesSignatureStatus('requested');
		$session->setMinutesSigners($signers);
		$session->setUpdatedAt($this->now());
		$updated = $this->sessions->update($session);
		$this->audit->record($uid, 'session', $sessionId, 'minutes.libresign.requested', $before, $updated->jsonSerialize());
		return $updated;
	}

	private function sanitize(string $html): string {
		$document = new \DOMDocument('1.0', 'UTF-8');
		$previous = libxml_use_internal_errors(true);
		$document->loadHTML('<?xml encoding="UTF-8"><div id="minutes-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);
		$root = $document->getElementById('minutes-root');
		if ($root === null) return '';
		$this->cleanChildren($root);
		$output = '';
		foreach ($root->childNodes as $child) $output .= $document->saveHTML($child);
		return trim($output);
	}

	private function cleanChildren(\DOMNode $parent): void {
		foreach (iterator_to_array($parent->childNodes) as $node) {
			if (!$node instanceof \DOMElement) continue;
			$name = strtolower($node->tagName);
			if (in_array($name, ['script', 'style', 'iframe', 'object'], true)) {
				$parent->removeChild($node);
				continue;
			}
			$this->cleanChildren($node);
			if (!in_array($name, self::ALLOWED_TAGS, true)) {
				while ($node->firstChild !== null) $parent->insertBefore($node->firstChild, $node);
				$parent->removeChild($node);
				continue;
			}
			while ($node->attributes->length > 0) $node->removeAttributeNode($node->attributes->item(0));
		}
	}

	/** @param array<int|string,mixed>|string $raw @return list<string> */
	private function parseSigners(array|string $raw): array {
		$values = is_array($raw) ? $raw : (preg_split('/[,;\n]+/', $raw) ?: []);
		$emails = [];
		foreach ($values as $value) {
			$email = strtolower(trim((string)$value));
			if ($email === '') continue;
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new ValidationException('E-mail de signatário inválido: ' . $email);
			$emails[$email] = true;
		}
		$result = array_keys($emails);
		if ($result === [] || count($result) > 50) throw new ValidationException('Informe entre 1 e 50 signatários.');
		return $result;
	}

	private function now(): \DateTime {
		return new \DateTime('now', new \DateTimeZone('UTC'));
	}
}
