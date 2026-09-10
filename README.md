# Gestão Legislativa para Nextcloud

Versão 0.16.0: inclui fluxo documental, votação eletrônica, cadastro de parlamentares, fila de oradores, cronômetro sincronizado, painel público, editor versionado da ata, assinatura da ata no LibreSign, portal público acessível com vereadores e busca global, legislação consolidada, importação CSV em lote, tramitação para múltiplos destinatários, painel de prazos, consulta detalhada da auditoria e busca avançada tolerante a acentos e plurais.

O servidor precisa de Ghostscript (`gs`) para o carimbo e LibreOffice (`libreoffice` ou `soffice`) para converter DOCX. Arquivos PDF não dependem do LibreOffice. O LibreSign deve estar habilitado e o operador precisa pertencer a um grupo autorizado em `groups_request_sign`.

Fundação de uma solução integrada para o processo legislativo municipal, iniciada a partir da Concorrência Eletrônica nº 01/2026 da Câmara Municipal de Conchal/SP.

## Estado atual

Esta versão cria:

- um app instalável no Nextcloud 32–35;
- painel de matérias e endpoint de saúde em `/apps/legislativo/api/v1/health`;
- modelo de dados inicial para matérias, protocolos, tramitações, sessões, votos, versões de normas e auditoria imutável na aplicação;
- primeira fatia funcional: cadastro/edição, pesquisa, protocolo, tramitação, prazo e linha do tempo;
- documentos vinculados a arquivos acessíveis do Nextcloud;
- caixa de triagem com aceite/rejeição antes da protocolização;
- calendário de feriados, pontos facultativos e recessos aplicado aos prazos;
- sessões plenárias, presença, pauta, votação nominal/secreta/simbólica e apuração em tempo real;
- mandatos parlamentares vinculados às contas Nextcloud;
- inscrição para uso da palavra e chamada ordenada com tempo individual;
- painel público em `/apps/legislativo/panel/{id}`;
- geração da Ordem do Dia e da ata da sessão em PDF;
- editor visual seguro da minuta, com revisões, auditoria e inclusão do texto no PDF;
- solicitação de assinatura da ata no LibreSign e invalidação controlada quando a minuta muda;
- permissão de escrita para administradores e para o grupo `legislativo_operadores`, criado na instalação;
- permissão de condução do plenário para administradores e para o grupo `legislativo_presidentes`;
- atualização condicional do painel público a cada segundo, sem payload quando o estado não mudou;
- portal público em `/apps/legislativo/portal`, sem autenticação, com matérias, tramitação, sessões e resultados agregados;
- normas com versões históricas, vigência, relações de alteração/revogação e busca pública;
- importação CSV de até 1.000 normas por lote, com prévia, validação, duplicatas e versões de texto;
- tramitação atômica para até 50 destinatários, com prazo e auditoria do lote;
- painel de prazos com filtros por situação, destinatário, tipo e período, além do encerramento auditado de cada tramitação;
- consulta privilegiada da auditoria com filtros, navegador, sistema operacional, dispositivo, IP, request ID e comparação antes/depois;
- índice textual retroalimentado para matérias e normas, cobrindo todos os campos relevantes e textos consolidados com tolerância a acentos e plurais;
- busca avançada administrativa e pública com todos/qualquer termo, frase exata, intervalos de número/ano e ordenação configurável;
- busca global pública integrada para matérias, normas, sessões e vereadores;
- perfis públicos sanitizados dos vereadores, com mandato, partido, cadeira e proposições de autoria em URL compartilhável;
- portal com alto contraste, controle de tamanho do texto, atalho para conteúdo, foco visível e navegação de abas por teclado;

O modelo de arquivo está em [docs/modelo-normas.csv](docs/modelo-normas.csv). No painel interno, abra **Legislação**, cole o conteúdo do CSV, execute **Validar prévia** e só então use **Importar lote**.
- matriz de aderência e plano de implementação orientado pela prova de conceito.

Ela ainda **não constitui a solução completa exigida pelo edital**. Consulte [docs/MATRIZ_ADERENCIA.md](docs/MATRIZ_ADERENCIA.md).

## Instalação local

```bash
php occ app:enable legislativo
php occ migrations:status legislativo
```

Depois, abra `/index.php/apps/legislativo/` com um usuário autenticado.

Adicione os usuários responsáveis por cadastrar e tramitar matérias ao grupo
`legislativo_operadores` e quem conduz sessões ao grupo `legislativo_presidentes`.
Os demais usuários autenticados permanecem em modo de consulta.

## API da primeira fatia

