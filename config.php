<?php
mysqli_report(MYSQLI_REPORT_OFF);

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'eazy_dp';

$dbConnected = false;
$dbError = '';
$conn = null;

$conn = @new mysqli($host, $user, $password, $database);

if ($conn && !$conn->connect_error) {
    $dbConnected = true;
} else {
    $dbError = $conn ? $conn->connect_error : 'Unable to initialize database connection.';
    $conn = null;
}
?>