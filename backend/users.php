<?php
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/../config/database.php';

if (!hasAccessToSection('users')) {
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
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'admin';
        
        if (empty($username)) {
            $error = 'Username is required';
        } elseif ($action === 'add' && empty($password)) {
            $error = 'Password is required';
        } else {
            if ($action === 'add') {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query = "INSERT INTO admins (username, password, role) VALUES (:username, :password, :role)";
                $params = [':username' => $username, ':password' => $hashed_password, ':role' => $role];
                
                if ($db->execute($query, $params)) {
                    $new_admin_id = $db->lastInsertId();
                    
                    if ($role === 'admin') {
                        $categories = $_POST['categories'] ?? [];
                        $products = $_POST['products'] ?? [];
                        
                        foreach ($categories as $cat_id) {
                            $perm_query = "INSERT INTO admin_permissions (admin_id, category_id) VALUES (:admin_id, :category_id)";
                            $db->execute($perm_query, [':admin_id' => $new_admin_id, ':category_id' => $cat_id]);
                        }
                        
                        foreach ($products as $prod_id) {
                            $perm_query = "INSERT INTO admin_permissions (admin_id, product_id) VALUES (:admin_id, :product_id)";
                            $db->execute($perm_query, [':admin_id' => $new_admin_id, ':product_id' => $prod_id]);
                        }
                    }
                    
                    $success = 'Admin user added successfully';
                } else {
                    $error = 'Failed to add admin user';
                }
            } else {
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $query = "UPDATE admins SET username = :username, password = :password, role = :role WHERE id = :id";
                    $params = [':username' => $username, ':password' => $hashed_password, ':role' => $role, ':id' => $id];
                } else {
                    $query = "UPDATE admins SET username = :username, role = :role WHERE id = :id";
                    $params = [':username' => $username, ':role' => $role, ':id' => $id];
                }
                
                if ($db->execute($query, $params)) {
                    $db->execute("DELETE FROM admin_permissions WHERE admin_id = :admin_id", [':admin_id' => $id]);
                    
                    if ($role === 'admin') {
                        $categories = $_POST['categories'] ?? [];
                        $products = $_POST['products'] ?? [];
                        
                        foreach ($categories as $cat_id) {
                            $perm_query = "INSERT INTO admin_permissions (admin_id, category_id) VALUES (:admin_id, :category_id)";
                            $db->execute($perm_query, [':admin_id' => $id, ':category_id' => $cat_id]);
                        }
                        
                        foreach ($products as $prod_id) {
                            $perm_query = "INSERT INTO admin_permissions (admin_id, product_id) VALUES (:admin_id, :product_id)";
                            $db->execute($perm_query, [':admin_id' => $id, ':product_id' => $prod_id]);
                        }
                    }
                    
                    $success = 'Admin user updated successfully';
                } else {
                    $error = 'Failed to update admin user';
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id == $_SESSION['admin_id']) {
            $error = 'You cannot delete yourself';
        } else {
            $query = "DELETE FROM admins WHERE id = :id";
            if ($db->execute($query, [':id' => $id])) {
                $success = 'Admin user deleted successfully';
            } else {
                $error = 'Failed to delete admin user';
            }
        }
    }
}

$all_categories = $db->fetchAll("SELECT * FROM categories ORDER BY name ASC");
$all_products = $db->fetchAll("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY c.name, p.name ASC");

$query = "SELECT * FROM admins ORDER BY username ASC";
$admins = $db->fetchAll($query);

$edit_admin = null;
$edit_permissions = ['categories' => [], 'products' => []];
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $query = "SELECT * FROM admins WHERE id = :id";
    $edit_admin = $db->fetchOne($query, [':id' => $edit_id]);
    
    if ($edit_admin) {
        $perm_query = "SELECT category_id, product_id FROM admin_permissions WHERE admin_id = :admin_id";
        $perms = $db->fetchAll($perm_query, [':admin_id' => $edit_id]);
        
        foreach ($perms as $perm) {
            if ($perm['category_id']) {
                $edit_permissions['categories'][] = $perm['category_id'];
            }
            if ($perm['product_id']) {
                $edit_permissions['products'][] = $perm['product_id'];
            }
        }
    }
}
    
