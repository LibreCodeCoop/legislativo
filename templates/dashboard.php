<?php

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * @var array $_
 */
$canWrite = (bool)($_['canWrite'] ?? false);
$canPreside = (bool)($_['canPreside'] ?? false);
$version = (string)($_['version'] ?? '');
?>
<main class="legislativo-dashboard" data-can-write="<?php p($canWrite ? '1' : '0'); ?>" data-can-preside="<?php p($canPreside ? '1' : '0'); ?>">
	<header class="legislativo-hero">
		<div>
			<p class="legislativo-eyebrow">Processo legislativo eletrônico</p>
			<h1>Matérias e tramitações</h1>
			<p class="legislativo-lead">Cadastre, protocole e acompanhe cada movimentação com prazos e trilha de auditoria.</p>
		</div>
		<span class="legislativo-version">v<?php p($version); ?></span>
	</header>

	<?php if (!$canWrite && !$canPreside): ?>
		<div class="legislativo-permission-note" role="status">
			Você está em modo de consulta. Para alterar registros, solicite inclusão no grupo <code>legislativo_operadores</code>.
		</div>
	<?php endif; ?>
	<?php if ($canPreside && !$canWrite): ?>
		<div class="legislativo-permission-note" role="status">Perfil de presidência: você pode conduzir sessões, votações, falas e cronômetros; os registros administrativos permanecem em modo de consulta.</div>
	<?php endif; ?>

	<section class="legislativo-toolbar" aria-label="Pesquisa de matérias">
		<form id="legislativo-search-form" class="legislativo-search-form">
			<label class="legislativo-sr-only" for="legislativo-search">Pesquisar matérias</label>
			<input id="legislativo-search" name="query" type="search" placeholder="Assunto, texto ou tema">
			<select name="status" aria-label="Filtrar por situação">
				<option value="">Todas as situações</option>
				<option value="draft">Rascunho</option>
				<option value="protocolled">Protocolada</option>
				<option value="processing">Em tramitação</option>
				<option value="agenda">Em pauta</option>
				<option value="approved">Aprovada</option>
				<option value="rejected">Rejeitada</option>
				<option value="archived">Arquivada</option>
			</select>
			<button type="submit" class="button">Pesquisar</button>
			<details class="legislativo-advanced-search">
				<summary>Busca avançada</summary>
				<div>
					<label>Correspondência <select name="queryMode"><option value="all">Todos os termos (E)</option><option value="any">Qualquer termo (OU)</option><option value="phrase">Frase exata</option></select></label>
					<label>Número inicial <input name="numberFrom" type="number" min="1"></label>
					<label>Número final <input name="numberTo" type="number" min="1"></label>
					<label>Ano inicial <input name="yearFrom" type="number" min="1900" max="2200"></label>
					<label>Ano final <input name="yearTo" type="number" min="1900" max="2200"></label>
					<label>Ordenar por <select name="sort"><option value="year">Ano</option><option value="number">Número</option><option value="subject">Assunto</option><option value="presentedAt">Apresentação</option><option value="updatedAt">Atualização</option></select></label>
					<label>Direção <select name="direction"><option value="desc">Decrescente</option><option value="asc">Crescente</option></select></label>
				</div>
			</details>
		</form>
		<div class="legislativo-toolbar-actions">
			<a class="button" href="<?php p(OC::$server->getURLGenerator()->linkToRoute('legislativo.publicPortal.page')); ?>" target="_blank" rel="noopener">Portal público</a>
			<button id="legislativo-open-voting" type="button" class="button">Votação</button>
			<button id="legislativo-open-deadlines" type="button" class="button">Prazos</button>
			<?php if ($canWrite): ?>
				<button id="legislativo-open-audit" type="button" class="button">Auditoria</button>
				<button id="legislativo-open-norms" type="button" class="button">Legislação</button>
				<button id="legislativo-open-triage" type="button" class="button">Triagem</button>
				<button id="legislativo-open-calendar" type="button" class="button">Calendário</button>
				<button id="legislativo-new-matter" type="button" class="primary">Nova matéria</button>
			<?php endif; ?>
		</div>
	</section>

	<div id="legislativo-feedback" class="legislativo-feedback" role="status" aria-live="polite" hidden></div>

	<section class="legislativo-workspace">
		<div class="legislativo-list-panel">
			<div class="legislativo-panel-title">
				<h2>Matérias</h2>
				<span id="legislativo-result-count">0 registros</span>
			</div>
			<div id="legislativo-matter-list" class="legislativo-matter-list" aria-live="polite">
				<p class="legislativo-empty">Carregando matérias…</p>
			</div>
		</div>

		<aside id="legislativo-detail" class="legislativo-detail" aria-live="polite">
			<div class="legislativo-empty-detail">
				<span aria-hidden="true">§</span>
				<h2>Selecione uma matéria</h2>
				<p>Os dados cadastrais, protocolos, tramitações e eventos de auditoria serão exibidos aqui.</p>
			</div>
		</aside>
	</section>
