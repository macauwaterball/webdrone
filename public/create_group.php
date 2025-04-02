<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

// 獲取所有未分組的隊伍
$stmt = $pdo->query("SELECT * FROM teams WHERE group_id IS NULL ORDER BY team_name");
$available_teams = $stmt->fetchAll();

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_name = trim($_POST['group_name']);
    $selected_teams = $_POST['teams'] ?? [];

    if (!empty($group_name) && !empty($selected_teams)) {
        try {
            $pdo->beginTransaction();

            // 創建小組
            $stmt = $pdo->prepare("INSERT INTO team_groups (group_name) VALUES (?)");
            $stmt->execute([$group_name]);
            $group_id = $pdo->lastInsertId();

            // 更新選中隊伍的小組ID
            $stmt = $pdo->prepare("UPDATE teams SET group_id = ? WHERE team_id = ?");
            foreach ($selected_teams as $team_id) {
                $stmt->execute([$group_id, $team_id]);
            }

            $pdo->commit();
            $message = "小組 '$group_name' 創建成功！";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "創建失敗: " . $e->getMessage();
        }
    } else {
        $error = "請填寫小組名稱並選擇至少一個隊伍";
    }
}

// 獲取所有小組及其隊伍
$groups = [];
$stmt = $pdo->query("
    SELECT g.*, GROUP_CONCAT(t.team_name) as team_names
    FROM team_groups g
    LEFT JOIN teams t ON g.group_id = t.group_id
    GROUP BY g.group_id
    ORDER BY g.created_at DESC
");
$groups = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>創建小組 - 無人機足球計分系統</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .team-selection {
            margin: 10px 0;
            max-height: 200px;
            overflow-y: auto;
        }
        .team-checkbox {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 導航欄保持不變 -->

        <div class="form-section">
            <h2>創建新小組</h2>
            <?php if (isset($message)): ?>
                <div class="success-message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="create-form">
                <div class="form-group">
                    <label for="group_name">小組名稱（A~H）：</label>
                    <input type="text" id="group_name" name="group_name" required maxlength="1" pattern="[A-H]">
                </div>
                
                <div class="form-group">
                    <label>選擇隊伍：</label>
                    <div class="team-selection">
                        <?php foreach ($available_teams as $team): ?>
                        <div class="team-checkbox">
                            <input type="checkbox" name="teams[]" value="<?= htmlspecialchars($team['team_id']) ?>" 
                                   id="team_<?= htmlspecialchars($team['team_id']) ?>">
                            <label for="team_<?= htmlspecialchars($team['team_id']) ?>">
                                <?= htmlspecialchars($team['team_name']) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit">創建小組</button>
            </form>
        </div>

        <div class="list-section">
            <h2>現有小組</h2>
            <table class="group-list">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>小組名稱</th>
                        <th>隊伍</th>
                        <th>創建時間</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group): ?>
                    <tr>
                        <td><?= htmlspecialchars($group['group_id']) ?></td>
                        <td><?= htmlspecialchars($group['group_name']) ?></td>
                        <td><?= htmlspecialchars($group['team_names'] ?? '無') ?></td>
                        <td><?= htmlspecialchars($group['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>