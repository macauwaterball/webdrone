<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

$match_id = $_GET['match_id'] ?? null;
if (!$match_id) {
    die('未指定比賽ID');
}

$stmt = $pdo->prepare("SELECT m.*, t1.team_name as team1_name, t2.team_name as team2_name 
                       FROM matches m 
                       JOIN teams t1 ON m.team1_id = t1.team_id 
                       JOIN teams t2 ON m.team2_id = t2.team_id 
                       WHERE match_id = ?");
$stmt->execute([$match_id]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>無人機足球計分系統</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .match-title {
            text-align: center;
            font-size: 36px;
            margin-bottom: 10px;
        }

        .timer-section {
            text-align: center;
            margin-bottom: 20px;
        }

        .timer {
            font-size: 96px;
            font-weight: bold;
            margin: 10px 0;
            font-family: 'Digital', Arial, sans-serif;
        }

        .timer-controls {
            margin: 10px 0;
        }

        .timer-input {
            font-size: 24px;
            width: 80px;
            padding: 5px;
            margin: 0 10px;
        }

        .match-grid {
            display: grid;
            grid-template-columns: 45% 10% 45%;
            gap: 20px;
            margin: 20px auto;
            max-width: 1200px;
        }

        .team-box {
            border: 2px solid #ddd;
            padding: 20px;
            border-radius: 10px;
            background-color: #f8f9fa;
        }

        .team-name {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 30px;
            color: #333;
        }

        .score {
            font-size: 160px;
            font-weight: bold;
            margin: 20px 0;
            color: #000;
        }

        .fouls {
            font-size: 32px;
            color: #dc3545;
            margin-top: 20px;
        }

        .vs-section {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            color: #666;
        }

        .key-guide {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin: 20px auto;
            max-width: 800px;
            text-align: center;
        }

        .export-button {
            display: block;
            margin: 20px auto;
            padding: 15px 30px;
            font-size: 24px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .export-button:hover {
            background-color: #218838;
        }

        /* 比賽結束彈窗樣式 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 1000;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            min-width: 300px;
        }

        .modal-title {
            font-size: 36px;
            color: #dc3545;
            margin-bottom: 20px;
        }

        .modal-score {
            font-size: 48px;
            margin: 20px 0;
        }

        .modal-button {
            padding: 10px 20px;
            font-size: 18px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="match-title">第 <?= $match['match_number'] ?> 場比賽</h1>
        
        <div class="timer-section">
            <div class="timer" id="timer">03:00</div>
            <div class="timer-controls">
                <input type="number" id="minutes" class="timer-input" min="0" max="99" value="3"> 分
                <input type="number" id="seconds" class="timer-input" min="0" max="59" value="0"> 秒
                <button onclick="setCustomTime()">設定時間</button>
                <button onclick="toggleTimer()">開始/暫停</button>
            </div>
        </div>

        <div class="match-grid">
            <div class="team-box">
                <div class="team-name"><?= htmlspecialchars($match['team1_name']) ?></div>
                <div class="score" id="team1-score"><?= $match['team1_score'] ?></div>
                <div class="fouls">
                    犯規次數: <span id="team1-fouls"><?= $match['team1_fouls'] ?></span>
                </div>
            </div>

            <div class="vs-section">VS</div>

            <div class="team-box">
                <div class="team-name"><?= htmlspecialchars($match['team2_name']) ?></div>
                <div class="score" id="team2-score"><?= $match['team2_score'] ?></div>
                <div class="fouls">
                    犯規次數: <span id="team2-fouls"><?= $match['team2_fouls'] ?></span>
                </div>
            </div>
        </div>

        <div class="key-guide">
            <p>快捷鍵說明：</p>
            <p>隊伍1：A (加分) / S (減分) | Z (增加犯規) / X (減少犯規)</p>
            <p>隊伍2：J (加分) / K (減分) | N (增加犯規) / M (減少犯規)</p>
        </div>

        <button onclick="exportToCSV()" class="export-button">導出CSV</button>
    </div>

    // 比賽結束彈窗
    <div id="endGameModal" class="modal">
        <div class="modal-content">
            <div class="modal-title">比賽結束！</div>
            <div class="modal-score">
                <div><?= htmlspecialchars($match['team1_name']) ?>: <span id="final-score1"><?= $match['team1_score'] ?></span></div>
                <div><?= htmlspecialchars($match['team2_name']) ?>: <span id="final-score2"><?= $match['team2_score'] ?></span></div>
            </div>
            
            <?php if ($match['team1_score'] == $match['team2_score']): ?>
                <div class="draw-section">
                    <p>比賽平局！請選擇獲勝隊伍：</p>
                    <button onclick="setDrawWinner(1)"><?= htmlspecialchars($match['team1_name']) ?></button>
                    <button onclick="setDrawWinner(2)"><?= htmlspecialchars($match['team2_name']) ?></button>
                </div>
            <?php else: ?>
                <button class="modal-button" onclick="closeModal()">確定</button>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let matchId = <?= $match_id ?>;
        let timerRunning = false;
        let timeLeft = <?= $match['match_duration'] ?>;
        let timerInterval;

        function setCustomTime() {
            const minutes = parseInt(document.getElementById('minutes').value) || 0;
            const seconds = parseInt(document.getElementById('seconds').value) || 0;
            timeLeft = minutes * 60 + seconds;
            updateTimerDisplay();
        }

        function updateTimerDisplay() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('timer').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }

        function keydownHandler(event) {
            switch(event.key.toLowerCase()) {
                case 'a': updateScore(1, 1); break;
                case 's': updateScore(1, -1); break;
                case 'j': updateScore(2, 1); break;
                case 'k': updateScore(2, -1); break;
                case 'z': updateFouls(1, 1); break;
                case 'x': updateFouls(1, -1); break;
                case 'n': updateFouls(2, 1); break;
                case 'm': updateFouls(2, -1); break;
            }
        }

        document.addEventListener('keydown', keydownHandler);

        function updateScore(team, change) {
            fetch('update_match.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    match_id: matchId,
                    team: team,
                    score_change: change
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById(`team${team}-score`).textContent = data.score;
            });
        }

        function updateFouls(team, change) {
            fetch('update_match.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    match_id: matchId,
                    team: team,
                    foul_change: change
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById(`team${team}-fouls`).textContent = data.fouls;
            });
        }

        function toggleTimer() {
            if (timerRunning) {
                clearInterval(timerInterval);
                timerRunning = false;
            } else {
                timerInterval = setInterval(updateTimer, 1000);
                timerRunning = true;
            }
        }

        // 原版
        function updateTimer() {
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                timerRunning = false;
                updateTimerDisplay();
                showEndGameModal();
                return;
            }
            timeLeft--;
            updateTimerDisplay();
        }

        // 建議版本增加加時賽判斷
        function updateTimer() {
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                timerRunning = false;
                updateTimerDisplay();
                
                // 檢查是否為加時賽
                const isOvertime = document.querySelector('.modal-content')?.innerHTML.includes('加時賽');
                showEndGameModal(isOvertime);
                return;
            }
            timeLeft--;
            updateTimerDisplay();
        }

        function exportToCSV() {
            window.location.href = `export_csv.php?match_id=${matchId}`;
        }

        function showEndGameModal(isOvertime = false) {
            const modal = document.getElementById('endGameModal');
            const score1 = parseInt(document.getElementById('team1-score').textContent);
            const score2 = parseInt(document.getElementById('team2-score').textContent);
            
            let winner = '';
            let needDraw = false;
            
            if (score1 > score2) {
                winner = '<?= htmlspecialchars($match['team1_name']) ?>';
            } else if (score2 > score1) {
                winner = '<?= htmlspecialchars($match['team2_name']) ?>';
            } else {
                if (isOvertime) {
                    needDraw = true;
                } else {
                    // 顯示加時賽提示
                    document.querySelector('.modal-content').innerHTML = `
                        <div class="modal-title">比賽進入加時！</div>
                        <div class="modal-score">
                            <div><?= htmlspecialchars($match['team1_name']) ?>: ${score1}</div>
                            <div><?= htmlspecialchars($match['team2_name']) ?>: ${score2}</div>
                        </div>
                        <p>得分相同，請進行加時賽</p>
                        <button class="modal-button" onclick="continueMatch()">繼續比賽</button>
                    `;
                    modal.style.display = 'block';
                    return;
                }
            }

            // 設置模態框內容
            const modalContent = document.querySelector('.modal-content');
            if (needDraw) {
                modalContent.innerHTML = `
                    <div class="modal-title">加時賽結束！</div>
                    <div class="modal-score">
                        <div><?= htmlspecialchars($match['team1_name']) ?>: ${score1}</div>
                        <div><?= htmlspecialchars($match['team2_name']) ?>: ${score2}</div>
                    </div>
                    <div class="draw-section">
                        <p>加時賽後仍然平手，請裁判抽籤決定勝負</p>
                        <div class="draw-buttons">
                            <button class="modal-button draw-button" onclick="recordDrawResult(1)"><?= htmlspecialchars($match['team1_name']) ?> 勝</button>
                            <button class="modal-button draw-button" onclick="recordDrawResult(2)"><?= htmlspecialchars($match['team2_name']) ?> 勝</button>
                        </div>
                    </div>
                `;
            } else {
                modalContent.innerHTML = `
                    <div class="modal-title">比賽${isOvertime ? '加時賽' : ''}結束！</div>
                    <div class="modal-score">
                        <div><?= htmlspecialchars($match['team1_name']) ?>: ${score1}</div>
                        <div><?= htmlspecialchars($match['team2_name']) ?>: ${score2}</div>
                    </div>
                    <div class="winner-announcement">獲勝隊伍：${winner}</div>
                    <button class="modal-button" onclick="closeModal()">確定</button>
                `;
            }
            
            modal.style.display = 'block';
        }

        function continueMatch() {
            // 關閉模態框
            document.getElementById('endGameModal').style.display = 'none';
            // 重置計時器狀態
            timerRunning = false;
            clearInterval(timerInterval);
            // 設置預設加時時間（3分鐘）
            document.getElementById('minutes').value = 3;
            document.getElementById('seconds').value = 0;
            setCustomTime();
            // 提示用戶設置時間
            alert('請設置加時賽時間並點擊開始按鈕開始比賽');
        }

        function recordDrawResult(winnerTeam) {
            fetch('update_match.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    match_id: matchId,
                    draw_winner: winnerTeam,
                    complete_match: true
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const winnerName = winnerTeam === 1 ? 
                        '<?= htmlspecialchars($match['team1_name']) ?>' : 
                        '<?= htmlspecialchars($match['team2_name']) ?>';
                    
                    document.querySelector('.modal-content').innerHTML = `
                        <div class="modal-title">比賽結束！</div>
                        <div class="modal-score">
                            <div><?= htmlspecialchars($match['team1_name']) ?>: ${document.getElementById('team1-score').textContent}</div>
                            <div><?= htmlspecialchars($match['team2_name']) ?>: ${document.getElementById('team2-score').textContent}</div>
                        </div>
                        <div class="winner-announcement">抽籤結果：${winnerName} 勝</div>
                        <button class="modal-button" onclick="window.location.href='list_matches.php'">返回比賽列表</button>
                    `;
                } else {
                    alert('記錄抽籤結果失敗: ' + (data.error || '未知錯誤'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('記錄抽籤結果時發生錯誤');
            });
        }

        const styles = `
            .winner-announcement {
                font-size: 24px;
                color: #4CAF50;
                margin: 20px 0;
                font-weight: bold;
            }
            .draw-section {
                margin: 20px 0;
            }
            .draw-buttons {
                display: flex;
                justify-content: center;
                gap: 20px;
                margin-top: 20px;
            }
            .draw-button {
                background-color: #6c757d;
            }
            .draw-button:hover {
                background-color: #5a6268;
            }
        `;
        
        // 將樣式添加到頭部
        const styleSheet = document.createElement("style");
        styleSheet.innerText = styles;
        document.head.appendChild(styleSheet);

        function closeModal() {
            fetch('update_match.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    match_id: matchId,
                    complete_match: true
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'list_matches.php';
                }
            });
        }

        // 當點擊模態框外部時關閉
        window.onclick = function(event) {
            const modal = document.getElementById('endGameModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // 初始化顯示
        updateTimerDisplay();

        document.addEventListener('DOMContentLoaded', function() {
            if ('<?= $match['match_status'] ?>' === 'completed') {
                document.querySelectorAll('button').forEach(button => {
                    button.disabled = true;
                });
                document.removeEventListener('keydown', keydownHandler);
                alert('此比賽已結束，無法修改');
            }
        });
    </script>
</body>
</html>

function setDrawWinner(winner) {
    fetch('update_match.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            match_id: matchId,
            draw_winner: winner,
            complete_match: true
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal();
            window.location.reload();
        } else {
            alert('記錄抽籤結果失敗: ' + data.error);
        }
    });
}