</main>

<?php if ($canWrite): ?>
	<dialog id="legislativo-matter-dialog" class="legislativo-dialog">
		<form id="legislativo-matter-form" method="dialog">
			<header>
				<div>
					<p class="legislativo-eyebrow">Cadastro legislativo</p>
					<h2 id="legislativo-matter-dialog-title">Nova matéria</h2>
				</div>
				<button type="button" class="legislativo-dialog-close" data-close-dialog aria-label="Fechar">×</button>
			</header>
			<input name="id" type="hidden">
			<div class="legislativo-form-grid">
				<label>Tipo <input name="type" maxlength="64" required placeholder="Projeto de Lei"></label>
				<label>Número <input name="number" type="number" min="1" required></label>
				<label>Ano <input name="year" type="number" min="1900" max="2200" required></label>
				<label>Data de apresentação <input name="presentedAt" type="date" required></label>
				<label class="legislativo-span-2">Assunto <input name="subject" maxlength="512" required></label>
				<label>Tema <input name="theme" maxlength="255"></label>
				<label>Autoria <input name="authorUid" maxlength="64" placeholder="Usuário ou parlamentar"></label>
				<label>Quórum <input name="quorum" maxlength="64" placeholder="Maioria simples"></label>
				<label>Regime de tramitação <input name="procedure" maxlength="64" placeholder="Ordinário"></label>
				<label>Situação
					<select name="status">
						<option value="draft">Rascunho</option>
						<option value="received">Recebida</option>
						<option value="protocolled">Protocolada</option>
						<option value="processing">Em tramitação</option>
						<option value="agenda">Em pauta</option>
						<option value="approved">Aprovada</option>
						<option value="rejected">Rejeitada</option>
						<option value="archived">Arquivada</option>
					</select>
				</label>
				<label class="legislativo-span-2">Texto/ementa ampliada <textarea name="body" rows="5"></textarea></label>
				<label class="legislativo-span-2">Observações <textarea name="notes" rows="3" maxlength="4000"></textarea></label>
			</div>
			<footer>
				<button type="button" class="button" data-close-dialog>Cancelar</button>
				<button type="submit" class="primary">Salvar matéria</button>
			</footer>
		</form>
	</dialog>

	<dialog id="legislativo-protocol-dialog" class="legislativo-dialog legislativo-dialog--small">
		<form id="legislativo-protocol-form" method="dialog">
			<header>
				<div><p class="legislativo-eyebrow">Recebimento</p><h2>Protocolar matéria</h2></div>
				<button type="button" class="legislativo-dialog-close" data-close-dialog aria-label="Fechar">×</button>
			</header>
			<input name="matterId" type="hidden">
			<div class="legislativo-form-grid">
				<label class="legislativo-span-2">Remetente <input name="sender" maxlength="255" required></label>
				<label>Ano <input name="year" type="number" min="1900" max="2200" required></label>
				<label>Número <input name="number" type="number" min="1" placeholder="Automático"></label>
				<label class="legislativo-span-2">Assunto <input name="subject" maxlength="512" placeholder="Usa o assunto da matéria se vazio"></label>
				<label class="legislativo-span-2">Entrada de triagem aceita
					<select name="submissionId"><option value="">Protocolo direto</option></select>
				</label>
			</div>
			<footer><button type="button" class="button" data-close-dialog>Cancelar</button><button type="submit" class="primary">Gerar protocolo</button></footer>
		</form>
	</dialog>

	<dialog id="legislativo-proceeding-dialog" class="legislativo-dialog legislativo-dialog--small">
		<form id="legislativo-proceeding-form" method="dialog">
			<header>
				<div><p class="legislativo-eyebrow">Movimentação</p><h2>Nova tramitação</h2></div>
				<button type="button" class="legislativo-dialog-close" data-close-dialog aria-label="Fechar">×</button>
			</header>
			<input name="matterId" type="hidden">
			<div class="legislativo-form-grid">
				<label class="legislativo-span-2">Destinatários <textarea name="recipient" rows="2" maxlength="4000" required placeholder="Uma comissão por linha ou ponto e vírgula"></textarea></label>
				<label class="legislativo-span-2">Objetivo <input name="objective" maxlength="512" required placeholder="Emitir parecer"></label>
				<label>Prazo em dias <input name="deadlineDays" type="number" min="0" max="3650" value="10"></label>
				<label class="legislativo-checkbox"><input name="businessDays" type="checkbox" checked> Contar somente dias úteis</label>
				<label class="legislativo-span-2">Observações <textarea name="notes" rows="3" maxlength="4000"></textarea></label>
			</div>
			<p class="legislativo-form-help">Nesta etapa, dias úteis excluem sábados e domingos. O calendário de feriados, pontos facultativos e recessos será conectado no próximo incremento.</p>
			<footer><button type="button" class="button" data-close-dialog>Cancelar</button><button type="submit" class="primary">Registrar tramitação</button></footer>
		</form>
	</dialog>

	<dialog id="legislativo-attachment-dialog" class="legislativo-dialog legislativo-dialog--small">
		<form id="legislativo-attachment-form" method="dialog">
			<header><div><p class="legislativo-eyebrow">Arquivos</p><h2>Anexar documento</h2></div><button type="button" class="legislativo-dialog-close" data-close-dialog aria-label="Fechar">×</button></header>
			<input name="matterId" type="hidden">
			<div class="legislativo-form-grid">
				<label class="legislativo-span-2">Arquivo no Nextcloud
					<span class="legislativo-file-field"><input name="filePath" required readonly placeholder="Selecione um arquivo"><button type="button" class="button" data-pick-file>Escolher</button></span>
				</label>
			</div>
			<footer><button type="button" class="button" data-close-dialog>Cancelar</button><button type="submit" class="primary">Vincular arquivo</button></footer>
		</form>
	</dialog>

	<dialog id="legislativo-document-dialog" class="legislativo-dialog legislativo-dialog--small">
		<form id="legislativo-document-form" method="dialog">
			<header><div><p class="legislativo-eyebrow">Documento oficial</p><h2>Preparar e assinar</h2></div><button type="button" class="legislativo-dialog-close" data-close-dialog aria-label="Fechar">×</button></header>
			<input name="matterId" type="hidden">
			<div class="legislativo-form-grid">
				<label class="legislativo-span-2">Protocolo <select name="protocolId" required></select></label>
				<label class="legislativo-span-2">DOCX ou PDF no Nextcloud
					<span class="legislativo-file-field"><input name="filePath" required readonly placeholder="Selecione o documento"><button type="button" class="button" data-pick-file>Escolher</button></span>
				</label>
				<label class="legislativo-span-2">Signatários <textarea name="signers" rows="3" required placeholder="presidente@camara.sp.gov.br, secretario@camara.sp.gov.br"></textarea></label>
			</div>
			<p class="legislativo-form-help">O documento será convertido para PDF, carimbado com o protocolo, salvo em Documentos Legislativos e encaminhado ao LibreSign. Separe os e-mails por vírgula ou linha.</p>
			<footer><button type="button" class="button" data-close-dialog>Cancelar</button><button type="submit" class="primary">Preparar e solicitar assinaturas</button></footer>
		</form>
	</dialog>

	<dialog id="legislativo-submission-dialog" class="legislativo-dialog legislativo-dialog--small">
		<form id="legislativo-submission-form" method="dialog">
			<header><div><p class="legislativo-eyebrow">Secretaria</p><h2>Enviar à triagem</h2></div><button type="button" class="legislativo-dialog-close" data-close-dialog aria-label="Fechar">×</button></header>
			<input name="matterId" type="hidden">
			<div class="legislativo-form-grid">
				<label class="legislativo-span-2">Remetente <input name="sender" required maxlength="255"></label>
				<label class="legislativo-span-2">Assunto <input name="subject" required maxlength="512"></label>
				<label class="legislativo-span-2">Documento <span class="legislativo-file-field"><input name="filePath" readonly placeholder="Opcional"><button type="button" class="button" data-pick-file>Escolher</button></span></label>
				<label class="legislativo-span-2">Observações <textarea name="notes" rows="3" maxlength="4000"></textarea></label>
			</div>
			<footer><button type="button" class="button" data-close-dialog>Cancelar</button><button type="submit" class="primary">Enviar para análise</button></footer>
		</form>
	</dialog>

	<dialog id="legislativo-triage-dialog" class="legislativo-dialog">
		<header><div><p class="legislativo-eyebrow">Secretaria</p><h2>Caixa de triagem</h2></div><button type="button" class="legislativo-dialog-close" data-close-dialog aria-label="Fechar">×</button></header>
		<div id="legislativo-triage-list" class="legislativo-management-list"><p class="legislativo-empty">Carregando…</p></div>
	</dialog>

	<dialog id="legislativo-calendar-dialog" class="legislativo-dialog">
		<header><div><p class="legislativo-eyebrow">Prazos</p><h2>Calendário legislativo</h2></div><button type="button" class="legislativo-dialog-close" data-close-dialog aria-label="Fechar">×</button></header>
		<form id="legislativo-calendar-form" class="legislativo-inline-form">
			<label>Nome <input name="name" required maxlength="255" placeholder="Recesso parlamentar"></label>
			<label>Tipo <select name="kind"><option value="holiday">Feriado</option><option value="optional">Ponto facultativo</option><option value="recess">Recesso</option></select></label>
			<label>Início <input name="startsOn" type="date" required></label>
			<label>Fim <input name="endsOn" type="date" required></label>
			<button type="submit" class="primary">Adicionar</button>
		</form>
		<div id="legislativo-calendar-list" class="legislativo-management-list"><p class="legislativo-empty">Carregando…</p></div>
	</dialog>
	<dialog id="legislativo-norm-dialog" class="legislativo-dialog">
		<header><div><p class="legislativo-eyebrow">Legislação consolidada</p><h2>Normas e versões</h2></div><button type="button" class="legislativo-dialog-close" data-close-dialog aria-label="Fechar">×</button></header>
		<form id="legislativo-norm-filter" class="legislativo-inline-form legislativo-search-filter"><input name="query" type="search" placeholder="Texto, título ou ementa"><select name="queryMode"><option value="all">Todos os termos (E)</option><option value="any">Qualquer termo (OU)</option><option value="phrase">Frase exata</option></select><input name="yearFrom" type="number" min="1800" max="2200" placeholder="Ano inicial"><input name="yearTo" type="number" min="1800" max="2200" placeholder="Ano final"><select name="sort"><option value="year">Ordenar por ano</option><option value="number">Ordenar por número</option><option value="title">Ordenar por título</option><option value="publishedAt">Ordenar por publicação</option></select><select name="direction"><option value="desc">Decrescente</option><option value="asc">Crescente</option></select><button class="button" type="submit">Pesquisar</button></form>
		<form id="legislativo-norm-form" class="legislativo-inline-form"><input name="type" required maxlength="64" placeholder="Lei"><input name="number" required type="number" min="1" placeholder="Número"><input name="year" required type="number" min="1800" max="2200" placeholder="Ano"><input name="title" required maxlength="512" placeholder="Título da norma"><select name="status"><option value="draft">Rascunho</option><option value="published">Publicada</option><option value="revoked">Revogada</option></select><textarea name="ementa" placeholder="Ementa"></textarea><button class="primary" type="submit">Salvar norma</button></form>
		<div id="legislativo-norm-list" class="legislativo-management-list"><p class="legislativo-empty">Carregando…</p></div>
		<form id="legislativo-norm-version-form" class="legislativo-inline-form" hidden><input name="normId" type="hidden"><input name="label" required placeholder="Versão original"><input name="validFrom" required type="date"><input name="validUntil" type="date"><textarea name="bodyHtml" required placeholder="Texto HTML da norma"></textarea><button class="button" type="submit">Adicionar versão</button></form>
		<section class="legislativo-norm-import"><h3>Importar normas em lote</h3><p class="legislativo-form-help">CSV UTF-8 com as colunas <code>type,number,year,title,ementa,status,body_html,version_label,valid_from,valid_until</code>. Primeiro valide a prévia; somente o botão de importação grava dados.</p><form id="legislativo-norm-import-form"><textarea name="csv" rows="7" required placeholder="type,number,year,title,ementa,status,body_html,version_label,valid_from,valid_until&#10;Lei,1,2026,Título da norma,Ementa,published,&lt;p&gt;Art. 1º...&lt;/p&gt;,Texto original,2026-01-01,"></textarea><div class="legislativo-detail-actions"><button class="button" type="button" data-norm-import-preview>Validar prévia</button><button class="primary" type="button" data-norm-import-apply>Importar lote</button></div></form><div id="legislativo-norm-import-result" class="legislativo-form-help" hidden></div></section>
	</dialog>
	<dialog id="legislativo-audit-dialog" class="legislativo-dialog legislativo-dialog--wide">
		<header><div><p class="legislativo-eyebrow">Rastreabilidade</p><h2>Auditoria do sistema</h2></div><button type="button" class="legislativo-dialog-close" data-close-dialog aria-label="Fechar">×</button></header>
		<form id="legislativo-audit-filter" class="legislativo-inline-form legislativo-audit-filter">
			<label>Módulo <select name="entityType"><option value="">Todos</option><option value="matter">Matérias</option><option value="session">Sessões</option><option value="norm">Normas</option><option value="calendar">Calendário</option><option value="parliamentarian">Parlamentares</option></select></label>
			<label>ID da entidade <input name="entityId" type="number" min="1"></label>
			<label>Ação <input name="action" maxlength="64" placeholder="matter.update"></label>
			<label>Usuário <input name="userUid" maxlength="64"></label>
			<label>Data inicial <input name="from" type="date"></label>
			<label>Data final <input name="to" type="date"></label>
			<button type="submit" class="primary">Filtrar</button>
		</form>
		<div id="legislativo-audit-summary" class="legislativo-deadline-summary" role="status"></div>
		<div id="legislativo-audit-list" class="legislativo-management-list" aria-live="polite"><p class="legislativo-empty">Carregando…</p></div>
	</dialog>
