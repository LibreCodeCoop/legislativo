# Matriz inicial de aderência ao edital de Conchal

Fonte analisada em 04/08/2026:

- `Edital-1-Edital.pdf`
- `Termo_de_Referencia-2-Termo_de_Referencia.pdf` (documento digitalizado, 78 páginas)

Esta matriz é de engenharia, não um parecer jurídico. A aceitação depende da Comissão Avaliadora e de demonstração prática.

## Condições eliminatórias e operacionais

| Exigência | Evidência no TR | Situação | Tratamento |
|---|---:|---|---|
| Plataforma web integrada e centralizada | itens 3.1–3.6 | Parcial | Nextcloud como plataforma e app de domínio único |
| Acesso 24x7 e usuários simultâneos ilimitados | itens 2.2 e 5.1 | Infraestrutura | cluster, observabilidade, capacidade e ANS |
| Banco relacional SQL | item 2.4 | Atende na fundação | migrations portáveis do Nextcloud |
| Implantação, migração e treinamento em até 30 dias | itens 4.5 e 6.2.1 | Pendente | plano de migração e treinamento presencial mínimo de 8h |
| Chamado crítico em 4h úteis; normal em 24h úteis | itens 5.5.1–5.5.2 | Operação | central de suporte e escalonamento |
| Backup diário, retenção mínima de 10 dias | item 5.10 | Infraestrutura | backup validado e teste de restauração |
| Atualizar cerca de 400 normas em até 10 dias do recebimento | item 5.6 | Pendente | equipe/editor de consolidação e SLA editorial |
| Apps Android e iOS personalizados nas lojas | item 5.11 | Não atende | projetos móveis e contas oficiais nas lojas |
| Prova de conceito em até 5 dias úteis, mínimo de 90% | Anexo IV, p. 56 | Crítico | congelar roteiro e massa de demonstração antes da sessão |

## Prova de conceito: Item 2 — processo legislativo

| Ref. | Demonstração exigida | Fundação atual | Próxima entrega |
|---:|---|---|---|
| 6.5 | Log permanente com ação, módulo, documento, usuário, data/hora, dispositivo, navegador, IP e antes/depois | Demonstrável | consulta privilegiada com filtros, dispositivo, navegador, sistema, IP, request ID e comparação antes/depois |
| 10.2.5 | Cadastro completo de vereador, mandatos, autoria, comissões e dados pessoais | Parcial demonstrável | mandato, partido, função e cadeira implementados; comissões e dados pessoais ampliados pendentes |
| 11.2 | Matéria com número, assunto, data, autoria, tema, quórum, regime e observações | Demonstrável | cadastro, edição, validações e arquivamento lógico implementados |
| 12.1 | Legislação vinculada a normas alteradoras, inclusive entre tipos | Demonstrável | normas, versões, vigência, relações N:N e importação CSV em lote implementadas |
| 13.1 | Pauta configurável, envio de matérias e geração de Ordem do Dia/Expediente, editável durante sessão | Parcial demonstrável | pauta ordenada implementada; edição durante a sessão e gerador de documentos pendentes |
| 13.6 | Ata automática com documentos e resultados de votação, layout configurável | Parcial demonstrável | ata PDF automática com presença, pauta, resultados, oradores e texto do editor visual versionado; modelos de layout configuráveis pendentes |
| 15.1 | Protocolização e consulta aos detalhes do trâmite | Demonstrável | protocolo anual automático, anexos do Nextcloud, tramitação e timeline implementados |
| 15.2.3 | Recebimento/análise pela secretaria antes do protocolo | Demonstrável | caixa de triagem com pendência, aceite/rejeição e protocolo vinculado |
| 15.2.4 | Converter DOCX em PDF, carimbar numeração e assinar digitalmente | Parcial demonstrável | pipeline, carimbo e LibreSign implementados; instalar LibreOffice para demonstrar DOCX |
| 16.2 | Prazo automático em dias úteis/corridos, considerando feriados, pontos facultativos e recessos | Demonstrável | calendário legislativo e cálculo integrado implementados |
| 16.4 | Tramitar para vários destinatários | Demonstrável | lote atômico para até 50 destinatários, com prazo individual e timeline; notificações pendentes |
| 16.7 | Regras de encaminhamento e resultados possíveis por destino | Parcial demonstrável | encaminhamento múltiplo e resultado individual auditado implementados; motor declarativo de regras pendente |
| 17.1 | Pesquisa combinada e textual tolerante a acentos/plural | Demonstrável | índice textual de todos os campos relevantes, combinação de termos e normalização pt-BR para acentos e plurais |
| 17.2 | Gestão de prazos vencidos/a vencer por tipo, período e destino | Demonstrável | painel com situação calculada, filtros por destinatário, tipo e período, e conclusão auditada da tramitação |
| 17.13 | Busca em todos os campos, exata/parcial/faixa e ordenação | Demonstrável | texto em todos os campos, frase exata, filtros tipados, intervalos de número/ano e ordenação configurável |
| 18.2 | Assinatura PDF A3 ICP-Brasil por smart card/token | Alto risco | prova técnica com bridge local e LibreSign/PAdES |
| 18.7 | Edição HTML integrada sem download/upload | Parcial demonstrável | editor HTML seguro e versionado implementado para a ata; expandir para os demais documentos |
| 18.8–18.10 | Digitalização PDF, OCR automático e comunicação com scanner | Não | serviço OCR e agente local de digitalização |
| 19.1 e 19.10 | Arquivo físico, localização, temporalidade e destinação | Parcial | módulo de arquivo e tabela de temporalidade |
| 20.1–20.3 | Publicação de proposições, sessões e consulta multicritério no portal | Demonstrável | portal responsivo com busca global/avançada, matérias, tramitações, legislação, sessões, resultados e perfis parlamentares |
| 23.7 | Caixa de entrada/itens enviados semelhante a e-mail | Não | inbox legislativa |
| 24.4–24.5 | Busca de proposituras e exibição integral da tramitação | Não | consulta pública e timeline |

