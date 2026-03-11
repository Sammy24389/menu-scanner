<?php
/**
 * Helper Functions
 * Common utility functions for the application
 */

require_once __DIR__ . '/../config/database.php';

// Generate UUID v4
function generateUuid() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Format price
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

// Generate random PIN for waitstaff
function generatePin($length = 4) {
    return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

// Upload image helper
function uploadImage($file, $targetDir = '../uploads/') {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['error' => 'File too large. Max size: 5MB'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $targetPath = $targetDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => $filename];
    }
    
    return ['error' => 'Failed to upload file'];
}

// Delete image helper
function deleteImage($filename, $targetDir = '../uploads/') {
    if ($filename) {
        $filepath = $targetDir . $filename;
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
}

// Get all categories
function getCategories($activeOnly = true) {
    $sql = "SELECT * FROM categories";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY sort_order ASC, name ASC";
    return dbFetchAll($sql);
}

// Get all tables
function getTables($status = null) {
    $sql = "SELECT * FROM tables";
    if ($status) {
        $sql .= " WHERE status = ?";
        return dbFetchAll($sql, [$status]);
    }
    $sql .= " ORDER BY table_number ASC";
    return dbFetchAll($sql);
}

// Get menu items by category
function getMenuItemsByCategory($categoryId = null, $availableOnly = false) {
    $sql = "SELECT m.*, c.name as category_name 
            FROM menu_items m 
            JOIN categories c ON m.category_id = c.id";
    
    $where = [];
    $params = [];
    
    if ($categoryId) {
        $where[] = "m.category_id = ?";
        $params[] = $categoryId;
    }
    
    if ($availableOnly) {
        $where[] = "m.is_available = 1";
    }
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    
    $sql .= " ORDER BY c.sort_order, m.name";
    
    return dbFetchAll($sql, $params);
}

// Get item variations
function getItemVariations($itemId) {
    $sql = "SELECT * FROM item_variations WHERE item_id = ? ORDER BY sort_order, name";
    return dbFetchAll($sql, [$itemId]);
}

// Get pending service calls count
function getPendingServiceCallsCount() {
    $sql = "SELECT COUNT(*) as count FROM service_calls WHERE status = 'pending'";
    $result = dbFetchOne($sql);
    return $result['count'] ?? 0;
}

// Get active service calls
function getActiveServiceCalls() {
    $sql = "SELECT sc.*, t.table_number, t.qr_code_uuid, w.name as waitstaff_name
            FROM service_calls sc
            JOIN tables t ON sc.table_id = t.id
            LEFT JOIN waitstaff w ON sc.waitstaff_id = w.id
            WHERE sc.status IN ('pending', 'assigned')
            ORDER BY sc.created_at DESC";
    return dbFetchAll($sql);
}

// Flash message helper
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Redirect helper
function redirect($url) {
    header("Location: $url");
    exit;
}

// JSON response helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
