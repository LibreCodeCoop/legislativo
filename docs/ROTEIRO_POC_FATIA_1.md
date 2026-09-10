# Roteiro de demonstração — fatia 1

## Preparação

1. Habilitar o app e adicionar o demonstrador ao grupo `legislativo_operadores`.
2. Preparar uma matéria de exemplo com tipo, número, ano, assunto, autoria, tema, quórum e regime.
3. Abrir as ferramentas do navegador para evidenciar chamadas web e ausência de instalação local.

## Demonstração

1. **Cadastrar matéria:** preencher todos os campos exigidos pelo item 11.2 da prova de conceito.
2. **Pesquisar:** localizar a matéria por parte do assunto ou tema e filtrar por situação.
3. **Editar:** alterar o assunto e mostrar na linha do tempo o evento `matter.update`.
4. **Protocolar:** informar remetente e deixar o número vazio para gerar numeração anual automática.
5. **Anexar documento:** escolher um arquivo do Nextcloud e abri-lo pelo vínculo da matéria.
6. **Triar:** enviar documento à caixa da secretaria, aceitar a entrada e gerar protocolo vinculado.
7. **Cadastrar calendário:** incluir feriado, ponto facultativo ou recesso.
8. **Tramitar:** encaminhar para uma comissão, informar objetivo e prazo em dias úteis.
9. **Conferir prazo:** demonstrar que sábado, domingo e períodos do calendário não entram na contagem.
10. **Auditar:** exibir usuário, data/hora e IP; consultar a resposta da API para mostrar navegador, request ID e estado anterior/posterior.
11. **Permissões:** acessar com usuário fora do grupo e demonstrar modo somente leitura.

## Cobertura demonstrável

- Item 11.2: campos básicos da matéria.
- Item 15.1: protocolo, anexos e acesso aos detalhes do trâmite.
- Item 15.2.3: recebimento e análise pela secretaria antes do protocolo.
- Item 16.2: prazo útil/corrido com feriados, pontos facultativos e recessos.
- Item 17.1: pesquisa combinada em todos os campos com tolerância a acentos e plurais.
- Item 17.13: pesquisa parcial, frase exata, filtros tipados, intervalos de número/ano e ordenação configurável.
- Item 6.5: trilha de auditoria com identificação de dispositivo, navegador, sistema, IP, request ID e antes/depois.

Na demonstração do item 17.13, combinar intervalo, correspondência E/OU e ordenação para evidenciar que os critérios funcionam em conjunto.
