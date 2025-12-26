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
                $query = "SELECT * FROM categories WHERE id = :id";
                $category = $db->fetchOne($query, [':id' => $id]);
                
                if ($category) {
                    echo json_encode([
                        'success' => true,
                        'data' => $category
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Category not found'
                    ]);
                }
            } else {
                $query = "SELECT * FROM categories ORDER BY name ASC";
                $categories = $db->fetchAll($query);
                
                echo json_encode([
                    'success' => true,
                    'data' => $categories
                ]);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['name'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Category name is required'
                ]);
                break;
            }
            
            $query = "INSERT INTO categories (name, description) VALUES (:name, :description)";
            $params = [
                ':name' => $data['name'],
                ':description' => $data['description'] ?? ''
            ];
            
            if ($db->execute($query, $params)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Category created successfully',
                    'data' => ['id' => $db->lastInsertId()]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create category'
                ]);
            }
            break;
            
        case 'PUT': 
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id']) || empty($data['name'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Category ID and name are required'
                ]);
                break;
            }
            
            $query = "UPDATE categories SET name = :name, description = :description WHERE id = :id";
            $params = [
                ':id' => $data['id'],
                ':name' => $data['name'],
                ':description' => $data['description'] ?? ''
            ];
            
            if ($db->execute($query, $params)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Category updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update category'
                ]);
            }
            break;
            
        case 'DELETE':
                        
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Category ID is required'
                ]);
                break;
            }
            
            $query = "DELETE FROM categories WHERE id = :id";
            
            if ($db->execute($query, [':id' => $data['id']])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Category deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to delete category'
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

