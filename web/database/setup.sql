-- ============================================================
-- Dashboard Admin - Setup do Banco de Dados
-- ============================================================

CREATE DATABASE IF NOT EXISTS dashboard_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dashboard_admin;

-- ------------------------------------------------------------
-- Tabela: usuarios
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('admin','gerente','operador') DEFAULT 'operador',
    avatar VARCHAR(255) DEFAULT NULL,
    ativo TINYINT(1) DEFAULT 1,
    ultimo_acesso DATETIME DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Tabela: clientes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telefone VARCHAR(20) DEFAULT NULL,
    cidade VARCHAR(100) DEFAULT NULL,
    estado CHAR(2) DEFAULT NULL,
    status ENUM('ativo','inativo','bloqueado') DEFAULT 'ativo',
    total_pedidos INT DEFAULT 0,
    total_gasto DECIMAL(12,2) DEFAULT 0.00,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Tabela: categorias
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    descricao TEXT DEFAULT NULL,
    ativo TINYINT(1) DEFAULT 1
);

-- ------------------------------------------------------------
-- Tabela: produtos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    sku VARCHAR(60) NOT NULL UNIQUE,
    categoria_id INT DEFAULT NULL,
    preco DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    estoque INT NOT NULL DEFAULT 0,
    status ENUM('ativo','inativo','esgotado') DEFAULT 'ativo',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- Tabela: pedidos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL UNIQUE,
    cliente_id INT NOT NULL,
    status ENUM('pendente','processando','enviado','entregue','cancelado') DEFAULT 'pendente',
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    desconto DECIMAL(12,2) DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Tabela: itens_pedido
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS itens_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    preco_unitario DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

-- ------------------------------------------------------------
-- Tabela: receitas (dados para gráfico mensal)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS receitas_mensais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ano INT NOT NULL,
    mes TINYINT NOT NULL,
    receita DECIMAL(14,2) DEFAULT 0.00,
    pedidos INT DEFAULT 0,
    novos_clientes INT DEFAULT 0
);

-- ------------------------------------------------------------
-- Tabela: atividades (log de ações)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS atividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT DEFAULT NULL,
    descricao VARCHAR(255) NOT NULL,
    tipo ENUM('pedido','usuario','produto','sistema') DEFAULT 'sistema',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- ============================================================
-- Dados de Exemplo
-- ============================================================

INSERT INTO usuarios (nome, email, senha, perfil) VALUES
('Administrador', 'admin@dashboard.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Carlos Souza', 'carlos@dashboard.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gerente'),
('Ana Lima', 'ana@dashboard.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operador'),
('Pedro Martins', 'pedro@dashboard.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operador'),
('Juliana Costa', 'juliana@dashboard.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gerente');

INSERT INTO clientes (nome, email, telefone, cidade, estado, status, total_pedidos, total_gasto) VALUES
('Fernanda Oliveira', 'fernanda@email.com', '(11) 99001-2345', 'São Paulo', 'SP', 'ativo', 12, 4580.00),
('Ricardo Alves', 'ricardo@email.com', '(21) 98765-4321', 'Rio de Janeiro', 'RJ', 'ativo', 8, 2340.50),
('Mariana Santos', 'mariana@email.com', '(31) 97654-3210', 'Belo Horizonte', 'MG', 'ativo', 5, 1850.00),
('Bruno Ferreira', 'bruno@email.com', '(41) 96543-2109', 'Curitiba', 'PR', 'inativo', 3, 780.00),
('Camila Rocha', 'camila@email.com', '(51) 95432-1098', 'Porto Alegre', 'RS', 'ativo', 9, 3120.75),
('Thiago Mendes', 'thiago@email.com', '(61) 94321-0987', 'Brasília', 'DF', 'ativo', 6, 2200.00),
('Larissa Nunes', 'larissa@email.com', '(85) 93210-9876', 'Fortaleza', 'CE', 'ativo', 4, 1400.00),
('Gabriel Ramos', 'gabriel@email.com', '(71) 92109-8765', 'Salvador', 'BA', 'ativo', 7, 2750.00),
('Patrícia Lima', 'patricia@email.com', '(92) 91098-7654', 'Manaus', 'AM', 'bloqueado', 2, 450.00),
('Lucas Barbosa', 'lucas@email.com', '(48) 90987-6543', 'Florianópolis', 'SC', 'ativo', 11, 3900.00);

