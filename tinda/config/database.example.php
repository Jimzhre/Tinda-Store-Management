<?php
date_default_timezone_set('Asia/Manila');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Copy this file to database.php and update the credentials below
$host   = "localhost";
$dbname = "tinda_db";
$dbuser = "root";
$dbpass = "";              
$conn = new mysqli($host, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) { die("Database connection failed."); }
$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '+08:00'");
