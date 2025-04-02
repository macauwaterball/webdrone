<?php
session_start();
require_once 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: list_matches.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND password = ?");
            $stmt->execute([$username, $password]);
            $admin = $stmt->fetch();

            if ($admin) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['admin_id'];
                header('Location: list_matches.php');
                exit;
            }
            $error = '用戶名或密碼錯誤';
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error = '數據庫錯誤：' . $e->getMessage();
        }
    } else {
        $error = '請輸入用戶名和密碼';
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>登入 - 無人機足球計分系統</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .login-title {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1 class="login-title">無人機足球計分系統</h1>
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="username">用戶名：</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">密碼：</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">登入</button>
        </form>
    </div>
</body>
</html>