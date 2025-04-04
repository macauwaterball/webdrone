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
        $stmt = $pdo->prepare("UPDATE matches SET match_status = 'completed' WHERE match_id = ? AND match_status = 'active'");
        $result = $stmt->execute([$_POST['match_id']]);
        
        if ($result && $stmt->rowCount() > 0) {
            $response = ['success' => true, 'message' => '比賽已完成'];
        } else {
            $response = ['success' => false, 'message' => '只能完成進行中的比賽'];
        }
    } catch (PDOException $e) {
        $response = ['success' => false, 'message' => '操作失敗：' . $e->getMessage()];
    }
}

header('Content-Type: application/json');
echo json_encode($response);