<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

$team_id = $_GET['team_id'] ?? null;
if ($team_id) {
    $stmt = $pdo->prepare("SELECT * FROM players WHERE team_id = ? ORDER BY jersey_number");
    $stmt->execute([$team_id]);
    $players = $stmt->fetchAll();
    
    // 獲取隊伍名稱
    $stmt = $pdo->prepare("SELECT team_name FROM teams WHERE team_id = ?");
    $stmt->execute([$team_id]);
    $team_name = $stmt->fetchColumn();
    
    echo json_encode([
        'players' => $players,
        'team_name' => $team_name
    ]);
} 