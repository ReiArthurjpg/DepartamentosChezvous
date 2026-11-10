# Chez Vous · Inteligência e Diretrizes de Projeto (API de Departamentos & Colaboradores)

Este documento contém o contexto de arquitetura, regras de negócio, infraestrutura e padrões de engenharia para desenvolvimento e manutenção da API de Departamentos e Colaboradores da Chez Vous Produção & Eventos.

---

## 1. Visão Geral e Arquitetura

* **Objetivo:** API RESTful de alta performance e resiliência para o módulo administrativo `/Departamentos` e `/Departamentos/[id]` do ecossistema Chez Vous.
* **Linguagem & Runtime:** PHP 8.3 com tipagem estrita (`declare(strict_types=1);`).
* **Design Pattern:** Arquitetura desacoplada em camadas (padrão LogMovy):
  * `src/Controller/`: Manipulação de requisições e respostas HTTP PSR-7 (`DepartamentoController`, `ColaboradorController`, `HealthController`).
  * `src/Service/`: Regras de negócio, unicidade de código/e-mail e orquestração (`DepartamentoService`, `ColaboradorService`).
  * `src/Repository/`: Acesso a dados via PDO MySQL 8.4 (`DepartamentoRepository`, `ColaboradorRepository`).
  * `src/Middleware/`: Autenticação (`AdminApiKeyMiddleware`), CORS (`CorsMiddleware`), tratamento global de erros (`JsonErrorMiddleware`) e rate limiting (`RateLimitMiddleware`).
  * `src/Validation/`: Validações de dados de departamento (`DepartamentoValidator`) e colaboradores (`ColaboradorValidator`).
  * `src/Support/StandaloneRuntime.php`: Implementação autônoma de contratos PSR-7 e container para funcionar tanto em Docker quanto em hospedagens compartilhadas (Hostinger).

---

## 2. Regras de Negócio Principais

1. **Contagem Dinâmica de Colaboradores:**
   * A quantidade de colaboradores de um departamento **nunca** é um número estático informado em formulário: ela é calculada dinamicamente via `SELECT COUNT(*)` sobre a tabela `colaboradores` vinculada ao `departamento_id`.
2. **Identificação Flexível (`public_id` ou `codigo`):**
   * Os endpoints de detalhe e sub-rotas (`/api/v1/admin/departamentos/{id}`) aceitam tanto o `public_id` (UUID v4 ou `dep-1`..`dep-6`) quanto o `codigo` do setor (ex: `GAST-01`).
3. **Proteção na Exclusão de Departamentos:**
   * Por padrão (`ON DELETE RESTRICT`), tentar excluir um departamento que possui colaboradores vinculados retorna `409 Conflict` (`DEPARTMENT_HAS_COLABORADORES`), a menos que seja enviado `?force=1` para exclusão em cascata transacional.
4. **Avatar Automático de Colaboradores:**
   * Ao cadastrar um colaborador na aba **Pessoas Cadastradas**, o sistema deriva automaticamente as iniciais do primeiro e último nome em letras maiúsculas (ex: `Chef Marcus Vinícius` -> `CV`, `Helena Paiva` -> `HP`).

---

## 3. Endpoints Principais (`/api/v1`)

Todos os endpoints administrativos exigem o header `X-API-Key: <ADMIN_API_KEY>`.

* `GET /api/v1/admin/departamentos/stats`: Indicadores gerais (`totalDepartamentos`, `totalColaboradores`).
* `GET /api/v1/admin/departamentos`: Listagem paginada com busca (`search`), ordenação (`sort`) e contagem de colaboradores em tempo real.
* `POST /api/v1/admin/departamentos`: Criação de novo departamento.
* `GET /api/v1/admin/departamentos/{id}`: Detalhes do departamento + lista de pessoas cadastradas (`pessoas`).
* `PUT /api/v1/admin/departamentos/{id}`: Atualização cadastral do departamento.
* `DELETE /api/v1/admin/departamentos/{id}`: Exclusão do departamento (`?force=1` opcional).
* `GET /api/v1/admin/departamentos/{id}/colaboradores`: Lista de colaboradores do departamento com filtro de busca (`search`).
* `POST /api/v1/admin/departamentos/{id}/colaboradores`: Adiciona um novo colaborador ao departamento.
* `GET /api/v1/admin/colaboradores`: Lista global de todos os colaboradores.
* `PUT /api/v1/admin/colaboradores/{colabId}`: Edição de dados ou transferência de departamento de um colaborador.
* `DELETE /api/v1/admin/colaboradores/{colabId}`: Remoção de um colaborador.

