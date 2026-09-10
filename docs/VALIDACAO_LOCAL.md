# Validação local — Nextcloud 33

Data: 06/08/2026.

## Resultado

- App `legislativo` habilitado na versão `0.16.0`.

## Busca textual tolerante

- Busca autenticada de matérias por `transparencia` encontrou o assunto com `Transparência`, sem exigir o acento.
- A combinação `projetos leis` encontrou `Projeto de Lei`, confirmando a equivalência entre singular e plural e a combinação de todos os termos.
- A busca pública de normas por `leis` retornou as normas do tipo `Lei` já publicadas.
- A busca pública por `municipios` encontrou a Lei 123/2026 pelo termo `Município` presente somente no corpo HTML de sua versão consolidada.
- O índice textual persistido foi preenchido pela migração para matérias e normas existentes e passa a ser atualizado nas gravações futuras.
- Os modos todos os termos (E), qualquer termo (OU) e frase exata foram validados em teste transacional.
- Intervalos inclusivos de número e ano e ordenação configurável estão disponíveis na administração e no portal público.

## Portal público e acessibilidade

- Busca global por `transparencia` reuniu matéria, normas e sessões relacionadas em uma resposta pública.
- Dois perfis parlamentares ativos foram publicados sem UID, datas de auditoria ou outros metadados internos.
- Perfil parlamentar individual respondeu com mandato, partido, cadeira e proposições de autoria.
- URL compartilhável `/apps/legislativo/portal/parliamentarians/1` respondeu HTTP 200.
- Alto contraste, três níveis de ajuste de texto, atalho para o conteúdo, foco visível, fechamento por Escape e navegação de abas por setas foram implementados.
- Teste integrado somente-leitura validou sanitização dos perfis e composição da busca global.

## Gestão de prazos

- API `GET /api/v1/deadlines` validada com HTTP 200 para prazos abertos, vencidos, do dia, a vencer, sem prazo e concluídos.
- Massa local retornou quatro tramitações abertas e a vencer; filtros combinados por destinatário, tipo e período retornaram apenas os registros esperados.
- Validação de conclusão sem resultado retornou HTTP 422 sem modificar a tramitação.
- Caminho de sucesso de `DeadlineService::complete()` validado dentro de transação externa, incluindo persistência do resultado, data de resposta e rollback integral da tramitação e da auditoria.
- Página administrativa respondeu HTTP 200 contendo o painel de prazos da versão 0.12.0.
- Navegação autenticada em `/apps/legislativo/` validada com HTTP 200; apenas a página GET dispensa CSRF, enquanto escrita sem token permanece bloqueada com HTTP 412.

## Auditoria consultável

- API `GET /api/v1/audit` validada com HTTP 200 e acesso restrito a administradores e operadores legislativos.
- Filtros combinados por módulo, entidade, ação, usuário e período retornaram somente os eventos esperados.
- Resposta inclui dispositivo, navegador, sistema operacional, IP, request ID e os valores anteriores/posteriores.
- Painel administrativo de auditoria renderizado com detalhes expansíveis sem interpolar HTML dos dados auditados.

## Plenário, mandatos e documentos

- Mandatos dos usuários `admin` e `teste` cadastrados para 2025–2028.
- Sessão de painel 4/2026 aberta com dois presentes.
- Usuário `teste` inscreveu-se para falar sobre transparência legislativa e foi chamado pelo operador.
- Cronômetro individual iniciado com 90 segundos, sincronizado pelo horário UTC do servidor, e parado com 58 segundos restantes.
- Painel e API pública responderam HTTP 200 sem autenticação.
- Resposta pública limitada a dados plenários; textos internos, notas e identificadores secretos não são expostos.
- Ordem do Dia PDF gerada no node ID 703.
- Minuta da ata salva em duas revisões; sanitização removeu script e atributo de evento do HTML de teste.
- Ata PDF final gerada no node ID 707, contendo presença, resultado 2 × 0, orador, horários e o texto editado.
- Ata encaminhada ao LibreSign com UUID `39d0eaaa-e249-4715-9604-be459a5a875a` e estado `requested`.
- Alterar uma minuta já enviada marcou o documento como `draft_changed`; a regeneração do PDF liberou uma nova solicitação.
- API pública condicional respondeu HTTP 204 e zero bytes ao receber o `eventId` de um estado sem alterações.
- Usuário `teste` incluído em `legislativo_presidentes`: criação administrativa retornou HTTP 403, enquanto abertura e encerramento da sessão de teste 5/2026 retornaram HTTP 200.
- Portal público `/apps/legislativo/portal` respondeu HTTP 200 sem autenticação; busca de matérias, detalhe da matéria 1 e sessões públicas responderam HTTP 200.
- Norma demonstrativa Lei 123/2026 cadastrada e publicada, com duas versões de vigência e texto HTML sanitizado.
- Decreto 50/2026 relacionado à Lei 123/2026; relação apareceu na API pública.
- Tentativa de vigência sobreposta retornou HTTP 422, preservando a linha temporal consolidada.
- Importação CSV em lote validada com prévia, detecção de duplicata e criação de norma com versão textual.
- Painel interno recebeu formulário de prévia e confirmação explícita para importação CSV, com modelo em `docs/modelo-normas.csv`.
- Tramitação em lote validada para as comissões de Justiça e Finanças, com dois registros, prazo de cinco dias úteis e auditoria única do lote.