## Prova de conceito: Item 3 — votação eletrônica

| Ref. | Demonstração exigida | Situação |
|---:|---|---|
| 1.4 e 1.6 | Presença, recomposição de quórum, listagem e relatório PDF | Demonstrável na fatia atual |
| 1.9 | Relatórios de votações e resultados | Demonstrável na ata PDF e Ordem do Dia |
| 2.3–2.4 | Painel de presença e indicadores Sim/Não/Abstenção; sigilo quando aplicável | Demonstrável |
| 2.7 | Relógio sincronizado para presidente e operador | Demonstrável; relógio e cronômetro usam horário retornado pelo servidor e a presidência possui papel próprio |
| 2.9 | Exibição da matéria, ementa, regime, quórum e mensagens | Parcial demonstrável |
| 3.1 | Compatibilidade com celular, tablet, notebook e computador | Interface web responsiva implementada |
| 3.4 | Voto em tempo real: Sim, Não e Abstenção | Demonstrável; inclui obstrução e bloqueio de voto duplicado |
| 3.6 | Inscrição eletrônica para uso da palavra, em ordem | Demonstrável com fila, chamada, conclusão e tempo individual |

## Prova de conceito: Item 4 — compilação da legislação

| Ref. | Demonstração exigida | Situação |
|---:|---|---|
| 3.4.3 e 4.3.3 | Pesquisa avançada combinada, intervalos, texto, conectores e/ou, tolerante a acentos | Demonstrável | busca pública/administrativa com E/OU, frase exata, intervalos, ordenação e normalização de acentos/plurais |
| 3.4.9 | HTML com âncoras e índice sistemático para dispositivos | Parcial | texto HTML vigente publicado; geração automática de índice e âncoras pendente |
| 3.4.10 e 4.5.3 | Versionamento e consulta do texto vigente em uma data | Demonstrável | versões com início/fim de vigência e consulta pública do texto vigente |
| 4.1 | Apps Android 5+ e iOS 10+ publicados nas lojas, sem custo ao usuário | Fora do app Nextcloud |

## Prova de conceito: Item 5 — portal

| Ref. | Demonstração exigida | Situação |
|---:|---|---|
| 1.12 | Layout responsivo | Portal e painel responsivos |
| 2.4 | Controle de contraste para baixa visão | Demonstrável; alto contraste persistente, ajuste de fonte, foco visível e navegação por teclado |
| 3.1 | Busca global no conteúdo do site | Demonstrável; reúne matérias, normas, sessões e vereadores em uma consulta |
| 3.13 | Página de cada vereador integrada ao sistema legislativo | Demonstrável; perfil sanitizado, mandato, partido, cadeira e proposições em URL compartilhável |
| 3.26 | Enquetes com quantidade e percentual | Pendente |
| 3.27.4 | Notícias rotativas a cada 10 segundos | Pendente |
| 3.29.8 | Votar/alterar voto, comentar e acompanhar participações | Pendente |
| 3.30.2 e 3.30.5 | Manifestação ao vereador, imagens/GPS e notificação por e-mail | Pendente |
| 4.6 | Licitações e dispensas com metadados e anexos | Pendente |
| 4.7 | Contas públicas por categoria, ano e período | Pendente |
| 4.8 | Perfis administrativos e recuperação de senha | Base Nextcloud; ACL editorial pendente |

## Sequência de implementação recomendada

1. **Vertical de matéria e protocolo (demonstrável):** cadastro, numeração, anexos do Nextcloud, triagem, timeline, auditoria, consulta e calendário de prazos implementados.
2. **Sessão demonstrável:** pauta, presença, quórum, votação em tempo real, painel e ata automática.
3. **Legislação:** importação das cerca de 400 normas, relações, HTML estruturado, versões e busca.
4. **Portal público:** vereador, proposições, sessões, licitações, contas, acessibilidade e manifestações.
5. **Integrações críticas:** conversão DOCX/PDF, carimbo, A3 ICP-Brasil, OCR/scanner, notificações e calendário de prazos.
6. **Mobile e operação:** apps das lojas, observabilidade, backup/restore, suporte, migração e treinamento.

## Riscos e esclarecimentos formais recomendados

- O edital e seus anexos usam números de processo divergentes (`839/2026`, `1114/2025`/`1114/2026` e `837/2026`). Convém pedir esclarecimento formal.
- Confirmar se um aplicativo móvel baseado no cliente Nextcloud personalizado é aceito ou se exigem aplicativos legislativos independentes nas lojas.
- Confirmar o padrão esperado para assinatura A3 (PAdES, cadeia, carimbo do tempo e validação) e se será admitido componente local.
- Solicitar amostra e esquema do banco legado antes de fechar prazo/preço de conversão.
- Confirmar o critério de arredondamento dos 90% e se todos os itens da matriz têm o mesmo peso.
