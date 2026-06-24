CREATE DATABASE IF NOT EXISTS tradelens;
USE tradelens;

-- Users table (Managed by Rajan, but needed for foreign keys)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Trades table (Managed by Sudan)
CREATE TABLE IF NOT EXISTS trades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    asset_name VARCHAR(100) NOT NULL,
    trade_type ENUM('Buy', 'Sell') NOT NULL,
    entry_price DECIMAL(15, 4) NOT NULL,
    exit_price DECIMAL(15, 4) NOT NULL,
    quantity DECIMAL(15, 4) NOT NULL DEFAULT 1,
    trade_date DATE NOT NULL,
    notes TEXT,
    emotion VARCHAR(50),
    emotion_note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
