<?php
/**
 * Service Calls Management
 * Live dashboard for managing customer service calls
 */

session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Service Calls';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token'] ?? '') {
        setFlashMessage('error', 'Invalid security token');
        redirect('service-calls.php');
    }
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'assign':
                $waitstaffId = !empty($_POST['waitstaff_id']) ? (int)$_POST['waitstaff_id'] : null;
                dbExecute(
                    "UPDATE service_calls SET status = 'assigned', waitstaff_id = ?, assigned_at = NOW() WHERE id = ?",
                    [$waitstaffId, $_POST['id']]
                );
                setFlashMessage('success', 'Service call assigned');
                redirect('service-calls.php');
                break;
                
            case 'complete':
                dbExecute(
                    "UPDATE service_calls SET status = 'completed', resolved_at = NOW() WHERE id = ?",
                    [$_POST['id']]
                );
                setFlashMessage('success', 'Service call marked as completed');
                redirect('service-calls.php');
                break;
                
            case 'reopen':
                dbExecute(
                    "UPDATE service_calls SET status = 'pending', waitstaff_id = NULL, resolved_at = NULL WHERE id = ?",
                    [$_POST['id']]
                );
                setFlashMessage('success', 'Service call reopened');
                redirect('service-calls.php');
                break;
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get filter
$filter = $_GET['filter'] ?? 'active';

// Get service calls based on filter
if ($filter === 'completed') {
    $calls = dbFetchAll(
        "SELECT sc.*, t.table_number, t.qr_code_uuid, w.name as waitstaff_name
         FROM service_calls sc
         JOIN tables t ON sc.table_id = t.id
         LEFT JOIN waitstaff w ON sc.waitstaff_id = w.id
         WHERE sc.status = 'completed'
         ORDER BY sc.resolved_at DESC
         LIMIT 50"
    );
} else {
    $calls = getActiveServiceCalls();
}

// Get active waitstaff for assignment
$activeWaitstaff = dbFetchAll("SELECT * FROM waitstaff WHERE is_active = 1 ORDER BY name");

// Get statistics
$stats = [
    'pending' => dbFetchOne("SELECT COUNT(*) as count FROM service_calls WHERE status = 'pending'")['count'],
    'assigned' => dbFetchOne("SELECT COUNT(*) as count FROM service_calls WHERE status = 'assigned'")['count'],
    'completed_today' => dbFetchOne(
        "SELECT COUNT(*) as count FROM service_calls 
         WHERE status = 'completed' AND DATE(resolved_at) = CURDATE()"
    )['count'],
    'completed_total' => dbFetchOne("SELECT COUNT(*) as count FROM service_calls WHERE status = 'completed'")['count'],
];
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-bell"></i> Service Calls</h2>
    </div>
    <div class="col text-end">
        <div class="btn-group" role="group">
            <a href="service-calls.php?filter=active" class="btn btn-<?= $filter === 'active' ? 'primary' : 'outline-primary' ?>">
                Active Calls
            </a>
            <a href="service-calls.php?filter=completed" class="btn btn-<?= $filter === 'completed' ? 'primary' : 'outline-primary' ?>">
                Completed
            </a>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-warning text-white h-100">
            <div class="card-body text-center">
                <i class="bi bi-exclamation-triangle display-6"></i>
                <h3 class="mt-2"><?= $stats['pending'] ?></h3>
                <p class="mb-0">Pending</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body text-center">
                <i class="bi bi-person-check display-6"></i>
                <h3 class="mt-2"><?= $stats['assigned'] ?></h3>
                <p class="mb-0">Assigned</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body text-center">
                <i class="bi bi-check-circle display-6"></i>
                <h3 class="mt-2"><?= $stats['completed_today'] ?></h3>
                <p class="mb-0">Completed Today</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-white h-100">
            <div class="card-body text-center">
                <i class="bi bi-archive display-6"></i>
                <h3 class="mt-2"><?= $stats['completed_total'] ?></h3>
                <p class="mb-0">Total Completed</p>
            </div>
        </div>
    </div>
