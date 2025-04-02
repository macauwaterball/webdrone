<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

// 獲取所有隊伍
$stmt = $pdo->query("SELECT * FROM teams");
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 獲取所有小組
$stmt = $pdo->query("SELECT * FROM team_groups");
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 獲取錯誤信息（如果有）
$error = $_GET['error'] ?? '';

// 獲取選定小組的現有場次
$selected_group_id = $_POST['group_id'] ?? $_GET['group_id'] ?? null;
$existing_matches = [];
if ($selected_group_id) {
    $stmt = $pdo->prepare("
        SELECT m.match_number, t1.team_name as team1_name, t2.team_name as team2_name
        FROM matches m
        JOIN teams t1 ON m.team1_id = t1.team_id
        JOIN teams t2 ON m.team2_id = t2.team_id
        WHERE m.group_id = ?
        ORDER BY m.match_number");
    $stmt->execute([$selected_group_id]);
    $existing_matches = $stmt->fetchAll();
}

// 保存之前選擇的值
$selected_team1 = $_POST['team1_id'] ?? '';
$selected_team2 = $_POST['team2_id'] ?? '';
$selected_match_number = $_POST['match_number'] ?? '';

// 如果是 AJAX 請求，返回 JSON 響應
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    switch ($_POST['action']) {
        case 'create_team':
            // ... 創建隊伍的代碼 ...
            $response['success'] = true;
            $response['message'] = "隊伍創建成功！";
            break;
            
        case 'update_team':
            // ... 更新隊伍名稱的代碼 ...
            $response['success'] = true;
            $response['message'] = "隊伍名稱更新成功！";
            break;
            
        case 'add_player':
            // ... 添加運動員的代碼 ...
            $response['success'] = true;
            $response['message'] = "運動員添加成功！";
            break;
            
        case 'remove_player':
            // ... 移除運動員的代碼 ...
            $response['success'] = true;
            $response['message'] = "運動員已移除！";
            break;
    }
    
    // 返回 JSON 響應
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>創建新比賽 - 無人機足球計分系統</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .existing-matches {
            margin: 20px 0;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .existing-matches h3 {
            margin-top: 0;
        }
        .match-info {
            margin: 5px 0;
            color: #666;
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
            <h2>創建新比賽</h2>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (empty($teams)): ?>
                <div class="error-message">請先創建隊伍才能創建比賽</div>
            <?php else: ?>
                <form method="POST" class="create-form" id="matchForm" action="process_create_match.php">
                    <div class="form-group">
                        <label for="group_id">小組：</label>
                        <select name="group_id" id="group_id">
                            <option value="">無小組</option>
                            <?php foreach($groups as $group): ?>
                                <option value="<?= $group['group_id'] ?>" 
                                    <?= ($selected_group_id == $group['group_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($group['group_name']) ?>組
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if (!empty($existing_matches)): ?>
                    <div class="existing-matches">
                        <h3>當前小組已有場次：</h3>
                        <?php foreach($existing_matches as $match): ?>
                            <div class="match-info">
                                第<?= htmlspecialchars($match['match_number']) ?>場：
                                <?= htmlspecialchars($match['team1_name']) ?> vs 
                                <?= htmlspecialchars($match['team2_name']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="match_number">比賽場次：</label>
                        <input type="number" id="match_number" name="match_number" required min="1" 
                               value="<?= htmlspecialchars($selected_match_number) ?>">
                    </div>

                    <div class="form-group">
                        <label for="team1_id">隊伍1：</label>
                        <select name="team1_id" id="team1_id" required>
                            <option value="">請選擇隊伍</option>
                            <?php foreach($teams as $team): ?>
                                <option value="<?= $team['team_id'] ?>" 
                                    <?= ($selected_team1 == $team['team_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($team['team_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="team2_id">隊伍2：</label>
                        <select name="team2_id" id="team2_id" required>
                            <option value="">請選擇隊伍</option>
                            <?php foreach($teams as $team): ?>
                                <option value="<?= $team['team_id'] ?>"
                                    <?= ($selected_team2 == $team['team_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($team['team_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="button" id="checkButton">檢查場次</button>
                        <button type="submit" id="submitButton" style="display: none;">創建比賽</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // 防止選擇相同的隊伍
    document.getElementById('team2_id').addEventListener('change', function() {
        const team1 = document.getElementById('team1_id').value;
        const team2 = this.value;
        
        if (team1 && team2 && team1 === team2) {
            alert('請選擇不同的隊伍！');
            this.value = '';
        }
    });

    document.getElementById('team1_id').addEventListener('change', function() {
        const team1 = this.value;
        const team2 = document.getElementById('team2_id').value;
        
        if (team1 && team2 && team1 === team2) {
            alert('請選擇不同的隊伍！');
            this.value = '';
        }
    });

    // 檢查場次按鈕處理
    document.getElementById('checkButton').addEventListener('click', function() {
        const form = document.getElementById('matchForm');
        const formData = new FormData(form);
        
        // 使用 AJAX 檢查場次
        fetch('check_match.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('submitButton').style.display = 'block';
                this.style.display = 'none';
                alert('場次可用，請點擊創建比賽按鈕完成創建。');
            } else {
                alert(data.message || '該場次已存在，請選擇其他場次。');
            }
        });
    });

    // 小組選擇變更時更新顯示
    document.getElementById('group_id').addEventListener('change', function() {
        if (this.value) {
            const form = document.getElementById('matchForm');
            form.submit();
        }
    });

    function showTeamPlayers(teamId, teamName) {  // 添加 teamName 參數
        // 高亮顯示選中的隊伍
        document.querySelectorAll('.team-item').forEach(item => {
            item.classList.remove('active');
        });
        event.currentTarget.classList.add('active');

        // 更新標題
        document.getElementById('team-players-title').textContent = `${teamName} 隊伍運動員`;

        loadTeamPlayers(teamId);
    }

    function loadTeamPlayers(teamId) {
        fetch(`get_team_players.php?team_id=${teamId}`)
            .then(response => response.json())
            .then(data => {
                updatePlayersList(data.players);
                updateTeamPlayerCount(teamId, data.players.length);
                
                // 顯示添加運動員表單
                document.getElementById('add-player-form').style.display = 'block';
                document.getElementById('player-team-id').value = teamId;
            });
    }

    function updatePlayersList(players) {
        const container = document.getElementById('players-list');
        if (players.length === 0) {
            container.innerHTML = '<p>該隊伍還沒有運動員</p>';
        } else {
            container.innerHTML = players.map(player => `
                <div class="player-item" data-player-id="${player.player_id}">
                    <span>${player.player_name} ${player.jersey_number ? `(${player.jersey_number}號)` : ''}</span>
                    <button class="remove-btn" onclick="removePlayer(${player.player_id})">移除</button>
                </div>
            `).join('');
        }
    }

    function updateTeamPlayerCount(teamId, count) {
        const teamItem = document.querySelector(`.team-item[data-team-id="${teamId}"]`);
        if (teamItem) {
            const countSpan = teamItem.querySelector('.player-count');
            if (countSpan) {
                countSpan.textContent = `(${count}/8 名運動員)`;
            }
        }
    }

    function removePlayer(playerId) {
        if (confirm('確定要移除該運動員嗎？')) {
            const form = new FormData();
            form.append('action', 'remove_player');
            form.append('player_id', playerId);

            fetch('create_team.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: form
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const teamId = document.getElementById('player-team-id').value;
                    loadTeamPlayers(teamId); // 這會同時更新列表和計數
                } else {
                    alert(data.message || '移除失敗');
                }
            });
        }
    }

    function addPlayer(form) {
        const formData = new FormData(form);
        fetch('create_team.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const teamId = document.getElementById('player-team-id').value;
                loadTeamPlayers(teamId); // 這會同時更新列表和計數
                form.reset();
            } else {
                alert(data.message || '添加失敗');
            }
        });
        return false;
    }
    </script>
</body>
</html> 