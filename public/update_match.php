<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$match_id = $data['match_id'];
$response = ['success' => false];

try {
    if (isset($data['complete_match'])) {
        // 更新比賽狀態為已完成
        $stmt = $pdo->prepare("UPDATE matches SET match_status = 'completed' WHERE match_id = ?");
        $stmt->execute([$match_id]);
        $response['success'] = true;
        $response['redirect'] = 'list_matches.php';
    } else {
        // 檢查比賽是否已結束
        $stmt = $pdo->prepare("SELECT match_status FROM matches WHERE match_id = ?");
        $stmt->execute([$match_id]);
        $match = $stmt->fetch();
        
        if ($match['match_status'] === 'completed') {
            $response['error'] = '比賽已結束，無法修改';
            echo json_encode($response);
            exit;
        }

        $team = $data['team'];
        
        if (isset($data['score_change'])) {
            $field = "team{$team}_score";
            $change = $data['score_change'];
            
            // 檢查分數不會變成負數
            $stmt = $pdo->prepare("SELECT $field as current_score FROM matches WHERE match_id = ?");
            $stmt->execute([$match_id]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($current['current_score'] + $change < 0) {
                $response['error'] = '分數不能為負數';
                echo json_encode($response);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE matches SET $field = $field + ? WHERE match_id = ?");
            $stmt->execute([$change, $match_id]);
            
            $stmt = $pdo->prepare("SELECT $field as score FROM matches WHERE match_id = ?");
            $stmt->execute([$match_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $response['score'] = $result['score'];
        }
        
        if (isset($data['foul_change'])) {
            $field = "team{$team}_fouls";
            $change = $data['foul_change'];
            
            // 檢查犯規次數不會變成負數
            $stmt = $pdo->prepare("SELECT $field as current_fouls FROM matches WHERE match_id = ?");
            $stmt->execute([$match_id]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($current['current_fouls'] + $change < 0) {
                $response['error'] = '犯規次數不能為負數';
                echo json_encode($response);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE matches SET $field = $field + ? WHERE match_id = ?");
            $stmt->execute([$change, $match_id]);
            
            $stmt = $pdo->prepare("SELECT $field as fouls FROM matches WHERE match_id = ?");
            $stmt->execute([$match_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $response['fouls'] = $result['fouls'];
        }
        
        $response['success'] = true;
    }
} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);