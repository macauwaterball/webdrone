<?php
require_once 'db.php';

$match_number = $_POST['match_number'] ?? '';
$group_id = $_POST['group_id'] ?? null;

try {
    if ($group_id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE match_number = ? AND group_id = ?");
        $stmt->execute([$match_number, $group_id]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE match_number = ? AND group_id IS NULL");
        $stmt->execute([$match_number]);
    }
    
    $exists = $stmt->fetchColumn() > 0;
    
    echo json_encode([
        'success' => !$exists,
        'message' => $exists ? '該場次已存在於此小組中' : '場次可用'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '檢查失敗: ' . $e->getMessage()
    ]);
} 