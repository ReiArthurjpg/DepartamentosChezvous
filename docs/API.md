# Documentação de Endpoints · API de Departamentos & Colaboradores (Chez Vous)

Todas as rotas administrativas requerem o cabeçalho HTTP:
```
X-API-Key: <ADMIN_API_KEY>
```

## 1. Departamentos

### `GET /api/v1/admin/departamentos`
Retorna a lista de departamentos com a contagem real de colaboradores vinculados (`colaboradores` / `colaboradoresCount`) e estatísticas globais.
- Query params opcionais: `search`, `status` (`Ativo`|`Inativo`), `sort` (`recent`|`nome`|`codigo`|`colaboradores`), `page`, `perPage`.

### `POST /api/v1/admin/departamentos`
Cria um novo departamento.
```json
{
  "nome": "Pâtisserie & Confeitaria",
  "codigo": "PAT-07",
  "centroCusto": "CC-107 - Doces Finos",
  "gestor": "Beatriz Fagundes",
  "email": "beatriz.fagundes@chezvous.com",
  "descricao": "Confeitaria fina para eventos corporativos e sociais."
}
```

### `GET /api/v1/admin/departamentos/{id}`
Retorna os detalhes do departamento (aceita `id` público como `dep-1`/UUID ou `codigo` como `GAST-01`) junto com o array `pessoas` contendo os colaboradores do setor.

### `PUT /api/v1/admin/departamentos/{id}`
Atualiza os dados cadastrais do departamento.

### `DELETE /api/v1/admin/departamentos/{id}`
Remove um departamento. Caso possua colaboradores vinculados, retorna `409 Conflict` (`DEPARTMENT_HAS_COLABORADORES`), salvo se enviado `?force=1`.

---

## 2. Colaboradores (Pessoas Cadastradas)

### `GET /api/v1/admin/departamentos/{id}/colaboradores`
Lista as pessoas cadastradas no departamento `{id}` com suporte a busca (`?search=`).

### `POST /api/v1/admin/departamentos/{id}/colaboradores`
Adiciona um novo colaborador ao departamento `{id}`.
```json
{
  "nome": "Rodrigo Almeida",
  "cargo": "Cozinheiro Sênior",
  "email": "rodrigo.almeida@chezvous.com",
  "telefone": "(11) 98765-4321"
}
```

### `PUT /api/v1/admin/colaboradores/{colabId}`
Atualiza nome, cargo, e-mail, telefone, status ou transfere o colaborador de departamento (`departamentoId`).

### `DELETE /api/v1/admin/colaboradores/{colabId}`
Remove o colaborador.