<?php endif; ?>

<dialog id="legislativo-deadline-dialog" class="legislativo-dialog legislativo-dialog--wide">
	<header><div><p class="legislativo-eyebrow">Controle de tramitação</p><h2>Prazos legislativos</h2></div><button type="button" class="legislativo-dialog-close" data-close-deadlines aria-label="Fechar">×</button></header>
	<form id="legislativo-deadline-filter" class="legislativo-inline-form legislativo-deadline-filter">
		<label>Situação
			<select name="state">
				<option value="open">Todos em aberto</option>
				<option value="overdue">Vencidos</option>
				<option value="due_today">Vencem hoje</option>
				<option value="upcoming">A vencer</option>
				<option value="no_deadline">Sem prazo</option>
				<option value="completed">Concluídos</option>
			</select>
		</label>
		<label>Destinatário <input name="recipient" maxlength="255" placeholder="Comissão ou setor"></label>
		<label>Tipo de matéria <input name="type" maxlength="64" placeholder="Projeto de Lei"></label>
		<label>Vencimento inicial <input name="dueFrom" type="date"></label>
		<label>Vencimento final <input name="dueTo" type="date"></label>
		<button type="submit" class="primary">Filtrar</button>
	</form>
	<div id="legislativo-deadline-summary" class="legislativo-deadline-summary" role="status"></div>
	<div id="legislativo-deadline-list" class="legislativo-management-list" aria-live="polite"><p class="legislativo-empty">Carregando…</p></div>
