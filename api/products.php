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
                $query = "SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE p.id = :id";
                $product = $db->fetchOne($query, [':id' => $id]);
                
                if ($product) {
                    echo json_encode([
                        'success' => true,
                        'data' => $product
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Product not found'
                    ]);
                }
            } elseif (isset($_GET['category_id'])) {
                $category_id = intval($_GET['category_id']);
                $query = "SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE p.category_id = :category_id 
                          ORDER BY p.name ASC";
                $products = $db->fetchAll($query, [':category_id' => $category_id]);
                
                echo json_encode([
                    'success' => true,
                    'data' => $products
                ]);
            } else {
                $query = "SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          ORDER BY p.name ASC";
                $products = $db->fetchAll($query);
                
                echo json_encode([
                    'success' => true,
                    'data' => $products
                ]);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['name']) || empty($data['sku']) || empty($data['category_id']) || empty($data['price'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Name, SKU, category, and price are required'
                ]);
                break;
            }
            
            $query = "INSERT INTO products (category_id, name, sku, description, price, image) 
                      VALUES (:category_id, :name, :sku, :description, :price, :image)";
            $params = [
                ':category_id' => $data['category_id'],
                ':name' => $data['name'],
                ':sku' => $data['sku'],
                ':description' => $data['description'] ?? '',
                ':price' => $data['price'],
                ':image' => $data['image'] ?? ''
            ];
            
            if ($db->execute($query, $params)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Product created successfully',
                    'data' => ['id' => $db->lastInsertId()]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create product'
                ]);
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id']) || empty($data['name']) || empty($data['sku'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Product ID, name, and SKU are required'
                ]);
                break;
            }
            
            $query = "UPDATE products SET category_id = :category_id, name = :name, 
                      sku = :sku, description = :description, price = :price";
            
            $params = [
                ':id' => $data['id'],
                ':category_id' => $data['category_id'],
                ':name' => $data['name'],
                ':sku' => $data['sku'],
                ':description' => $data['description'] ?? '',
                ':price' => $data['price']
            ];
            
            if (!empty($data['image'])) {
                $query .= ", image = :image";
                $params[':image'] = $data['image'];
            }
            
            $query .= " WHERE id = :id";
            
            if ($db->execute($query, $params)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Product updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update product'
                ]);
            }
            break;
            
        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Product ID is required'
                ]);
                break;
            }
            
            $query = "DELETE FROM products WHERE id = :id";
            
            if ($db->execute($query, [':id' => $data['id']])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Product deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to delete product'
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

