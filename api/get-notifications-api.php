<?php
// api/get-notifications.php
header('Content-Type: application/json');
require_once '../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $limit = (int)($_GET['limit'] ?? 10);
    $limit = min(50, max(1, $limit)); // Between 1 and 50
    
    $notifications = getNotifications($_SESSION['user_id'], $limit);
    $unread_count = count(array_filter($notifications, function($n) { return $n['is_read'] == 0; }));
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unread_count,
        'total' => count($notifications)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading notifications: ' . $e->getMessage()
    ]);
}
?>
