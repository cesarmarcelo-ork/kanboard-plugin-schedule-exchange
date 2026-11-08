# Kanboard Schedule Exchange

Plugin para **planejamento individual de subtarefas**, **cadastro de dependências entre cartões e subtarefas** e **exportação XML Microsoft Project (MSPDI)**.

**Situação:** v0.1.0 preparada para publicação estável após homologação funcional. A tag e a release no GitHub ainda precisam ser publicadas. O antigo Schedule Export 0.6.1 é outro estágio do projeto.

**Compatibilidade declarada:** Kanboard >= 1.2.50. Matriz automatizada: Kanboard 1.2.50/1.2.57 e PHP 8.1–8.4.

## Responsabilidades

- O **Kanboard** guarda cartões, subtarefas, datas originais, estimativas, progresso e vínculos cadastrados.
- O **Schedule Exchange** cadastra relacionamentos e exporta dados interoperáveis.
- As aplicações de planejamento (ProjectLibre, Open Planner Studio e outras) interpretam o XML, calculam cronogramas, restrições e caminho crítico.
- **O plugin não recalcula datas, não desloca atividades e não faz nivelamento de recursos.** A exportação não modifica o planejamento do Kanboard.

## Funcionalidades

- Planejamento individual de subtarefas com período inicial/final, jornada e dias de trabalho, sem substituir o controle nativo de tempo.
- Vínculos entre **Cartão → Cartão**, **Cartão → Subtarefa**, **Subtarefa → Cartão** e **Subtarefa → Subtarefa**.
- Relações **FS, SS, FF e SF**, com defasagem positiva, negativa ou zero, em dias.
- Verificação de referências, permissões, duplicidade e dependências circulares armazenadas pelo Schedule Exchange.
- Exportação detalhada XML MSPDI, com identificadores próprios e preservação dos registros originais.

## Instalação e utilização

1. Faça backup dos arquivos do plugin atual **e do banco Kanboard**.
2. Instale o repositório exatamente em `plugins/ScheduleExchange` (não em `plugins/ScheduleExport`).
3. Não mantenha o Schedule Export antigo ativo em paralelo; a coexistência não foi homologada.
4. Abra o cartão, expanda **Dependências de planejamento** e cadastre a predecessora e a sucessora. A predecessora pode pertencer a qualquer cartão/subtarefa do projeto; a sucessora é o cartão aberto ou uma de suas subtarefas.
5. Selecione FS/SS/FF/SF, a defasagem e salve.
6. No menu do projeto, exporte **cronograma (.xml)** e abra no ProjectLibre ou aplicação compatível.

Consulte [MIGRACAO.md](MIGRACAO.md) para procedimentos de instalação, recuperação e cuidados.

## Interpretação das atividades-resumo

Quando um cartão possui subtarefas, o XML pode representá-lo como **tarefa-resumo**. Para evitar atribuir dependências diretamente ao resumo, o exportador vincula o cartão à linha auxiliar **Período do cartão**. Essa linha representa a data original cadastrada, não uma nova subtarefa.

Se o cartão-resumo não tiver data inicial planejada adequada, a exportação de uma dependência envolvendo esse cartão é rejeitada de forma explícita, em vez de criar um vínculo incorreto.

A exportação simplificada `?subtasks=legacy` não deve ser usada com dependências que envolvem subtarefas.

## Evidências funcionais

Foi realizada validação no projeto de testes #44 com dois cartões e duas subtarefas, comprovando no **ProjectLibre**:

| Combinação | Relação testada | Campo Antecessores |
|---|---|---|
| Subtarefa A.1 → Subtarefa B.1 | FS, SS, FF e SF / 0 | 3FS, 3SS, 3FF, 3SF |
| Cartão A → Cartão B | FS/0 e SS/+1 dia | 2; 2SS+1 dia |
| Subtarefa A.1 → Subtarefa B.1 | FS/-1 dia | 3FS-1 dia |
| Cartão A → Subtarefa B.1 | FS/0 | 2 |
| Subtarefa A.1 → Cartão B | FS/0 | 3 |

A validação não equivale à homologação universal. As ferramentas podem interpretar **duração**, **calendário 24/7**, **defasagem**, restrições e caminho crítico de maneira própria. Os testes comprovam principalmente a identificação e a interpretação dos vínculos no ProjectLibre. Diferenças temporais precisam ser documentadas e verificadas em cada aplicação.

## Testes automatizados

```bash
php tests/test_dependency_endpoints.php
php tests/test_dependency_export_mspdi.php
php tests/test_native_plans_v040.php
python3 tests/verify_plugin_rename.py
```

O workflow GitHub Actions também verifica PHP, XML, integrações com Kanboard e regressões.

**Diretriz:** o Kanboard registra; o Schedule Exchange intercambia; as aplicações de planejamento calculam e analisam.
