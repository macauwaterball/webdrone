<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'debian.mcpesp.org';
$dbname = 'drone_soccer';
$username = 'dronesoccer';
$password = 'Qweszxc!23';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    echo "連接成功！";
} catch (PDOException $e) {
    die("連接失敗: " . $e->getMessage());
}
?> 