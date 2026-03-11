<?php
/**
 * Service Call API Endpoint
 * Handles customer requests to call waitstaff
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Get parameters
$tableId = (int)($input['table_id'] ?? $_POST['table_id'] ?? 0);
$tableUuid = sanitize($input['table_uuid'] ?? $_POST['table_uuid'] ?? '');
$callType = sanitize($input['call_type'] ?? $_POST['call_type'] ?? 'waiter');
$notes = sanitize($input['notes'] ?? $_POST['notes'] ?? '');

// Validate table
if (!$tableId && !$tableUuid) {
    jsonResponse(['success' => false, 'error' => 'Table information is required'], 400);
}

// Get table details
$table = null;
if ($tableUuid) {
    $table = dbFetchOne("SELECT id, table_number, status FROM tables WHERE qr_code_uuid = ?", [$tableUuid]);
} elseif ($tableId) {
    $table = dbFetchOne("SELECT id, table_number, status FROM tables WHERE id = ?", [$tableId]);
}

if (!$table) {
    jsonResponse(['success' => false, 'error' => 'Invalid table'], 404);
}

if ($table['status'] !== 'active') {
    jsonResponse(['success' => false, 'error' => 'This table is currently inactive'], 400);
}

// Validate call type
$allowedCallTypes = ['waiter', 'bill', 'complaint', 'other'];
if (!in_array($callType, $allowedCallTypes)) {
    $callType = 'waiter';
}

// Rate limiting: Check for recent calls from this table (within 2 minutes)
$recentCall = dbFetchOne(
    "SELECT id, created_at FROM service_calls 
     WHERE table_id = ? AND status IN ('pending', 'assigned') 
     AND created_at > DATE_SUB(NOW(), INTERVAL 2 MINUTE)
     ORDER BY created_at DESC LIMIT 1",
    [$table['id']]
);

if ($recentCall) {
    $waitTime = strtotime('+2 minutes', strtotime($recentCall['created_at'])) - time();
    jsonResponse([
        'success' => false, 
        'error' => 'Please wait before making another request',
        'wait_time' => max(0, $waitTime)
    ], 429);
}

// Create service call
try {
    $callId = dbInsert(
        "INSERT INTO service_calls (table_id, call_type, notes, status) VALUES (?, ?, ?, 'pending')",
        [$table['id'], $callType, $notes]
    );
    
    jsonResponse([
        'success' => true,
        'message' => 'Service call created successfully',
        'call_id' => $callId,
        'table' => $table['table_number'],
        'call_type' => $callType
    ]);
    
} catch (Exception $e) {
    error_log("Service call error: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Failed to create service call'], 500);
}
?>
