<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

// 獲取所有比賽，按組別和場次排序
$stmt = $pdo->query("SELECT m.*, 
                            t1.team_name as team1_name, 
                            t2.team_name as team2_name,
                            g.group_name
                     FROM matches m
                     JOIN teams t1 ON m.team1_id = t1.team_id
                     JOIN teams t2 ON m.team2_id = t2.team_id
                     LEFT JOIN team_groups g ON m.group_id = g.group_id
                     ORDER BY g.group_name ASC, m.match_number DESC");
$matches = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>比賽列表 - 無人機足球計分系統</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .group-name {
            font-weight: bold;
            color: #666;
            padding-right: 10px;
        }
        .match-info {
            display: flex;
            align-items: center;
        }
        .group-row td {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: left;
            padding: 10px;
        }
        .match-list tr.group-matches td {
            border-left: 3px solid #ddd;
        }
        .match-list tr.group-matches.active td {
            border-left: 3px solid #4CAF50;
        }
        .match-status {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        .status-pending {
            background-color: #ffd700;
            color: #000;
        }
        .status-active {
            background-color: #4CAF50;
            color: white;
        }
        .status-completed {
            background-color: #808080;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>無人機足球計分系統</h1>
        <nav class="navigation">
            <a href="create_team.php" class="nav-link">創建隊伍</a>
            <a href="create_match.php" class="nav-link">創建比賽</a>
            <a href="create_group_matches.php" class="nav-link">創建小組循環賽</a>
            <a href="list_matches.php" class="nav-link">比賽列表</a>
            <a href="create_group.php" class="nav-link">創建小組</a>
        </nav>

        <div class="list-section">
            <h2>比賽列表</h2>
            <table class="match-list">
                <thead>
                    <tr>
                        <th>組別</th>
                        <th>場次</th>
                        <th>隊伍1</th>
                        <th>隊伍2</th>
                        <th>比分</th>
                        <th>狀態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $current_group = null;
                    foreach ($matches as $match): 
                        // 當組別改變時，添加組別標題行
                        if ($match['group_name'] !== $current_group):
                            if ($match['group_name']):
                    ?>
                        <tr class="group-row">
                            <td colspan="7"><?= htmlspecialchars($match['group_name']) ?>組</td>
                        </tr>
                    <?php 
                            endif;
                            $current_group = $match['group_name'];
                        endif;
                    ?>
                    <tr class="group-matches <?= $match['match_status'] === 'active' ? 'active' : '' ?>">
                        <td>
                            <?php if ($match['group_name']): ?>
                                <?= htmlspecialchars($match['group_name']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($match['match_number']) ?></td>
                        <td><?= htmlspecialchars($match['team1_name']) ?></td>
                        <td><?= htmlspecialchars($match['team2_name']) ?></td>
                        <td><?= $match['team1_score'] ?> - <?= $match['team2_score'] ?></td>
                        <td>
                            <span class="match-status status-<?= $match['match_status'] ?>">
                                <?= htmlspecialchars($match['match_status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($match['match_status'] === 'pending'): ?>
                                <a href="dronesoccer.php?match_id=<?= $match['match_id'] ?>" class="button">進入比賽</a>
                            <?php else: ?>
                                <a href="match_result.php?match_id=<?= $match['match_id'] ?>" class="button result-button">顯示結果</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 