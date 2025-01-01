<?php
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