</div>

<!-- Auto-refresh for active calls -->
<?php if ($filter === 'active'): ?>
<div class="alert alert-info d-flex align-items-center">
    <i class="bi bi-info-circle me-2"></i>
    <div>
        <strong>Live Updates:</strong> This page will auto-refresh every 30 seconds to show new calls.
        <button type="button" class="btn btn-sm btn-outline-info ms-3" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Refresh Now
        </button>
    </div>
</div>
<meta http-equiv="refresh" content="30">
<?php endif; ?>

<!-- Service Calls List -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <?= $filter === 'active' ? 'Active Service Calls' : 'Completed Service Calls' ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($calls)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <p class="text-muted mt-3">
                <?= $filter === 'active' ? 'No active service calls. All caught up!' : 'No completed calls yet.' ?>
            </p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Table</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($calls as $call): ?>
                    <tr class="<?= $call['status'] === 'pending' ? 'table-warning' : '' ?>">
                        <td>#<?= $call['id'] ?></td>
                        <td>
                            <strong><?= sanitize($call['table_number']) ?></strong>
                            <?php if (!empty($call['qr_code_uuid'])): ?>
                            <br>
                            <small class="text-muted">
                                <a href="../public/index.php?table=<?= $call['qr_code_uuid'] ?>" target="_blank">
                                    <i class="bi bi-box-arrow-up-right"></i> View Menu
                                </a>
                            </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $call['call_type'] === 'waiter' ? 'primary' : ($call['call_type'] === 'bill' ? 'success' : ($call['call_type'] === 'complaint' ? 'danger' : 'secondary')) ?>">
                                <?= ucfirst($call['call_type']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $call['status'] === 'pending' ? 'warning' : 'info' ?>">
                                <?= ucfirst($call['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($call['waitstaff_name']): ?>
                            <i class="bi bi-person-circle"></i> <?= sanitize($call['waitstaff_name']) ?>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= date('M j, g:i A', strtotime($call['created_at'])) ?>
                            <?php if ($call['status'] === 'completed' && $call['resolved_at']): ?>
                            <br>
                            <small class="text-success">
                                Resolved: <?= date('g:i A', strtotime($call['resolved_at'])) ?>
                            </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($call['status'] !== 'completed'): ?>
                            <div class="btn-group btn-group-sm">
                                <?php if ($call['status'] === 'pending'): ?>
                                <button type="button" class="btn btn-outline-primary" 
                                        data-bs-toggle="modal" data-bs-target="#assignModal<?= $call['id'] ?>">
                                    <i class="bi bi-person-plus"></i> Assign
                                </button>
                                <?php endif; ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="complete">
                                    <input type="hidden" name="id" value="<?= $call['id'] ?>">
                                    <button type="submit" class="btn btn-outline-success">
                                        <i class="bi bi-check-circle"></i> Complete
                                    </button>
                                </form>
                            </div>
                            <?php else: ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Reopen this service call?')">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="reopen">
                                <input type="hidden" name="id" value="<?= $call['id'] ?>">
                                <button type="submit" class="btn btn-outline-warning btn-sm">
                                    <i class="bi bi-arrow-clockwise"></i> Reopen
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <!-- Assign Modal -->
                    <?php if ($call['status'] === 'pending'): ?>
                    <div class="modal fade" id="assignModal<?= $call['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="assign">
                                    <input type="hidden" name="id" value="<?= $call['id'] ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Assign Service Call #<?= $call['id'] ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Assign to Waitstaff</label>
                                            <select class="form-select" name="waitstaff_id">
                                                <option value="">-- Unassigned --</option>
                                                <?php foreach ($activeWaitstaff as $staff): ?>
                                                <option value="<?= $staff['id'] ?>"><?= sanitize($staff['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <p class="text-muted small mb-0">
                                            Table: <?= sanitize($call['table_number']) ?> | Type: <?= ucfirst($call['call_type']) ?>
                                        </p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle"></i> Assign
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
