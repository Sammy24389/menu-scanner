<?php
/**
 * Menu API Endpoint
 * Returns menu data in JSON format
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Get table info if provided
$tableUuid = $_GET['table'] ?? null;
$tableInfo = null;

if ($tableUuid) {
    $tableInfo = dbFetchOne("SELECT id, table_number, status FROM tables WHERE qr_code_uuid = ?", [$tableUuid]);
}

// Get categories
$categories = getCategories(true);

// Get menu items
$menuItems = getMenuItemsByCategory(null, true);

// Build response
$response = [
    'success' => true,
    'timestamp' => date('c'),
    'table' => $tableInfo ? [
        'id' => $tableInfo['id'],
        'number' => $tableInfo['table_number'],
    ] : null,
    'categories' => [],
];

// Group items by category
foreach ($categories as $category) {
    $categoryData = [
        'id' => $category['id'],
        'name' => $category['name'],
        'description' => $category['description'],
        'items' => [],
    ];
    
    foreach ($menuItems as $item) {
        if ($item['category_id'] == $category['id']) {
            $variations = getItemVariations($item['id']);
            
            $itemData = [
                'id' => $item['id'],
                'name' => $item['name'],
                'description' => $item['description'],
                'price' => (float)$item['price'],
                'image' => $item['image'] ? '../uploads/' . $item['image'] : null,
                'is_featured' => (bool)$item['is_featured'],
                'variations' => [],
            ];
            
            foreach ($variations as $var) {
                $itemData['variations'][] = [
                    'id' => $var['id'],
                    'name' => $var['name'],
                    'price_modifier' => (float)$var['price_modifier'],
                    'is_default' => (bool)$var['is_default'],
                ];
            }
            
            $categoryData['items'][] = $itemData;
        }
    }
    
    $response['categories'][] = $categoryData;
}

echo json_encode($response);
?>
