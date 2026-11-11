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

CREATE TABLE IF NOT EXISTS Products (
    product_ID      INT AUTO_INCREMENT PRIMARY KEY,
    category_ID     INT,
    product_name    VARCHAR(100) NOT NULL,
    price           DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    stock_quantity  INT NOT NULL DEFAULT 0,
    low_stock_threshold INT NOT NULL DEFAULT 5,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_ID) REFERENCES Categories(category_ID)
        ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS Sales (
    sale_ID       INT AUTO_INCREMENT PRIMARY KEY,
    sale_date     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_amount  DECIMAL(10, 2) NOT NULL DEFAULT 0.00
);

CREATE TABLE IF NOT EXISTS Sale_Details (
    detail_ID    INT AUTO_INCREMENT PRIMARY KEY,
    sale_ID      INT NOT NULL,
    product_ID   INT NOT NULL,
    quantity     INT NOT NULL,
    price        DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (sale_ID) REFERENCES Sales(sale_ID) ON DELETE CASCADE,
    FOREIGN KEY (product_ID) REFERENCES Products(product_ID)
);

INSERT INTO `categories` (`category_ID`, `category_name`) VALUES
(1, 'Beverages'),
(2, 'Canned & Dry Goods'),
(3, 'Condiments'),
(4, 'Household'),
(5, 'Medicine'),
(6, 'Personal Care'),
(7, 'Snacks & Chips');
