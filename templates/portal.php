<?php

/**
 * SPDX-FileCopyrightText: 2026 LibreCode
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
$parliamentarianId = (int)($_['parliamentarianId'] ?? 0);
?>
<a class="portal-skip-link" href="#portal-content">Ir para o conteúdo</a>
<main id="legislativo-public-portal" class="legislativo-public-portal" data-parliamentarian-id="<?php p((string)$parliamentarianId); ?>">
	<header class="portal-hero">
		<div>
			<p class="portal-eyebrow">Câmara Municipal de Conchal</p>
			<h1>Transparência legislativa</h1>
			<p>Consulte matérias, normas, sessões plenárias e a atuação dos vereadores.</p>
		</div>
		<div class="portal-hero-actions">
			<div class="portal-accessibility" aria-label="Recursos de acessibilidade">
				<button type="button" data-font-decrease aria-label="Diminuir texto">A−</button>
				<button type="button" data-font-reset aria-label="Restaurar tamanho do texto">A</button>
				<button type="button" data-font-increase aria-label="Aumentar texto">A+</button>
				<button type="button" data-contrast aria-pressed="false">Alto contraste</button>
			</div>
			<a class="portal-button" href="<?php p($_['baseUrl'] ?? '/'); ?>">Gestão interna</a>
		</div>
	</header>

	<section class="portal-global" aria-labelledby="portal-global-title">
		<h2 id="portal-global-title">Busca global</h2>
		<form id="portal-global-search" role="search">
			<label class="portal-sr-only" for="portal-global-query">Pesquisar em todo o portal</label>
			<input id="portal-global-query" name="query" type="search" minlength="2" required placeholder="Matéria, norma, sessão ou vereador">
			<button class="portal-button" type="submit">Buscar em tudo</button>
		</form>
		<div id="portal-global-results" class="portal-global-results" aria-live="polite" hidden></div>
	</section>

	<nav class="portal-tabs" aria-label="Conteúdo público" role="tablist">
		<button id="portal-tab-matters" class="is-active" type="button" role="tab" aria-selected="true" aria-controls="portal-view-matters" data-portal-tab="matters">Matérias</button>
		<button id="portal-tab-norms" type="button" role="tab" aria-selected="false" aria-controls="portal-view-norms" data-portal-tab="norms">Legislação</button>
		<button id="portal-tab-sessions" type="button" role="tab" aria-selected="false" aria-controls="portal-view-sessions" data-portal-tab="sessions">Sessões</button>
		<button id="portal-tab-parliamentarians" type="button" role="tab" aria-selected="false" aria-controls="portal-view-parliamentarians" data-portal-tab="parliamentarians">Vereadores</button>
	</nav>

	<div id="portal-content">
		<section id="portal-view-matters" role="tabpanel" aria-labelledby="portal-tab-matters" data-portal-view="matters">
			<form id="portal-matter-filter" class="portal-filter" role="search">
				<input name="query" type="search" placeholder="Buscar por assunto, texto ou tema" aria-label="Texto da matéria">
				<select name="status" aria-label="Situação"><option value="">Todas as situações</option><option value="protocolled">Protocoladas</option><option value="processing">Em tramitação</option><option value="agenda">Em pauta</option><option value="approved">Aprovadas</option><option value="rejected">Rejeitadas</option></select>
				<input name="year" type="number" min="1900" max="2200" placeholder="Ano exato" aria-label="Ano exato">
				<button class="portal-button" type="submit">Pesquisar</button>
				<details class="portal-advanced"><summary>Busca avançada</summary><div><label>Correspondência <select name="queryMode"><option value="all">Todos os termos (E)</option><option value="any">Qualquer termo (OU)</option><option value="phrase">Frase exata</option></select></label><label>Número inicial <input name="numberFrom" type="number" min="1"></label><label>Número final <input name="numberTo" type="number" min="1"></label><label>Ano inicial <input name="yearFrom" type="number" min="1900" max="2200"></label><label>Ano final <input name="yearTo" type="number" min="1900" max="2200"></label><label>Ordenar <select name="sort"><option value="year">Ano</option><option value="number">Número</option><option value="subject">Assunto</option><option value="presentedAt">Apresentação</option></select></label><label>Direção <select name="direction"><option value="desc">Decrescente</option><option value="asc">Crescente</option></select></label></div></details>
			</form>
			<div id="portal-matter-list" class="portal-grid" aria-live="polite"><p class="portal-empty">Carregando matérias…</p></div>
		</section>

		<section id="portal-view-sessions" role="tabpanel" aria-labelledby="portal-tab-sessions" data-portal-view="sessions" hidden><div id="portal-session-list" class="portal-grid" aria-live="polite"><p class="portal-empty">Carregando sessões…</p></div></section>

		<section id="portal-view-norms" role="tabpanel" aria-labelledby="portal-tab-norms" data-portal-view="norms" hidden>
			<form id="portal-norm-filter" class="portal-filter" role="search">
				<input name="query" type="search" placeholder="Buscar norma, ementa ou tipo" aria-label="Texto da norma">
				<select name="type" aria-label="Tipo da norma"><option value="">Todos os tipos</option><option value="Lei">Lei</option><option value="Decreto">Decreto</option><option value="Resolução">Resolução</option></select>
				<input name="year" type="number" min="1800" max="2200" placeholder="Ano exato" aria-label="Ano exato">
				<button class="portal-button" type="submit">Pesquisar</button>
				<details class="portal-advanced"><summary>Busca avançada</summary><div><label>Correspondência <select name="queryMode"><option value="all">Todos os termos (E)</option><option value="any">Qualquer termo (OU)</option><option value="phrase">Frase exata</option></select></label><label>Número inicial <input name="numberFrom" type="number" min="1"></label><label>Número final <input name="numberTo" type="number" min="1"></label><label>Ano inicial <input name="yearFrom" type="number" min="1800" max="2200"></label><label>Ano final <input name="yearTo" type="number" min="1800" max="2200"></label><label>Ordenar <select name="sort"><option value="year">Ano</option><option value="number">Número</option><option value="title">Título</option><option value="publishedAt">Publicação</option></select></label><label>Direção <select name="direction"><option value="desc">Decrescente</option><option value="asc">Crescente</option></select></label></div></details>
			</form>
			<div id="portal-norm-list" class="portal-grid" aria-live="polite"><p class="portal-empty">Carregando legislação…</p></div>
		</section>

		<section id="portal-view-parliamentarians" role="tabpanel" aria-labelledby="portal-tab-parliamentarians" data-portal-view="parliamentarians" hidden>
			<form id="portal-parliamentarian-filter" class="portal-filter portal-filter--simple" role="search"><input name="query" type="search" placeholder="Nome, partido ou função" aria-label="Pesquisar vereador"><button class="portal-button" type="submit">Pesquisar</button></form>
			<div id="portal-parliamentarian-list" class="portal-grid" aria-live="polite"><p class="portal-empty">Carregando vereadores…</p></div>
		</section>
	</div>

	<dialog id="portal-matter-dialog" class="portal-dialog" aria-labelledby="portal-dialog-title">
		<button class="portal-dialog-close" type="button" data-close-portal aria-label="Fechar">×</button>
		<div id="portal-matter-detail"></div>
	</dialog>
</main>
