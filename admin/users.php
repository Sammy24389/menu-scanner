<?php
/**
 * Users & Roles Management
 * Role-based access control administration
 */

session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/rbac.php';
requireLogin();
require_permission('settings'); // Only owner/manager can manage users

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Users & Roles';
$action = $_GET['action'] ?? 'list';
$userId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token'] ?? '') {
        setFlashMessage('error', 'Invalid security token');
        redirect('users.php');
    }
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_user':
            case 'update_user':
                $username = sanitize($_POST['username']);
                $email = sanitize($_POST['email']);
                $password = $_POST['password'] ?? '';
                $roleId = (int)$_POST['role_id'];
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($username)) {
                    setFlashMessage('error', 'Username is required');
                } else {
                    if ($_POST['action'] === 'add_user') {
                        if (empty($password)) {
                            setFlashMessage('error', 'Password is required for new users');
                        } else {
                            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                            dbInsert(
                                "INSERT INTO admin_users (username, email, password_hash, role_id, is_active) VALUES (?, ?, ?, ?, ?)",
                                [$username, $email, $passwordHash, $roleId, $isActive]
                            );
                            setFlashMessage('success', 'User added successfully');
                        }
                    } else {
                        if (!empty($password)) {
                            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                            dbExecute(
                                "UPDATE admin_users SET username = ?, email = ?, password_hash = ?, role_id = ?, is_active = ? WHERE id = ?",
                                [$username, $email, $passwordHash, $roleId, $isActive, $_POST['id']]
                            );
                        } else {
                            dbExecute(
                                "UPDATE admin_users SET username = ?, email = ?, role_id = ?, is_active = ? WHERE id = ?",
                                [$username, $email, $roleId, $isActive, $_POST['id']]
                            );
                        }
                        setFlashMessage('success', 'User updated successfully');
                    }
                }
                redirect('users.php');
                break;
                
            case 'delete_user':
                if ($_POST['id'] == $_SESSION['admin_id']) {
                    setFlashMessage('error', 'You cannot delete your own account');
                } else {
                    dbExecute("DELETE FROM admin_users WHERE id = ?", [$_POST['id']]);
                    setFlashMessage('success', 'User deleted successfully');
                }
                redirect('users.php');
                break;
                
            case 'add_role':
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                $permissions = $_POST['permissions'] ?? [];
                
                if (empty($name)) {
                    setFlashMessage('error', 'Role name is required');
                } else {
                    dbInsert(
                        "INSERT INTO roles (name, description, permissions) VALUES (?, ?, ?)",
                        [$name, $description, json_encode($permissions)]
                    );
                    setFlashMessage('success', 'Role created successfully');
                }
                redirect('users.php');
                break;
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get all users
$users = dbFetchAll(
    "SELECT u.*, r.name as role_name 
     FROM admin_users u 
     LEFT JOIN roles r ON u.role_id = r.id 
     ORDER BY u.created_at DESC"
);

// Get all roles
$roles = dbFetchAll("SELECT * FROM roles ORDER BY id");

// Get permissions schema
$permissionSchema = [
    'Dashboard' => ['dashboard', 'View dashboard and analytics'],
    ['Tables' => ['tables', 'Manage tables and QR codes']],
    ['Categories' => ['categories', 'Manage menu categories']],
    ['Menu Items' => ['items', 'Manage menu items']],
    ['Orders' => ['orders', 'Manage customer orders']],
    ['Reservations' => ['reservations', 'Manage reservations']],
    ['Kitchen' => ['kitchen', 'Access kitchen display']],
    ['Inventory' => ['inventory', 'Manage inventory']],
    ['Reports' => ['reports', 'View reports and analytics']],
    ['Staff' => ['staff', 'Manage staff accounts']],
    ['Settings' => ['settings', 'Manage system settings']],
    ['Payments' => ['payments', 'Process payments']],
];
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-people"></i> Users & Roles Management</h2>
    </div>
    <div class="col text-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus"></i> Add User
        </button>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addRoleModal">
            <i class="bi bi-shield-plus"></i> Add Role
        </button>
    </div>
</div>

<!-- Users List -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-people"></i> System Users</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><strong><?= sanitize($user['username']) ?></strong></td>
                        <td><?= sanitize($user['email'] ?? '—') ?></td>
                        <td><span class="badge bg-primary"><?= sanitize($user['role_name'] ?? 'No Role') ?></span></td>
                        <td>
                            <span class="badge bg-<?= $user['is_active'] ? 'success' : 'secondary' ?>">
                                <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td><?= $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : '—' ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($user['id'] != $_SESSION['admin_id']): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this user?')">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Roles List -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-shield-check"></i> Roles & Permissions</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($roles as $role): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><?= sanitize($role['name']) ?></h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small"><?= sanitize($role['description']) ?></p>
                        <?php
                        $permissions = json_decode($role['permissions'], true);
                        if ($permissions && !isset($permissions['all'])):
                        ?>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach (array_keys($permissions) as $perm): ?>
                            <span class="badge bg-info"><?= ucfirst($perm) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php elseif (isset($permissions['all'])): ?>
                        <span class="badge bg-warning">All Permissions</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="add_user">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role_id" required>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>"><?= sanitize($role['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" checked>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="add_role">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Role Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-block">Permissions</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[dashboard]" id="perm_dashboard">
                                    <label class="form-check-label" for="perm_dashboard">Dashboard</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[tables]" id="perm_tables">
                                    <label class="form-check-label" for="perm_tables">Tables</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[categories]" id="perm_categories">
                                    <label class="form-check-label" for="perm_categories">Categories</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[items]" id="perm_items">
                                    <label class="form-check-label" for="perm_items">Menu Items</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[orders]" id="perm_orders">
                                    <label class="form-check-label" for="perm_orders">Orders</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[reservations]" id="perm_reservations">
                                    <label class="form-check-label" for="perm_reservations">Reservations</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[kitchen]" id="perm_kitchen">
                                    <label class="form-check-label" for="perm_kitchen">Kitchen</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[inventory]" id="perm_inventory">
                                    <label class="form-check-label" for="perm_inventory">Inventory</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[reports]" id="perm_reports">
                                    <label class="form-check-label" for="perm_reports">Reports</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[settings]" id="perm_settings">
                                    <label class="form-check-label" for="perm_settings">Settings</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    // Implement edit functionality
    alert('Edit user: ' + user.username);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
