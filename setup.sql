-- ============================================
-- TradeLens Database Setup
-- ============================================

CREATE DATABASE IF NOT EXISTS tradelens CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tradelens;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    google_id VARCHAR(255) UNIQUE DEFAULT NULL,
    auth_provider VARCHAR(20) NOT NULL DEFAULT 'local',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS trades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    asset_name VARCHAR(100) NOT NULL,
    trade_type ENUM('Buy', 'Sell') NOT NULL,
    entry_price DECIMAL(15,4) NOT NULL,
    exit_price DECIMAL(15,4) NOT NULL,
    quantity DECIMAL(15,4) NOT NULL DEFAULT 1,
    trade_date DATE NOT NULL,
    notes TEXT,
    emotion VARCHAR(50) DEFAULT NULL,
    source VARCHAR(30) NOT NULL DEFAULT 'manual',
    broker_trade_id VARCHAR(120) DEFAULT NULL,
    account_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Indexes for performance
CREATE INDEX idx_trades_user_id ON trades(user_id);
CREATE INDEX idx_trades_trade_date ON trades(trade_date);
CREATE INDEX idx_trades_asset_name ON trades(asset_name);
CREATE INDEX idx_trades_account ON trades(account_id);

CREATE TABLE IF NOT EXISTS trading_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    display_name VARCHAR(120) NOT NULL,
    account_type VARCHAR(40) NOT NULL DEFAULT 'manual',
    broker_login VARCHAR(50) NULL,
    broker_server VARCHAR(120) NULL,
    metaapi_id VARCHAR(120) NULL,
    region VARCHAR(50) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    last_sync_at DATETIME NULL,
    last_error TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ta_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS broker_connections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    broker_key VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'disconnected',
    last_sync_at DATETIME NULL,
    last_error TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_broker (user_id, broker_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chat_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(160) NOT NULL DEFAULT 'New chat',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chat_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    role ENUM('user','assistant') NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_chat_conv (conversation_id),
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE
);

-- If you already created the old schema, run:
-- ALTER TABLE users ADD COLUMN google_id VARCHAR(255) UNIQUE DEFAULT NULL;
-- ALTER TABLE users ADD COLUMN auth_provider VARCHAR(20) NOT NULL DEFAULT 'local';
-- ALTER TABLE trades ADD COLUMN source VARCHAR(30) NOT NULL DEFAULT 'manual';
-- ALTER TABLE trades ADD COLUMN broker_trade_id VARCHAR(120) DEFAULT NULL;
