(function () {
	'use strict';

	var root = document.querySelector('.legislativo-dashboard');
	if (!root) return;

	var canWrite = root.dataset.canWrite === '1';
	var canPreside = root.dataset.canPreside === '1';
	var listNode = document.getElementById('legislativo-matter-list');
	var countNode = document.getElementById('legislativo-result-count');
	var detailNode = document.getElementById('legislativo-detail');
	var feedbackNode = document.getElementById('legislativo-feedback');
	var searchForm = document.getElementById('legislativo-search-form');
	var selectedId = null;
	var selectedMatter = null;
	var selectedSessionId = null;
	var votingTimer = null;
	var adminTimerState = null;

	var statusLabels = {
		draft: 'Rascunho', received: 'Recebida', protocolled: 'Protocolada',
		processing: 'Em tramitação', agenda: 'Em pauta', approved: 'Aprovada',
		rejected: 'Rejeitada', archived: 'Arquivada'
	};
	var deadlineLabels = {
		overdue: 'Vencido', due_today: 'Vence hoje', upcoming: 'A vencer',
		completed: 'Concluído', no_deadline: 'Sem prazo'
	};

	function api(path, options) {
		var config = Object.assign({ headers: {} }, options || {});
		config.headers = Object.assign({
			'Accept': 'application/json',
			'requesttoken': OC.requestToken
		}, config.headers || {});
		if (config.body && typeof config.body !== 'string') {
			config.headers['Content-Type'] = 'application/json';
			config.body = JSON.stringify(config.body);
		}

		return fetch(OC.generateUrl('/apps/legislativo/api/v1' + path), config)
			.then(function (response) {
				return response.json().catch(function () { return {}; }).then(function (payload) {
					if (!response.ok) throw new Error(payload.message || 'Não foi possível concluir a operação.');
					return payload;
				});
			});
	}

	function el(tag, className, text) {
		var node = document.createElement(tag);
		if (className) node.className = className;
		if (typeof text !== 'undefined') node.textContent = text;
		return node;
	}

	function setFeedback(message, kind) {
		feedbackNode.textContent = message || '';
		feedbackNode.className = 'legislativo-feedback legislativo-feedback--' + (kind || 'info');
		feedbackNode.hidden = !message;
		if (message) window.setTimeout(function () { feedbackNode.hidden = true; }, 6000);
	}

	function formatDate(value, withTime) {
		if (!value) return '—';
		var date = new Date(value.length === 10 ? value + 'T12:00:00' : value);
		return new Intl.DateTimeFormat('pt-BR', withTime ? {
			dateStyle: 'short', timeStyle: 'short'
		} : { dateStyle: 'short' }).format(date);
	}

	function formData(form) {
		var data = {};
		new FormData(form).forEach(function (value, key) { data[key] = value; });
		form.querySelectorAll('input[type="checkbox"]').forEach(function (input) {
			data[input.name] = input.checked;
		});
		Object.keys(data).forEach(function (key) {
			if (data[key] === '') delete data[key];
		});
		return data;
	}

	function loadList() {
		listNode.replaceChildren(el('p', 'legislativo-empty', 'Carregando matérias…'));
		var params = new URLSearchParams(new FormData(searchForm));
		return api('/matters?' + params.toString()).then(function (payload) {
			renderList(payload.data || []);
		}).catch(function (error) {
			listNode.replaceChildren(el('p', 'legislativo-empty', error.message));
		});
	}

	function renderList(matters) {
		listNode.replaceChildren();
		countNode.textContent = matters.length + (matters.length === 1 ? ' registro' : ' registros');
		if (!matters.length) {
			listNode.appendChild(el('p', 'legislativo-empty', 'Nenhuma matéria encontrada.'));
			return;
		}

		matters.forEach(function (matter) {
			var button = el('button', 'legislativo-matter-row' + (matter.id === selectedId ? ' is-selected' : ''));
			button.type = 'button';
			button.dataset.id = matter.id;
			var top = el('span', 'legislativo-matter-row__top');
			top.appendChild(el('strong', '', matter.type + ' nº ' + matter.number + '/' + matter.year));
			top.appendChild(el('span', 'legislativo-status legislativo-status--' + matter.status, statusLabels[matter.status] || matter.status));
			button.appendChild(top);
			button.appendChild(el('span', 'legislativo-matter-row__subject', matter.subject));
			button.appendChild(el('small', '', [matter.theme, formatDate(matter.presentedAt, false)].filter(Boolean).join(' · ')));
			button.addEventListener('click', function () { loadDetail(matter.id); });
			listNode.appendChild(button);
		});
	}

	function loadDetail(id) {
		selectedId = Number(id);
		detailNode.replaceChildren(el('p', 'legislativo-empty', 'Carregando detalhes…'));
		return api('/matters/' + id).then(function (payload) {
			selectedMatter = payload.data.matter;
			selectedMatter._submissions = payload.data.submissions || [];
			selectedMatter._protocols = payload.data.protocols || [];
			renderDetail(payload.data);
			loadList();
		}).catch(function (error) {
			detailNode.replaceChildren(el('p', 'legislativo-empty', error.message));
		});
	}

	function detailField(label, value) {
		var item = el('div', 'legislativo-data-item');
		item.appendChild(el('dt', '', label));
		item.appendChild(el('dd', '', value || '—'));
		return item;
	}

	function renderDetail(data) {
		var matter = data.matter;
		detailNode.replaceChildren();
		var header = el('header', 'legislativo-detail-header');
		var title = el('div');
		title.appendChild(el('p', 'legislativo-eyebrow', matter.type + ' nº ' + matter.number + '/' + matter.year));
		title.appendChild(el('h2', '', matter.subject));
		header.appendChild(title);

		if (canWrite) {
			var actions = el('div', 'legislativo-detail-actions');
			[['Editar', openEdit], ['Anexar', openAttachment], ['Preparar assinatura', openDocumentFlow], ['Enviar à triagem', openSubmission], ['Protocolar', openProtocol], ['Tramitar', openProceeding]].forEach(function (entry) {
				var button = el('button', 'button', entry[0]);
				button.type = 'button';
				button.addEventListener('click', entry[1]);
				actions.appendChild(button);
			});
			header.appendChild(actions);
		}
		detailNode.appendChild(header);

		var dl = el('dl', 'legislativo-data-grid');
		dl.appendChild(detailField('Situação', statusLabels[matter.status] || matter.status));
		dl.appendChild(detailField('Apresentação', formatDate(matter.presentedAt, false)));
		dl.appendChild(detailField('Autoria', matter.authorUid));
		dl.appendChild(detailField('Tema', matter.theme));
		dl.appendChild(detailField('Quórum', matter.quorum));
		dl.appendChild(detailField('Regime', matter.procedure));
		detailNode.appendChild(dl);

		var attachments = el('section', 'legislativo-attachments');
		attachments.appendChild(el('h3', '', 'Documentos vinculados'));
		if (!(data.attachments || []).length) attachments.appendChild(el('p', 'legislativo-empty-inline', 'Nenhum documento vinculado.'));
		(data.attachments || []).forEach(function (attachment) {
			var link = el('a', 'legislativo-file-link', attachment.fileName);
			link.href = OC.generateUrl('/f/' + attachment.fileId);
			link.target = '_blank'; link.rel = 'noopener';
			attachments.appendChild(link);
		});
		detailNode.appendChild(attachments);

		var documents = el('section', 'legislativo-attachments');
		documents.appendChild(el('h3', '', 'Fluxos de assinatura'));
		if (!(data.documentFlows || []).length) documents.appendChild(el('p', 'legislativo-empty-inline', 'Nenhum PDF preparado para assinatura.'));
		(data.documentFlows || []).forEach(function (flow) {
			var row = el('article', 'legislativo-document-flow');
			var content = el('div');
			var labels = { prepared: 'PDF preparado', requested: 'Enviado ao LibreSign', error: 'Falha no envio' };
			content.appendChild(el('strong', '', labels[flow.status] || flow.status));
			content.appendChild(el('p', '', 'SHA-256 ' + flow.checksum.slice(0, 16) + '… · ' + formatDate(flow.updatedAt, true)));
			if (flow.errorMessage) content.appendChild(el('small', 'legislativo-error-text', flow.errorMessage));
			row.appendChild(content);
			var actions = el('div', 'legislativo-detail-actions');
			var pdf = el('a', 'button', 'Abrir PDF'); pdf.href = OC.generateUrl('/f/' + flow.pdfFileId); pdf.target = '_blank'; pdf.rel = 'noopener'; actions.appendChild(pdf);
			if (flow.status === 'requested') {
				var libre = el('a', 'button', 'Abrir LibreSign'); libre.href = OC.generateUrl('/apps/libresign/'); libre.target = '_blank'; libre.rel = 'noopener'; actions.appendChild(libre);
			} else if (canWrite) {
				var retry = el('button', 'button', 'Reenviar'); retry.type = 'button'; retry.addEventListener('click', function () { retryDocumentFlow(flow); }); actions.appendChild(retry);
			}
			row.appendChild(actions); documents.appendChild(row);
		});
		detailNode.appendChild(documents);

		if (matter.body) {
			var body = el('section', 'legislativo-body');
			body.appendChild(el('h3', '', 'Texto/ementa'));
			body.appendChild(el('p', '', matter.body));
			detailNode.appendChild(body);
		}

		var timeline = el('section', 'legislativo-timeline');
		timeline.appendChild(el('h3', '', 'Linha do tempo'));
		var events = [];
		(data.protocols || []).forEach(function (protocol) {
			events.push({ at: protocol.receivedAt, title: 'Protocolo ' + protocol.number + '/' + protocol.year, text: 'Recebido de ' + protocol.sender });
		});
		(data.proceedings || []).forEach(function (proceeding) {
			events.push({ at: proceeding.sentAt, title: 'Tramitação para ' + proceeding.recipient, text: proceeding.objective + (proceeding.dueAt ? ' · prazo até ' + formatDate(proceeding.dueAt, false) : '') });
		});
		(data.submissions || []).forEach(function (submission) {
			events.push({ at: submission.submittedAt, title: 'Triagem · ' + submission.subject, text: submission.sender + ' · ' + submission.status });
		});
		(data.audit || []).forEach(function (audit) {
			events.push({ at: audit.createdAt, title: audit.action, text: 'Registrado por ' + audit.userUid + ' · IP ' + (audit.ipAddress || 'não informado'), audit: true });
		});
		events.sort(function (a, b) { return new Date(a.at) - new Date(b.at); });
		if (!events.length) timeline.appendChild(el('p', 'legislativo-empty', 'Nenhum evento registrado.'));
		events.forEach(function (event) {
			var row = el('article', 'legislativo-timeline-row' + (event.audit ? ' is-audit' : ''));
			row.appendChild(el('time', '', formatDate(event.at, true)));
			var content = el('div');
			content.appendChild(el('strong', '', event.title));
			content.appendChild(el('p', '', event.text));
			row.appendChild(content);
			timeline.appendChild(row);
		});
		detailNode.appendChild(timeline);
	}

	function openEdit() {
		var dialog = document.getElementById('legislativo-matter-dialog');
		var form = document.getElementById('legislativo-matter-form');
		form.reset();
		document.getElementById('legislativo-matter-dialog-title').textContent = 'Editar matéria';
		Object.keys(selectedMatter || {}).forEach(function (key) {
			var input = form.elements.namedItem(key);
			if (input && selectedMatter[key] !== null) input.value = selectedMatter[key];
		});
		dialog.showModal();
	}

	function openProtocol() {
		var form = document.getElementById('legislativo-protocol-form');
		form.reset();
		form.elements.matterId.value = selectedId;
		form.elements.year.value = new Date().getFullYear();
		var select = form.elements.submissionId;
		select.replaceChildren(new Option('Protocolo direto', ''));
		((selectedMatter && selectedMatter._submissions) || []).filter(function (item) { return item.status === 'accepted'; }).forEach(function (item) {
			select.appendChild(new Option(item.subject + ' — ' + item.sender, item.id));
		});
		document.getElementById('legislativo-protocol-dialog').showModal();
	}

	function openAttachment() {
		var form = document.getElementById('legislativo-attachment-form');
		form.reset(); form.elements.matterId.value = selectedId;
		document.getElementById('legislativo-attachment-dialog').showModal();
	}

	function openDocumentFlow() {
		if (!selectedMatter || !(selectedMatter._protocols || []).length) {
			setFeedback('Protocole a matéria antes de preparar o documento oficial.', 'error'); return;
		}
		var form = document.getElementById('legislativo-document-form'); form.reset(); form.elements.matterId.value = selectedId;
		var select = form.elements.protocolId; select.replaceChildren();
		selectedMatter._protocols.forEach(function (protocol) { select.appendChild(new Option('Protocolo ' + protocol.number + '/' + protocol.year, protocol.id)); });
		document.getElementById('legislativo-document-dialog').showModal();
	}

	function retryDocumentFlow(flow) {
		var signers = window.prompt('E-mails dos signatários, separados por vírgula:', (flow.signers || []).join(', '));
		if (!signers) return;
		api('/matters/' + flow.matterId + '/documents/' + flow.id + '/signature', { method: 'POST', body: { signers: signers } })
			.then(function () { setFeedback('Solicitação criada no LibreSign.', 'success'); return loadDetail(flow.matterId); })
			.catch(function (error) { setFeedback(error.message, 'error'); return loadDetail(flow.matterId); });
	}

	function openSubmission() {
		var form = document.getElementById('legislativo-submission-form');
		form.reset(); form.elements.matterId.value = selectedId;
		form.elements.subject.value = selectedMatter.subject;
		document.getElementById('legislativo-submission-dialog').showModal();
	}

	function openProceeding() {
		var form = document.getElementById('legislativo-proceeding-form');
		form.reset();
		form.elements.matterId.value = selectedId;
		form.elements.deadlineDays.value = 10;
		form.elements.businessDays.checked = true;
		document.getElementById('legislativo-proceeding-dialog').showModal();
	}

	function loadDeadlines() {
		var form = document.getElementById('legislativo-deadline-filter');
		var list = document.getElementById('legislativo-deadline-list');
		var summary = document.getElementById('legislativo-deadline-summary');
		list.replaceChildren(el('p', 'legislativo-empty', 'Carregando prazos…'));
		var params = new URLSearchParams(new FormData(form));
		return api('/deadlines?' + params.toString()).then(function (payload) {
			var deadlines = payload.data || [];
			list.replaceChildren();
			summary.textContent = deadlines.length + (deadlines.length === 1 ? ' tramitação encontrada' : ' tramitações encontradas');
			if (!deadlines.length) {
				list.appendChild(el('p', 'legislativo-empty', 'Nenhum prazo encontrado para os filtros informados.'));
				return;
			}
			deadlines.forEach(function (item) {
				var row = el('article', 'legislativo-deadline-row legislativo-deadline-row--' + item.deadlineState);
				var content = el('div', 'legislativo-deadline-content');
				var heading = el('div', 'legislativo-deadline-heading');
				heading.appendChild(el('strong', '', item.matter.type + ' nº ' + item.matter.number + '/' + item.matter.year));
				heading.appendChild(el('span', 'legislativo-deadline-state', deadlineLabels[item.deadlineState] || item.deadlineState));
				content.appendChild(heading);
				content.appendChild(el('p', '', item.matter.subject));
				content.appendChild(el('small', '', item.recipient + ' · ' + item.objective + ' · vencimento ' + formatDate(item.dueAt, false)));
				if (item.result) content.appendChild(el('small', 'legislativo-deadline-result', 'Resultado: ' + item.result));
				row.appendChild(content);
				var actions = el('div', 'legislativo-detail-actions');
				var matterButton = el('button', 'button', 'Abrir matéria');
				matterButton.type = 'button';
				matterButton.addEventListener('click', function () {
					document.getElementById('legislativo-deadline-dialog').close();
					loadDetail(item.matter.id);
				});
				actions.appendChild(matterButton);
				if (canWrite && !item.answeredAt) {
					var complete = el('button', 'primary', 'Concluir');
					complete.type = 'button';
					complete.addEventListener('click', function () {
						var result = window.prompt('Informe o resultado desta tramitação:');
						if (!result || !result.trim()) return;
						api('/proceedings/' + item.id, { method: 'PUT', body: { result: result.trim() } })
							.then(function () { setFeedback('Tramitação concluída.', 'success'); return loadDeadlines(); })
							.catch(function (error) { setFeedback(error.message, 'error'); });
					});
					actions.appendChild(complete);
				}
				row.appendChild(actions);
				list.appendChild(row);
			});
		}).catch(function (error) {
			list.replaceChildren(el('p', 'legislativo-empty', error.message));
			summary.textContent = '';
		});
	}

	var deadlineDialog = document.getElementById('legislativo-deadline-dialog');
	document.getElementById('legislativo-open-deadlines').addEventListener('click', function () { deadlineDialog.showModal(); loadDeadlines(); });
	document.querySelector('[data-close-deadlines]').addEventListener('click', function () { deadlineDialog.close(); });
	document.getElementById('legislativo-deadline-filter').addEventListener('submit', function (event) { event.preventDefault(); loadDeadlines(); });

	function loadVotingSessions(selectNewest) {
		var node = document.getElementById('legislativo-session-list');
		return api('/sessions').then(function (payload) {
			var sessions = payload.data || []; node.replaceChildren();
			if (!sessions.length) { node.appendChild(el('p', 'legislativo-empty', 'Nenhuma sessão cadastrada.')); return; }
			sessions.forEach(function (session) {
				var button = el('button', 'legislativo-session-row' + (session.id === selectedSessionId ? ' is-selected' : ''));
				button.type = 'button'; button.appendChild(el('strong', '', session.type + ' nº ' + session.number + '/' + session.year));
				button.appendChild(el('span', '', (session.status === 'scheduled' ? 'Agendada' : session.status === 'open' ? 'Em andamento' : 'Encerrada') + ' · ' + session.presentCount + '/' + session.totalSeats + ' presentes'));
				button.addEventListener('click', function () { selectedSessionId = session.id; loadSessionDetail(session.id); }); node.appendChild(button);
			});
			if ((selectNewest || !selectedSessionId) && sessions[0]) { selectedSessionId = sessions[0].id; return loadSessionDetail(selectedSessionId); }
		});
	}

	function loadSessionDetail(id) {
		return api('/sessions/' + id).then(function (payload) { renderSessionDetail(payload.data); });
	}

	function tallyText(tally) {
		if (!tally) return 'Sem votos';
		if (typeof tally.yes === 'undefined') return tally.total + ' voto(s) secreto(s) computado(s)';
		return 'Sim ' + tally.yes + ' · Não ' + tally.no + ' · Abstenção ' + tally.abstain + ' · Obstrução ' + tally.obstruction;
	}

	function sessionAction(path, body, message) {
		return api(path, { method: 'POST', body: body }).then(function () {
			setFeedback(message, 'success');
			return loadVotingSessions(false).then(function () { if (selectedSessionId) return loadSessionDetail(selectedSessionId); });
		}).catch(function (error) { setFeedback(error.message, 'error'); });
	}

	function renderSessionDetail(data) {
		var node = document.getElementById('legislativo-session-detail'); var session = data.session; node.replaceChildren();
		var header = el('div', 'legislativo-session-heading'); var title = el('div'); title.appendChild(el('p', 'legislativo-eyebrow', session.type + ' nº ' + session.number + '/' + session.year)); title.appendChild(el('h3', '', session.status === 'open' ? 'Sessão em andamento' : session.status === 'closed' ? 'Sessão encerrada' : 'Sessão agendada')); header.appendChild(title);
		var publicPanel = el('a', 'button', 'Painel público'); publicPanel.href = OC.generateUrl('/apps/legislativo/panel/' + session.id); publicPanel.target = '_blank'; publicPanel.rel = 'noopener';
		var controls = el('div', 'legislativo-detail-actions'); controls.appendChild(publicPanel);
		if (canPreside) {
			if (session.status === 'scheduled') { var open = el('button', 'primary', 'Abrir sessão'); open.type = 'button'; open.addEventListener('click', function () { sessionAction('/sessions/' + session.id + '/transition', { action: 'open' }, 'Sessão aberta.'); }); controls.appendChild(open); }
			if (session.status === 'open') { var close = el('button', 'button', 'Encerrar sessão'); close.type = 'button'; close.addEventListener('click', function () { sessionAction('/sessions/' + session.id + '/transition', { action: 'close' }, 'Sessão encerrada.'); }); controls.appendChild(close); }
		}
		if (canWrite) {
			var agendaPdf = el('button', 'button', 'Ordem do Dia PDF'); agendaPdf.type = 'button'; agendaPdf.addEventListener('click', function () { generateSessionDocument(session.id, 'agenda'); }); controls.appendChild(agendaPdf);
			if (session.status === 'closed') { var minutesPdf = el('button', 'button', 'Gerar ata PDF'); minutesPdf.type = 'button'; minutesPdf.addEventListener('click', function () { generateSessionDocument(session.id, 'minutes'); }); controls.appendChild(minutesPdf); }
		}
		if (session.agendaFileId) { var openAgenda = el('a', 'button', 'Abrir Ordem do Dia'); openAgenda.href = OC.generateUrl('/f/' + session.agendaFileId); openAgenda.target = '_blank'; openAgenda.rel = 'noopener'; controls.appendChild(openAgenda); }
		if (session.minutesFileId) { var openMinutes = el('a', 'button', 'Abrir ata'); openMinutes.href = OC.generateUrl('/f/' + session.minutesFileId); openMinutes.target = '_blank'; openMinutes.rel = 'noopener'; controls.appendChild(openMinutes); }
		header.appendChild(controls);
		node.appendChild(header);
		var stats = el('div', 'legislativo-vote-stats');
		[['Presentes', session.presentCount], ['Quórum mínimo', session.presenceRequired], ['Cadeiras', session.totalSeats], ['Itens na pauta', session.agendaCount]].forEach(function (item) { var card = el('div'); card.appendChild(el('strong', '', item[1])); card.appendChild(el('span', '', item[0])); stats.appendChild(card); }); node.appendChild(stats);

		var timer = el('section', 'legislativo-admin-timer'); var timerInfo = el('div'); timerInfo.appendChild(el('span', '', session.timerLabel || 'Cronômetro do plenário')); timerInfo.appendChild(el('strong', 'legislativo-admin-timer-value', '00:00')); timer.appendChild(timerInfo);
		adminTimerState = { status: session.timerStatus, start: session.timerStartedAt, duration: session.timerDuration, server: new Date(session.serverTime).getTime(), received: Date.now() };
if (canPreside && session.status === 'open') { var timerForm = document.createElement('form'); timerForm.className = 'legislativo-timer-form'; timerForm.innerHTML = '<input name="label" maxlength="255" placeholder="Discussão"><input name="duration" type="number" min="1" max="7200" value="300" aria-label="Segundos"><button class="primary" type="submit">Iniciar</button><button class="button" type="button" data-stop-timer>Parar</button><button class="button" type="button" data-reset-timer>Zerar</button>'; timerForm.addEventListener('submit', function (event) { event.preventDefault(); var values = formData(timerForm); values.action = 'start'; sessionAction('/sessions/' + session.id + '/timer', values, 'Cronômetro iniciado.'); }); timerForm.querySelector('[data-stop-timer]').addEventListener('click', function () { sessionAction('/sessions/' + session.id + '/timer', { action: 'stop' }, 'Cronômetro parado.'); }); timerForm.querySelector('[data-reset-timer]').addEventListener('click', function () { sessionAction('/sessions/' + session.id + '/timer', { action: 'reset' }, 'Cronômetro zerado.'); }); timer.appendChild(timerForm); }
		node.appendChild(timer);

		if (canWrite && session.status !== 'closed') {
			var management = el('div', 'legislativo-voting-management');
			var attendanceForm = document.createElement('form'); attendanceForm.className = 'legislativo-compact-form'; attendanceForm.innerHTML = '<strong>Presença</strong><input name="userUid" required placeholder="usuário Nextcloud"><select name="present"><option value="true">Presente</option><option value="false">Ausente</option></select><button class="button" type="submit">Registrar</button>';
			attendanceForm.addEventListener('submit', function (event) { event.preventDefault(); sessionAction('/sessions/' + session.id + '/attendance', formData(attendanceForm), 'Presença atualizada.'); }); management.appendChild(attendanceForm);
			if (session.status === 'scheduled') {
				var agendaForm = document.createElement('form'); agendaForm.className = 'legislativo-compact-form'; agendaForm.innerHTML = '<strong>Adicionar à pauta</strong><input name="matterId" type="number" min="1" required placeholder="ID da matéria"><select name="voteType"><option value="nominal">Nominal</option><option value="symbolic">Simbólica</option><option value="secret">Secreta</option></select><select name="quorumType"><option value="simple">Maioria simples</option><option value="absolute">Maioria absoluta</option><option value="two_thirds">Dois terços</option></select><button class="button" type="submit">Adicionar</button>';
				agendaForm.addEventListener('submit', function (event) { event.preventDefault(); sessionAction('/sessions/' + session.id + '/agenda', formData(agendaForm), 'Matéria adicionada à pauta.'); }); management.appendChild(agendaForm);
			}
			node.appendChild(management);
		}

		var attendance = el('section', 'legislativo-voting-section'); attendance.appendChild(el('h4', '', 'Presença'));
		var chips = el('div', 'legislativo-attendance-chips'); (data.attendance || []).forEach(function (entry) { chips.appendChild(el('span', entry.present ? 'is-present' : 'is-absent', entry.displayName)); });
		if (!(data.attendance || []).length) chips.appendChild(el('span', 'is-absent', 'Nenhuma presença registrada')); attendance.appendChild(chips); node.appendChild(attendance);

		var speakers = el('section', 'legislativo-voting-section'); speakers.appendChild(el('h4', '', 'Uso da palavra'));
		if (session.status === 'open' && data.me.present) { var speechForm = document.createElement('form'); speechForm.className = 'legislativo-compact-form'; speechForm.innerHTML = '<input name="topic" maxlength="512" placeholder="Tema da fala"><input name="seconds" type="number" min="30" max="3600" value="300"><button class="button" type="submit">Inscrever-me</button>'; speechForm.addEventListener('submit', function (event) { event.preventDefault(); sessionAction('/sessions/' + session.id + '/speakers', formData(speechForm), 'Inscrição adicionada à fila.'); }); speakers.appendChild(speechForm); }
		var speakerList = el('div', 'legislativo-speaker-list');
		(data.speakers || []).forEach(function (entry) {
			var row = el('article', 'legislativo-speaker-row ' + (entry.status === 'speaking' ? 'is-speaking' : '')); var info = el('div'); info.appendChild(el('strong', '', entry.position + 'º · ' + entry.displayName)); info.appendChild(el('p', '', entry.topic || 'Sem tema informado')); row.appendChild(info); row.appendChild(el('span', '', entry.status === 'waiting' ? 'Aguardando' : entry.status === 'speaking' ? 'Com a palavra' : 'Concluído'));
			if (canPreside && session.status === 'open') { var speakerActions = el('div', 'legislativo-detail-actions'); if (entry.status === 'waiting') { var call = el('button', 'primary', 'Chamar'); call.type = 'button'; call.addEventListener('click', function () { sessionAction('/sessions/' + session.id + '/speakers/' + entry.id + '/transition', { action: 'start' }, 'Orador chamado.'); }); speakerActions.appendChild(call); } if (entry.status === 'speaking') { var finishSpeech = el('button', 'button', 'Concluir fala'); finishSpeech.type = 'button'; finishSpeech.addEventListener('click', function () { sessionAction('/sessions/' + session.id + '/speakers/' + entry.id + '/transition', { action: 'finish' }, 'Fala concluída.'); }); speakerActions.appendChild(finishSpeech); } row.appendChild(speakerActions); }
			speakerList.appendChild(row);
		});
		if (!(data.speakers || []).length) speakerList.appendChild(el('p', 'legislativo-empty-inline', 'Nenhum orador inscrito.')); speakers.appendChild(speakerList); node.appendChild(speakers);

		if (session.status === 'closed') {
			var minutes = el('section', 'legislativo-voting-section legislativo-minutes');
			var minutesHeading = el('div', 'legislativo-minutes-heading');
			var minutesTitle = el('div'); minutesTitle.appendChild(el('h4', '', 'Minuta da ata'));
			minutesTitle.appendChild(el('small', '', 'Revisão ' + session.minutesRevision + (session.minutesUpdatedBy ? ' · salva por ' + session.minutesUpdatedBy + ' em ' + formatDate(session.minutesUpdatedAt, true) : '')));
			minutesHeading.appendChild(minutesTitle);
			var signatureLabels = { not_requested: 'Assinatura não solicitada', requested: 'Enviada ao LibreSign', draft_changed: 'Minuta alterada — gere novo PDF' };
			minutesHeading.appendChild(el('span', 'legislativo-status', signatureLabels[session.minutesSignatureStatus] || session.minutesSignatureStatus));
			minutes.appendChild(minutesHeading);
			var toolbar = el('div', 'legislativo-editor-toolbar');
			var editor = el('div', 'legislativo-minutes-editor'); editor.contentEditable = canWrite ? 'true' : 'false'; editor.setAttribute('role', 'textbox'); editor.setAttribute('aria-label', 'Texto da minuta da ata'); editor.innerHTML = session.minutesHtml || '<p>Registre aqui as deliberações, ocorrências e observações finais da sessão.</p>';
			if (canWrite) {
				[['Negrito','bold'],['Itálico','italic'],['Lista','insertUnorderedList']].forEach(function (command) { var tool = el('button', 'button', command[0]); tool.type = 'button'; tool.addEventListener('click', function () { editor.focus(); document.execCommand(command[1], false); }); toolbar.appendChild(tool); });
				var saveMinutes = el('button', 'primary', 'Salvar minuta'); saveMinutes.type = 'button'; saveMinutes.addEventListener('click', function () { sessionAction('/sessions/' + session.id + '/minutes', { html: editor.innerHTML }, 'Minuta da ata salva.'); }); toolbar.appendChild(saveMinutes);
				if (session.minutesFileId && session.minutesSignatureStatus !== 'requested' && session.minutesSignatureStatus !== 'draft_changed') { var signMinutes = el('button', 'button', 'Solicitar assinaturas'); signMinutes.type = 'button'; signMinutes.addEventListener('click', function () { var signers = window.prompt('E-mails dos signatários, separados por vírgula:'); if (signers) sessionAction('/sessions/' + session.id + '/minutes/signature', { signers: signers }, 'Ata encaminhada ao LibreSign.'); }); toolbar.appendChild(signMinutes); }
			}
			if (session.minutesSignatureStatus === 'requested') { var openLibreSign = el('a', 'button', 'Abrir LibreSign'); openLibreSign.href = OC.generateUrl('/apps/libresign/'); openLibreSign.target = '_blank'; openLibreSign.rel = 'noopener'; toolbar.appendChild(openLibreSign); }
			minutes.appendChild(toolbar); minutes.appendChild(editor); node.appendChild(minutes);
		}

		var agenda = el('section', 'legislativo-voting-section'); agenda.appendChild(el('h4', '', 'Ordem do dia'));
		if (!(data.agenda || []).length) agenda.appendChild(el('p', 'legislativo-empty-inline', 'Nenhuma matéria na pauta.'));
		(data.agenda || []).forEach(function (item) {
			var card = el('article', 'legislativo-ballot legislativo-ballot--' + item.status); var top = el('div', 'legislativo-ballot-heading'); var info = el('div');
			info.appendChild(el('small', '', item.position + 'º item · ' + item.voteType + ' · ' + item.quorumType)); info.appendChild(el('strong', '', item.matter.type + ' nº ' + item.matter.number + '/' + item.matter.year)); info.appendChild(el('p', '', item.matter.subject)); top.appendChild(info);
			var badge = el('span', 'legislativo-status legislativo-status--' + (item.result || item.status), item.status === 'voting' ? 'Votação aberta' : item.status === 'closed' ? (item.result === 'approved' ? 'Aprovada' : item.result === 'no_quorum' ? 'Sem quórum' : 'Rejeitada') : 'Aguardando'); top.appendChild(badge); card.appendChild(top);
			card.appendChild(el('p', 'legislativo-tally', tallyText(item.tally)));
			var actions = el('div', 'legislativo-ballot-actions');
			if (item.status === 'voting' && data.me.present) [['Sim','yes'],['Não','no'],['Abstenção','abstain'],['Obstrução','obstruction']].forEach(function (choice) { var vote = el('button', choice[1] === 'yes' ? 'primary' : 'button', choice[0]); vote.type='button'; vote.addEventListener('click', function () { sessionAction('/sessions/' + session.id + '/agenda/' + item.id + '/votes', { choice: choice[1] }, 'Voto computado.'); }); actions.appendChild(vote); });
			if (canPreside && session.status === 'open' && item.status === 'pending') { var start=el('button','primary','Abrir votação'); start.type='button'; start.addEventListener('click',function(){sessionAction('/sessions/'+session.id+'/agenda/'+item.id+'/transition',{action:'open'},'Votação aberta.');}); actions.appendChild(start); }
			if (canPreside && item.status === 'voting') { var finish=el('button','button','Encerrar e apurar'); finish.type='button'; finish.addEventListener('click',function(){sessionAction('/sessions/'+session.id+'/agenda/'+item.id+'/transition',{action:'close'},'Votação encerrada e apurada.');}); actions.appendChild(finish); }
			card.appendChild(actions); agenda.appendChild(card);
		}); node.appendChild(agenda);
	}

	function generateSessionDocument(sessionId, type) {
		api('/sessions/' + sessionId + '/documents', { method: 'POST', body: { type: type } }).then(function (payload) {
			setFeedback(type === 'minutes' ? 'Ata PDF gerada.' : 'Ordem do Dia PDF gerada.', 'success'); window.open(OC.generateUrl('/f/' + payload.data.fileId), '_blank', 'noopener'); return loadSessionDetail(sessionId);
		}).catch(function (error) { setFeedback(error.message, 'error'); });
	}

	function tickAdminTimer() {
		var node = document.querySelector('.legislativo-admin-timer-value'); if (!node || !adminTimerState || adminTimerState.status === 'idle' || !adminTimerState.duration) { if (node) node.textContent = '00:00'; return; }
		var elapsed = adminTimerState.status === 'running' ? Math.floor((adminTimerState.server + (Date.now() - adminTimerState.received) - new Date(adminTimerState.start).getTime()) / 1000) : 0; var left = Math.max(0, adminTimerState.duration - elapsed); node.textContent = String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0'); node.classList.toggle('is-expired', left === 0);
	}

	function loadParliamentarians() {
		if (!canWrite) return Promise.resolve(); var node = document.getElementById('legislativo-parliamentarian-list');
		return api('/parliamentarians').then(function (payload) { node.replaceChildren(); (payload.data || []).forEach(function (profile) { node.appendChild(el('span', profile.active ? 'is-active' : 'is-inactive', profile.displayName + (profile.party ? ' · ' + profile.party : '') + ' · ' + profile.role)); }); if (!(payload.data || []).length) node.appendChild(el('span', 'is-inactive', 'Nenhum parlamentar cadastrado.')); });
	}

	searchForm.addEventListener('submit', function (event) {
		event.preventDefault();
		loadList();
	});

	if (canWrite) {
		var matterDialog = document.getElementById('legislativo-matter-dialog');
		var matterForm = document.getElementById('legislativo-matter-form');
		document.getElementById('legislativo-new-matter').addEventListener('click', function () {
			matterForm.reset();
			matterForm.elements.id.value = '';
			matterForm.elements.year.value = new Date().getFullYear();
			matterForm.elements.presentedAt.value = new Date().toISOString().slice(0, 10);
			document.getElementById('legislativo-matter-dialog-title').textContent = 'Nova matéria';
			matterDialog.showModal();
		});

		document.querySelectorAll('[data-close-dialog]').forEach(function (button) {
			button.addEventListener('click', function () { button.closest('dialog').close(); });
		});

		document.querySelectorAll('[data-pick-file]').forEach(function (button) {
			button.addEventListener('click', function () {
				var input = button.closest('label').querySelector('input[name="filePath"]');
				if (!window.OC || !OC.dialogs || !OC.dialogs.filepicker) {
					input.readOnly = false; input.focus();
					setFeedback('Seletor indisponível; informe o caminho do arquivo no Nextcloud.', 'info');
					return;
				}
				OC.dialogs.filepicker('Selecionar documento legislativo', function (path) { input.value = path; }, false);
			});
		});

		matterForm.addEventListener('submit', function (event) {
			event.preventDefault();
			var data = formData(matterForm);
			var id = data.id;
			delete data.id;
			api('/matters' + (id ? '/' + id : ''), { method: id ? 'PUT' : 'POST', body: data })
				.then(function (payload) {
					matterDialog.close();
					setFeedback(id ? 'Matéria atualizada.' : 'Matéria cadastrada.', 'success');
					return loadDetail(payload.data.id);
				}).catch(function (error) { setFeedback(error.message, 'error'); });
		});

		var protocolDialog = document.getElementById('legislativo-protocol-dialog');
		var protocolForm = document.getElementById('legislativo-protocol-form');
		protocolForm.addEventListener('submit', function (event) {
			event.preventDefault();
			var data = formData(protocolForm);
			var id = data.matterId;
			delete data.matterId;
			api('/matters/' + id + '/protocols', { method: 'POST', body: data })
				.then(function () {
					protocolDialog.close();
					setFeedback('Protocolo gerado com sucesso.', 'success');
					return loadDetail(id);
				}).catch(function (error) { setFeedback(error.message, 'error'); });
		});

		var proceedingDialog = document.getElementById('legislativo-proceeding-dialog');
		var proceedingForm = document.getElementById('legislativo-proceeding-form');
		proceedingForm.addEventListener('submit', function (event) {
			event.preventDefault();
			var data = formData(proceedingForm);
			var id = data.matterId;
			delete data.matterId;
			var recipients = (data.recipient || '').split(/[;\n]+/).map(function (value) { return value.trim(); }).filter(Boolean); delete data.recipient;
			var endpoint = recipients.length > 1 ? '/matters/' + id + '/proceedings/batch' : '/matters/' + id + '/proceedings'; if (recipients.length > 1) data.recipients = recipients; else data.recipient = recipients[0] || '';
			api(endpoint, { method: 'POST', body: data })
				.then(function () {
					proceedingDialog.close();
					setFeedback('Tramitação registrada.', 'success');
					return loadDetail(id);
				}).catch(function (error) { setFeedback(error.message, 'error'); });
		});

		var attachmentDialog = document.getElementById('legislativo-attachment-dialog');
		var attachmentForm = document.getElementById('legislativo-attachment-form');
		attachmentForm.addEventListener('submit', function (event) {
			event.preventDefault(); var data = formData(attachmentForm); var id = data.matterId; delete data.matterId;
			api('/matters/' + id + '/attachments', { method: 'POST', body: data }).then(function () {
				attachmentDialog.close(); setFeedback('Documento vinculado.', 'success'); return loadDetail(id);
			}).catch(function (error) { setFeedback(error.message, 'error'); });
		});

		var documentDialog = document.getElementById('legislativo-document-dialog');
		var documentForm = document.getElementById('legislativo-document-form');
		documentForm.addEventListener('submit', function (event) {
			event.preventDefault();
			var data = formData(documentForm); var id = data.matterId; var signers = data.signers; delete data.matterId; delete data.signers;
			var submit = documentForm.querySelector('button[type="submit"]'); submit.disabled = true; submit.textContent = 'Preparando PDF…';
			api('/matters/' + id + '/documents', { method: 'POST', body: data })
				.then(function (payload) {
					submit.textContent = 'Enviando ao LibreSign…';
					return api('/matters/' + id + '/documents/' + payload.data.id + '/signature', { method: 'POST', body: { signers: signers } });
				})
				.then(function () { documentDialog.close(); setFeedback('PDF carimbado e solicitação criada no LibreSign.', 'success'); return loadDetail(id); })
				.catch(function (error) { setFeedback(error.message, 'error'); return loadDetail(id); })
				.finally(function () { submit.disabled = false; submit.textContent = 'Preparar e solicitar assinaturas'; });
		});

		var submissionDialog = document.getElementById('legislativo-submission-dialog');
		var submissionForm = document.getElementById('legislativo-submission-form');
		submissionForm.addEventListener('submit', function (event) {
			event.preventDefault();
			api('/submissions', { method: 'POST', body: formData(submissionForm) }).then(function () {
				submissionDialog.close(); setFeedback('Entrada enviada para triagem.', 'success'); return loadDetail(selectedId);
			}).catch(function (error) { setFeedback(error.message, 'error'); });
		});

		function loadTriage() {
			var node = document.getElementById('legislativo-triage-list'); node.replaceChildren(el('p', 'legislativo-empty', 'Carregando…'));
			return api('/submissions?status=pending').then(function (payload) {
				node.replaceChildren();
				if (!payload.data.length) node.appendChild(el('p', 'legislativo-empty', 'Nenhuma entrada pendente.'));
				payload.data.forEach(function (item) {
					var row = el('article', 'legislativo-management-row');
					var content = el('div'); content.appendChild(el('strong', '', item.subject)); content.appendChild(el('p', '', item.sender + ' · ' + formatDate(item.submittedAt, true))); row.appendChild(content);
					var actions = el('div', 'legislativo-detail-actions');
					[['Aceitar', 'accepted'], ['Rejeitar', 'rejected']].forEach(function (choice) {
						var button = el('button', 'button', choice[0]); button.type = 'button'; button.addEventListener('click', function () {
							api('/submissions/' + item.id + '/decision', { method: 'POST', body: { decision: choice[1] } }).then(loadTriage).then(function () { if (selectedId === item.matterId) loadDetail(selectedId); });
						}); actions.appendChild(button);
					}); row.appendChild(actions); node.appendChild(row);
				});
			});
		}

		var triageDialog = document.getElementById('legislativo-triage-dialog');
		document.getElementById('legislativo-open-triage').addEventListener('click', function () { triageDialog.showModal(); loadTriage(); });

		function loadCalendar() {
			var node = document.getElementById('legislativo-calendar-list'); node.replaceChildren(el('p', 'legislativo-empty', 'Carregando…'));
			return api('/calendar').then(function (payload) {
				node.replaceChildren();
				if (!payload.data.length) node.appendChild(el('p', 'legislativo-empty', 'Nenhum período cadastrado.'));
				payload.data.forEach(function (item) {
					var row = el('article', 'legislativo-management-row' + (item.active ? '' : ' is-inactive'));
					var content = el('div'); content.appendChild(el('strong', '', item.name)); content.appendChild(el('p', '', item.kind + ' · ' + formatDate(item.startsOn, false) + ' a ' + formatDate(item.endsOn, false))); row.appendChild(content);
					var toggle = el('button', 'button', item.active ? 'Desativar' : 'Ativar'); toggle.type = 'button'; toggle.addEventListener('click', function () {
						api('/calendar/' + item.id, { method: 'PUT', body: { active: !item.active } }).then(loadCalendar);
					}); row.appendChild(toggle); node.appendChild(row);
				});
			});
		}

		var calendarDialog = document.getElementById('legislativo-calendar-dialog');
		document.getElementById('legislativo-open-calendar').addEventListener('click', function () { calendarDialog.showModal(); loadCalendar(); });
		document.getElementById('legislativo-calendar-form').addEventListener('submit', function (event) {
			event.preventDefault(); var form = event.currentTarget;
			api('/calendar', { method: 'POST', body: formData(form) }).then(function () { form.reset(); return loadCalendar(); }).catch(function (error) { setFeedback(error.message, 'error'); });
		});

		var normDialog = document.getElementById('legislativo-norm-dialog');
		function loadNorms() { var list = document.getElementById('legislativo-norm-list'); var filter = document.getElementById('legislativo-norm-filter'); var params = new URLSearchParams(new FormData(filter)); params.set('limit', '100'); list.replaceChildren(el('p', 'legislativo-empty', 'Carregando…')); return api('/norms?' + params.toString()).then(function (payload) { list.replaceChildren(); (payload.data || []).forEach(function (norm) { var row = el('article', 'legislativo-management-row'); var content = el('div'); content.appendChild(el('strong', '', norm.type + ' nº ' + norm.number + '/' + norm.year + ' · ' + norm.title)); content.appendChild(el('p', '', norm.status + (norm.ementa ? ' · ' + norm.ementa : ''))); row.appendChild(content); var actions = el('div', 'legislativo-detail-actions'); var version = el('button', 'button', 'Nova versão'); version.type = 'button'; version.addEventListener('click', function () { var form = document.getElementById('legislativo-norm-version-form'); form.hidden = false; form.elements.normId.value = norm.id; form.elements.validFrom.value = new Date().toISOString().slice(0, 10); }); actions.appendChild(version); row.appendChild(actions); list.appendChild(row); }); if (!(payload.data || []).length) list.appendChild(el('p', 'legislativo-empty', 'Nenhuma norma cadastrada.')); }); }
		document.getElementById('legislativo-open-norms').addEventListener('click', function () { normDialog.showModal(); loadNorms(); });
		document.getElementById('legislativo-norm-filter').addEventListener('submit', function (event) { event.preventDefault(); loadNorms().catch(function (error) { setFeedback(error.message, 'error'); }); });
		document.getElementById('legislativo-norm-form').addEventListener('submit', function (event) { event.preventDefault(); api('/norms', { method: 'POST', body: formData(event.currentTarget) }).then(function () { event.currentTarget.reset(); setFeedback('Norma cadastrada.', 'success'); return loadNorms(); }).catch(function (error) { setFeedback(error.message, 'error'); }); });
		document.getElementById('legislativo-norm-version-form').addEventListener('submit', function (event) { event.preventDefault(); var form = event.currentTarget; var id = form.elements.normId.value; var values = formData(form); delete values.normId; api('/norms/' + id + '/versions', { method: 'POST', body: values }).then(function () { form.reset(); form.hidden = true; setFeedback('Versão da norma registrada.', 'success'); return loadNorms(); }).catch(function (error) { setFeedback(error.message, 'error'); }); });
		function importNorms(dryRun) { var form = document.getElementById('legislativo-norm-import-form'); var result = document.getElementById('legislativo-norm-import-result'); var csv = form.elements.csv.value; if (!csv.trim()) { setFeedback('Cole o CSV antes de importar.', 'error'); return; } result.hidden = false; result.textContent = dryRun ? 'Validando prévia…' : 'Importando lote…'; api('/norms/import', { method: 'POST', body: { csv: csv, dryRun: dryRun } }).then(function (payload) { var data = payload.data; result.textContent = (dryRun ? 'Prévia concluída. ' : 'Importação concluída. ') + data.imported + ' norma(s) processada(s), ' + data.skipped + ' duplicata(s), ' + data.errors.length + ' erro(s).'; if (data.errors.length) result.textContent += ' Linhas: ' + data.errors.map(function (error) { return error.line + ' (' + error.message + ')'; }).join('; '); if (!dryRun) loadNorms(); }).catch(function (error) { result.textContent = error.message; setFeedback(error.message, 'error'); }); }
		document.querySelector('[data-norm-import-preview]').addEventListener('click', function () { importNorms(true); }); document.querySelector('[data-norm-import-apply]').addEventListener('click', function () { if (window.confirm('Importar este lote e gravar as normas válidas?')) importNorms(false); });

		function auditDataBlock(label, value) {
			var section = el('section', 'legislativo-audit-data');
			section.appendChild(el('h4', '', label));
			var pre = el('pre', '', JSON.stringify(value, null, 2));
			section.appendChild(pre);
			return section;
		}

		function loadAudit() {
			var form = document.getElementById('legislativo-audit-filter');
			var list = document.getElementById('legislativo-audit-list');
			var summary = document.getElementById('legislativo-audit-summary');
			list.replaceChildren(el('p', 'legislativo-empty', 'Carregando auditoria…'));
			var params = new URLSearchParams(new FormData(form));
			return api('/audit?' + params.toString()).then(function (payload) {
				var entries = payload.data || [];
				list.replaceChildren();
				summary.textContent = entries.length + (entries.length === 1 ? ' evento encontrado' : ' eventos encontrados') + (entries.length === 100 ? ' · refine os filtros para ver outros eventos' : '');
				if (!entries.length) list.appendChild(el('p', 'legislativo-empty', 'Nenhum evento encontrado.'));
				entries.forEach(function (entry) {
					var row = el('article', 'legislativo-audit-row');
					var heading = el('div', 'legislativo-audit-heading');
					var title = el('div'); title.appendChild(el('strong', '', entry.action)); title.appendChild(el('p', '', entry.entityType + ' #' + entry.entityId + ' · ' + formatDate(entry.createdAt, true))); heading.appendChild(title);
					heading.appendChild(el('span', 'legislativo-status', entry.userUid)); row.appendChild(heading);
					var metadata = el('dl', 'legislativo-audit-metadata');
					[['Dispositivo', entry.client.device], ['Navegador', entry.client.browser], ['Sistema', entry.client.operatingSystem], ['IP', entry.ipAddress || 'Não informado'], ['Request ID', entry.requestId || 'Não informado']].forEach(function (item) { metadata.appendChild(detailField(item[0], item[1])); });
					row.appendChild(metadata);
					var details = el('details', 'legislativo-audit-details'); details.appendChild(el('summary', '', 'Ver dados anteriores e posteriores'));
					var comparison = el('div', 'legislativo-audit-comparison'); comparison.appendChild(auditDataBlock('Antes', entry.before)); comparison.appendChild(auditDataBlock('Depois', entry.after)); details.appendChild(comparison); row.appendChild(details);
					list.appendChild(row);
				});
			}).catch(function (error) { summary.textContent = ''; list.replaceChildren(el('p', 'legislativo-empty', error.message)); });
		}

		var auditDialog = document.getElementById('legislativo-audit-dialog');
		document.getElementById('legislativo-open-audit').addEventListener('click', function () { auditDialog.showModal(); loadAudit(); });
		document.getElementById('legislativo-audit-filter').addEventListener('submit', function (event) { event.preventDefault(); loadAudit(); });
	}

	var votingDialog = document.getElementById('legislativo-voting-dialog');
	document.getElementById('legislativo-open-voting').addEventListener('click', function () {
		votingDialog.style.removeProperty('display');
		votingDialog.showModal(); loadVotingSessions(true); loadParliamentarians();
		if (votingTimer) window.clearInterval(votingTimer);
		votingTimer = window.setInterval(function () { if (votingDialog.open && selectedSessionId && !(document.activeElement && document.activeElement.classList.contains('legislativo-minutes-editor'))) loadSessionDetail(selectedSessionId); }, 4000);
	});
	document.querySelector('[data-close-voting]').addEventListener('click', function () {
		if (votingDialog.open) votingDialog.close();
		else votingDialog.style.display = 'none';
		if (votingTimer) window.clearInterval(votingTimer); votingTimer = null;
	});
	if (canWrite) {
		var sessionForm = document.getElementById('legislativo-session-form');
		sessionForm.elements.year.value = new Date().getFullYear();
		sessionForm.elements.scheduledAt.value = new Date(Date.now() + 3600000).toISOString().slice(0, 16);
		sessionForm.addEventListener('submit', function (event) {
			event.preventDefault(); api('/sessions', { method: 'POST', body: formData(sessionForm) }).then(function () {
				setFeedback('Sessão criada.', 'success'); sessionForm.elements.number.value = ''; return loadVotingSessions(true);
			}).catch(function (error) { setFeedback(error.message, 'error'); });
		});
		var parliamentarianForm = document.getElementById('legislativo-parliamentarian-form');
		parliamentarianForm.elements.termStart.value = new Date().getFullYear() + '-01-01'; parliamentarianForm.elements.termEnd.value = (new Date().getFullYear() + 4) + '-12-31';
		parliamentarianForm.addEventListener('submit', function (event) { event.preventDefault(); api('/parliamentarians', { method: 'POST', body: formData(parliamentarianForm) }).then(function () { setFeedback('Cadastro parlamentar salvo.', 'success'); parliamentarianForm.elements.userUid.value = ''; return loadParliamentarians(); }).catch(function (error) { setFeedback(error.message, 'error'); }); });
	}
	window.setInterval(tickAdminTimer, 250);

	loadList();
}());
