<?php
require_once 'db.php';

$match_id = $_GET['match_id'] ?? die('未指定比賽ID');

$stmt = $pdo->prepare("SELECT m.*, t1.team_name as team1_name, t2.team_name as team2_name 
                       FROM matches m 
                       JOIN teams t1 ON m.team1_id = t1.team_id 
                       JOIN teams t2 ON m.team2_id = t2.team_id 
                       WHERE match_id = ?");
$stmt->execute([$match_id]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

// 設置CSV檔案標頭
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="match_' . $match_id . '.csv"');

// 創建CSV檔案
$output = fopen('php://output', 'w');

// 寫入UTF-8 BOM
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// 寫入標題行
fputcsv($output, ['比賽場次', '隊伍1', '隊伍2', '隊伍1得分', '隊伍2得分', '隊伍1犯規', '隊伍2犯規']);

// 寫入資料
fputcsv($output, [
    $match['match_number'],
    $match['team1_name'],
    $match['team2_name'],
    $match['team1_score'],
    $match['team2_score'],
    $match['team1_fouls'],
    $match['team2_fouls']
]);

fclose($output); 