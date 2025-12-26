<?php
/**
 * Backend Dashboard - Main admin page
 */

require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/permissions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
        
        .header h1 {
            font-size: 24px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info span {
            font-size: 14px;
        }
        
        .badge {
            background: rgba(255,255,255,0.3);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .welcome {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .welcome h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .welcome p {
            color: #666;
            line-height: 1.6;
        }
        
        .nav-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .nav-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            border-left: 4px solid #667eea;
        }
        
        .nav-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .nav-card h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #667eea;
        }
        
        .nav-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .nav-card.disabled {
            opacity: 0.5;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Admin Dashboard</h1>
            <div class="user-info">
                <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <span class="badge"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome">
            <h2>Welcome to Admin Panel</h2>
            <p>Manage your system from this dashboard. Select a module below to get started.</p>
        </div>
        
        <div class="nav-cards">
            <?php if (hasAccessToSection('users')): ?>
            <a href="users.php" class="nav-card">
                <h3>👥 Users Management</h3>
                <p>Create and manage admin users with custom permissions</p>
            </a>
            <?php endif; ?>
            
            <?php if (hasAccessToSection('categories')): ?>
            <a href="categories.php" class="nav-card">
                <h3>📁 Categories</h3>
                <p>Add, edit, and organize product categories</p>
            </a>
            <?php endif; ?>
            
            <?php if (hasAccessToSection('products')): ?>
            <a href="products.php" class="nav-card">
                <h3>📦 Products</h3>
                <p>Manage products, prices, images, and descriptions</p>
            </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

