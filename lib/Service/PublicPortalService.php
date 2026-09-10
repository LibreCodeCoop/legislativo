<?php

declare(strict_types=1);

namespace OCA\Legislativo\Service;

use OCA\Legislativo\Db\AgendaItemMapper;
use OCA\Legislativo\Db\LegislativeSessionMapper;
use OCA\Legislativo\Db\Matter;
use OCA\Legislativo\Db\MatterMapper;
use OCA\Legislativo\Db\NormMapper;
use OCA\Legislativo\Db\Parliamentarian;
use OCA\Legislativo\Db\ParliamentarianMapper;
use OCA\Legislativo\Db\Proceeding;
use OCA\Legislativo\Db\ProceedingMapper;
use OCA\Legislativo\Db\Protocol;
use OCA\Legislativo\Db\ProtocolMapper;
use OCA\Legislativo\Db\VoteMapper;

class PublicPortalService {
	public function __construct(
		private MatterMapper $matters,
		private ProtocolMapper $protocols,
		private ProceedingMapper $proceedings,
		private AgendaItemMapper $agenda,
		private LegislativeSessionMapper $sessions,
		private VotingService $voting,
		private VoteMapper $votes,
		private NormMapper $norms,
		private ParliamentarianMapper $parliamentarianMapper,
		private TextNormalizer $textNormalizer,
	) {
	}

	/** @return list<array<string,mixed>> */
	public function listMatters(array $filters): array {
		$filters['status'] = $filters['status'] ?? '';
		$items = $this->matters->search($filters, min(100, max(1, (int)($filters['limit'] ?? 50))), max(0, (int)($filters['offset'] ?? 0)));
		return array_map(fn (Matter $matter): array => $this->publicMatter($matter), $items);
	}

	/** @return array<string,mixed> */
	public function matter(int $id): array {
		return $this->detail($this->matters->find($id));
	}

	/** @return list<array<string,mixed>> */
	public function sessions(array $filters = []): array {
		$status = trim((string)($filters['status'] ?? ''));
		return array_values(array_map(function ($session): array {
			$data = $this->voting->publicGet($session->getId());
			return [
				'id' => $data['session']['id'], 'type' => $data['session']['type'], 'number' => $data['session']['number'], 'year' => $data['session']['year'],
				'scheduledAt' => $data['session']['scheduledAt'], 'status' => $data['session']['status'], 'openedAt' => $data['session']['openedAt'], 'closedAt' => $data['session']['closedAt'],
				'presentCount' => $data['session']['presentCount'], 'totalSeats' => $data['session']['totalSeats'], 'agenda' => $data['agenda'],
			];
		}, array_filter($this->sessions->findAllSessions(), static fn ($session): bool => $status === '' || $session->getStatus() === $status)));
	}

	/** @return list<array<string,mixed>> */
	public function parliamentarians(string $query = ''): array {
		$profiles = array_filter(
			$this->parliamentarianMapper->findAllProfiles(true),
			fn (Parliamentarian $profile): bool => trim($query) === '' || $this->matches([
				$profile->getDisplayName(), $profile->getParty(), $profile->getRole(), $profile->getSeatNumber(),
			], $query),
		);
		return array_values(array_map(fn (Parliamentarian $profile): array => $this->publicParliamentarian($profile), $profiles));
	}

	/** @return array<string,mixed> */
	public function parliamentarian(int $id): array {
		$profile = $this->parliamentarianMapper->find($id);
		if (!$profile->getActive()) {
			throw new \OCP\AppFramework\Db\DoesNotExistException('Parlamentar inativo.');
		}
		return [
			'profile' => $this->publicParliamentarian($profile),
			'matters' => array_map(
				fn (Matter $matter): array => $this->publicMatter($matter),
				$this->matters->search(['authorUid' => $profile->getUserUid(), 'sort' => 'year', 'direction' => 'desc'], 100),
			),
		];
	}

