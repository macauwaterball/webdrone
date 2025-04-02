<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

$match_id = $_GET['match_id'] ?? null;
if (!$match_id) {
    die('未指定比賽ID');
}

$stmt = $pdo->prepare("SELECT m.*, 
                              t1.team_name as team1_name, 
                              t2.team_name as team2_name,
                              g.group_name
                       FROM matches m 
                       JOIN teams t1 ON m.team1_id = t1.team_id 
                       JOIN teams t2 ON m.team2_id = t2.team_id 
                       LEFT JOIN team_groups g ON m.group_id = g.group_id
                       WHERE match_id = ?");
$stmt->execute([$match_id]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

if ($match['match_status'] !== 'completed') {
    header('Location: list_matches.php');
    exit;
}

// 獲取兩支隊伍的運動員
$stmt = $pdo->prepare("
    SELECT p.*, t.team_name 
    FROM players p 
    JOIN teams t ON p.team_id = t.team_id 
    WHERE t.team_id IN (?, ?)
    ORDER BY t.team_id, p.jersey_number");
$stmt->execute([$match['team1_id'], $match['team2_id']]);
$players = $stmt->fetchAll();

$team1_players = array_filter($players, function($p) use ($match) {
    return $p['team_id'] == $match['team1_id'];
});
$team2_players = array_filter($players, function($p) use ($match) {
    return $p['team_id'] == $match['team2_id'];
});
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>比賽結果 - 無人機足球計分系統</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .result-container {
            text-align: center;
            margin: 50px auto;
            max-width: 800px;
        }
        .final-score {
            font-size: 48px;
            margin: 30px 0;
        }
        .team-result {
            margin: 20px 0;
            font-size: 24px;
        }
        .back-button {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>比賽結果</h1>
        <div class="result-container">
            <h2>第 <?= $match['match_number'] ?> 場比賽</h2>
            <div class="final-score">
                <?= htmlspecialchars($match['team1_name']) ?> <?= $match['team1_score'] ?> 
                - 
                <?= $match['team2_score'] ?> <?= htmlspecialchars($match['team2_name']) ?>
            </div>
            <div class="team-result">
                <?= htmlspecialchars($match['team1_name']) ?> 犯規次數: <?= $match['team1_fouls'] ?>
            </div>
            <div class="team-result">
                <?= htmlspecialchars($match['team2_name']) ?> 犯規次數: <?= $match['team2_fouls'] ?>
            </div>
            <a href="list_matches.php" class="button back-button">返回比賽列表</a>
        </div>
        <div class="players-info">
            <div class="team-players">
                <h3><?= htmlspecialchars($match['team1_name']) ?> 運動員</h3>
                <ul>
                    <?php foreach ($team1_players as $player): ?>
                        <li>
                            <?= htmlspecialchars($player['player_name']) ?>
                            <?php if ($player['jersey_number']): ?>
                                (<?= htmlspecialchars($player['jersey_number']) ?>號)
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="team-players">
                <h3><?= htmlspecialchars($match['team2_name']) ?> 運動員</h3>
                <ul>
                    <?php foreach ($team2_players as $player): ?>
                        <li>
                            <?= htmlspecialchars($player['player_name']) ?>
                            <?php if ($player['jersey_number']): ?>
                                (<?= htmlspecialchars($player['jersey_number']) ?>號)
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</body>
</html> 