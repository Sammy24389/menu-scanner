<?php
/**
 * Waitstaff Management
 */

session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Waitstaff';
$action = $_GET['action'] ?? 'list';
$staffId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token'] ?? '') {
        setFlashMessage('error', 'Invalid security token');
        redirect('waitstaff.php');
    }
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
            case 'edit':
                $name = sanitize($_POST['name']);
                $pinCode = sanitize($_POST['pin_code']);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($name)) {
                    setFlashMessage('error', 'Name is required');
                } elseif (strlen($pinCode) < 4) {
                    setFlashMessage('error', 'PIN must be at least 4 digits');
                } else {
                    // Check PIN uniqueness
                    $existingPin = dbFetchOne(
                        "SELECT id FROM waitstaff WHERE pin_code = ? AND id != ?",
                        [$pinCode, $_POST['id'] ?? 0]
                    );
                    if ($existingPin) {
                        setFlashMessage('error', 'PIN code already in use');
                    } else {
                        if ($_POST['action'] === 'add') {
                            dbInsert(
                                "INSERT INTO waitstaff (name, pin_code, is_active) VALUES (?, ?, ?)",
                                [$name, $pinCode, $isActive]
                            );
                            setFlashMessage('success', 'Waitstaff added successfully');
                        } else {
                            dbExecute(
                                "UPDATE waitstaff SET name = ?, pin_code = ?, is_active = ? WHERE id = ?",
                                [$name, $pinCode, $isActive, $_POST['id']]
                            );
                            setFlashMessage('success', 'Waitstaff updated successfully');
                        }
                    }
                }
                redirect('waitstaff.php');
                break;
                
            case 'delete':
                dbExecute("DELETE FROM waitstaff WHERE id = ?", [$_POST['id']]);
                setFlashMessage('success', 'Waitstaff deleted successfully');
                redirect('waitstaff.php');
                break;
                
            case 'toggle_active':
                dbExecute("UPDATE waitstaff SET is_active = NOT is_active WHERE id = ?", [$_POST['id']]);
                setFlashMessage('success', 'Status updated');
                redirect('waitstaff.php');
                break;
                
            case 'regenerate_pin':
                $newPin = generatePin();
                dbExecute("UPDATE waitstaff SET pin_code = ? WHERE id = ?", [$newPin, $_POST['id']]);
                setFlashMessage('success', 'New PIN generated: ' . $newPin);
                redirect('waitstaff.php');
                break;
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get staff for edit
$editStaff = null;
if ($action === 'edit' && $staffId) {
    $editStaff = dbFetchOne("SELECT * FROM waitstaff WHERE id = ?", [$staffId]);
    if (!$editStaff) {
        setFlashMessage('error', 'Staff member not found');
        redirect('waitstaff.php');
    }
}

// Get all waitstaff with table assignments
$waitstaff = dbFetchAll(
    "SELECT w.*, t.table_number 
     FROM waitstaff w 
     LEFT JOIN tables t ON w.current_table_id = t.id 
     ORDER BY w.name ASC"
);

// Get all tables for assignment dropdown
$tables = getTables('active');
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-people"></i> Waitstaff Management</h2>
    </div>
    <div class="col text-end">
        <?php if ($action === 'add' || $action === 'edit'): ?>
        <a href="waitstaff.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
        <?php else: ?>
        <a href="waitstaff.php?action=add" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Staff
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- Add/Edit Form -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><?= $action === 'add' ? 'Add New' : 'Edit' ?> Staff Member</h5>
    </div>
    <div class="card-body">
        <form method="POST" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="<?= $action ?>">
            <?php if ($action === 'edit'): ?>
            <input type="hidden" name="id" value="<?= $editStaff['id'] ?>">
            <?php endif; ?>
            
            <div class="col-md-6">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="<?= $editStaff ? sanitize($editStaff['name']) : '' ?>" required>
            </div>
            
            <div class="col-md-3">
                <label for="pin_code" class="form-label">PIN Code</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="pin_code" name="pin_code" 
                           value="<?= $editStaff ? sanitize($editStaff['pin_code']) : generatePin() ?>" 
                           pattern="[0-9]{4,6}" required>
                    <?php if ($action === 'edit'): ?>
                    <button type="button" class="btn btn-outline-secondary" onclick="generateNewPin()">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <div class="form-text">4-6 digits for staff login</div>
            </div>
            
            <div class="col-md-3">
                <label class="form-label d-block">Status</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                           <?= (!$editStaff || $editStaff['is_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> <?= $action === 'add' ? 'Add' : 'Update' ?> Staff
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function generateNewPin() {
    document.getElementById('pin_code').value = Math.floor(1000 + Math.random() * 9000);
}
</script>
<?php else: ?>
<!-- Waitstaff List -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>PIN Code</th>
                        <th>Status</th>
                        <th>Current Table</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($waitstaff)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No waitstaff found. Add your first staff member!</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($waitstaff as $staff): ?>
                    <tr>
                        <td><?= $staff['id'] ?></td>
                        <td><strong><?= sanitize($staff['name']) ?></strong></td>
                        <td>
                            <code class="fs-5"><?= $staff['pin_code'] ?></code>
                        </td>
                        <td>
                            <span class="badge bg-<?= $staff['is_active'] ? 'success' : 'secondary' ?>">
                                <?= $staff['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($staff['table_number']): ?>
                            <span class="badge bg-primary"><?= sanitize($staff['table_number']) ?></span>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="waitstaff.php?action=edit&id=<?= $staff['id'] ?>" 
                                   class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="id" value="<?= $staff['id'] ?>">
                                    <button type="submit" class="btn btn-outline-<?= $staff['is_active'] ? 'warning' : 'success' ?>" 
                                            title="<?= $staff['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                        <i class="bi bi-<?= $staff['is_active'] ? 'pause' : 'play' ?>-circle"></i>
                                    </button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="regenerate_pin">
                                    <input type="hidden" name="id" value="<?= $staff['id'] ?>">
                                    <button type="submit" class="btn btn-outline-info" title="Regenerate PIN">
                                        <i class="bi bi-key"></i>
                                    </button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this staff member?')">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $staff['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Staff Login Info Card -->
<div class="alert alert-info mt-4">
    <h5><i class="bi bi-info-circle"></i> Staff Login Information</h5>
    <p class="mb-0">
        Staff members can log in using their PIN code to view assigned tables and respond to service calls.
        Share each staff member's PIN code with them securely.
    </p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
