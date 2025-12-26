<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

try {
    if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'data' => [
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'logged_in' => false
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'logged_in' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>

