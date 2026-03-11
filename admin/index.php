<?php
/**
 * Admin Dashboard
 */

session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';

// Get statistics
$stats = [
    'tables' => dbFetchOne("SELECT COUNT(*) as count FROM tables")['count'],
    'categories' => dbFetchOne("SELECT COUNT(*) as count FROM categories")['count'],
    'items' => dbFetchOne("SELECT COUNT(*) as count FROM menu_items")['count'],
    'waitstaff' => dbFetchOne("SELECT COUNT(*) as count FROM waitstaff WHERE is_active = 1")['count'],
    'pending_calls' => dbFetchOne("SELECT COUNT(*) as count FROM service_calls WHERE status = 'pending'")['count'],
];

// Get recent service calls
$recentCalls = dbFetchAll(
    "SELECT sc.*, t.table_number, w.name as waitstaff_name 
     FROM service_calls sc 
     JOIN tables t ON sc.table_id = t.id 
     LEFT JOIN waitstaff w ON sc.waitstaff_id = w.id 
     ORDER BY sc.created_at DESC 
     LIMIT 5"
);

// Get low stock items (unavailable)
$unavailableItems = dbFetchAll(
    "SELECT name FROM menu_items WHERE is_available = 0 LIMIT 5"
);
?>

<div class="row">
    <div class="col-12">
        <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
        <p class="text-muted">Welcome back, <?= sanitize($_SESSION['admin_username']) ?>!</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4 col-lg-2">
        <div class="card bg-primary text-white h-100">
            <div class="card-body text-center">
                <i class="bi bi-table display-4"></i>
                <h3 class="mt-2"><?= $stats['tables'] ?></h3>
                <p class="mb-0">Tables</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card bg-success text-white h-100">
            <div class="card-body text-center">
                <i class="bi bi-list-ul display-4"></i>
                <h3 class="mt-2"><?= $stats['categories'] ?></h3>
                <p class="mb-0">Categories</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card bg-info text-white h-100">
            <div class="card-body text-center">
                <i class="bi bi-card-list display-4"></i>
                <h3 class="mt-2"><?= $stats['items'] ?></h3>
                <p class="mb-0">Menu Items</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card bg-warning text-white h-100">
            <div class="card-body text-center">
                <i class="bi bi-people display-4"></i>
                <h3 class="mt-2"><?= $stats['waitstaff'] ?></h3>
                <p class="mb-0">Waitstaff</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card bg-danger text-white h-100">
            <div class="card-body text-center">
                <i class="bi bi-bell display-4"></i>
                <h3 class="mt-2"><?= $stats['pending_calls'] ?></h3>
                <p class="mb-0">Pending Calls</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card bg-secondary text-white h-100">
            <div class="card-body text-center">
                <a href="service-calls.php" class="text-white text-decoration-none">
                    <i class="bi bi-arrow-right-circle display-4"></i>
                    <h3 class="mt-2">View All</h3>
                    <p class="mb-0">Service Calls</p>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Service Calls -->
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Service Calls</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentCalls)): ?>
                <p class="text-muted text-center">No recent service calls</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Table</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentCalls as $call): ?>
                            <tr>
                                <td><strong><?= sanitize($call['table_number']) ?></strong></td>
                                <td>
                                    <span class="badge bg-<?= $call['call_type'] === 'waiter' ? 'primary' : 'secondary' ?>">
                                        <?= ucfirst($call['call_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $call['status'] === 'pending' ? 'warning' : ($call['status'] === 'assigned' ? 'info' : 'success') ?>">
                                        <?= ucfirst($call['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M j, g:i A', strtotime($call['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="service-calls.php" class="btn btn-sm btn-outline-primary">View All Calls</a>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-lightning-charge"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="tables.php?action=add" class="btn btn-outline-primary">
                        <i class="bi bi-plus-circle"></i> Add New Table
                    </a>
                    <a href="categories.php?action=add" class="btn btn-outline-success">
                        <i class="bi bi-plus-circle"></i> Add Category
                    </a>
                    <a href="items.php?action=add" class="btn btn-outline-info">
                        <i class="bi bi-plus-circle"></i> Add Menu Item
                    </a>
                    <a href="waitstaff.php?action=add" class="btn btn-outline-warning">
                        <i class="bi bi-plus-circle"></i> Add Waitstaff
                    </a>
                    <hr>
                    <a href="../public/index.php" target="_blank" class="btn btn-outline-secondary">
                        <i class="bi bi-eye"></i> View Customer Menu
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
