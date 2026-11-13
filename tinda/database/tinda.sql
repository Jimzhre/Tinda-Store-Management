-- =============================================
-- TINDA — Database setup
-- Safe to run on a new OR an existing database:
--   * tables are only created if they don't exist yet
--   * starter rows are only added if they aren't there yet
--   * the last step converts old [Deleted] products to [Archived]
-- =============================================

CREATE DATABASE IF NOT EXISTS tinda_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tinda_db;

CREATE TABLE IF NOT EXISTS Users (
    user_ID       INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    full_name     VARCHAR(100),
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS Categories (
    category_ID   INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(80) NOT NULL UNIQUE
);

-- user_ID = the store owner (account) the product belongs to
-- A product that is archived has "[Archived] " in front of its name
CREATE TABLE IF NOT EXISTS Products (
    product_ID      INT AUTO_INCREMENT PRIMARY KEY,
    user_ID         INT NOT NULL,
    category_ID     INT,
    product_name    VARCHAR(100) NOT NULL,
    price           DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    stock_quantity  INT NOT NULL DEFAULT 0,
    low_stock_threshold INT NOT NULL DEFAULT 5,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_ID) REFERENCES Users(user_ID),
    FOREIGN KEY (category_ID) REFERENCES Categories(category_ID)
        ON DELETE SET NULL
);

-- user_ID = the store owner (account) who made the sale
CREATE TABLE IF NOT EXISTS Sales (
    sale_ID       INT AUTO_INCREMENT PRIMARY KEY,
    user_ID       INT NOT NULL,
    sale_date     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_amount  DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (user_ID) REFERENCES Users(user_ID)
);

-- One row per item in a sale. A product that appears here can't be
-- permanently deleted (the foreign key protects your sales history).
CREATE TABLE IF NOT EXISTS Sale_Details (
    detail_ID    INT AUTO_INCREMENT PRIMARY KEY,
    sale_ID      INT NOT NULL,
    product_ID   INT NOT NULL,
    quantity     INT NOT NULL,
    price        DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (sale_ID) REFERENCES Sales(sale_ID) ON DELETE CASCADE,
    FOREIGN KEY (product_ID) REFERENCES Products(product_ID)
);

-- Starter data (INSERT IGNORE = skipped if it already exists)
INSERT IGNORE INTO `Categories` (`category_ID`, `category_name`) VALUES
    (1, 'Beverages'), (2, 'Canned & Dry Goods'), (3, 'Condiments'), (4, 'Household'), (5, 'Medicine'), (6, 'Personal Care'), (7, 'Snacks & Chips');

INSERT IGNORE INTO `Users` (`user_ID`, `username`, `password`, `full_name`, `created_at`) VALUES
    (1, 'admin', 'admin123', 'Admin', '2026-11-11 15:17:42');

-- ---------------------------------------------
-- UPGRADE: old archived products "[Deleted] Name" become "[Archived] Name"
-- (does nothing if there are none left)
-- ---------------------------------------------
UPDATE Products
SET product_name = CONCAT('[Archived]', SUBSTRING(product_name, 10))
WHERE product_name LIKE '[Deleted]%';