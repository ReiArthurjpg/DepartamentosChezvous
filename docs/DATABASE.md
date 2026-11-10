# Modelo de Banco de Dados · Departamentos & Colaboradores (Chez Vous)

## Tabela `departamentos`
- `id` (`BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`)
- `public_id` (`VARCHAR(36) NOT NULL UNIQUE`) — UUIDv4 ou identificador canônico (`dep-1`..`dep-6`)
- `nome` (`VARCHAR(160) NOT NULL`)
- `codigo` (`VARCHAR(32) NOT NULL UNIQUE`) — Sigla do setor (ex: `GAST-01`)
- `centro_custo` (`VARCHAR(160) NOT NULL`)
- `gestor` (`VARCHAR(160) NOT NULL`)
- `email` (`VARCHAR(180) NOT NULL DEFAULT ''`)
- `descricao` (`TEXT NULL`)
- `status` (`ENUM('Ativo', 'Inativo') NOT NULL DEFAULT 'Ativo'`)
- `created_at`, `updated_at` (`DATETIME`)

## Tabela `colaboradores`
- `id` (`BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`)
- `public_id` (`VARCHAR(36) NOT NULL UNIQUE`)
- `departamento_id` (`BIGINT UNSIGNED NOT NULL`) — FK para `departamentos(id)` (`ON DELETE RESTRICT`)
- `nome` (`VARCHAR(160) NOT NULL`)
- `cargo` (`VARCHAR(160) NOT NULL`)
- `email` (`VARCHAR(180) NOT NULL UNIQUE`)
- `telefone` (`VARCHAR(30) NOT NULL`)
- `avatar` (`VARCHAR(8) NOT NULL`) — Iniciais geradas automaticamente
- `status` (`ENUM('Ativo', 'Inativo') NOT NULL DEFAULT 'Ativo'`)
- `created_at`, `updated_at` (`DATETIME`)

