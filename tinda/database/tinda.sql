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

CREATE TABLE IF NOT EXISTS Suppliers (
    supplier_ID     INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name   VARCHAR(100) NOT NULL,
    contact_number  VARCHAR(20)
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

INSERT INTO Categories (category_name) VALUES
    ('Snacks'), ('Beverages'), ('Canned Goods'), ('Household'), ('Personal Care');

INSERT INTO Suppliers (supplier_name, contact_number) VALUES
    ('Mama Sita Trading', '0917-111-2222'),
    ('JolliCares Wholesale', '0918-333-4444');

INSERT INTO Products (category_ID, product_name, price, stock_quantity, low_stock_threshold) VALUES
    (1, 'Skyflakes Crackers',      8.00, 50, 10),
    (2, 'Coke Mismo 300ml',       20.00, 30, 8),
    (1, 'Piattos Cheese',         22.00, 25, 8),
    (3, 'Lucky Me Pancit Canton', 15.00, 40, 10),
    (4, 'Tide Sachet 45g',         9.00, 20, 5);