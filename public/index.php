<?php
/**
 * Customer Menu View
 * Mobile-friendly menu display for customers scanning QR codes
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Get table from QR code
$tableUuid = $_GET['table'] ?? null;
$table = null;
$tableInfo = null;

if ($tableUuid) {
    $tableInfo = dbFetchOne("SELECT * FROM tables WHERE qr_code_uuid = ?", [$tableUuid]);
    if ($tableInfo) {
        $table = $tableInfo['table_number'];
    }
}

// Get menu data
$categories = getCategories(true);
$menuItems = getMenuItemsByCategory(null, true);

// Group items by category
$itemsByCategory = [];
foreach ($menuItems as $item) {
    $itemsByCategory[$item['category_id']][] = $item;
}

$pageTitle = $table ? 'Table ' . $table . ' Menu' : 'Menu';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/customer.css" rel="stylesheet">
    <meta name="theme-color" content="#667eea">
</head>
<body>
    <!-- Header -->
    <header class="header bg-gradient">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="mb-1">
                        <i class="bi bi-qr-code-scan"></i> Menu
                    </h1>
                    <?php if ($table): ?>
                    <p class="mb-0 opacity-75">
                        <i class="bi bi-table"></i> Table <?= sanitize($table) ?>
                    </p>
                    <?php else: ?>
                    <p class="mb-0 opacity-75">Welcome!</p>
                    <?php endif; ?>
                </div>
                <div class="col-auto">
                    <button class="btn btn-light btn-call-waiter" data-bs-toggle="modal" data-bs-target="#callWaiterModal">
                        <i class="bi bi-bell-fill"></i>
                        <span class="d-none d-sm-inline">Call Waiter</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Category Navigation (Sticky Tabs) -->
    <?php if (!empty($categories)): ?>
    <nav class="category-nav sticky-top">
        <div class="container">
            <ul class="nav nav-pills" id="categoryTabs">
                <?php foreach ($categories as $index => $category): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $index === 0 ? 'active' : '' ?>" 
                       href="#category-<?= $category['id'] ?>" 
                       data-bs-toggle="tab">
                        <?= sanitize($category['name']) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Menu Content -->
    <main class="py-4">
        <div class="container">
            <?php if (empty($categories)): ?>
            <div class="text-center py-5">
                <i class="bi bi-cone-striped display-1 text-warning"></i>
                <h3 class="mt-3">Menu Not Available</h3>
                <p class="text-muted">Please ask a staff member for assistance.</p>
            </div>
            <?php else: ?>
            <div class="menu-content">
                <?php foreach ($categories as $category): ?>
                <section id="category-<?= $category['id'] ?>" class="category-section mb-5">
                    <div class="category-header mb-3">
                        <h2 class="mb-1"><?= sanitize($category['name']) ?></h2>
                        <?php if ($category['description']): ?>
                        <p class="text-muted mb-0"><?= sanitize($category['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row g-3">
                        <?php
                        $categoryItems = $itemsByCategory[$category['id']] ?? [];
                        foreach ($categoryItems as $item):
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card menu-item-card h-100">
                                <?php if ($item['image']): ?>
                                <img src="../uploads/<?= $item['image'] ?>" 
                                     class="card-img-top menu-item-image" 
                                     alt="<?= sanitize($item['name']) ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title mb-0 flex-grow-1">
                                            <?= sanitize($item['name']) ?>
                                            <?php if ($item['is_featured']): ?>
                                            <i class="bi bi-star-fill text-warning" title="Featured"></i>
                                            <?php endif; ?>
                                        </h5>
                                        <span class="price-badge"><?= formatPrice($item['price']) ?></span>
                                    </div>
                                    <?php if ($item['description']): ?>
                                    <p class="card-text text-muted small"><?= sanitize($item['description']) ?></p>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $variations = getItemVariations($item['id']);
                                    if (!empty($variations)):
                                    ?>
                                    <div class="variations-section mt-2">
                                        <small class="text-muted">
                                            <i class="bi bi-sliders"></i> Options available
                                        </small>
                                        <select class="form-select form-select-sm mt-1 variation-select" data-item-id="<?= $item['id'] ?>">
                                            <?php foreach ($variations as $var): ?>
                                            <option value="<?= $var['id'] ?>" data-price="<?= $var['price_modifier'] ?>" <?= $var['is_default'] ? 'selected' : '' ?>>
                                                <?= sanitize($var['name']) ?>
                                                <?= $var['price_modifier'] != 0 ? ' (' . ($var['price_modifier'] > 0 ? '+' : '') . formatPrice($var['price_modifier']) . ')' : '' ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Call Waiter Modal -->
    <div class="modal fade" id="callWaiterModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <i class="bi bi-bell-fill text-primary"></i> Call Waitstaff
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (!$tableInfo): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        Please scan the QR code on your table to access the menu.
                    </div>
                    <?php else: ?>
                    <p class="mb-3">You're calling a waiter to <strong>Table <?= sanitize($table) ?></strong></p>
                    
                    <form id="callWaiterForm">
                        <input type="hidden" name="table_uuid" value="<?= $tableInfo['qr_code_uuid'] ?>">
                        <input type="hidden" name="table_id" value="<?= $tableInfo['id'] ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Reason for calling</label>
                            <select class="form-select" name="call_type" id="callType">
                                <option value="waiter">Waiter Assistance</option>
                                <option value="bill">Request Bill</option>
                                <option value="complaint">Complaint/Issue</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Additional Notes (Optional)</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Any specific request?"></textarea>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <?php if ($tableInfo): ?>
                    <button type="button" class="btn btn-primary" id="submitCallWaiter">
                        <i class="bi bi-bell"></i> Call Now
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-body py-4">
                    <i class="bi bi-check-circle-fill text-success display-1"></i>
                    <h4 class="mt-3">Request Sent!</h4>
                    <p class="text-muted">A staff member will assist you shortly.</p>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer py-3 mt-4">
        <div class="container text-center">
            <p class="text-muted mb-0">
                <i class="bi bi-qr-code-scan"></i> Menu Scanner System
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/customer.js"></script>
</body>
</html>
