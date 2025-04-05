-- 設置認證策略
SET GLOBAL authentication_policy='*';

-- 創建數據庫
CREATE DATABASE IF NOT EXISTS drone_soccer;
USE drone_soccer;

-- 創建管理員表
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 創建默認管理員帳號
INSERT INTO admins (username, password) 
VALUES ('admin', 'admin123')
ON DUPLICATE KEY UPDATE password = VALUES(password);

-- 創建小組表
CREATE TABLE IF NOT EXISTS team_groups (
    group_id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(50) NOT NULL UNIQUE,  -- 修改為 VARCHAR 以支持更長的小組名稱
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 創建隊伍表
CREATE TABLE IF NOT EXISTS teams (
    team_id INT AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(100) NOT NULL,
    group_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES team_groups(group_id) ON DELETE SET NULL
);

-- 創建比賽表（包含所有必要字段）
CREATE TABLE IF NOT EXISTS matches (
    match_id INT AUTO_INCREMENT PRIMARY KEY,
    match_number INT NOT NULL,
    team1_id INT NOT NULL,
    team2_id INT NOT NULL,
    team1_score INT DEFAULT 0,
    team2_score INT DEFAULT 0,
    team1_fouls INT DEFAULT 0,
    team2_fouls INT DEFAULT 0,
    match_duration INT DEFAULT 180,  -- 默認3分鐘（180秒）
    match_status ENUM('pending', 'active', 'completed', 'overtime') DEFAULT 'pending',
    winner_team_id INT NULL,  -- 新增：獲勝隊伍ID
    win_method ENUM('normal', 'draw', 'forfeit') NULL,  -- 新增：獲勝方式
    group_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team1_id) REFERENCES teams(team_id),
    FOREIGN KEY (team2_id) REFERENCES teams(team_id),
    FOREIGN KEY (winner_team_id) REFERENCES teams(team_id),
    FOREIGN KEY (group_id) REFERENCES team_groups(group_id) ON DELETE SET NULL
);

-- 創建用戶並授予權限
CREATE USER IF NOT EXISTS 'dronesoccer'@'%' IDENTIFIED BY 'Qweszxc!23';
GRANT ALL PRIVILEGES ON drone_soccer.* TO 'dronesoccer'@'%';
FLUSH PRIVILEGES;
