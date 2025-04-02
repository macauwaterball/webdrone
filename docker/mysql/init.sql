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
    group_name CHAR(1) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 創建隊伍表
CREATE TABLE IF NOT EXISTS teams (
    team_id INT AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(100) NOT NULL UNIQUE,
    group_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES team_groups(group_id)
);

-- 創建比賽表
CREATE TABLE IF NOT EXISTS matches (
    match_id INT AUTO_INCREMENT PRIMARY KEY,
    team1_id INT NOT NULL,
    team2_id INT NOT NULL,
    team1_score INT DEFAULT 0,
    team2_score INT DEFAULT 0,
    team1_fouls INT DEFAULT 0,
    team2_fouls INT DEFAULT 0,
    match_status ENUM('pending', 'ongoing', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team1_id) REFERENCES teams(team_id),
    FOREIGN KEY (team2_id) REFERENCES teams(team_id)
);