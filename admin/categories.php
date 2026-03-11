<?php
/**
 * Categories Management
 */

session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Categories';
$action = $_GET['action'] ?? 'list';
$categoryId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token'] ?? '') {
        setFlashMessage('error', 'Invalid security token');
        redirect('categories.php');
    }
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
            case 'edit':
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                $sortOrder = (int)($_POST['sort_order'] ?? 0);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($name)) {
                    setFlashMessage('error', 'Category name is required');
                } else {
                    if ($_POST['action'] === 'add') {
                        dbInsert(
                            "INSERT INTO categories (name, description, sort_order, is_active) VALUES (?, ?, ?, ?)",
                            [$name, $description, $sortOrder, $isActive]
                        );
                        setFlashMessage('success', 'Category added successfully');
                    } else {
                        dbExecute(
                            "UPDATE categories SET name = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?",
                            [$name, $description, $sortOrder, $isActive, $_POST['id']]
                        );
                        setFlashMessage('success', 'Category updated successfully');
                    }
                }
                redirect('categories.php');
                break;
                
            case 'delete':
                dbExecute("DELETE FROM categories WHERE id = ?", [$_POST['id']]);
                setFlashMessage('success', 'Category deleted successfully');
                redirect('categories.php');
                break;
                
            case 'toggle_active':
                dbExecute("UPDATE categories SET is_active = NOT is_active WHERE id = ?", [$_POST['id']]);
                setFlashMessage('success', 'Category status updated');
                redirect('categories.php');
                break;
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get category for edit
$editCategory = null;
if ($action === 'edit' && $categoryId) {
    $editCategory = dbFetchOne("SELECT * FROM categories WHERE id = ?", [$categoryId]);
    if (!$editCategory) {
        setFlashMessage('error', 'Category not found');
        redirect('categories.php');
    }
}

// Get all categories
$categories = dbFetchAll("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-list-ul"></i> Categories Management</h2>
    </div>
    <div class="col text-end">
        <?php if ($action === 'add' || $action === 'edit'): ?>
        <a href="categories.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
        <?php else: ?>
        <a href="categories.php?action=add" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Category
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- Add/Edit Form -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><?= $action === 'add' ? 'Add New' : 'Edit' ?> Category</h5>
    </div>
    <div class="card-body">
        <form method="POST" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="<?= $action ?>">
            <?php if ($action === 'edit'): ?>
            <input type="hidden" name="id" value="<?= $editCategory['id'] ?>">
            <?php endif; ?>
            
            <div class="col-md-6">
                <label for="name" class="form-label">Category Name</label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="<?= $editCategory ? sanitize($editCategory['name']) : '' ?>" required>
            </div>
            
            <div class="col-md-2">
                <label for="sort_order" class="form-label">Sort Order</label>
                <input type="number" class="form-control" id="sort_order" name="sort_order" 
                       value="<?= $editCategory ? (int)$editCategory['sort_order'] : 0 ?>" min="0">
            </div>
            
            <div class="col-md-4">
                <label for="is_active" class="form-label d-block">Status</label>
                <div class="form-check form-switch d-inline-block">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                           <?= (!$editCategory || $editCategory['is_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
            
            <div class="col-12">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= $editCategory ? sanitize($editCategory['description']) : '' ?></textarea>
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> <?= $action === 'add' ? 'Add' : 'Update' ?> Category
                </button>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<!-- Categories List -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Items Count</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No categories found. Add your first category!</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                    <?php
                    $itemsCount = dbFetchOne("SELECT COUNT(*) as count FROM menu_items WHERE category_id = ?", [$category['id']]);
                    ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?= $category['sort_order'] ?></span></td>
                        <td><strong><?= sanitize($category['name']) ?></strong></td>
                        <td><?= sanitize(substr($category['description'] ?? '', 0, 50)) ?><?= strlen($category['description'] ?? '') > 50 ? '...' : '' ?></td>
                        <td>
                            <span class="badge bg-<?= $category['is_active'] ? 'success' : 'secondary' ?>">
                                <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-info"><?= $itemsCount['count'] ?> items</span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="categories.php?action=edit&id=<?= $category['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="id" value="<?= $category['id'] ?>">
                                    <button type="submit" class="btn btn-outline-<?= $category['is_active'] ? 'warning' : 'success' ?>" 
                                            title="<?= $category['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                        <i class="bi bi-<?= $category['is_active'] ? 'pause' : 'play' ?>-circle"></i>
                                    </button>
                                </form>
                                <a href="items.php?category=<?= $category['id'] ?>" class="btn btn-outline-info" title="View Items">
                                    <i class="bi bi-card-list"></i>
                                </a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this category? All items in this category will also be deleted.')">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $category['id'] ?>">
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
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