$products_by_category = [];
foreach ($all_products as $product) {
    $cat_id = $product['category_id'];
    if (!isset($products_by_category[$cat_id])) {
        $products_by_category[$cat_id] = [];
    }
    $products_by_category[$cat_id][] = $product;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management</title>
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
        input[type="password"],
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .permissions-section {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            background: #f8f9fa;
        }
        
        .permissions-section h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .category-group {
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            background: white;
        }
        
        .category-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            cursor: pointer;
            font-weight: 600;
            color: #495057;
        }
        
        .category-header input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .toggle-icon {
            margin-left: auto;
            color: #667eea;
        }
        
        .products-list {
            margin-left: 30px;
            margin-top: 10px;
        }
        
        .product-item {
            display: flex;
            align-items: center;
            padding: 5px 0;
        }
        
        .product-item input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .product-item label {
            margin: 0;
            font-weight: normal;
            color: #666;
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
        
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .badge-super {
            background: #667eea;
            color: white;
        }
        
        .badge-admin {
            background: #28a745;
            color: white;
        }
        
        #permissions-container {
            display: none;
        }
        
        #permissions-container.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>👥 Users Management</h1>
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
            <h2><?php echo $edit_admin ? 'Edit Admin User' : 'Add New Admin User'; ?></h2>
            <form method="POST" action="" id="adminForm">
                <input type="hidden" name="action" value="<?php echo $edit_admin ? 'edit' : 'add'; ?>">
                <?php if ($edit_admin): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_admin['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo $edit_admin ? htmlspecialchars($edit_admin['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password <?php echo $edit_admin ? '(Leave empty to keep current)' : '*'; ?></label>
                    <input type="password" id="password" name="password" <?php echo $edit_admin ? '' : 'required'; ?>>
                </div>
                
                <div class="form-group">
                    <label for="role">Role *</label>
                    <select id="role" name="role" required onchange="togglePermissions()">
                        <option value="admin" <?php echo ($edit_admin && $edit_admin['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="super" <?php echo ($edit_admin && $edit_admin['role'] == 'super') ? 'selected' : ''; ?>>Super Admin</option>
                    </select>
                </div>
                
                <div id="permissions-container" class="<?php echo ($edit_admin && $edit_admin['role'] == 'admin') || !$edit_admin ? 'show' : ''; ?>">
                    <div class="permissions-section">
                        <h3>Assign Permissions</h3>
                        <p style="color: #666; margin-bottom: 15px; font-size: 14px;">Select categories and products this admin can manage:</p>
                        
                        <?php foreach ($all_categories as $category): ?>
                            <div class="category-group">
                                <div class="category-header">
                                    <input type="checkbox" 
                                           id="cat_<?php echo $category['id']; ?>" 
                                           name="categories[]" 
                                           value="<?php echo $category['id']; ?>"
                                           <?php echo in_array($category['id'], $edit_permissions['categories']) ? 'checked' : ''; ?>
                                           onchange="toggleCategoryProducts(<?php echo $category['id']; ?>)">
                                    <label for="cat_<?php echo $category['id']; ?>" style="margin: 0; cursor: pointer;">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </label>
                                </div>
                                
                                <?php if (isset($products_by_category[$category['id']])): ?>
                                    <div class="products-list" id="products_<?php echo $category['id']; ?>">
                                        <?php foreach ($products_by_category[$category['id']] as $product): ?>
                                            <div class="product-item">
                                                <input type="checkbox" 
                                                       id="prod_<?php echo $product['id']; ?>" 
                                                       name="products[]" 
                                                       value="<?php echo $product['id']; ?>"
                                                       class="product-check-<?php echo $category['id']; ?>"
                                                       <?php echo in_array($product['id'], $edit_permissions['products']) ? 'checked' : ''; ?>>
                                                <label for="prod_<?php echo $product['id']; ?>">
                                                    <?php echo htmlspecialchars($product['name']); ?> (SKU: <?php echo htmlspecialchars($product['sku']); ?>)
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $edit_admin ? 'Update Admin' : 'Add Admin'; ?>
                    </button>
                    <?php if ($edit_admin): ?>
                        <a href="users.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h2>All Admin Users</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo $admin['id']; ?></td>
                                <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $admin['role']; ?>">
                                        <?php echo htmlspecialchars($admin['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($admin['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="?edit=<?php echo $admin['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                        <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this admin?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $admin['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function togglePermissions() {
            const role = document.getElementById('role').value;
            const permissionsContainer = document.getElementById('permissions-container');
            
            if (role === 'admin') {
                permissionsContainer.classList.add('show');
            } else {
                permissionsContainer.classList.remove('show');
            }
        }
        
        function toggleCategoryProducts(categoryId) {
            const categoryCheck = document.getElementById('cat_' + categoryId);
            const productChecks = document.querySelectorAll('.product-check-' + categoryId);
            
            productChecks.forEach(check => {
                check.checked = categoryCheck.checked;
            });
        }
    </script>
</body>
</html>

