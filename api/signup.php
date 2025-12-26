<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';

$db = new Database();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $name = trim($data['name'] ?? '');
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $email = trim($data['email'] ?? '');
        
        if (empty($name) || empty($username) || empty($password) || empty($email)) {
            echo json_encode([
                'success' => false,
                'message' => 'All fields are required'
            ]);
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid email format'
            ]);
            exit;
        }
        
        if (strlen($password) < 6) {
            echo json_encode([
                'success' => false,
                'message' => 'Password must be at least 6 characters'
            ]);
            exit;
        }
        
        $check_query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $existing = $db->fetchOne($check_query, [
            ':username' => $username,
            ':email' => $email
        ]);
        
        if ($existing) {
            echo json_encode([
                'success' => false,
                'message' => 'Username or email already exists'
            ]);
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (name, username, password, email) VALUES (:name, :username, :password, :email)";
        $params = [
            ':name' => $name,
            ':username' => $username,
            ':password' => $hashed_password,
            ':email' => $email
        ];
        
        if ($db->execute($query, $params)) {
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful! You can now login.',
                'data' => [
                    'user_id' => $db->lastInsertId(),
                    'username' => $username
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create user account'
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

