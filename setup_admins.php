<?php
/**
 * Admin Account Setup Script
 * Run this once to create/reset admin accounts with proper password hashing
 */

require_once __DIR__ . '/config/database.php';

echo "<h1>Admin Account Setup</h1>";
echo "<hr>";

$db = new Database();

// Create super admin account
$username_super = 'super';
$password_super = 'super';
$hashed_super = password_hash($password_super, PASSWORD_DEFAULT);

// Create regular admin account
$username_admin = 'admin';
$password_admin = 'admin';
$hashed_admin = password_hash($password_admin, PASSWORD_DEFAULT);

try {
    // Delete existing accounts if they exist
    $db->execute("DELETE FROM admins WHERE username IN ('super', 'admin')");
    echo "<p style='color: orange;'>✓ Removed old admin accounts (if any)</p>";
    
    // Insert super admin
    $query = "INSERT INTO admins (username, password, role) VALUES (:username, :password, :role)";
    $db->execute($query, [
        ':username' => $username_super,
        ':password' => $hashed_super,
        ':role' => 'super'
    ]);
    echo "<p style='color: green;'>✓ Super admin created: <strong>super / super</strong></p>";
    
    // Insert regular admin
    $db->execute($query, [
        ':username' => $username_admin,
        ':password' => $hashed_admin,
        ':role' => 'admin'
    ]);
    echo "<p style='color: green;'>✓ Regular admin created: <strong>admin / admin</strong></p>";
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✓ SUCCESS!</h2>";
    echo "<p>Admin accounts have been created with properly hashed passwords.</p>";
    echo "<p><strong>You can now login:</strong></p>";
    echo "<ul>";
    echo "<li>Super Admin: <code>super</code> / <code>super</code></li>";
    echo "<li>Regular Admin: <code>admin</code> / <code>admin</code></li>";
    echo "</ul>";
    echo "<hr>";
    echo "<p><a href='backend/login.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page →</a></p>";
    echo "<hr>";
    echo "<p style='color: red;'><strong>Security Note:</strong> Delete this file after setup for security!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p>Make sure the database 'idealogy_test' exists and is properly configured.</p>";
}
?>

