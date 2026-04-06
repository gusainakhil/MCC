<?php
// Simple MySQL connection
$host = '163.227.92.73';
$username = 'mccbeatlebuddy_user';
$password = 'h-CylEQPK!rW';
$database = 'mccbeatlebuddy_db';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
	die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
?>
