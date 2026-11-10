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
    `status` ENUM('Ativo', 'Inativo') NOT NULL DEFAULT 'Ativo',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_departamentos_public_id` (`public_id`),
    UNIQUE KEY `uk_departamentos_codigo` (`codigo`),
    KEY `idx_departamentos_status_created` (`status`, `created_at`),
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
    `status` ENUM('Ativo', 'Inativo') NOT NULL DEFAULT 'Ativo',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_colaboradores_public_id` (`public_id`),
    UNIQUE KEY `uk_colaboradores_email` (`email`),
    KEY `idx_colaboradores_departamento` (`departamento_id`, `status`),
    KEY `idx_colaboradores_nome` (`nome`),
    CONSTRAINT `fk_colaboradores_departamento`
        FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `departamentos` (`id`, `public_id`, `nome`, `codigo`, `centro_custo`, `gestor`, `email`, `descricao`, `status`, `created_at`) VALUES
(1, 'dep-1', 'Cozinha & Gastronomia', 'GAST-01', 'CC-101 - Produção Culinária', 'Chef Marcus Vinícius', 'marcus.chef@chezvous.com', 'Desenvolvimento de cardápios, preparação de pratos, fichas técnicas e controle de qualidade dos insumos.', 'Ativo', '2026-01-15 10:00:00'),
(2, 'dep-2', 'Operações & Eventos', 'OPER-02', 'CC-102 - Operação de Campo', 'Valeria Souza', 'valeria.souza@chezvous.com', 'Coordenação logística no local do evento, montagem de praças, cronograma de execução e desmontagem.', 'Ativo', '2026-01-15 10:05:00'),
(3, 'dep-3', 'Logística & Expedição', 'LOG-03', 'CC-103 - Transporte & Frotas', 'Mayrlon Sérgio', 'mayrlon.sergio@chezvous.com', 'Controle de fretes, expedição de louças finas, equipamentos, transporte térmico e devoluções.', 'Ativo', '2026-01-20 11:00:00'),
(4, 'dep-4', 'Atendimento & Salão', 'ATD-04', 'CC-104 - Serviço de Salão', 'Camila Bittencourt', 'camila.b@chezvous.com', 'Equipe de garçons, maîtres, copeiros, baristas e atendimento de hospitalidade aos convidados.', 'Ativo', '2026-02-01 09:30:00'),
(5, 'dep-5', 'Suprimentos & Compras', 'SUP-05', 'CC-105 - Compras & Armazenagem', 'Roberto Albuquerque', 'roberto.suprimentos@chezvous.com', 'Aquisição de insumos secos e frescos, negociação de fornecedores e controle de inventário.', 'Ativo', '2026-02-05 14:20:00'),
(6, 'dep-6', 'Administrativo & Financeiro', 'ADM-06', 'CC-106 - Diretoria & Finanças', 'Alisson Rodrigo Soares', 'alisson@chezvous.com', 'Faturamento, contas a pagar/receber, fechamento de contratos de eventos e compliance fiscal.', 'Ativo', '2026-01-10 08:00:00');

INSERT IGNORE INTO `colaboradores` (`public_id`, `departamento_id`, `nome`, `cargo`, `email`, `telefone`, `avatar`, `status`) VALUES
('c1', 1, 'Chef Marcus Vinícius', 'Chef Executivo & Gestor', 'marcus.chef@chezvous.com', '(11) 98765-4321', 'MV', 'Ativo'),
('c2', 1, 'Helena Paiva', 'Sous Chef de Cozinha', 'helena.paiva@chezvous.com', '(11) 98765-1102', 'HP', 'Ativo'),
('c3', 1, 'Lucas Santoro', 'Cozinheiro Líder de Carnes', 'lucas.santoro@chezvous.com', '(11) 98765-2234', 'LS', 'Ativo'),
('c4', 1, 'Beatriz Fagundes', 'Chef Pâtissière', 'beatriz.fagundes@chezvous.com', '(11) 98765-3345', 'BF', 'Ativo'),
('c5', 1, 'Gabriel Nogueira', 'Auxiliar de Cozinha Pleno', 'gabriel.nogueira@chezvous.com', '(11) 98765-4456', 'GN', 'Ativo'),
('c6', 2, 'Valeria Souza', 'Coordenadora Geral de Eventos', 'valeria.souza@chezvous.com', '(11) 98765-5567', 'VS', 'Ativo'),
('c7', 2, 'Diego Brandão', 'Supervisor de Montagem de Praça', 'diego.brandao@chezvous.com', '(11) 98765-6678', 'DB', 'Ativo'),
('c8', 2, 'Fernanda Lima', 'Produtora de Campo', 'fernanda.lima@chezvous.com', '(11) 98765-7789', 'FL', 'Ativo'),
('c9', 3, 'Mayrlon Sérgio', 'Gerente de Logística & Frotas', 'mayrlon.sergio@chezvous.com', '(11) 98765-8890', 'MS', 'Ativo'),
('c10', 3, 'Carlos Eduardo Ramos', 'Encarregado de Expedição', 'carlos.ramos@chezvous.com', '(11) 98765-9901', 'CR', 'Ativo'),
('c11', 3, 'Thiago Martins', 'Motorista Especializado em Frio', 'thiago.martins@chezvous.com', '(11) 98765-0012', 'TM', 'Ativo'),
('c12', 4, 'Camila Bittencourt', 'Chefe de Salão & Sommelier', 'camila.b@chezvous.com', '(11) 98765-1223', 'CB', 'Ativo'),
('c13', 4, 'Rafael Silveira', 'Primeiro Maître', 'rafael.silveira@chezvous.com', '(11) 98765-2334', 'RS', 'Ativo'),
('c14', 4, 'Mariana Duarte', 'Capitã de Garçons', 'mariana.duarte@chezvous.com', '(11) 98765-3445', 'MD', 'Ativo'),
('c15', 5, 'Roberto Albuquerque', 'Comprador Sênior & Almoxarife', 'roberto.suprimentos@chezvous.com', '(11) 98765-4556', 'RA', 'Ativo'),
('c16', 5, 'Aline Pires', 'Assistente de Compras', 'aline.pires@chezvous.com', '(11) 98765-5667', 'AP', 'Ativo'),
('c17', 6, 'Alisson Rodrigo Soares', 'Administrador Geral / Produção', 'alisson@chezvous.com', '(11) 98765-6778', 'AS', 'Ativo'),
('c18', 6, 'Juliana Castro', 'Analista Financeiro & Contratos', 'juliana.castro@chezvous.com', '(11) 98765-7889', 'JC', 'Ativo');