## Votação eletrônica

- Sessão Ordinária 1/2026 criada com quórum mínimo de uma presença na massa local.
- Presença do usuário `admin` registrada.
- Projeto de Lei 1/2026 incluído como primeiro item da pauta, em votação nominal por maioria simples.
- Sessão e votação abertas pelas transições controladas da API.
- Voto `yes` computado uma única vez para o usuário presente.
- Apuração: 1 Sim, 0 Não, 0 Abstenções e 0 Obstruções; resultado `approved`.
- Matéria atualizada automaticamente para `approved` e sessão encerrada.
- Eventos de presença, pauta, abertura, voto, apuração e encerramento registrados na auditoria.
- Voto secreto validado com `admin` e `teste`: durante a votação, a consulta retornou apenas o total de dois votos, sem escolhas ou identidades; após o encerramento, exibiu somente a apuração agregada.
- Segunda tentativa de voto do mesmo usuário bloqueada com HTTP 422 e mensagem controlada.

## Fluxo documental e LibreSign

- PDF de origem: `/demonstracao-fluxo.pdf` (node ID 693).
- PDF carimbado: `/Documentos Legislativos/2026/Projeto-de-Lei-1/protocolo-2-2026-carimbado-4.pdf` (node ID 700).
- Carimbo extraído do PDF: `CAMARA MUNICIPAL DE CONCHAL | PROTOCOLO 2/2026 | Projeto de Lei 1/2026 | 04/08/2026 14:16:13`.
- SHA-256: `5b442a24bb73d64772c49f600727d64af45171061f497c8779c28aa40966b876`.
- Solicitação LibreSign criada: UUID `2b8d9ef5-6c2c-4729-9ca8-676df9f34ccd`.
- Estado persistido e exposto pela matéria: `requested`.
- O contêiner atual possui Ghostscript, mas não LibreOffice; por isso o caminho PDF foi validado integralmente e o caminho DOCX retorna orientação de instalação explícita.
- Nove migrações aplicadas, sem pendências.
- Cadastro, edição, pesquisa, protocolo e tramitação validados via HTTP.
- Leitura por usuário comum retorna HTTP 200; escrita sem papel retorna HTTP 403.
- Arquivo `demonstracao-edital.txt` criado no Nextcloud e vinculado à matéria 1.
- Entrada de triagem 1 aceita e vinculada ao protocolo 2/2026.
- Feriado de teste em 10/08/2026 aplicado ao cálculo: quatro dias úteis iniciados em
  04/08/2026 resultaram em vencimento em 11/08/2026.
- Página principal, JavaScript e CSS responderam HTTP 200.

## Massa demonstrativa

- Matéria: Projeto de Lei 1/2026.
- Protocolos: 1/2026 direto e 2/2026 originado da triagem.
- Documento: `/demonstracao-edital.txt`.
- Calendário: `Feriado municipal de teste`, em 10/08/2026.

## Problema preexistente da instância

Durante `occ upgrade`, o listener do app `updatenotification` tentou inserir um job e encontrou
o campo `oc_jobs.id` sem valor automático (`SQLSTATE 1364`). As migrações do app legislativo
já haviam sido aplicadas. O modo de manutenção foi desativado e o Nextcloud voltou ao estado
normal, com `needsDbUpgrade: false`.

O campo `oc_jobs.id` foi corrigido para `AUTO_INCREMENT` em 06/08/2026, após backup da tabela em
`/tmp/legislativo-oc_jobs-before-autoincrement.sql`. Os upgrades até 0.16.0 foram concluídos, o modo de
manutenção foi desativado e o Nextcloud voltou a `needsDbUpgrade: false`.
