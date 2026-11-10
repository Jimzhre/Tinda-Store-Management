<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// =============================================
// TINDA — Database connection
// Include this on any page that needs MySQL.
// =============================================

$host   = "localhost";
$dbname = "tinda_db";
$dbuser = "root";
$dbpass = "";              // XAMPP default: empty

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");