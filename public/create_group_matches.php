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

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = $_POST['group_id'] ?? '';
    
    if (!empty($group_id)) {
        try {
            // 獲取該小組的所有隊伍
            $stmt = $pdo->prepare("SELECT * FROM teams WHERE group_id = ?");
            $stmt->execute([$group_id]);
            $teams = $stmt->fetchAll();
            
            // 生成循環賽配對
            for ($i = 0; $i < count($teams); $i++) {
                for ($j = $i + 1; $j < count($teams); $j++) {
                    $stmt = $pdo->prepare("INSERT INTO matches (team1_id, team2_id, match_status) VALUES (?, ?, 'pending')");
                    $stmt->execute([$teams[$i]['team_id'], $teams[$j]['team_id']]);
                }
            }
            
            $message = "小組循環賽創建成功！";
        } catch (PDOException $e) {
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
            <h2>創建小組循環賽</h2>
            <?php if (isset($message)): ?>
                <div class="success-message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="create-form">
                <div class="form-group">
                    <label for="group_id">選擇小組：</label>
                    <select id="group_id" name="group_id" required>
                        <option value="">請選擇小組</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= htmlspecialchars($group['group_id']) ?>">
                                <?= htmlspecialchars($group['group_name']) ?>組
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">創建循環賽</button>
            </form>
        </div>
    </div>
</body>
</html>