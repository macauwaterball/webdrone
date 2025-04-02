<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_name = trim($_POST['group_name']);
    if (!empty($group_name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO team_groups (group_name) VALUES (?)");
            $stmt->execute([$group_name]);
            $message = "小組 '$group_name' 創建成功！";
        } catch (PDOException $e) {
            $error = "創建失敗: " . $e->getMessage();
        }
    }
}

// 獲取所有小組
$stmt = $pdo->query("SELECT * FROM team_groups ORDER BY created_at DESC");
$groups = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>創建小組 - 無人機足球計分系統</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>無人機足球計分系統</h1>
        <nav class="navigation">
            <a href="create_team.php" class="nav-link">創建隊伍</a>
            <a href="create_match.php" class="nav-link">創建比賽</a>
            <a href="list_matches.php" class="nav-link">比賽列表</a>
            <a href="create_group.php" class="nav-link">創建小組</a>
        </nav>

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
                        <th>創建時間</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group): ?>
                    <tr>
                        <td><?= htmlspecialchars($group['group_id']) ?></td>
                        <td><?= htmlspecialchars($group['group_name']) ?></td>
                        <td><?= htmlspecialchars($group['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>