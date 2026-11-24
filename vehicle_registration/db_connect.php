<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "sql211.infinityfree.com";
$username = "if0_43481529";
$password = "aposaga20";
$database = "if0_43481529_vehicle_registration_db";
$port = 3306; // Change this to your MySQL port if different.

try {
    $conn = new mysqli($host, $username, $password, $database, $port);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
