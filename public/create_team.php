<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_team':
                $team_name = trim($_POST['team_name']);
                if (!empty($team_name)) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO teams (team_name) VALUES (?)");
                        $stmt->execute([$team_name]);
                        $response['success'] = true;
                        $response['message'] = "隊伍 '$team_name' 創建成功！";
                    } catch (PDOException $e) {
                        $response['message'] = "創建失敗: " . $e->getMessage();
                    }
                }
                break;

            case 'update_team':
                $team_id = $_POST['team_id'];
                $team_name = trim($_POST['team_name']);
                try {
                    $stmt = $pdo->prepare("UPDATE teams SET team_name = ? WHERE team_id = ?");
                    $stmt->execute([$team_name, $team_id]);
                    $response['success'] = true;
                    $response['message'] = "隊伍名稱更新成功！";
                } catch (PDOException $e) {
                    $response['message'] = "更新失敗: " . $e->getMessage();
                }
                break;

            case 'add_player':
                $team_id = $_POST['team_id'];
                $player_name = trim($_POST['player_name']);
                $jersey_number = trim($_POST['jersey_number']);
                
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM players WHERE team_id = ?");
                $stmt->execute([$team_id]);
                if ($stmt->fetchColumn() >= 8) {
                    $response['message'] = "該隊伍已達到最大運動員數量（8人）";
                } else {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO players (team_id, player_name, jersey_number) VALUES (?, ?, ?)");
                        $stmt->execute([$team_id, $player_name, $jersey_number]);
                        $response['success'] = true;
                        $response['message'] = "運動員添加成功！";
                    } catch (PDOException $e) {
                        $response['message'] = "添加失敗: " . $e->getMessage();
                    }
                }
                break;

            case 'remove_player':
                $player_id = $_POST['player_id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM players WHERE player_id = ?");
                    $stmt->execute([$player_id]);
                    $response['success'] = true;
                    $response['message'] = "運動員已移除！";
                } catch (PDOException $e) {
                    $response['message'] = "移除失敗: " . $e->getMessage();
                }
                break;

            case 'delete_team':
                $team_id = $_POST['team_id'];
                try {
                    // 檢查隊伍是否參與過比賽
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM matches 
                        WHERE team1_id = ? OR team2_id = ?
                    ");
                    $stmt->execute([$team_id, $team_id]);
                    if ($stmt->fetchColumn() > 0) {
                        $response['success'] = false;
                        $response['message'] = "無法刪除：該隊伍已參與比賽";
                    } else {
                        // 先刪除該隊伍的所有運動員
                        $stmt = $pdo->prepare("DELETE FROM players WHERE team_id = ?");
                        $stmt->execute([$team_id]);
                        
                        // 再刪除隊伍
                        $stmt = $pdo->prepare("DELETE FROM teams WHERE team_id = ?");
                        $stmt->execute([$team_id]);
                        
                        $response['success'] = true;
                        $response['message'] = "隊伍已成功移除";
                    }
                } catch (PDOException $e) {
                    $response['success'] = false;
                    $response['message'] = "刪除失敗: " . $e->getMessage();
                }
                break;
        }
    }
    
    // 如果是 AJAX 請求，返回 JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// 獲取所有隊伍及其運動員
