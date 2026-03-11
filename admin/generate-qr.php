<?php
/**
 * QR Code Generator Script
 * Generates and downloads QR codes for tables
 * Usage: generate-qr.php?table=T1&uuid=xxx-xxx-xxx
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if chillerlan/php-qrcode is available, otherwise use API
function generateQrCode($data, $size = 300) {
    // Try using chillerlan/php-qrcode if available
    if (class_exists('chillerlan\QRCode\QRCode')) {
        $options = [
            'outputType' => 'png',
            'eccLevel' => 'M',
            'scale' => 5,
        ];
        $qrcode = new \chillerlan\QRCode\QRCode($options);
        return $qrcode->render($data);
    }
    
    // Fallback: Use QR code API
    $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($data);
    return file_get_contents($apiUrl);
}

// Get parameters
$tableId = $_GET['table'] ?? null;
$uuid = $_GET['uuid'] ?? null;
$baseUrl = $_GET['base_url'] ?? 'http://localhost:8080/menu-scanner/public/index.php';

// If table ID provided, fetch from database
if ($tableId) {
    $table = dbFetchOne("SELECT * FROM tables WHERE id = ?", [$tableId]);
    if ($table) {
        $uuid = $table['qr_code_uuid'];
        $tableNumber = $table['table_number'];
    }
}

if (!$uuid) {
    die('Table UUID is required');
}

// Generate menu URL
$menuUrl = $baseUrl . '?table=' . $uuid;

// Generate QR code
$qrCodeData = generateQrCode($menuUrl);

// Output as PNG
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="qr-table-' . ($tableNumber ?? substr($uuid, 0, 8)) . '.png"');
header('Content-Length: ' . strlen($qrCodeData));
echo $qrCodeData;
exit;
?>
