<?php
// Simple MySQL connection
$host = '163.227.92.73';
$username = 'mccbeatlebuddy_user';
$password = 'h-CylEQPK!rW';
$database = 'mccbeatlebuddy_db';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
	$conn = new mysqli($host, $username, $password, $database);
	$conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
	error_log('Database connection failed: ' . $exception->getMessage());
	http_response_code(503);
	die('Database temporarily unavailable. Please try again later.');
}
?>
