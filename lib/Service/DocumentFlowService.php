<?php

declare(strict_types=1);

namespace OCA\Legislativo\Service;

use OCA\Legislativo\Db\DocumentFlow;
use OCA\Legislativo\Db\DocumentFlowMapper;
use OCA\Legislativo\Db\MatterMapper;
use OCA\Legislativo\Db\ProtocolMapper;
use OCA\Legislativo\Exception\ValidationException;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;

class DocumentFlowService {
	private const DOCX_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

	public function __construct(
		private IRootFolder $rootFolder,
		private MatterMapper $matterMapper,
		private ProtocolMapper $protocolMapper,
		private DocumentFlowMapper $flowMapper,
		private AttachmentService $attachmentService,
		private AuthorizationService $authorization,
		private AuditService $auditService,
		private LibreSignGateway $libreSign,
	) {
	}

	public function prepare(int $matterId, int $protocolId, string $filePath): DocumentFlow {
		$uid = $this->authorization->requireWrite();
		$matter = $this->matterMapper->find($matterId);
		$protocol = $this->protocolMapper->find($protocolId);
		if ($protocol->getMatterId() !== $matterId) throw new ValidationException('O protocolo não pertence a esta matéria.');
		$path = ltrim(trim($filePath), '/');
		if ($path === '') throw new ValidationException('Selecione um DOCX ou PDF.', ['filePath' => 'Arquivo obrigatório.']);
		try { $source = $this->rootFolder->getUserFolder($uid)->get($path); }
		catch (\Throwable) { throw new ValidationException('O arquivo de origem não foi encontrado ou não está acessível.'); }
		if (!$source instanceof File) throw new ValidationException('Selecione um arquivo, não uma pasta.');
		$extension = strtolower($source->getExtension());
		if (!in_array($extension, ['pdf', 'docx'], true)) throw new ValidationException('Formato não suportado. Use DOCX ou PDF.');
		if ($source->getSize() > 50 * 1024 * 1024) throw new ValidationException('O documento excede o limite de 50 MB.');

		$tempDir = sys_get_temp_dir() . '/legislativo-' . bin2hex(random_bytes(8));
		if (!mkdir($tempDir, 0700, true) && !is_dir($tempDir)) throw new ValidationException('Não foi possível criar a área temporária.');
		try {
			$input = $tempDir . '/source.' . $extension;
			file_put_contents($input, $source->getContent());
			$pdf = $extension === 'pdf' ? $input : $this->convertDocx($input, $tempDir);
			$stamped = $tempDir . '/stamped.pdf';
			$stamp = sprintf('CAMARA MUNICIPAL DE CONCHAL | PROTOCOLO %d/%d | %s %d/%d | %s',
				$protocol->getNumber(), $protocol->getYear(), $matter->getType(), $matter->getNumber(), $matter->getYear(),
				(new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo')))->format('d/m/Y H:i:s'));
			$this->stampPdf($pdf, $stamped, $stamp);
			$pdfContent = file_get_contents($stamped);
			if ($pdfContent === false || !str_starts_with($pdfContent, '%PDF-')) throw new ValidationException('A conversão não produziu um PDF válido.');
			$pdfNode = $this->savePdf($uid, $matter->getYear(), $matter->getType() . '-' . $matter->getNumber(),
				'protocolo-' . $protocol->getNumber() . '-' . $protocol->getYear() . '-carimbado.pdf', $pdfContent);
			$relativePath = $this->relativeUserPath($pdfNode->getPath(), $uid);
			$this->attachmentService->add($matterId, $relativePath);

			$now = new \DateTime('now', new \DateTimeZone('UTC'));
			$flow = new DocumentFlow();
			$flow->setMatterId($matterId); $flow->setProtocolId($protocolId);
			$flow->setSourceFileId($source->getId()); $flow->setSourcePath('/' . $path);
			$flow->setPdfFileId($pdfNode->getId()); $flow->setPdfPath('/' . $relativePath);
			$flow->setStatus('prepared'); $flow->setChecksum(hash('sha256', $pdfContent));
			$flow->setCreatedBy($uid); $flow->setCreatedAt($now); $flow->setUpdatedAt($now);
			$inserted = $this->flowMapper->insert($flow);
			$this->auditService->record($uid, 'matter', $matterId, 'document.prepared', null, $inserted->jsonSerialize());
			return $inserted;
		} finally {
			$this->removeTempDirectory($tempDir);
		}
	}

	/** @param array<int|string, mixed>|string $rawSigners */
	public function requestSignature(int $matterId, int $flowId, array|string $rawSigners): DocumentFlow {
		$uid = $this->authorization->requireWrite();
		$flow = $this->flowMapper->find($flowId);
		if ($flow->getMatterId() !== $matterId) throw new ValidationException('O fluxo documental não pertence a esta matéria.');
		if ($flow->getStatus() === 'requested') throw new ValidationException('Este documento já foi encaminhado ao LibreSign.');
		$signers = $this->parseSigners($rawSigners);
		try {
			$result = $this->libreSign->request($flow->getPdfFileId(), basename($flow->getPdfPath(), '.pdf'), $signers);
			$before = $flow->jsonSerialize();
			$flow->setStatus('requested'); $flow->setLibresignUuid($result['uuid']); $flow->setSigners($signers);
			$flow->setErrorMessage(null); $flow->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
			$updated = $this->flowMapper->update($flow);
			$this->auditService->record($uid, 'matter', $matterId, 'document.libresign.requested', $before, $updated->jsonSerialize());
			return $updated;
		} catch (\Throwable $e) {
			$flow->setStatus('error'); $flow->setErrorMessage(substr($e->getMessage(), 0, 4000));
			$flow->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC'))); $this->flowMapper->update($flow);
			throw $e;
		}
	}

	private function convertDocx(string $input, string $tempDir): string {
		$binary = null;
		foreach (['/usr/bin/libreoffice', '/usr/bin/soffice'] as $candidate) if (is_executable($candidate)) { $binary = $candidate; break; }
		if ($binary === null) throw new ValidationException('Conversão DOCX indisponível: instale LibreOffice no servidor Nextcloud. PDFs podem ser processados normalmente.');
		$profile = $tempDir . '/lo-profile'; mkdir($profile, 0700);
		$this->run([$binary, '-env:UserInstallation=file://' . $profile, '--headless', '--convert-to', 'pdf', '--outdir', $tempDir, $input], 'Falha ao converter o DOCX para PDF.');
		$output = $tempDir . '/source.pdf';
		if (!is_file($output)) throw new ValidationException('O LibreOffice não produziu o PDF esperado.');
		return $output;
	}

	private function stampPdf(string $input, string $output, string $text): void {
		$gs = '/usr/bin/gs';
		if (!is_executable($gs)) throw new ValidationException('Ghostscript não está instalado; não foi possível aplicar o carimbo.');
		$ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
		$escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $ascii);
		$postScript = dirname($output) . '/stamp.ps';
		file_put_contents($postScript, "<</EndPage { exch pop 2 ne { gsave /Helvetica findfont 8 scalefont setfont 0.25 setgray 24 18 moveto ($escaped) show grestore } if true } bind>> setpagedevice\n");
		$this->run([$gs, '-q', '-dBATCH', '-dNOPAUSE', '-dSAFER', '-sDEVICE=pdfwrite', '-dCompatibilityLevel=1.7', '-sOutputFile=' . $output, $postScript, $input], 'Falha ao aplicar o carimbo de protocolo ao PDF.');
	}

	/** @param list<string> $command */
	private function run(array $command, string $message): void {
		$pipes = [];
		$process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
		if (!is_resource($process)) throw new ValidationException($message);
		fclose($pipes[0]); $stdout = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
		$code = proc_close($process);
		if ($code !== 0) throw new ValidationException($message . ' ' . trim((string)$stderr ?: (string)$stdout));
	}

	private function savePdf(string $uid, int $year, string $matterSlug, string $name, string $content): File {
		$folder = $this->rootFolder->getUserFolder($uid);
		foreach (['Documentos Legislativos', (string)$year, preg_replace('/[^A-Za-z0-9_-]+/', '-', $matterSlug) ?: 'materia'] as $part) {
			$folder = $folder->nodeExists($part) ? $folder->get($part) : $folder->newFolder($part);
			if (!$folder instanceof Folder) throw new ValidationException('O destino do documento não é uma pasta.');
		}
		$base = pathinfo($name, PATHINFO_FILENAME); $candidate = $name; $index = 2;
		while ($folder->nodeExists($candidate)) $candidate = $base . '-' . $index++ . '.pdf';
		$file = $folder->newFile($candidate); $file->putContent($content); return $file;
	}

	private function relativeUserPath(string $absolutePath, string $uid): string {
		foreach (['/' . $uid . '/files/', '/files/' . $uid . '/'] as $marker) {
			$position = strpos($absolutePath, $marker);
			if ($position !== false) return substr($absolutePath, $position + strlen($marker));
		}
		return ltrim($absolutePath, '/');
	}

	private function removeTempDirectory(string $directory): void {
		if (!is_dir($directory)) return;
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::CHILD_FIRST,
		);
		foreach ($iterator as $item) $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
		@rmdir($directory);
	}

	/** @param array<int|string, mixed>|string $raw @return list<string> */
	private function parseSigners(array|string $raw): array {
		$values = is_array($raw) ? $raw : preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
		$emails = [];
		foreach ($values ?: [] as $value) {
			$email = strtolower(trim((string)$value));
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new ValidationException('E-mail de signatário inválido: ' . $email);
			$emails[$email] = $email;
		}
		if ($emails === []) throw new ValidationException('Informe ao menos um signatário.');
		return array_values($emails);
	}
}
