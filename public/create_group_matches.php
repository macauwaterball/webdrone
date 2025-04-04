<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

// 獲取所有小組
$stmt = $pdo->query("SELECT * FROM team_groups ORDER BY group_name");
$groups = $stmt->fetchAll();

// 獲取選定小組的隊伍
$selected_group_id = $_GET['group_id'] ?? '';
$group_teams = [];
$available_teams = [];

if (!empty($selected_group_id)) {
    // 獲取該小組現有的隊伍
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE group_id = ?");
    $stmt->execute([$selected_group_id]);
    $group_teams = $stmt->fetchAll();

    // 獲取所有未分組的隊伍
    $stmt = $pdo->query("SELECT * FROM teams WHERE group_id IS NULL");
    $available_teams = $stmt->fetchAll();
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = $_POST['group_id'] ?? '';
    $selected_teams = $_POST['teams'] ?? [];
    
    if (!empty($group_id)) {
        try {
            $pdo->beginTransaction();

            // 先清除該小組所有隊伍的關聯
            $stmt = $pdo->prepare("UPDATE teams SET group_id = NULL WHERE group_id = ?");
            $stmt->execute([$group_id]);

            // 更新選中隊伍的小組ID
            if (!empty($selected_teams)) {
                $stmt = $pdo->prepare("UPDATE teams SET group_id = ? WHERE team_id = ?");
                foreach ($selected_teams as $team_id) {
                    $stmt->execute([$group_id, $team_id]);
                }
            }

            // 生成循環賽配對
            if (count($selected_teams) > 1) {
                // 刪除該小組現有的比賽
                $stmt = $pdo->prepare("DELETE FROM matches WHERE group_id = ?");
                $stmt->execute([$group_id]);

                // 創建新的比賽
                $match_number = 1;
                $matches = []; // 存儲所有可能的比賽組合
                $schedule = []; // 最終的比賽安排

                // 生成所有可能的比賽組合
                for ($i = 0; $i < count($selected_teams); $i++) {
                    for ($j = $i + 1; $j < count($selected_teams); $j++) {
                        $matches[] = [
                            'team1_id' => $selected_teams[$i],
                            'team2_id' => $selected_teams[$j]
                        ];
                    }
                }

                // 重新排序比賽，避免隊伍連續出賽
                $lastTeams = []; // 記錄上一場比賽的隊伍
                while (!empty($matches)) {
                    $found = false;
                    foreach ($matches as $key => $match) {
                        // 檢查這場比賽的隊伍是否剛剛比賽過
                        if (empty($lastTeams) || 
                            (!in_array($match['team1_id'], $lastTeams) && 
                             !in_array($match['team2_id'], $lastTeams))) {
                            // 將這場比賽加入賽程
                            $schedule[] = $match;
                            $lastTeams = [$match['team1_id'], $match['team2_id']];
                            unset($matches[$key]);
                            $found = true;
                            break;
                        }
                    }
                    
                    // 如果找不到合適的比賽（所有隊伍都打過了），重置lastTeams
                    if (!$found && !empty($matches)) {
                        $lastTeams = [];
                    }
                }

                // 將排好的賽程寫入數據庫
                $stmt = $pdo->prepare("INSERT INTO matches (match_number, team1_id, team2_id, match_status, group_id) VALUES (?, ?, ?, 'pending', ?)");
                foreach ($schedule as $index => $match) {
                    $stmt->execute([
                        $index + 1,
                        $match['team1_id'],
                        $match['team2_id'],
                        $group_id
                    ]);
                }
            }

            $pdo->commit();
            $message = "小組循環賽創建成功！";
            
            // 重定向以更新顯示
            header("Location: create_group_matches.php?group_id=" . $group_id);
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "創建失敗: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>創建小組循環賽 - 無人機足球計分系統</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .team-selection {
            margin: 20px 0;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .team-checkbox {
            margin: 10px 0;
        }
        .existing-matches {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>無人機足球計分系統</h1>
        <nav class="navigation">
            <a href="create_team.php" class="nav-link">創建隊伍</a>
            <a href="create_match.php" class="nav-link">創建比賽</a>
            <a href="list_matches.php" class="nav-link">比賽列表</a>
            <a href="create_group.php" class="nav-link">創建小組</a>
            <a href="create_group_matches.php" class="nav-link">創建小組循環賽</a>
        </nav>

        <div class="form-section">
            <h2>創建/修改小組循環賽</h2>
            <?php if (isset($message)): ?>
                <div class="success-message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="GET" class="select-group-form">
                <div class="form-group">
                    <label for="group_id">選擇小組：</label>
                    <select id="group_id" name="group_id" onchange="this.form.submit()">
                        <option value="">請選擇小組</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= htmlspecialchars($group['group_id']) ?>" 
                                    <?= $selected_group_id == $group['group_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($group['group_name']) ?>組
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <?php if (!empty($selected_group_id)): ?>
                <form method="POST" class="create-form">
                    <input type="hidden" name="group_id" value="<?= htmlspecialchars($selected_group_id) ?>">
                    
                    <div class="team-selection">
                        <h3>選擇參賽隊伍</h3>
                        <?php foreach ($available_teams as $team): ?>
                            <div class="team-checkbox">
                                <input type="checkbox" name="teams[]" value="<?= htmlspecialchars($team['team_id']) ?>" 
                                       id="team_<?= htmlspecialchars($team['team_id']) ?>">
                                <label for="team_<?= htmlspecialchars($team['team_id']) ?>">
                                    <?= htmlspecialchars($team['team_name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php foreach ($group_teams as $team): ?>
                            <div class="team-checkbox">
                                <input type="checkbox" name="teams[]" value="<?= htmlspecialchars($team['team_id']) ?>" 
                                       id="team_<?= htmlspecialchars($team['team_id']) ?>" checked>
                                <label for="team_<?= htmlspecialchars($team['team_id']) ?>">
                                    <?= htmlspecialchars($team['team_name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="submit">生成循環賽程</button>
                </form>

                <?php
                // 顯示該小組的現有比賽
                $stmt = $pdo->prepare("
                    SELECT m.*, t1.team_name as team1_name, t2.team_name as team2_name
                    FROM matches m
                    JOIN teams t1 ON m.team1_id = t1.team_id
                    JOIN teams t2 ON m.team2_id = t2.team_id
                    WHERE m.group_id = ?
                    ORDER BY m.match_id
                ");
                $stmt->execute([$selected_group_id]);
                $matches = $stmt->fetchAll();
                
                if (!empty($matches)):
                ?>
                <div class="existing-matches">
                    <h3>現有比賽</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>場次</th>
                                <th>隊伍1</th>
                                <th>隊伍2</th>
                                <th>狀態</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matches as $index => $match): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($match['team1_name']) ?></td>
                                <td><?= htmlspecialchars($match['team2_name']) ?></td>
                                <td><?= htmlspecialchars($match['match_status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>