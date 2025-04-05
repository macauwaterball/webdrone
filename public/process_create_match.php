<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $match_number = $_POST['match_number'] ?? '';
    $team1_id = $_POST['team1_id'] ?? '';
    $team2_id = $_POST['team2_id'] ?? '';
    $group_id = $_POST['group_id'] ?? null;

    // 驗證數據
    $errors = [];
    if (empty($match_number)) {
        $errors[] = "比賽場次不能為空";
    }
    if (empty($team1_id)) {
        $errors[] = "請選擇隊伍1";
    }
    if (empty($team2_id)) {
        $errors[] = "請選擇隊伍2";
    }
    if ($team1_id === $team2_id) {
        $errors[] = "兩個隊伍不能相同";
    }

    // 檢查場次是否已存在（在同一小組內）
    if ($group_id) {
        // 如果有選擇小組，檢查該小組內的場次
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE match_number = ? AND group_id = ?");
        $stmt->execute([$match_number, $group_id]);
    } else {
        // 如果沒有選擇小組，檢查非小組賽的場次
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE match_number = ? AND group_id IS NULL");
        $stmt->execute([$match_number]);
    }
    
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "該場次已存在於此小組中";
    }

    if (empty($errors)) {
        // 處理 group_id 為空的情況
        $group_id = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO matches (match_number, team1_id, team2_id, match_status, group_id) VALUES (?, ?, ?, 'pending', ?)");
            $stmt->execute([
                (int)$_POST['match_number'],
                (int)$_POST['team1_id'],
                (int)$_POST['team2_id'],
                $group_id
            ]);
            
            // 創建成功後重定向
            header("Location: list_matches.php");
            exit;
        } catch (PDOException $e) {
            // 錯誤處理
            header("Location: create_match.php?error=" . urlencode("創建比賽失敗: " . $e->getMessage()));
            exit;
        }
    }

    // 如果有錯誤，返回創建頁面並顯示錯誤
    if (!empty($errors)) {
        $error_string = implode("\n", $errors);
        header("Location: create_match.php?error=" . urlencode($error_string));
        exit;
    }
} else {
    // 如果不是POST請求，重定向到創建頁面
    header("Location: create_match.php");
    exit;
}