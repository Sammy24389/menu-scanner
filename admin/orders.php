<?php
/**
 * Order Management - Online Ordering System
 */

session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Orders';
$filter = $_GET['filter'] ?? 'all';
$status = $_GET['status'] ?? '';

// Get orders
$sql = "SELECT o.*, t.table_number, w.name as waiter_name, c.name as customer_name
        FROM orders o
        LEFT JOIN tables t ON o.table_id = t.id
        LEFT JOIN waitstaff w ON o.assigned_waiter_id = w.id
        LEFT JOIN customers c ON o.customer_email = c.email";

$where = [];
$params = [];

if ($filter === 'pending') {
    $where[] = "o.status IN ('pending', 'confirmed')";
} elseif ($filter === 'completed') {
    $where[] = "o.status IN ('served', 'completed')";
} elseif ($filter === 'cancelled') {
    $where[] = "o.status = 'cancelled'";
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY o.created_at DESC";

$orders = dbFetchAll($sql, $params);

// Get statistics
$stats = [
    'pending' => dbFetchOne("SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'confirmed')")['count'],
    'preparing' => dbFetchOne("SELECT COUNT(*) as count FROM orders WHERE status = 'preparing'")['count'],
    'today' => dbFetchOne("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()")['count'],
    'revenue_today' => dbFetchOne("SELECT SUM(total) as total FROM orders WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid'")['total'] ?? 0,
];
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-receipt"></i> Order Management</h2>
    </div>
    <div class="col text-end">
        <a href="kitchen.php" class="btn btn-outline-primary">
            <i class="bi bi-display"></i> Kitchen Display
        </a>
    </div>
</div>

<!-- Statistics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-warning text-white h-100">
            <div class="card-body text-center">
                <i class="bi bi-clock-history display-4"></i>
                <h3 class="mt-2"><?= $stats['pending'] ?></h3>
                <p class="mb-0">Pending Orders</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body text-center">
                <i class="bi bi-fire display-4"></i>
                <h3 class="mt-2"><?= $stats['preparing'] ?></h3>
                <p class="mb-0">Preparing</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body text-center">
                <i class="bi bi-check-circle display-4"></i>
                <h3 class="mt-2"><?= $stats['today'] ?></h3>
                <p class="mb-0">Orders Today</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body text-center">
                <i class="bi bi-currency-dollar display-4"></i>
                <h3 class="mt-2"><?= formatPrice($stats['revenue_today']) ?></h3>
                <p class="mb-0">Revenue Today</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="btn-group" role="group">
            <a href="orders.php?filter=all" class="btn btn-<?= $filter === 'all' ? 'primary' : 'outline-primary' ?>">All Orders</a>
            <a href="orders.php?filter=pending" class="btn btn-<?= $filter === 'pending' ? 'primary' : 'outline-primary' ?>">Pending</a>
            <a href="orders.php?filter=completed" class="btn btn-<?= $filter === 'completed' ? 'primary' : 'outline-primary' ?>">Completed</a>
            <a href="orders.php?filter=cancelled" class="btn btn-<?= $filter === 'cancelled' ? 'primary' : 'outline-primary' ?>">Cancelled</a>
        </div>
    </div>
</div>

<!-- Orders List -->
<div class="card">
    <div class="card-body">
        <?php if (empty($orders)): ?>
        <p class="text-muted text-center">No orders found</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Table/Customer</th>
                        <th>Type</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <?php
                    $itemCount = dbFetchOne("SELECT COUNT(*) as count FROM order_items WHERE order_id = ?", [$order['id']]);
                    ?>
                    <tr>
                        <td><strong>#<?= sanitize($order['order_number']) ?></strong></td>
                        <td>
                            <?php if ($order['table_id']): ?>
                            <span class="badge bg-primary">Table <?= sanitize($order['table_number']) ?></span>
                            <?php else: ?>
                            <?= sanitize($order['customer_name'] ?? 'Guest') ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $order['order_type'] === 'dine_in' ? 'success' : 'secondary' ?>">
                                <?= ucfirst(str_replace('_', ' ', $order['order_type'])) ?>
                            </span>
                        </td>
                        <td><?= $itemCount['count'] ?> items</td>
                        <td><strong><?= formatPrice($order['total']) ?></strong></td>
                        <td>
                            <span class="badge bg-<?= $order['status'] === 'pending' ? 'warning' : ($order['status'] === 'completed' ? 'success' : 'info') ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                                <?= ucfirst($order['payment_status']) ?>
                            </span>
                        </td>
                        <td><?= date('M j, g:i A', strtotime($order['created_at'])) ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="order-view.php?id=<?= $order['id'] ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                                <button type="button" class="btn btn-outline-success" onclick="updateOrderStatus(<?= $order['id'] ?>, 'completed')">
                                    <i class="bi bi-check-circle"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
