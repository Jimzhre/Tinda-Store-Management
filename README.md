# TINDA - Store Management System
**Tagline:** Your Store. Your Stocks. Your Sales.

TINDA is a web-based inventory and sales management system designed specifically for small Filipino sari-sari stores. It allows store owners to manage their products, monitor available stocks, record sales transactions, and view business reports in one unified dashboard.

## Features
- **Public Landing Page:** Animated homepage with a modern "Tindahan" design.
- **Secure Authentication:** User registration and login system with hashed passwords. Every user has their own private store inventory.
- **Dashboard:** Real-time stats (Today's Sales, Total Products, Low Stock) and a dynamic 7-day sales bar chart.
- **Inventory:** View available products and track stock quantities.
- **Product Management:** Add, edit, and safely archive/delete products. Includes a custom toast notification and modal system.
- **Sales:** Record transactions which automatically deduct stock from the inventory.
- **Reports:** Filter sales by date range and view top-selling products.
- **Responsive Design:** Fully optimized for desktop, tablet, and mobile phones.

## Technology Stack
- **Frontend:** HTML5, CSS3, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Local Server:** XAMPP

---

## How to Run Locally
⚠️ Important Database Setup
Because database credentials are private, the `config/database.php` file is not included in this repository. 

To run the project:
1. Go to the `config` folder.
2. Rename `database.example.php` to `database.php`.
3. Open it and update the `$dbpass` if your local MySQL has a password (default is empty).
4. Import the `tinda_db.sql` file into phpMyAdmin.

## Test Account
If you don't want to create a new account, you can use this default test account (or register a new one on the website):
- **Username:** `admin`
- **Password:** `admin123`

*(Note: The database connection file `config/database.php` is included in this repository with default XAMPP credentials to make testing easy. In a production environment, this file would be hidden using `.gitignore`).*
