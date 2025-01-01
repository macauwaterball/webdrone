<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

// 獲取所有小組
$stmt = $pdo->query("SELECT * FROM team_groups ORDER BY group_name");
$groups = $stmt->fetchAll();

// 獲取所有隊伍
$stmt = $pdo->query("SELECT * FROM teams ORDER BY team_name");
$teams = $stmt->fetchAll();

// 獲取選定小組的比賽信息
$selected_group_id = null;

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_teams') {
        // 處理更新小組隊伍
        $group_id = $_POST['group_id'];
        $selected_group_id = $group_id; // 設置選中的小組
        $selected_teams = $_POST['teams'] ?? [];
        
        try {
            // 刪除該小組所有未開始的比賽
            $stmt = $pdo->prepare("DELETE FROM matches WHERE group_id = ? AND match_status = 'pending'");
            $stmt->execute([$group_id]);
            
            // 重新生成循環賽程
            if (count($selected_teams) >= 2) {
                $matches = generateRoundRobinMatches($selected_teams);
                $stmt = $pdo->prepare("INSERT INTO matches (match_number, team1_id, team2_id, group_id) VALUES (?, ?, ?, ?)");
                
                foreach ($matches as $index => $match) {
                    $match_number = $index + 1;
                    $stmt->execute([$match_number, $match[0], $match[1], $group_id]);
                }
                $message = "成功更新小組賽程！";
            }
        } catch (PDOException $e) {
            $error = "更新失敗: " . $e->getMessage();
        }
    } else if (isset($_POST['group_id'])) {
        // 處理選擇小組的情況
        $selected_group_id = $_POST['group_id'];
    }
} else if (isset($_GET['group_id'])) {
    $selected_group_id = $_GET['group_id'];
}

$group_matches = [];
$group_teams = [];

if ($selected_group_id) {
    // 獲取該小組的所有比賽
    $stmt = $pdo->prepare("
        SELECT m.*, 
               t1.team_name as team1_name, 
               t2.team_name as team2_name,
               g.group_name
        FROM matches m
        JOIN teams t1 ON m.team1_id = t1.team_id
        JOIN teams t2 ON m.team2_id = t2.team_id
        JOIN team_groups g ON m.group_id = g.group_id
        WHERE m.group_id = ?
        ORDER BY m.match_number");
    $stmt->execute([$selected_group_id]);
    $group_matches = $stmt->fetchAll();

    // 獲取該小組的所有隊伍（根據已創建的比賽）
    $stmt = $pdo->prepare("
        SELECT DISTINCT t.team_id, t.team_name
        FROM matches m
        JOIN teams t ON t.team_id IN (m.team1_id, m.team2_id)
        WHERE m.group_id = ?");
    $stmt->execute([$selected_group_id]);
    $group_teams = $stmt->fetchAll();
}

// 生成循環賽程的函數
function generateRoundRobinMatches($teams) {
    $num_teams = count($teams);
    $matches = [];
    
    // 如果是奇數隊伍，添加一個輪空隊
    if ($num_teams % 2 != 0) {
        $teams[] = null;
        $num_teams++;
    }
    
    // 生成循環賽程
    for ($round = 0; $round < $num_teams - 1; $round++) {
        for ($i = 0; $i < $num_teams / 2; $i++) {
            $team1 = $teams[$i];
            $team2 = $teams[$num_teams - 1 - $i];
            
            // 跳過包含輪空隊的比賽
            if ($team1 !== null && $team2 !== null) {
                // 交替主客場
                if ($round % 2 == 0) {
                    $matches[] = [$team1, $team2];
                } else {
                    $matches[] = [$team2, $team1];
                }
            }
        }
        
        // 輪轉隊伍順序，保持第一支隊伍不變
        $last = array_pop($teams);
        array_splice($teams, 1, 0, [$last]);
    }
    
    return $matches;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>創建小組循環賽 - 無人機足球計分系統</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .teams-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        .team-checkbox {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .group-info {
            margin-top: 30px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .matches-list {
            margin-top: 20px;
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

        <div class="form-section">
            <h2>創建小組循環賽</h2>
            
            <?php if (isset($message)): ?>
                <div class="success-message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- 小組選擇表單 -->
            <form method="GET" id="selectGroupForm">
                <div class="form-group">
                    <label for="group_id">選擇小組：</label>
                    <select name="group_id" id="group_id" required>
                        <option value="">請選擇小組</option>
                        <?php foreach($groups as $group): ?>
                            <option value="<?= $group['group_id'] ?>" 
                                <?= ($selected_group_id == $group['group_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($group['group_name']) ?>組
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <?php if ($selected_group_id): ?>
                <div class="group-info">
                    <?php if (!empty($group_matches)): ?>
                        <h3><?= htmlspecialchars($group_matches[0]['group_name']) ?>組 - 現有比賽</h3>
                        <div class="matches-list">
                            <table class="match-list">
                                <thead>
                                    <tr>
                                        <th>場次</th>
                                        <th>隊伍1</th>
                                        <th>隊伍2</th>
                                        <th>狀態</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($group_matches as $match): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($match['match_number']) ?></td>
                                        <td><?= htmlspecialchars($match['team1_name']) ?></td>
                                        <td><?= htmlspecialchars($match['team2_name']) ?></td>
                                        <td><?= htmlspecialchars($match['match_status']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <!-- 隊伍選擇表單 -->
                    <h3><?= empty($group_matches) ? '選擇參賽隊伍' : '修改小組隊伍' ?></h3>
                    <form method="POST" class="update-form" id="teamsForm">
                        <input type="hidden" name="action" value="update_teams">
                        <input type="hidden" name="group_id" value="<?= $selected_group_id ?>">
                        <div class="teams-container">
                            <?php foreach($teams as $team): ?>
                                <div class="team-checkbox">
                                    <input type="checkbox" 
                                           name="teams[]" 
                                           value="<?= $team['team_id'] ?>" 
                                           id="team_<?= $team['team_id'] ?>"
                                           <?= in_array($team['team_id'], array_column($group_teams, 'team_id')) ? 'checked' : '' ?>>
                                    <label for="team_<?= $team['team_id'] ?>">
                                        <?= htmlspecialchars($team['team_name']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit">
                            <?= empty($group_matches) ? '生成循環賽程' : '更新小組隊伍' ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // 小組選擇變更時自動提交
    document.getElementById('group_id').addEventListener('change', function() {
        if (this.value) {
            document.getElementById('selectGroupForm').submit();
        }
    });

    // 檢查隊伍選擇
    document.getElementById('teamsForm')?.addEventListener('submit', function(e) {
        const checkedTeams = this.querySelectorAll('input[name="teams[]"]:checked');
        if (checkedTeams.length < 2) {
            e.preventDefault();
            alert('請至少選擇兩支隊伍！');
        }
    });
    </script>
</body>
</html> 