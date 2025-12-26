<?php

require_once __DIR__ . '/../../config/database.php';

function hasAccessToSection($section) {
    if ($_SESSION['role'] === 'super') {
        return true;
    }
    
    if ($_SESSION['role'] === 'admin' && in_array($section, ['categories', 'products'])) {
        return true;
    }
    
    return false;
}

function getAllowedCategories() {
    if ($_SESSION['role'] === 'super') {
        return 'all';
    }
    
    $db = new Database();
    $query = "SELECT DISTINCT category_id FROM admin_permissions 
              WHERE admin_id = :admin_id AND category_id IS NOT NULL";
    $results = $db->fetchAll($query, [':admin_id' => $_SESSION['admin_id']]);
    
    return array_column($results, 'category_id');
}

function getAllowedProducts() {
    if ($_SESSION['role'] === 'super') {
        return 'all';
    }
    
    $db = new Database();
    $query = "SELECT DISTINCT product_id FROM admin_permissions 
              WHERE admin_id = :admin_id AND product_id IS NOT NULL";
    $results = $db->fetchAll($query, [':admin_id' => $_SESSION['admin_id']]);
    
    return array_column($results, 'product_id');
}

function filterCategories($categories) {
    $allowed = getAllowedCategories();
    
    if ($allowed === 'all') {
        return $categories;
    }
    
    return array_filter($categories, function($cat) use ($allowed) {
        return in_array($cat['id'], $allowed);
    });
}

function filterProducts($products) {
    $allowed = getAllowedProducts();
    
    if ($allowed === 'all') {
        return $products;
    }
    
    return array_filter($products, function($prod) use ($allowed) {
        return in_array($prod['id'], $allowed);
    });
}

function canEditCategory($category_id) {
    $allowed = getAllowedCategories();
    return $allowed === 'all' || in_array($category_id, $allowed);
}

function canEditProduct($product_id) {
    $allowed = getAllowedProducts();
    return $allowed === 'all' || in_array($product_id, $allowed);
}
?>

