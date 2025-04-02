<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 使用環境變量配置數據庫連接
$host = getenv('DB_HOST') ?: 'db';  // 改為 db
$dbname = getenv('DB_NAME') ?: 'drone_soccer';
$username = getenv('DB_USER') ?: 'dronesoccer';
$password = getenv('DB_PASSWORD') ?: 'Qweszxc!23';
$charset = 'utf8mb4';

// PDO連接設定
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("連接失敗: " . $e->getMessage());
}

// 創建小組表 (改名為 team_groups)
$pdo->exec("CREATE TABLE IF NOT EXISTS team_groups (
    group_id INT AUTO_INCREMENT PRIMARY KEY,
    group_name CHAR(1) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 創建隊伍表
$pdo->exec("CREATE TABLE IF NOT EXISTS teams (
    team_id INT AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 創建比賽表
$pdo->exec("CREATE TABLE IF NOT EXISTS matches (
    match_id INT AUTO_INCREMENT PRIMARY KEY,
    match_number INT NOT NULL,
    team1_id INT NOT NULL,
    team2_id INT NOT NULL,
    team1_score INT DEFAULT 0,
    team2_score INT DEFAULT 0,
    team1_fouls INT DEFAULT 0,
    team2_fouls INT DEFAULT 0,
    match_duration INT DEFAULT 600,
    match_status ENUM('pending', 'active', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    group_id INT NULL,
    FOREIGN KEY (team1_id) REFERENCES teams(team_id),
    FOREIGN KEY (team2_id) REFERENCES teams(team_id),
    FOREIGN KEY (group_id) REFERENCES team_groups(group_id)
)");

// 創建運動員表
$pdo->exec("CREATE TABLE IF NOT EXISTS players (
    player_id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    player_name VARCHAR(100) NOT NULL,
    jersey_number VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(team_id)
)");
