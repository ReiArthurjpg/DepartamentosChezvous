SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `departamentos` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `public_id` VARCHAR(36) NOT NULL COMMENT 'UUID v4 ou identificador público exposto na API',
    `nome` VARCHAR(160) NOT NULL,
    `codigo` VARCHAR(32) NOT NULL COMMENT 'Sigla única ex: GAST-01',
    `centro_custo` VARCHAR(160) NOT NULL COMMENT 'Ex: CC-101 - Produção Culinária',
    `gestor` VARCHAR(160) NOT NULL,
    `email` VARCHAR(180) NOT NULL DEFAULT '',
    `descricao` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_departamentos_public_id` (`public_id`),
    UNIQUE KEY `uk_departamentos_codigo` (`codigo`),
    KEY `idx_departamentos_created` (`created_at`),
    KEY `idx_departamentos_nome` (`nome`),
    KEY `idx_departamentos_gestor` (`gestor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `colaboradores` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `public_id` VARCHAR(36) NOT NULL COMMENT 'UUID v4 exposto na API',
    `departamento_id` BIGINT UNSIGNED NOT NULL,
    `nome` VARCHAR(160) NOT NULL,
    `cargo` VARCHAR(160) NOT NULL,
    `email` VARCHAR(180) NOT NULL,
    `telefone` VARCHAR(30) NOT NULL,
    `avatar` VARCHAR(8) NOT NULL COMMENT 'Iniciais ex: MV',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_colaboradores_public_id` (`public_id`),
    UNIQUE KEY `uk_colaboradores_email` (`email`),
    KEY `idx_colaboradores_departamento` (`departamento_id`),
    KEY `idx_colaboradores_nome` (`nome`),
    CONSTRAINT `fk_colaboradores_departamento`
        FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
