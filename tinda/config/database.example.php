<?php
// Copy this file to database.php and update the credentials below
$host   = "localhost";
$dbname = "tinda_db";
$dbuser = "root";
$dbpass = "";              
$conn = new mysqli($host, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) { die("Database connection failed."); }
$conn->set_charset("utf8mb4");
