# Changelog

Todas as mudanças notáveis deste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/),
e este projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [Não publicado]

### Adicionado
- `composer.json` com autoload PSR-4 e dependências de desenvolvimento
- PHPUnit 10 com configuração `phpunit.xml` e testes unitários migrados
- PHPStan nível 6 para análise estática
- php-cs-fixer com regras PER-CS2.0 para padronização de código
- CI/CD via GitHub Actions (lint, phpstan, phpunit, compatibilidade Nextcloud)
- `CHANGELOG.md` para rastreamento de mudanças
- `MatterValidator` extraído de `MatterService` — validação de matérias isolada
- `SpeakerService` extraído de `VotingService` — inscrição e transição de oradores
- `TimerService` extraído de `VotingService` — cronômetro sincronizado
- `DTOs/` com objetos tipados para criação de matérias e sessões

### Corrigido
- Formatação inconsistente em `VotingService` — métodos denfificados
- `proceedBatch()` — código comprimido em uma linha expandido para legibilidade
- `publicGet()` — código comprimido expandido

## [0.16.0] - 2026-08-06

### Adicionado
- Busca avançada administrativa e pública com tolerância a acentos e plurais
- Busca global pública integrada para matérias, normas, sessões e vereadores
- Perfis públicos sanitizados dos vereadores com URL compartilhável
- Portal com alto contraste, controle de tamanho do texto e acessibilidade
- Consulta privilegiada da auditoria com filtros por navegador, SO, dispositivo, IP
- Índice textual retroalimentado para matérias e normas
- Importação CSV em lote de até 1.000 normas
- Tramitação atômica para até 50 destinatários
- Painel de prazos com filtros por situação, destinatário, tipo e período

## [0.15.0] - 2026-08-05

### Adicionado
- Portal público em `/apps/legislativo/portal` sem autenticação
- Normas com versões históricas, vigência e relações de alteração/revogação
- Editor visual seguro da minuta com revisões e auditoria
- Solicitação de assinatura da ata no LibreSign

## [0.14.0] - 2026-08-04

### Adicionado
- Sessões plenárias, presença, pauta e votação nominal/secreta/simbólica
- Mandatos parlamentares vinculados às contas Nextcloud
- Inscrição para uso da palavra e chamada ordenada com tempo individual
- Painel público em `/apps/legislativo/panel/{id}`
- Geração da Ordem do Dia e da ata da sessão em PDF

## [0.13.0] - 2026-08-04

### Adicionado
- Caixa de triagem com aceite/rejeição antes da protocolização
- Calendário de feriados, pontos facultativos e recessos aplicado aos prazos
- Cronômetro sincronizado para painel público

## [0.12.0] - 2026-08-04

### Adicionado
- Cadastro/edição, pesquisa, protocolo, tramitação, prazo e linha do tempo
- Documentos vinculados a arquivos do Nextcloud
- Primeira versão funcional do app

## [0.1.0] - 2026-08-04

### Adicionado
- Modelo de dados inicial para matérias, protocolos, tramitações, sessões, votos
- Endpoint de saúde em `/apps/legislativo/api/v1/health`
- Migrações de banco de dados
