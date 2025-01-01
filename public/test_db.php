<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

try {
    // 檢查表是否存在
    $tables = ['team_groups', 'teams', 'matches'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "表 $table 已成功創建<br>";
        } else {
            echo "表 $table 創建失敗<br>";
        }
    }

    // 檢查表結構
    foreach ($tables as $table) {
        echo "<br>表 $table 的結構：<br>";
        $stmt = $pdo->query("DESCRIBE $table");
        while ($row = $stmt->fetch()) {
            print_r($row);
            echo "<br>";
        }
    }

} catch (PDOException $e) {
    die("錯誤: " . $e->getMessage());
} 