-- ============================================================================
-- MIGRATION 003: TABELAS DE AUTENTICAÇÃO E SESSÃO (CHEZ VOUS AUTH)
-- Banco compartilhado: chezvous_departamentos
-- ============================================================================
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Garante remoção da coluna status caso ainda exista no banco
ALTER TABLE `departamentos` DROP COLUMN IF EXISTS `status`;
ALTER TABLE `colaboradores` DROP COLUMN IF EXISTS `status`;

CREATE TABLE IF NOT EXISTS `_migrations_auth` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(255) NOT NULL UNIQUE,
    `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(160) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NULL,
    `google_id` VARCHAR(128) NULL UNIQUE,
    `role` ENUM('admin', 'coordenador', 'operador') NOT NULL DEFAULT 'coordenador',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `token_hash` CHAR(64) NOT NULL UNIQUE,
    `ip` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `expires_at` DATETIME NOT NULL,
    `revoked_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_sessions_user` (`user_id`),
    INDEX `idx_sessions_token` (`token_hash`),
    CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `token_hash` CHAR(64) NOT NULL UNIQUE,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(160) NOT NULL,
    `ip` VARCHAR(45) NOT NULL,
    `success` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_attempts_lookup` (`email`, `ip`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed inicial de usuários corporativos Chez Vous (senha padrão: 123456 em hash bcrypt)
INSERT IGNORE INTO `users` (`name`, `email`, `password_hash`, `role`, `is_active`) VALUES
('Alisson Rodrigo Soares', 'alisson@chezvous.com', '$2y$12$kOjDR38m5VTCdv0gUo/qkOwEn4BoT8vtH8PL1dya3arKkXg2L7dCy', 'admin', 1),
('Administrador Chez Vous', 'admin@chezvous.com', '$2y$12$kOjDR38m5VTCdv0gUo/qkOwEn4BoT8vtH8PL1dya3arKkXg2L7dCy', 'admin', 1),
('Arthur Ribeiro', 'arthurgui011@gmail.com', '$2y$12$kOjDR38m5VTCdv0gUo/qkOwEn4BoT8vtH8PL1dya3arKkXg2L7dCy', 'admin', 1);