$teams = $pdo->query("SELECT * FROM teams ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>隊伍管理 - 無人機足球計分系統</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .team-management {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .teams-list {
            flex: 1;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .players-list {
            flex: 2;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .team-item {
            padding: 10px;
            margin: 5px 0;
            background: white;
            border-radius: 3px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .team-item.active {
            background: #e3f2fd;
        }
        .team-name {
            font-size: 16px;
            font-weight: 500;
        }
        .edit-btn {
            background: #2196F3;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .edit-btn:hover {
            background: #1976D2;
        }
        .player-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            background: white;
            border-radius: 3px;
        }
        .remove-btn {
            background: #ff4444;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .remove-btn:hover {
            background: #cc0000;
        }
        .add-form {
            margin-top: 20px;
            padding: 15px;
            background: white;
            border-radius: 5px;
        }
        .form-section {
            max-width: 500px;
            margin: 20px auto;
        }
        .player-count {
            color: #666;
            font-size: 14px;
            margin-left: 10px;
        }
        .team-actions {
            display: flex;
            gap: 10px;
        }
        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .delete-btn:hover {
            background: #c82333;
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

        <?php if (isset($message)): ?>
            <div class="success-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- 創建新隊伍表單 -->
        <div class="form-section">
            <h2>創建新隊伍</h2>
            <form method="POST" class="create-form">
                <input type="hidden" name="action" value="create_team">
                <div class="form-group">
                    <label for="team_name">隊伍名稱：</label>
                    <input type="text" id="team_name" name="team_name" required>
                </div>
                <button type="submit">創建隊伍</button>
            </form>
        </div>

        <!-- 隊伍管理區域 -->
        <div class="team-management">
            <!-- 左側隊伍列表 -->
            <div class="teams-list">
                <h3>現有隊伍</h3>
                <?php foreach ($teams as $team): 
                    // 獲取隊伍的運動員數量
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM players WHERE team_id = ?");
                    $stmt->execute([$team['team_id']]);
                    $playerCount = $stmt->fetchColumn();
                ?>
                    <div class="team-item" data-team-id="<?= $team['team_id'] ?>" onclick="showTeamPlayers(<?= $team['team_id'] ?>, '<?= htmlspecialchars($team['team_name']) ?>')">
                        <div class="team-info">
                            <span class="team-name"><?= htmlspecialchars($team['team_name']) ?></span>
                            <span class="player-count">(<?= $playerCount ?>/8 名運動員)</span>
                        </div>
                        <div class="team-actions">
                            <button class="edit-btn" onclick="event.stopPropagation(); editTeamName(<?= $team['team_id'] ?>, '<?= htmlspecialchars($team['team_name']) ?>')">
                                編輯名稱
                            </button>
                            <button class="delete-btn" onclick="event.stopPropagation(); deleteTeam(<?= $team['team_id'] ?>, '<?= htmlspecialchars($team['team_name']) ?>')">
                                移除隊伍
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 右側運動員列表和添加表單 -->
            <div class="players-list" id="players-container">
                <h3>隊伍運動員</h3>
                <div id="players-list">
                    <!-- 運動員列表將通過 AJAX 載入 -->
                    <p>請選擇左側的隊伍來查看和管理運動員</p>
                </div>
                <div class="add-form" id="add-player-form" style="display: none;">
                    <h4>添加運動員</h4>
                    <form method="POST" onsubmit="return addPlayer(this);">
                        <input type="hidden" name="action" value="add_player">
                        <input type="hidden" name="team_id" id="player-team-id">
                        <div class="form-group">
                            <label>姓名：</label>
                            <input type="text" name="player_name" required>
                        </div>
                        <div class="form-group">
                            <label>球衣號碼：</label>
                            <input type="text" name="jersey_number">
                        </div>
                        <button type="submit">添加</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showTeamPlayers(teamId) {
        // 高亮顯示選中的隊伍
        document.querySelectorAll('.team-item').forEach(item => {
            item.classList.remove('active');
        });
        event.currentTarget.classList.add('active');

        // 通過 AJAX 載入隊伍運動員
        fetch(`get_team_players.php?team_id=${teamId}`)
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('players-list');
                if (data.players.length === 0) {
                    container.innerHTML = '<p>該隊伍還沒有運動員</p>';
                } else {
                    container.innerHTML = data.players.map(player => `
                        <div class="player-item">
                            <span>${player.player_name} ${player.jersey_number ? `(${player.jersey_number}號)` : ''}</span>
                            <button class="remove-btn" onclick="removePlayer(${player.player_id})">移除</button>
                        </div>
                    `).join('');
                }

                // 顯示添加運動員表單
                document.getElementById('add-player-form').style.display = 'block';
                document.getElementById('player-team-id').value = teamId;
            });
    }

    function editTeamName(teamId, currentName) {
        const newName = prompt('請輸入新的隊伍名稱：', currentName);
        if (newName && newName !== currentName) {
            const form = new FormData();
            form.append('action', 'update_team');
            form.append('team_id', teamId);
            form.append('team_name', newName);

            fetch('create_team.php', {
                method: 'POST',
                body: form
            }).then(() => location.reload());
        }
    }

    function removePlayer(playerId) {
        if (confirm('確定要移除該運動員嗎？')) {
            const form = new FormData();
            form.append('action', 'remove_player');
            form.append('player_id', playerId);

            fetch('create_team.php', {
                method: 'POST',
                body: form
            }).then(() => {
                // 重新載入當前隊伍的運動員列表
                const teamId = document.getElementById('player-team-id').value;
                showTeamPlayers(teamId);
            });
        }
    }

    function addPlayer(form) {
        const formData = new FormData(form);
        fetch('create_team.php', {
            method: 'POST',
            body: formData
        }).then(() => {
            // 重新載入當前隊伍的運動員列表
            const teamId = document.getElementById('player-team-id').value;
            showTeamPlayers(teamId);
            form.reset();
        });
        return false;
    }
    function deleteTeam(teamId, teamName) {
        if (confirm(`確定要刪除隊伍 "${teamName}" 嗎？`)) {
            const form = new FormData();
            form.append('action', 'delete_team');
            form.append('team_id', teamId);
    
            fetch('create_team.php', {
                method: 'POST',
                body: form
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    // 顯示更詳細的錯誤信息
                    if (data.message.includes("該隊伍已參與比賽")) {
                        alert("無法刪除：該隊伍已參與比賽。\n請先取消或完成該隊伍的所有比賽後再試。");
                    } else {
                        alert(data.message);
                    }
                }
            });
        }
    }
    </script>
</body>
</html>