<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '未登入']);
    exit;
}

require_once 'db.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['match_id'])) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT match_status FROM matches WHERE match_id = ? FOR UPDATE");
        $stmt->execute([$_POST['match_id']]);
        $status = $stmt->fetchColumn();
        
        if ($status === 'active') {
            $stmt = $pdo->prepare("UPDATE matches SET match_status = 'completed' WHERE match_id = ?");
            $stmt->execute([$_POST['match_id']]);
            $response = ['success' => true, 'message' => '比賽已完成'];
            $pdo->commit();
        } else {
            $response = ['success' => false, 'message' => '只能完成進行中的比賽'];
            $pdo->rollBack();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $response = ['success' => false, 'message' => '操作失敗：' . $e->getMessage()];
    }
}
header('Content-Type: application/json');
echo json_encode($response);