</dialog>

<dialog id="legislativo-voting-dialog" class="legislativo-dialog legislativo-dialog--voting">
	<header><div><p class="legislativo-eyebrow">Plenário eletrônico</p><h2>Sessões e votações</h2></div><button type="button" class="legislativo-dialog-close" data-close-voting aria-label="Fechar">×</button></header>
	<?php if ($canWrite): ?>
		<form id="legislativo-session-form" class="legislativo-inline-form legislativo-session-form">
			<label>Tipo <input name="type" required maxlength="64" value="Ordinária"></label>
			<label>Número <input name="number" type="number" min="1" required></label>
			<label>Ano <input name="year" type="number" min="1900" max="2200" required></label>
			<label>Data e hora <input name="scheduledAt" type="datetime-local" required></label>
			<label>Cadeiras <input name="totalSeats" type="number" min="1" max="999" value="11" required></label>
			<button type="submit" class="primary">Criar sessão</button>
		</form>
		<form id="legislativo-parliamentarian-form" class="legislativo-inline-form legislativo-parliamentarian-form">
			<label>Usuário <input name="userUid" required placeholder="usuário Nextcloud"></label>
			<label>Partido <input name="party" maxlength="32"></label>
			<label>Função <input name="role" value="vereador" maxlength="64"></label>
			<label>Cadeira <input name="seatNumber" type="number" min="1"></label>
			<label>Início mandato <input name="termStart" type="date" required></label>
			<label>Fim mandato <input name="termEnd" type="date" required></label>
			<input name="active" type="hidden" value="true">
			<button type="submit" class="button">Salvar parlamentar</button>
		</form>
		<div id="legislativo-parliamentarian-list" class="legislativo-parliamentarian-list"></div>
	<?php endif; ?>
	<div class="legislativo-voting-layout">
		<nav id="legislativo-session-list" class="legislativo-session-list" aria-label="Sessões"><p class="legislativo-empty">Carregando…</p></nav>
		<section id="legislativo-session-detail" class="legislativo-session-detail"><p class="legislativo-empty">Selecione uma sessão.</p></section>
	</div>
</dialog>
