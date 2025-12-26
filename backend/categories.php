<?php

require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/../config/database.php';

if (!hasAccessToSection('categories')) {
    header("Location: dashboard.php");
    exit();
}

$db = new Database();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $error = 'Category name is required';
        } else {
            $query = "INSERT INTO categories (name, description) VALUES (:name, :description)";
            if ($db->execute($query, [':name' => $name, ':description' => $description])) {
                $success = 'Category added successfully';
            } else {
                $error = 'Failed to add category';
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (!canEditCategory($id)) {
            $error = 'You do not have permission to edit this category';
        } elseif (empty($name)) {
            $error = 'Category name is required';
        } else {
            $query = "UPDATE categories SET name = :name, description = :description WHERE id = :id";
            if ($db->execute($query, [':name' => $name, ':description' => $description, ':id' => $id])) {
                $success = 'Category updated successfully';
            } else {
                $error = 'Failed to update category';
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if (!canEditCategory($id)) {
            $error = 'You do not have permission to delete this category';
        } else {
            $query = "DELETE FROM categories WHERE id = :id";
            if ($db->execute($query, [':id' => $id])) {
                $success = 'Category deleted successfully';
            } else {
                $error = 'Failed to delete category';
            }
        }
    }
}

$query = "SELECT * FROM categories ORDER BY name ASC";
$categories = $db->fetchAll($query);
$categories = filterCategories($categories);

$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    if (canEditCategory($edit_id)) {
        $query = "SELECT * FROM categories WHERE id = :id";
        $edit_category = $db->fetchOne($query, [':id' => $edit_id]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management</title>
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
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>📁 Categories Management</h1>
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
            <h2><?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?></h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo $edit_category ? 'edit' : 'add'; ?>">
                <?php if ($edit_category): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="name">Category Name *</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo $edit_category ? htmlspecialchars($edit_category['name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?php echo $edit_category ? htmlspecialchars($edit_category['description']) : ''; ?></textarea>
                </div>
                
                <div class="actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $edit_category ? 'Update Category' : 'Add Category'; ?>
                    </button>
                    <?php if ($edit_category): ?>
                        <a href="categories.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h2>All Categories</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #999;">No categories found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><?php echo $cat['id']; ?></td>
                                    <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($cat['description'], 0, 50)); ?><?php echo strlen($cat['description']) > 50 ? '...' : ''; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($cat['created_at'])); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="?edit=<?php echo $cat['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
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

