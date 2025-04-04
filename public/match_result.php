<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

$match_id = $_GET['match_id'] ?? '';
if (empty($match_id)) {
    header('Location: list_matches.php');
    exit;
}

// 獲取比賽詳細信息
$stmt = $pdo->prepare("
    SELECT m.*, 
           t1.team_name as team1_name, 
           t2.team_name as team2_name,
           g.group_name,
           m.created_at as match_date
    FROM matches m
    LEFT JOIN teams t1 ON m.team1_id = t1.team_id
    LEFT JOIN teams t2 ON m.team2_id = t2.team_id
    LEFT JOIN team_groups g ON m.group_id = g.group_id
    WHERE m.match_id = ?
");
$stmt->execute([$match_id]);
$match = $stmt->fetch();

// 獲取隊伍成員
$stmt = $pdo->prepare("SELECT * FROM players WHERE team_id = ?");
$stmt->execute([$match['team1_id']]);
$team1_players = $stmt->fetchAll();

$stmt->execute([$match['team2_id']]);
$team2_players = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>比賽結果 - 無人機足球計分系統</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            background-color: #f5f5f5;
            font-family: 'Microsoft JhengHei', sans-serif;
        }
        .result-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .match-info {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .match-info h2 {
            font-size: 2.2em;
            color: #333;
            margin-bottom: 20px;
        }
        .match-info p {
            color: #666;
            margin: 5px 0;
            font-size: 1.1em;
        }
        .match-group {
            color: #2196F3;
            font-weight: bold;
            font-size: 1.2em;
            margin: 10px 0;
        }
        .score-display {
            font-size: 2.5em;
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            color: #333;
        }
        .team-details {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            gap: 40px;
        }
        .team-column {
            flex: 1;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            position: relative;
        }
        .team-column h3 {
            color: #2196F3;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        .team-column p {
            margin: 10px 0;
            color: #666;
        }
        .team-column ul {
            list-style: none;
            padding: 0;
            margin: 15px 0;
        }
        .team-column li {
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
            color: #555;
        }
        .team-column li:last-child {
            border-bottom: none;
        }
        .foul-count {
            display: inline-block;
            padding: 5px 15px;
            background: #ff5722;
            color: white;
            border-radius: 15px;
            font-size: 0.9em;
        }
        .button-container {
            text-align: center;
            margin: 30px 0 10px;
        }
        .button, .print-button {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            margin: 0 10px;
        }
        .button {
            background-color: #2196F3;
            color: white;
            border: none;
        }
        .print-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        .button:hover, .print-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        @media print {
            body {
                background: white;
            }
            .no-print {
                display: none;
            }
            .result-container {
                box-shadow: none;
                margin: 0;
                padding: 20px;
            }
            .team-column {
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <div class="result-container">
        <div class="match-info">
            <h2>比賽結果</h2>
            <p>日期：<?= date('Y-m-d', strtotime($match['match_date'])) ?></p>
            <p>時間：<?= date('H:i', strtotime($match['match_date'])) ?></p>
            <?php if ($match['group_name']): ?>
                <div class="match-group">
                    <?= htmlspecialchars($match['group_name']) ?>組 第<?= htmlspecialchars($match['match_number']) ?>場比賽
                </div>
            <?php endif; ?>
        </div>

        <div class="score-display">
            <?= htmlspecialchars($match['team1_name']) ?> 
            <strong><?= $match['team1_score'] ?></strong> 
            - 
            <strong><?= $match['team2_score'] ?></strong> 
            <?= htmlspecialchars($match['team2_name']) ?>
        </div>

        <div class="team-details">
            <div class="team-column">
                <h3><?= htmlspecialchars($match['team1_name']) ?></h3>
                <p>犯規次數：<span class="foul-count"><?= $match['team1_fouls'] ?></span></p>
                <h4>隊員名單</h4>
                <ul>
                    <?php foreach ($team1_players as $player): ?>
                        <li><?= htmlspecialchars($player['player_name']) ?> (<?= htmlspecialchars($player['jersey_number']) ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="team-column">
                <h3><?= htmlspecialchars($match['team2_name']) ?></h3>
                <p>犯規次數：<span class="foul-count"><?= $match['team2_fouls'] ?></span></p>
                <h4>隊員名單</h4>
                <ul>
                    <?php foreach ($team2_players as $player): ?>
                        <li><?= htmlspecialchars($player['player_name']) ?> (<?= htmlspecialchars($player['jersey_number']) ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="button-container no-print">
            <a href="list_matches.php" class="button">返回比賽列表</a>
            <button onclick="window.print()" class="print-button">打印比賽結果</button>
        </div>
    </div>
</body>
</html>