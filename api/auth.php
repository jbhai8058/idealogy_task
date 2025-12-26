<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';

session_start();
$db = new Database();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? 'login';
        
        if ($action === 'login') {
            $username = trim($data['username'] ?? '');
            $password = $data['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Username and password are required'
                ]);
                exit;
            }
            
            $query = "SELECT * FROM users WHERE username = :username";
            $user = $db->fetchOne($query, [':username' => $username]);
            
            if ($user && password_verify($password, $user['password'])) {
                $session_token = bin2hex(random_bytes(32));
                
                $insert_query = "INSERT INTO user_sessions (user_id, session_token) VALUES (:user_id, :token)";
                $db->execute($insert_query, [
                    ':user_id' => $user['id'],
                    ':token' => $session_token
                ]);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['session_token'] = $session_token;
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'user_id' => $user['id'],
                        'username' => $user['username'],
                        'name' => $user['name'],
                        'session_token' => $session_token
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid username or password'
                ]);
            }
        } elseif ($action === 'logout') {
            if (isset($_SESSION['session_token'])) {
                $query = "DELETE FROM user_sessions WHERE session_token = :token";
                $db->execute($query, [':token' => $_SESSION['session_token']]);
            }
            
            session_destroy();
            
            echo json_encode([
                'success' => true,
                'message' => 'Logout successful'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>

