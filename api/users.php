<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';

$db = new Database();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                $query = "SELECT id, username, role, created_at FROM admins WHERE id = :id";
                $user = $db->fetchOne($query, [':id' => $id]);
                
                if ($user) {
                    echo json_encode([
                        'success' => true,
                        'data' => $user
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'User not found'
                    ]);
                }
            } else {
                $query = "SELECT id, username, role, created_at FROM admins ORDER BY username ASC";
                $users = $db->fetchAll($query);
                
                echo json_encode([
                    'success' => true,
                    'data' => $users
                ]);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['username']) || empty($data['password'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Username and password are required'
                ]);
                break;
            }
            
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            $query = "INSERT INTO admins (username, password, role) VALUES (:username, :password, :role)";
            $params = [
                ':username' => $data['username'],
                ':password' => $hashed_password,
                ':role' => $data['role'] ?? 'admin'
            ];
            
            if ($db->execute($query, $params)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Admin user created successfully',
                    'data' => ['id' => $db->lastInsertId()]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create admin user'
                ]);
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id']) || empty($data['username'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'User ID and username are required'
                ]);
                break;
            }
            
            if (!empty($data['password'])) {
                $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
                $query = "UPDATE admins SET username = :username, password = :password, role = :role WHERE id = :id";
                $params = [
                    ':id' => $data['id'],
                    ':username' => $data['username'],
                    ':password' => $hashed_password,
                    ':role' => $data['role'] ?? 'admin'
                ];
            } else {
                $query = "UPDATE admins SET username = :username, role = :role WHERE id = :id";
                $params = [
                    ':id' => $data['id'],
                    ':username' => $data['username'],
                    ':role' => $data['role'] ?? 'admin'
                ];
            }
            
            if ($db->execute($query, $params)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Admin user updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update admin user'
                ]);
            }
            break;
            
        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'User ID is required'
                ]);
                break;
            }
            
            $query = "DELETE FROM admins WHERE id = :id";
            
            if ($db->execute($query, [':id' => $data['id']])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Admin user deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to delete admin user'
                ]);
            }
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>

