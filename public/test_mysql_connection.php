<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 使用環境變量配置數據庫連接
$host = getenv('DB_HOST') ?: 'db:3307';
$dbname = getenv('DB_NAME') ?: 'drone_soccer';
$username = getenv('DB_USER') ?: 'dronesoccer';
$password = getenv('DB_PASSWORD') ?: 'Qweszxc!23';
$charset = 'utf8mb4';

try {
    // 建立PDO連接
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // 測試查詢
    $stmt = $pdo->query("SELECT 1");
    $result = $stmt->fetch();
    
    echo "數據庫連接成功！測試查詢結果：";
    print_r($result);
    
} catch (PDOException $e) {
    echo "數據庫連接失敗: " . $e->getMessage();
}
