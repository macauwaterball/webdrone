<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

// 設置輸出為 CSV 檔案
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=matches_results_' . date('Y-m-d') . '.csv');

// 創建輸出流
$output = fopen('php://output', 'w');

// 添加 UTF-8 BOM，解決 Excel 中文亂碼問題
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// 寫入 CSV 標題行
fputcsv($output, [
    '日期',
    '組別',
    '場次',
    '隊伍1',
    '隊伍2',
    '比分',
    '隊伍1犯規次數',
    '隊伍2犯規次數'
]);

// 獲取所有已完成的比賽
$query = "SELECT 
    m.match_number,
    m.team1_score,
    m.team2_score,
    m.team1_fouls,
    m.team2_fouls,
    m.created_at,
    t1.team_name as team1_name,
    t2.team_name as team2_name,
    g.group_name
    FROM matches m
    LEFT JOIN teams t1 ON m.team1_id = t1.team_id
    LEFT JOIN teams t2 ON m.team2_id = t2.team_id
    LEFT JOIN team_groups g ON m.group_id = g.group_id
    WHERE m.match_status = 'completed'
    ORDER BY g.group_name, m.match_number";

$stmt = $pdo->query($query);

// 寫入比賽數據
while ($match = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $row = [
        date('Y-m-d H:i', strtotime($match['created_at'])),
        $match['group_name'] ? $match['group_name'] . '組' : '無分組',
        $match['match_number'],
        $match['team1_name'],
        $match['team2_name'],
        '"' . $match['team1_score'] . ' - ' . $match['team2_score'] . '"',  // 添加引號避免 Excel 誤識別為日期
        $match['team1_fouls'],
        $match['team2_fouls']
    ];
    fputcsv($output, $row);
}

// 關閉輸出流
fclose($output);
exit;