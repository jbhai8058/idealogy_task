<?php
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/../config/database.php';

if (!hasAccessToSection('products')) {
    header("Location: dashboard.php");
    exit();
}

$db = new Database();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        
        $image = '';
        $image_uploaded = false;
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
                chmod($upload_dir, 0777);
            }
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_type = $_FILES['image']['type'];
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_type, $allowed_types) && !in_array($file_extension, $allowed_extensions)) {
                $error = 'Invalid image type. Only JPG, PNG, GIF, and WEBP are allowed.';
            } elseif ($_FILES['image']['size'] > 5000000) {
                $error = 'Image size must be less than 5MB';
            } else {
                $image = uniqid() . '_' . time() . '.' . $file_extension;
                $target_path = $upload_dir . $image;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    chmod($target_path, 0644);
                    $image_uploaded = true;
                } else {
                    $error = 'Failed to upload image. Check directory permissions.';
                }
            }
        } elseif ($action === 'edit') {
            $image = $_POST['existing_image'] ?? '';
        }
        
        if (!$error) {
            if (empty($name) || empty($sku) || $category_id <= 0 || $price <= 0) {
                $error = 'Please fill in all required fields';
            } else {
                if ($action === 'add') {
                    $query = "INSERT INTO products (category_id, name, sku, description, price, image) 
                              VALUES (:category_id, :name, :sku, :description, :price, :image)";
                    $params = [
                        ':category_id' => $category_id,
                        ':name' => $name,
                        ':sku' => $sku,
                        ':description' => $description,
                        ':price' => $price,
                        ':image' => $image
                    ];
                    if ($db->execute($query, $params)) {
                        $success = 'Product added successfully';
                    } else {
                        $error = 'Failed to add product';
                    }
                } else {
                    if (!canEditProduct($id)) {
                        $error = 'You do not have permission to edit this product';
                    } else {
                        if ($image_uploaded || !empty($image)) {
                            $query = "UPDATE products SET category_id = :category_id, name = :name, 
                                      sku = :sku, description = :description, price = :price, image = :image WHERE id = :id";
                            $params = [
                                ':category_id' => $category_id,
                                ':name' => $name,
                                ':sku' => $sku,
                                ':description' => $description,
                                ':price' => $price,
                                ':image' => $image,
                                ':id' => $id
                            ];
                        } else {
                            $query = "UPDATE products SET category_id = :category_id, name = :name, 
                                      sku = :sku, description = :description, price = :price WHERE id = :id";
                            $params = [
                                ':category_id' => $category_id,
                                ':name' => $name,
                                ':sku' => $sku,
                                ':description' => $description,
                                ':price' => $price,
                                ':id' => $id
                            ];
                        }
                        if ($db->execute($query, $params)) {
                            $success = 'Product updated successfully' . ($image_uploaded ? ' with new image' : '');
                        } else {
                            $error = 'Failed to update product';
                        }
                    }
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if (!canEditProduct($id)) {
            $error = 'You do not have permission to delete this product';
        } else {
            $query = "DELETE FROM products WHERE id = :id";
            if ($db->execute($query, [':id' => $id])) {
                $success = 'Product deleted successfully';
            } else {
                $error = 'Failed to delete product';
            }
        }
    }
}

$all_categories = $db->fetchAll("SELECT * FROM categories ORDER BY name ASC");
$categories = filterCategories($all_categories);

$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          ORDER BY p.name ASC";
$products = $db->fetchAll($query);
$products = filterProducts($products);
                
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    if (canEditProduct($edit_id)) {
        $query = "SELECT * FROM products WHERE id = :id";
        $edit_product = $db->fetchOne($query, [':id' => $edit_id]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn-back {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        input[type="text"],
        input[type="number"],
        input[type="file"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 6px 15px;
            font-size: 12px;
        }
        
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>📦 Products Management</h1>
            <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $edit_product ? 'edit' : 'add'; ?>">
                <?php if ($edit_product): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
                    <input type="hidden" name="existing_image" value="<?php echo $edit_product['image']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo ($edit_product && $edit_product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="sku">SKU *</label>
                    <input type="text" id="sku" name="sku" required 
                           value="<?php echo $edit_product ? htmlspecialchars($edit_product['sku']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="price">Price *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required 
                           value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="image">Product Image <?php echo $edit_product ? '(Leave empty to keep current)' : '*'; ?></label>
                    <?php if ($edit_product && $edit_product['image']): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="/test_project/uploads/products/<?php echo htmlspecialchars($edit_product['image']); ?>" 
                                 style="max-width: 200px; border-radius: 5px; border: 2px solid #ddd;" 
                                 alt="Current Image"
                                 onerror="this.style.display='none';">
                            <p style="color: #666; font-size: 12px; margin-top: 5px;">Current Image: <?php echo htmlspecialchars($edit_product['image']); ?></p>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp" <?php echo $edit_product ? '' : 'required'; ?>>
                    <p style="color: #666; font-size: 12px; margin-top: 5px;">Allowed: JPG, PNG, GIF, WEBP (Max 5MB)</p>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                </div>
                
                <div class="actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $edit_product ? 'Update Product' : 'Add Product'; ?>
                    </button>
                    <?php if ($edit_product): ?>
                        <a href="products.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h2>All Products</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #999;">No products found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $prod): ?>
                                <tr>
                                    <td><?php echo $prod['id']; ?></td>
                                    <td>
                                        <?php if ($prod['image']): ?>
                                            <img src="/test_project/uploads/products/<?php echo htmlspecialchars($prod['image']); ?>" 
                                                 class="product-image" alt="Product">
                                        <?php else: ?>
                                            <span style="color: #999;">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($prod['name']); ?></td>
                                    <td><?php echo htmlspecialchars($prod['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($prod['sku']); ?></td>
                                    <td>$<?php echo number_format($prod['price'], 2); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="?edit=<?php echo $prod['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $prod['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

