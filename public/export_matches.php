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
    '隊伍2犯規次數',
    '獲勝隊伍',
    '獲勝方式'
]);

// 獲取所有已完成的比賽
$group_id = $_GET['group_id'] ?? null;
$query = "SELECT 
    m.match_number,
    m.team1_score,
    m.team2_score,
    m.team1_fouls,
    m.team2_fouls,
    m.created_at,
    m.winner_team_id,
    m.win_method,
    t1.team_name as team1_name,
    t2.team_name as team2_name,
    winner.team_name as winner_name,
    g.group_name
    FROM matches m
    LEFT JOIN teams t1 ON m.team1_id = t1.team_id
    LEFT JOIN teams t2 ON m.team2_id = t2.team_id
    LEFT JOIN teams winner ON m.winner_team_id = winner.team_id
    LEFT JOIN team_groups g ON m.group_id = g.group_id
    WHERE m.match_status = 'completed'";
    
if ($group_id) {
    $query .= " AND m.group_id = ?";
    $params = [$group_id];
} else {
    $params = [];
}

$query .= " ORDER BY g.group_name, m.match_number";

$stmt = $pdo->prepare($query);
$stmt->execute($params);

// 寫入比賽數據
while ($match = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // 判斷獲勝隊伍
    $winner = '';
    if ($match['winner_team_id']) {
        $winner = $match['winner_name'];
    } elseif ($match['team1_score'] > $match['team2_score']) {
        $winner = $match['team1_name'];
    } elseif ($match['team2_score'] > $match['team1_score']) {
        $winner = $match['team2_name'];
    } elseif ($match['team1_fouls'] < $match['team2_fouls']) {
        $winner = $match['team1_name'];
    } elseif ($match['team2_fouls'] < $match['team1_fouls']) {
        $winner = $match['team2_name'];
    }

    $row = [
        date('Y-m-d H:i', strtotime($match['created_at'])),
        $match['group_name'] ? $match['group_name'] . '組' : '無分組',
        $match['match_number'],
        $match['team1_name'],
        $match['team2_name'],
        '"' . $match['team1_score'] . ' - ' . $match['team2_score'] . '"',
        $match['team1_fouls'],
        $match['team2_fouls'],
        $winner,
        $match['win_method'] ?? ($winner ? 'normal' : '') // 如果沒有記錄獲勝方式但有勝者，默認為 normal
    ];
    fputcsv($output, $row);
}

// 關閉輸出流
fclose($output);
exit;