INSERT INTO categorias (nome, slug, descricao) VALUES
('Eletrônicos', 'eletronicos', 'Smartphones, tablets, notebooks e acessórios'),
('Roupas', 'roupas', 'Moda masculina e feminina'),
('Casa e Jardim', 'casa-jardim', 'Móveis, decoração e utensílios'),
('Esportes', 'esportes', 'Equipamentos e vestuário esportivo'),
('Livros', 'livros', 'Livros físicos e digitais');

INSERT INTO produtos (nome, sku, categoria_id, preco, estoque, status) VALUES
('Smartphone Pro X12', 'SMRTX12', 1, 2499.90, 85, 'ativo'),
('Notebook Ultra 15', 'NTBK15', 1, 4799.00, 42, 'ativo'),
('Fone Bluetooth Premium', 'FONEBT1', 1, 349.90, 200, 'ativo'),
('Camiseta Básica Algodão', 'CMST01', 2, 59.90, 500, 'ativo'),
('Tênis Runner Pro', 'TNSR01', 4, 299.90, 120, 'ativo'),
('Cafeteira Espresso', 'CAFE01', 3, 899.00, 30, 'ativo'),
('Livro: PHP Moderno', 'LIVPHP', 5, 89.90, 75, 'ativo'),
('Mochila Esportiva 30L', 'MOCH30', 4, 189.90, 90, 'ativo'),
('Smart Watch Fit', 'SWTCH1', 1, 599.90, 55, 'ativo'),
('Mesa de Escritório', 'MESA01', 3, 1299.00, 15, 'ativo');

INSERT INTO pedidos (numero, cliente_id, status, subtotal, desconto, total, criado_em) VALUES
('PED-2026-0001', 1, 'entregue',  2499.90, 0.00, 2499.90, '2026-06-01 09:15:00'),
('PED-2026-0002', 2, 'entregue',  4799.00, 200.00, 4599.00, '2026-06-02 11:30:00'),
('PED-2026-0003', 3, 'enviado',   349.90, 0.00, 349.90, '2026-06-05 14:00:00'),
('PED-2026-0004', 5, 'entregue',  599.90, 0.00, 599.90, '2026-06-08 10:45:00'),
('PED-2026-0005', 6, 'processando',899.00, 50.00, 849.00, '2026-06-10 16:20:00'),
('PED-2026-0006', 8, 'entregue',  1489.80, 0.00, 1489.80, '2026-06-12 09:00:00'),
('PED-2026-0007', 10, 'enviado',  2799.80, 100.00, 2699.80, '2026-06-15 13:10:00'),
('PED-2026-0008', 1, 'pendente',  1299.00, 0.00, 1299.00, '2026-06-18 11:00:00'),
('PED-2026-0009', 7, 'processando',189.90, 0.00, 189.90, '2026-06-20 15:30:00'),
('PED-2026-0010', 4, 'cancelado', 299.90, 0.00, 299.90, '2026-06-22 08:45:00'),
('PED-2026-0011', 2, 'entregue',  59.90, 0.00, 59.90, '2026-06-23 10:00:00'),
('PED-2026-0012', 5, 'pendente',  1788.80, 0.00, 1788.80, '2026-06-25 17:00:00');

INSERT INTO receitas_mensais (ano, mes, receita, pedidos, novos_clientes) VALUES
(2026, 1, 32400.00, 48, 12),
(2026, 2, 28750.00, 41, 9),
(2026, 3, 41200.00, 62, 18),
(2026, 4, 38900.00, 57, 14),
(2026, 5, 47300.00, 71, 21),
(2026, 6, 24180.20, 35, 10);

INSERT INTO atividades (usuario_id, descricao, tipo) VALUES
(1, 'Novo pedido #PED-2026-0012 recebido de Camila Rocha', 'pedido'),
(3, 'Produto "Smart Watch Fit" com estoque baixo (55 un.)', 'produto'),
(2, 'Usuário "Patrícia Lima" foi bloqueado', 'usuario'),
(1, 'Pedido #PED-2026-0007 enviado para o cliente', 'pedido'),
(NULL, 'Backup automático do sistema realizado', 'sistema'),
(3, 'Novo cliente cadastrado: Lucas Barbosa', 'usuario'),
(2, 'Pedido #PED-2026-0006 marcado como entregue', 'pedido'),
(1, 'Categoria "Livros" adicionada ao catálogo', 'produto');
