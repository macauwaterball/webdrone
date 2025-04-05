<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

$match_number = $_POST['match_number'] ?? '';
$group_id = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;

// 檢查場次是否已存在
$query = "SELECT COUNT(*) FROM matches WHERE match_number = ?";
$params = [(int)$_POST['match_number']];

if ($group_id !== null) {
    $query .= " AND group_id = ?";
    $params[] = $group_id;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);

$exists = $stmt->fetchColumn() > 0;

echo json_encode([
    'success' => !$exists,
    'message' => $exists ? '該場次已存在於此小組中' : '場次可用'
]);