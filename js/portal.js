(function () {
	'use strict';

	var root = document.getElementById('legislativo-public-portal');
	if (!root) return;

	var dialog = document.getElementById('portal-matter-dialog');
	var detail = document.getElementById('portal-matter-detail');
	var returnFocus = null;
	var labels = {
		draft: 'Rascunho', protocolled: 'Protocolada', processing: 'Em tramitação', agenda: 'Em pauta',
		approved: 'Aprovada', rejected: 'Rejeitada', archived: 'Arquivada', open: 'Aberta', closed: 'Encerrada'
	};
	var kindLabels = { matter: 'Matéria', norm: 'Norma', session: 'Sessão', parliamentarian: 'Vereador' };

	function api(path) {
		return fetch(OC.generateUrl('/apps/legislativo/api/public/portal' + path), { headers: { Accept: 'application/json' } })
			.then(function (response) {
				return response.json().then(function (payload) {
					if (!response.ok) throw new Error(payload.message || 'Falha ao carregar o portal.');
					return payload.data;
				});
			});
	}

	function el(tag, className, text) {
		var node = document.createElement(tag);
		if (className) node.className = className;
		if (typeof text !== 'undefined') node.textContent = text;
		return node;
	}

	function date(value) {
		if (!value) return '—';
		return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short' }).format(new Date(value.length === 10 ? value + 'T12:00:00' : value));
	}

	function showLoading() {
		returnFocus = document.activeElement;
		detail.replaceChildren(el('p', 'portal-empty', 'Carregando…'));
		if (!dialog.open) dialog.showModal();
	}

	function setDialogTitle(title) {
		var heading = el('h2', '', title);
		heading.id = 'portal-dialog-title';
		return heading;
	}

	function closeDialog() {
		if (dialog.open) dialog.close();
		if (returnFocus && typeof returnFocus.focus === 'function') returnFocus.focus();
	}

	function activateTab(name) {
		document.querySelectorAll('[data-portal-tab]').forEach(function (tab) {
			var active = tab.dataset.portalTab === name;
			tab.classList.toggle('is-active', active);
			tab.setAttribute('aria-selected', active ? 'true' : 'false');
			tab.tabIndex = active ? 0 : -1;
		});
		document.querySelectorAll('[data-portal-view]').forEach(function (view) { view.hidden = view.dataset.portalView !== name; });
		if (name === 'sessions') loadSessions();
		if (name === 'norms') loadNorms();
		if (name === 'parliamentarians') loadParliamentarians();
	}

	function loadMatters() {
		var node = document.getElementById('portal-matter-list');
		node.replaceChildren(el('p', 'portal-empty', 'Carregando…'));
		var params = new URLSearchParams(new FormData(document.getElementById('portal-matter-filter')));
		return api('/matters?' + params).then(function (items) {
			node.replaceChildren();
			if (!items.length) return node.appendChild(el('p', 'portal-empty', 'Nenhuma matéria encontrada.'));
			items.forEach(function (item) {
				var card = el('article', 'portal-card');
				card.appendChild(el('small', 'portal-kicker', item.type + ' nº ' + item.number + '/' + item.year));
				card.appendChild(el('h2', '', item.subject));
				card.appendChild(el('span', 'portal-status', labels[item.status] || item.status));
				card.appendChild(el('p', '', [item.theme, item.authorUid ? 'Autoria: ' + item.authorUid : ''].filter(Boolean).join(' · ')));
				var button = el('button', 'portal-link', 'Ver tramitação');
				button.type = 'button';
				button.addEventListener('click', function () { showMatter(item.id); });
				card.appendChild(button);
				node.appendChild(card);
			});
		});
	}

	function showMatter(id) {
		showLoading();
		api('/matters/' + id).then(function (item) {
			detail.replaceChildren(el('p', 'portal-kicker', item.type + ' nº ' + item.number + '/' + item.year), setDialogTitle(item.subject));
			if (item.body) detail.appendChild(el('p', 'portal-detail-body', item.body));
			var timeline = el('div', 'portal-timeline');
			(item.protocols || []).forEach(function (protocol) { timeline.appendChild(el('article', '', 'Protocolo ' + protocol.number + '/' + protocol.year + ' · ' + protocol.sender + ' · ' + date(protocol.receivedAt))); });
			(item.proceedings || []).forEach(function (proceeding) { timeline.appendChild(el('article', '', proceeding.recipient + ' · ' + proceeding.objective + ' · ' + date(proceeding.sentAt) + (proceeding.dueAt ? ' · prazo ' + date(proceeding.dueAt) : ''))); });
			(item.votes || []).forEach(function (vote) { timeline.appendChild(el('article', '', 'Sessão ' + vote.sessionNumber + '/' + vote.sessionYear + ' · ' + (labels[vote.status] || vote.status) + ' · ' + (vote.result || 'pendente'))); });
			if (!timeline.children.length) timeline.appendChild(el('p', 'portal-empty', 'Nenhum evento público registrado.'));
			detail.appendChild(timeline);
		}).catch(function (error) { detail.replaceChildren(setDialogTitle('Não foi possível abrir a matéria'), el('p', '', error.message)); });
	}

	function loadSessions() {
		var node = document.getElementById('portal-session-list');
		node.replaceChildren(el('p', 'portal-empty', 'Carregando…'));
		return api('/sessions').then(function (items) {
			node.replaceChildren();
			if (!items.length) return node.appendChild(el('p', 'portal-empty', 'Nenhuma sessão encontrada.'));
			items.forEach(function (item) {
				var card = el('article', 'portal-card');
				card.appendChild(el('small', 'portal-kicker', item.type + ' nº ' + item.number + '/' + item.year));
				card.appendChild(el('h2', '', labels[item.status] || item.status));
				card.appendChild(el('p', '', date(item.scheduledAt) + ' · ' + item.presentCount + '/' + item.totalSeats + ' presentes'));
				(item.agenda || []).filter(function (agenda) { return agenda.status === 'closed'; }).forEach(function (agenda) { card.appendChild(el('p', 'portal-result', agenda.matter.subject + ' · ' + (labels[agenda.result] || agenda.result))); });
				var link = el('a', 'portal-link', 'Abrir painel plenário');
				link.href = OC.generateUrl('/apps/legislativo/panel/' + item.id); link.target = '_blank'; link.rel = 'noopener';
				card.appendChild(link); node.appendChild(card);
			});
		});
	}

	function loadNorms() {
		var node = document.getElementById('portal-norm-list');
		node.replaceChildren(el('p', 'portal-empty', 'Carregando…'));
		var params = new URLSearchParams(new FormData(document.getElementById('portal-norm-filter')));
		return api('/norms?' + params).then(function (items) {
			node.replaceChildren();
			if (!items.length) return node.appendChild(el('p', 'portal-empty', 'Nenhuma norma publicada encontrada.'));
			items.forEach(function (item) {
				var card = el('article', 'portal-card');
				card.appendChild(el('small', 'portal-kicker', item.type + ' nº ' + item.number + '/' + item.year));
				card.appendChild(el('h2', '', item.title));
				if (item.ementa) card.appendChild(el('p', '', item.ementa));
				var button = el('button', 'portal-link', 'Ver texto vigente'); button.type = 'button';
				button.addEventListener('click', function () { showNorm(item.id); });
				card.appendChild(button); node.appendChild(card);
			});
		});
	}

	function showNorm(id) {
		showLoading();
		api('/norms/' + id).then(function (data) {
			var item = data.norm; var version = (data.versions || [])[0];
			detail.replaceChildren(el('p', 'portal-kicker', item.type + ' nº ' + item.number + '/' + item.year), setDialogTitle(item.title));
			if (item.ementa) detail.appendChild(el('p', '', item.ementa));
			if (version) {
				var body = el('div', 'portal-detail-body'); body.innerHTML = version.bodyHtml; detail.appendChild(body);
				detail.appendChild(el('small', '', 'Versão ' + version.label + ' · vigente desde ' + version.validFrom));
			}
		}).catch(function (error) { detail.replaceChildren(setDialogTitle('Não foi possível abrir a norma'), el('p', '', error.message)); });
	}

	function loadParliamentarians() {
		var node = document.getElementById('portal-parliamentarian-list');
		var form = document.getElementById('portal-parliamentarian-filter');
		var params = new URLSearchParams(new FormData(form));
		node.replaceChildren(el('p', 'portal-empty', 'Carregando…'));
		return api('/parliamentarians?' + params).then(function (items) {
			node.replaceChildren();
			if (!items.length) return node.appendChild(el('p', 'portal-empty', 'Nenhum vereador encontrado.'));
			items.forEach(function (profile) {
				var card = el('article', 'portal-card portal-parliamentarian-card');
				var avatar = el('span', 'portal-avatar', profile.displayName.split(/\s+/).slice(0, 2).map(function (part) { return part.charAt(0); }).join('').toUpperCase());
				avatar.setAttribute('aria-hidden', 'true'); card.appendChild(avatar);
				card.appendChild(el('small', 'portal-kicker', profile.role)); card.appendChild(el('h2', '', profile.displayName));
				card.appendChild(el('p', '', [profile.party, profile.seatNumber ? 'Cadeira ' + profile.seatNumber : ''].filter(Boolean).join(' · ')));
				var link = el('a', 'portal-link', 'Ver perfil e proposições');
				link.href = OC.generateUrl('/apps/legislativo/portal/parliamentarians/' + profile.id);
				link.addEventListener('click', function (event) { event.preventDefault(); window.history.pushState({}, '', link.href); showParliamentarian(profile.id); });
				card.appendChild(link); node.appendChild(card);
			});
		});
	}

	function showParliamentarian(id) {
		showLoading();
		api('/parliamentarians/' + id).then(function (data) {
			var profile = data.profile;
			detail.replaceChildren(el('p', 'portal-kicker', profile.role), setDialogTitle(profile.displayName));
			detail.appendChild(el('p', '', [profile.party, profile.seatNumber ? 'Cadeira ' + profile.seatNumber : null].filter(Boolean).join(' · ')));
			detail.appendChild(el('p', '', 'Mandato: ' + date(profile.termStart) + ' a ' + date(profile.termEnd)));
			var heading = el('h3', '', 'Proposições de autoria'); detail.appendChild(heading);
			var list = el('div', 'portal-profile-matters');
			(data.matters || []).forEach(function (matter) { var button = el('button', 'portal-profile-matter', matter.type + ' nº ' + matter.number + '/' + matter.year + ' — ' + matter.subject); button.type = 'button'; button.addEventListener('click', function () { showMatter(matter.id); }); list.appendChild(button); });
			if (!(data.matters || []).length) list.appendChild(el('p', 'portal-empty', 'Nenhuma proposição vinculada a este cadastro.'));
			detail.appendChild(list);
		}).catch(function (error) { detail.replaceChildren(setDialogTitle('Não foi possível abrir o perfil'), el('p', '', error.message)); });
	}

	function globalSearch(query) {
		var node = document.getElementById('portal-global-results'); node.hidden = false;
		node.replaceChildren(el('p', 'portal-empty', 'Pesquisando todo o portal…'));
		return api('/search?query=' + encodeURIComponent(query)).then(function (items) {
			node.replaceChildren();
			if (!items.length) return node.appendChild(el('p', 'portal-empty', 'Nenhum resultado encontrado.'));
			items.forEach(function (item) {
				var button = el('button', 'portal-global-result'); button.type = 'button';
				button.appendChild(el('span', 'portal-status', kindLabels[item.kind] || item.kind));
				var text = el('span'); text.appendChild(el('strong', '', item.title)); text.appendChild(el('small', '', item.meta)); button.appendChild(text);
				button.addEventListener('click', function () {
					if (item.kind === 'matter') showMatter(item.id);
					if (item.kind === 'norm') showNorm(item.id);
					if (item.kind === 'parliamentarian') { activateTab('parliamentarians'); showParliamentarian(item.id); }
					if (item.kind === 'session') window.open(OC.generateUrl('/apps/legislativo/panel/' + item.id), '_blank', 'noopener');
				});
				node.appendChild(button);
			});
		});
	}

	function applyAccessibility() {
		var scale = Math.max(.9, Math.min(1.3, Number(localStorage.getItem('legislativo-font-scale')) || 1));
		var contrast = localStorage.getItem('legislativo-high-contrast') === '1';
		function render() { root.style.setProperty('--portal-font-scale', String(scale)); root.dataset.contrast = contrast ? 'high' : 'normal'; document.querySelector('[data-contrast]').setAttribute('aria-pressed', contrast ? 'true' : 'false'); }
		document.querySelector('[data-font-decrease]').addEventListener('click', function () { scale = Math.max(.9, scale - .1); localStorage.setItem('legislativo-font-scale', String(scale)); render(); });
		document.querySelector('[data-font-reset]').addEventListener('click', function () { scale = 1; localStorage.setItem('legislativo-font-scale', '1'); render(); });
		document.querySelector('[data-font-increase]').addEventListener('click', function () { scale = Math.min(1.3, scale + .1); localStorage.setItem('legislativo-font-scale', String(scale)); render(); });
		document.querySelector('[data-contrast]').addEventListener('click', function () { contrast = !contrast; localStorage.setItem('legislativo-high-contrast', contrast ? '1' : '0'); render(); });
		render();
	}

	document.getElementById('portal-matter-filter').addEventListener('submit', function (event) { event.preventDefault(); loadMatters(); });
	document.getElementById('portal-norm-filter').addEventListener('submit', function (event) { event.preventDefault(); loadNorms(); });
	document.getElementById('portal-parliamentarian-filter').addEventListener('submit', function (event) { event.preventDefault(); loadParliamentarians(); });
	document.getElementById('portal-global-search').addEventListener('submit', function (event) { event.preventDefault(); globalSearch(event.currentTarget.elements.query.value); });
	document.querySelector('[data-close-portal]').addEventListener('click', closeDialog);
	dialog.addEventListener('cancel', function (event) { event.preventDefault(); closeDialog(); });
	document.querySelectorAll('[data-portal-tab]').forEach(function (tab) {
		tab.addEventListener('click', function () { activateTab(tab.dataset.portalTab); });
		tab.addEventListener('keydown', function (event) { if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return; var tabs = Array.from(document.querySelectorAll('[data-portal-tab]')); var index = tabs.indexOf(tab) + (event.key === 'ArrowRight' ? 1 : -1); tabs[(index + tabs.length) % tabs.length].focus(); tabs[(index + tabs.length) % tabs.length].click(); });
	});
	window.addEventListener('popstate', function () { closeDialog(); });

	applyAccessibility();
	loadMatters();
	var initialParliamentarian = Number(root.dataset.parliamentarianId || 0);
	if (initialParliamentarian > 0) { activateTab('parliamentarians'); showParliamentarian(initialParliamentarian); }
}());