	/** @return list<array<string,mixed>> */
	public function globalSearch(string $query, int $limit = 20): array {
		$query = trim($query);
		if (mb_strlen($query) < 2) return [];
		$limit = max(1, min($limit, 50));
		$results = [];
		foreach ($this->matters->search(['query' => $query, 'queryMode' => 'all'], $limit) as $matter) {
			$results[] = ['kind' => 'matter', 'id' => $matter->getId(), 'title' => $matter->getSubject(), 'meta' => $matter->getType() . ' nº ' . $matter->getNumber() . '/' . $matter->getYear()];
		}
		foreach ($this->norms->search(['query' => $query, 'queryMode' => 'all'], $limit, 0, true) as $norm) {
			$results[] = ['kind' => 'norm', 'id' => $norm->getId(), 'title' => $norm->getTitle(), 'meta' => $norm->getType() . ' nº ' . $norm->getNumber() . '/' . $norm->getYear()];
		}
		foreach ($this->parliamentarians($query) as $profile) {
			$results[] = ['kind' => 'parliamentarian', 'id' => $profile['id'], 'title' => $profile['displayName'], 'meta' => implode(' · ', array_filter([$profile['role'], $profile['party']]))];
		}
		foreach ($this->sessions() as $session) {
			if ($this->matches([$session['type'], $session['number'], $session['year'], $session['status'], $session['agenda']], $query)) {
				$results[] = ['kind' => 'session', 'id' => $session['id'], 'title' => $session['type'] . ' nº ' . $session['number'] . '/' . $session['year'], 'meta' => (string)$session['status']];
			}
		}
		return array_slice($results, 0, $limit);
	}

	/** @return array<string,mixed> */
	private function detail(Matter $matter): array {
		$data = $this->publicMatter($matter);
		$data['protocols'] = array_map(fn (Protocol $protocol): array => [
			'id' => $protocol->getId(), 'number' => $protocol->getNumber(), 'year' => $protocol->getYear(), 'receivedAt' => $protocol->getReceivedAt()->format(DATE_ATOM), 'sender' => $protocol->getSender(), 'subject' => $protocol->getSubject(),
		], $this->protocols->findByMatter($matter->getId()));
		$data['proceedings'] = array_map(fn (Proceeding $proceeding): array => [
				'id' => $proceeding->getId(), 'recipient' => $proceeding->getRecipient(), 'objective' => $proceeding->getObjective(), 'sentAt' => $proceeding->getSentAt()->format(DATE_ATOM), 'dueAt' => $proceeding->getDueAt()?->format(DATE_ATOM), 'result' => $proceeding->getResult(), 'notes' => $proceeding->getNotes(),
		], $this->proceedings->findByMatter($matter->getId()));
		$data['votes'] = [];
		foreach ($this->agenda->findByMatter($matter->getId()) as $item) {
			foreach ($this->sessions->findAllSessions() as $session) {
				if ($session->getId() !== $item->getSessionId()) continue;
				$ballots = $this->votes->findForBallot($session->getId(), $matter->getId());
				$tally = ['yes' => 0, 'no' => 0, 'abstain' => 0, 'obstruction' => 0, 'total' => count($ballots)];
				foreach ($ballots as $ballot) if (isset($tally[$ballot->getChoice()])) $tally[$ballot->getChoice()]++;
				$data['votes'][] = ['sessionId' => $session->getId(), 'sessionNumber' => $session->getNumber(), 'sessionYear' => $session->getYear(), 'status' => $item->getStatus(), 'result' => $item->getResult(), 'tally' => $tally, 'voteType' => $item->getVoteType()];
			}
		}
		return $data;
	}

	/** @return array<string,mixed> */
	private function publicMatter(Matter $matter): array {
		$data = $matter->jsonSerialize();
		return array_intersect_key($data, array_flip(['id', 'type', 'number', 'year', 'subject', 'body', 'authorUid', 'status', 'theme', 'quorum', 'procedure', 'presentedAt', 'updatedAt']));
	}

	/** @return array<string,mixed> */
	private function publicParliamentarian(Parliamentarian $profile): array {
		return [
			'id' => $profile->getId(),
			'displayName' => $profile->getDisplayName(),
			'party' => $profile->getParty(),
			'role' => $profile->getRole(),
			'seatNumber' => $profile->getSeatNumber(),
			'termStart' => $profile->getTermStart()->format('Y-m-d'),
			'termEnd' => $profile->getTermEnd()->format('Y-m-d'),
		];
	}

	/** @param iterable<mixed> $values */
	private function matches(iterable $values, string $query): bool {
		$document = $this->textNormalizer->document($values);
		foreach ($this->textNormalizer->queryTokens($query) as $token) {
			if (!str_contains($document, $token)) return false;
		}
		return true;
	}
}