| Método | Caminho | Função |
|---|---|---|
| `GET` | `/api/v1/matters` | Pesquisa por texto, tipo, número, ano e situação |
| `GET` | `/api/v1/matters/{id}` | Matéria, protocolos, tramitações e auditoria |
| `POST` | `/api/v1/matters` | Cadastra matéria |
| `PUT` | `/api/v1/matters/{id}` | Atualiza matéria; arquivamento substitui exclusão destrutiva |
| `POST` | `/api/v1/matters/{id}/protocols` | Gera protocolo, com numeração anual automática |
| `POST` | `/api/v1/matters/{id}/proceedings` | Registra tramitação e calcula o vencimento |
| `POST` | `/api/v1/matters/{id}/proceedings/batch` | Registra tramitação para vários destinatários |
| `GET` | `/api/v1/deadlines` | Consulta prazos em aberto, vencidos, a vencer ou concluídos |
| `PUT` | `/api/v1/proceedings/{id}` | Conclui uma tramitação com resultado e auditoria |
| `GET` | `/api/v1/audit` | Consulta privilegiada da auditoria com filtros e contexto do cliente |
| `GET/POST` | `/api/v1/sessions` | Lista ou cria sessões plenárias |
| `POST` | `/api/v1/sessions/{id}/attendance` | Registra presença parlamentar |
| `POST` | `/api/v1/sessions/{id}/agenda` | Adiciona matéria à pauta |
| `POST` | `/api/v1/sessions/{id}/transition` | Abre ou encerra a sessão |
| `POST` | `/api/v1/sessions/{id}/agenda/{itemId}/votes` | Computa o voto do usuário presente |
| `POST` | `/api/v1/sessions/{id}/speakers` | Inscreve o usuário presente para uso da palavra |
| `POST` | `/api/v1/sessions/{id}/timer` | Controla o cronômetro sincronizado |
| `POST` | `/api/v1/sessions/{id}/documents` | Gera Ordem do Dia ou ata em PDF |
| `POST` | `/api/v1/sessions/{id}/minutes` | Salva uma revisão sanitizada da minuta da ata |
| `POST` | `/api/v1/sessions/{id}/minutes/signature` | Encaminha o PDF vigente da ata ao LibreSign |
| `GET` | `/api/public/sessions/{id}` | Dados sanitizados do painel público |
| `GET` | `/api/public/portal/matters` | Busca pública de matérias |
| `GET` | `/api/public/portal/matters/{id}` | Tramitação e resultados públicos da matéria |
| `GET` | `/api/public/portal/sessions` | Sessões e resultados de votação publicados |
| `GET` | `/api/public/portal/search` | Busca global em matérias, normas, sessões e vereadores |
| `GET` | `/api/public/portal/parliamentarians` | Lista os perfis parlamentares ativos e sanitizados |
| `GET` | `/api/public/portal/parliamentarians/{id}` | Perfil público e proposições de autoria |
| `GET` | `/api/v1/norms` | Pesquisa administrativa de normas |
| `POST` | `/api/v1/norms` | Cadastra norma |
| `POST` | `/api/v1/norms/{id}/versions` | Cadastra versão consolidada da norma |
| `POST` | `/api/v1/norms/{id}/relations` | Relaciona normas alteradoras/revogadoras |
| `POST` | `/api/v1/norms/import` | Pré-visualiza ou importa normas em lote via CSV |
| `GET` | `/api/public/portal/norms` | Pesquisa normas publicadas |
| `GET` | `/api/public/portal/norms/{id}` | Texto vigente e relações públicas |

As mutações usam transação de banco e registram usuário, data/hora, IP, navegador,
request ID e os valores anteriores/posteriores.

## Teste isolado disponível

```bash
php tests/run-deadline-calculator.php
php tests/run-deadline-status-calculator.php
php tests/run-audit-client-parser.php
php tests/run-text-normalizer.php
# Dentro do contêiner Nextcloud, com a instância instalada:
php /var/www/html/apps-extra/legislativo/tests/run-deadline-service-integration.php
php /var/www/html/apps-extra/legislativo/tests/run-text-search-integration.php
php /var/www/html/apps-extra/legislativo/tests/run-public-portal-integration.php
```

O cálculo considera fins de semana e os períodos ativos do calendário legislativo.

## Arquitetura pretendida

- Nextcloud: identidade, grupos, arquivos, permissões, versões e trilha de atividade.
- Este app: domínio legislativo, prazos, pautas, sessões, votação e publicação estruturada.
- LibreSign: fluxos de assinatura e validação, com adaptação específica para ICP-Brasil A3.
- OnlyOffice/Collabora: edição web e conversão DOCX/PDF.
- Transporte de eventos: consulta condicional já implementada; WebSocket depende de `notify_push` e proxy compatível na infraestrutura.
- Portal público e apps móveis: consumidores da API pública versionada.

## Licença

AGPL-3.0-or-later.
