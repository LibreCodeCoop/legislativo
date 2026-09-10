<?php

declare(strict_types=1);

namespace OCA\Legislativo\Service;

use OCA\Legislativo\Exception\ValidationException;
use OCA\Libresign\Helper\ValidateHelper;
use OCA\Libresign\Service\RequestSignatureService;
use OCP\IUserSession;

class LibreSignGateway {
	public function __construct(
		private IUserSession $userSession,
		private ValidateHelper $validateHelper,
		private RequestSignatureService $requestSignatureService,
	) {
	}

	/** @param list<string> $emails @return array{uuid:string, nodeId:int} */
	public function request(int $pdfFileId, string $name, array $emails): array {
		$user = $this->userSession->getUser();
		if ($user === null) throw new ValidationException('Sessão de usuário inválida.');
		$users = array_map(static fn (string $email): array => ['identify' => ['email' => $email]], $emails);
		$data = ['file' => ['fileId' => $pdfFileId], 'name' => $name, 'users' => $users, 'status' => 1, 'callback' => null, 'userManager' => $user];
		try {
			$this->validateHelper->canRequestSign($user);
			$this->requestSignatureService->validateNewRequestToFile($data);
			$file = $this->requestSignatureService->save($data);
			return ['uuid' => $file->getUuid(), 'nodeId' => $file->getNodeId()];
		} catch (\Throwable $e) {
			throw new ValidationException('O LibreSign recusou a solicitação: ' . $e->getMessage());
		}
	}
}
