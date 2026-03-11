<?php
/**
 * Tables Management
 */

session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Tables';
$action = $_GET['action'] ?? 'list';
$tableId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token'] ?? '') {
        setFlashMessage('error', 'Invalid security token');
        redirect('tables.php');
    }
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
            case 'edit':
                $tableNumber = sanitize($_POST['table_number']);
                $status = $_POST['status'];
                
                if (empty($tableNumber)) {
                    setFlashMessage('error', 'Table number is required');
                } else {
                    if ($_POST['action'] === 'add') {
                        $uuid = generateUuid();
                        dbInsert(
                            "INSERT INTO tables (table_number, qr_code_uuid, status) VALUES (?, ?, ?)",
                            [$tableNumber, $uuid, $status]
                        );
                        setFlashMessage('success', 'Table added successfully');
                    } else {
                        dbExecute(
                            "UPDATE tables SET table_number = ?, status = ? WHERE id = ?",
                            [$tableNumber, $status, $_POST['id']]
                        );
                        setFlashMessage('success', 'Table updated successfully');
                    }
                }
                redirect('tables.php');
                break;
                
            case 'delete':
                dbExecute("DELETE FROM tables WHERE id = ?", [$_POST['id']]);
                setFlashMessage('success', 'Table deleted successfully');
                redirect('tables.php');
                break;
                
            case 'regenerate_qr':
                $uuid = generateUuid();
                dbExecute("UPDATE tables SET qr_code_uuid = ? WHERE id = ?", [$uuid, $_POST['id']]);
                setFlashMessage('success', 'QR code regenerated successfully');
                redirect('tables.php');
                break;
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get table for edit
$editTable = null;
if ($action === 'edit' && $tableId) {
    $editTable = dbFetchOne("SELECT * FROM tables WHERE id = ?", [$tableId]);
    if (!$editTable) {
        setFlashMessage('error', 'Table not found');
        redirect('tables.php');
    }
}

// Get all tables
$tables = getTables();

// Base URL for QR codes (adjust for your setup)
$baseUrl = 'http://localhost:8080/menu-scanner/public/index.php';
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-table"></i> Tables Management</h2>
    </div>
    <div class="col text-end">
        <?php if ($action === 'add' || $action === 'edit'): ?>
        <a href="tables.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
        <?php else: ?>
        <a href="tables.php?action=add" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Table
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- Add/Edit Form -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><?= $action === 'add' ? 'Add New' : 'Edit' ?> Table</h5>
    </div>
    <div class="card-body">
        <form method="POST" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="<?= $action ?>">
            <?php if ($action === 'edit'): ?>
            <input type="hidden" name="id" value="<?= $editTable['id'] ?>">
            <?php endif; ?>
            
            <div class="col-md-6">
                <label for="table_number" class="form-label">Table Number</label>
                <input type="text" class="form-control" id="table_number" name="table_number" 
                       value="<?= $editTable ? sanitize($editTable['table_number']) : '' ?>" required>
            </div>
            
            <div class="col-md-6">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="active" <?= ($editTable && $editTable['status'] === 'active') || !$editTable ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($editTable && $editTable['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    <option value="maintenance" <?= ($editTable && $editTable['status'] === 'maintenance') ? 'selected' : '' ?>>Maintenance</option>
                </select>
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> <?= $action === 'add' ? 'Add' : 'Update' ?> Table
                </button>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<!-- Tables List -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Table Number</th>
                        <th>QR Code UUID</th>
                        <th>Status</th>
                        <th>QR Code</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tables)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No tables found. Add your first table!</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($tables as $table): ?>
                    <tr>
                        <td><?= $table['id'] ?></td>
                        <td><strong><?= sanitize($table['table_number']) ?></strong></td>
                        <td><code><?= substr($table['qr_code_uuid'], 0, 8) ?>...</code></td>
                        <td>
                            <span class="badge bg-<?= $table['status'] === 'active' ? 'success' : ($table['status'] === 'inactive' ? 'secondary' : 'warning') ?>">
                                <?= ucfirst($table['status']) ?>
                            </span>
                        </td>
                        <td>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=<?= urlencode($baseUrl . '?table=' . $table['qr_code_uuid']) ?>" 
                                 alt="QR Code" class="img-thumbnail" style="width: 60px; height: 60px;">
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="tables.php?action=edit&id=<?= $table['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-outline-info" title="View QR" 
                                        data-bs-toggle="modal" data-bs-target="#qrModal<?= $table['id'] ?>">
                                    <i class="bi bi-qr-code"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Regenerate QR code?')">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="regenerate_qr">
                                    <input type="hidden" name="id" value="<?= $table['id'] ?>">
                                    <button type="submit" class="btn btn-outline-warning" title="Regenerate QR">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this table?')">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $table['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- QR Modal -->
                    <div class="modal fade" id="qrModal<?= $table['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">QR Code for <?= sanitize($table['table_number']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?= urlencode($baseUrl . '?table=' . $table['qr_code_uuid']) ?>" 
                                         alt="QR Code" class="img-fluid mb-3">
                                    <p class="text-muted small">
                                        URL: <?= $baseUrl ?>?table=<?= $table['qr_code_uuid'] ?>
                                    </p>
                                    <button onclick="window.print()" class="btn btn-primary">
                                        <i class="bi bi-printer"></i> Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
