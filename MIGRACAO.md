# Schedule Exchange - instalação, migração e rollback

Este documento trata da migração do plugin **Schedule Export** para o **Schedule Exchange**. Os nomes `ScheduleExport` e `ScheduleExchange` identificam plugins diferentes. **Não instale os dois em paralelo**: o novo plugin reutiliza componentes e estruturas históricas do planejamento de subtarefas.

## Antes de modificar a instalação

1. Confirme a versão do Kanboard e o hash Git do plugin atual.
2. Faça backup íntegro do diretório do plugin **e do banco de dados Kanboard**.
3. Preserve cópia do antigo `ScheduleExport` fora do diretório ativo `plugins/`, sem alterá-la nem excluí-la.
4. Verifique a existência das tabelas `schedule_exchange_dependencies` e das tabelas históricas de planejamento. **Não exclua, recrie ou migre manualmente tabelas** nesta atualização.
5. Instale a nova revisão em um diretório temporário e execute os testes PHP antes de substituí-la.

## Instalação

- O diretório final de instalação é `plugins/ScheduleExchange` (S e E maiúsculos).
- Utilize um clone Git ou uma distribuição que contenha as classes e os arquivos do repositório atual; nunca copie arquivos isoladamente.
- Mantenha o ambiente Kanboard em execução apenas se a substituição de arquivos puder ser realizada atomicamente e com rollback disponível.
- Confirme sintaxe PHP, versão, estado saudável do Kanboard e exportação XML do projeto de testes.

## Rollback

Se houver falha, restaure **somente o diretório de plugin que foi substituído**, com o backup correspondente. Não restaure o antigo Schedule Export por engano e não reverta o banco sem um procedimento de recuperação explícito e verificado. Se forem feitas alterações de esquema em revisões futuras, a compatibilidade de rollback do banco deverá ser avaliada separadamente.

Um refresh do navegador pode ser suficiente; alterações de opcache ou reinício do contêiner exigem avaliação operacional.

## Situação da versão

A branch `review/ui-planning-20261105` foi testada no homelab, com exportações MSPDI verificadas no ProjectLibre. A branch candidata `release/0.1.0-rc1` declara `0.1.0-rc1`: **não foi publicada uma versão estável do Schedule Exchange** e este documento não autoriza instalação em outros ambientes.

Nenhum dado privado, arquivo real de projeto, credencial ou backup deve ser incluído em commits públicos.
