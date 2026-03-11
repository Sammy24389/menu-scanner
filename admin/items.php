<?php
/**
 * Menu Items Management
 */

session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Menu Items';
$action = $_GET['action'] ?? 'list';
$itemId = $_GET['id'] ?? null;
$filterCategory = $_GET['category'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token'] ?? '') {
        setFlashMessage('error', 'Invalid security token');
        redirect('items.php');
    }
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
            case 'edit':
                $categoryId = (int)$_POST['category_id'];
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                $price = (float)$_POST['price'];
                $isAvailable = isset($_POST['is_available']) ? 1 : 0;
                $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
                
                if (empty($name) || $categoryId <= 0) {
                    setFlashMessage('error', 'Name and category are required');
                } else {
                    // Handle image upload
                    $imagePath = null;
                    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $uploadResult = uploadImage($_FILES['image']);
                        if (isset($uploadResult['error'])) {
                            setFlashMessage('error', $uploadResult['error']);
                            redirect('items.php?action=' . $action . ($itemId ? '&id=' . $itemId : ''));
                        }
                        $imagePath = $uploadResult['success'];
                        
                        // Delete old image if editing
                        if ($_POST['action'] === 'edit' && $_POST['old_image']) {
                            deleteImage($_POST['old_image']);
                        }
                    } else {
                        $imagePath = $_POST['old_image'] ?? null;
                    }
                    
                    if ($_POST['action'] === 'add') {
                        dbInsert(
                            "INSERT INTO menu_items (category_id, name, description, price, image, is_available, is_featured) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)",
                            [$categoryId, $name, $description, $price, $imagePath, $isAvailable, $isFeatured]
                        );
                        setFlashMessage('success', 'Menu item added successfully');
                    } else {
                        dbExecute(
                            "UPDATE menu_items SET category_id = ?, name = ?, description = ?, price = ?, 
                             image = ?, is_available = ?, is_featured = ? WHERE id = ?",
                            [$categoryId, $name, $description, $price, $imagePath, $isAvailable, $isFeatured, $_POST['id']]
                        );
                        setFlashMessage('success', 'Menu item updated successfully');
                    }
                }
                redirect('items.php');
                break;
                
            case 'delete':
                // Delete associated image
                $item = dbFetchOne("SELECT image FROM menu_items WHERE id = ?", [$_POST['id']]);
                if ($item && $item['image']) {
                    deleteImage($item['image']);
                }
                dbExecute("DELETE FROM menu_items WHERE id = ?", [$_POST['id']]);
                setFlashMessage('success', 'Menu item deleted successfully');
                redirect('items.php');
                break;
                
            case 'toggle_available':
                dbExecute("UPDATE menu_items SET is_available = NOT is_available WHERE id = ?", [$_POST['id']]);
                setFlashMessage('success', 'Item availability updated');
                redirect('items.php');
                break;
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get item for edit
$editItem = null;
$variations = [];
if ($action === 'edit' && $itemId) {
    $editItem = dbFetchOne("SELECT * FROM menu_items WHERE id = ?", [$itemId]);
    if (!$editItem) {
        setFlashMessage('error', 'Item not found');
        redirect('items.php');
    }
    $variations = getItemVariations($itemId);
}

// Get all categories
$categories = getCategories(false);

// Get items with filter
if ($filterCategory) {
    $items = getMenuItemsByCategory($filterCategory, false);
} else {
    $items = getMenuItemsByCategory(null, false);
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-card-list"></i> Menu Items Management</h2>
    </div>
    <div class="col text-end">
        <?php if ($action === 'add' || $action === 'edit'): ?>
        <a href="items.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
        <?php else: ?>
        <div class="btn-group">
            <a href="items.php?action=add" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Item
            </a>
            <select class="btn btn-outline-secondary" onchange="location.href='items.php?category='+this.value">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $filterCategory == $cat['id'] ? 'selected' : '' ?>>
                    <?= sanitize($cat['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- Add/Edit Form -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><?= $action === 'add' ? 'Add New' : 'Edit' ?> Menu Item</h5>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="<?= $action ?>">
            <?php if ($action === 'edit'): ?>
            <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
            <input type="hidden" name="old_image" value="<?= $editItem['image'] ?>">
            <?php endif; ?>
            
            <div class="col-md-6">
                <label for="name" class="form-label">Item Name</label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="<?= $editItem ? sanitize($editItem['name']) : '' ?>" required>
            </div>
            
            <div class="col-md-3">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($editItem && $editItem['category_id'] == $cat['id']) || (!$editItem && isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : '' ?>>
                        <?= sanitize($cat['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="price" class="form-label">Price ($)</label>
                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0"
                       value="<?= $editItem ? $editItem['price'] : '0.00' ?>" required>
            </div>
            
            <div class="col-md-6">
                <label for="image" class="form-label">Image</label>
                <?php if ($editItem && $editItem['image']): ?>
                <div class="mb-2">
                    <img src="../uploads/<?= $editItem['image'] ?>" alt="Current Image" class="img-thumbnail" style="max-height: 100px;">
                    <p class="text-muted small">Current image. Upload new to replace.</p>
                </div>
                <?php endif; ?>
                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                <div class="form-text">Allowed: JPG, PNG, GIF, WEBP. Max: 5MB</div>
            </div>
            
            <div class="col-md-6">
                <label class="form-label d-block">Options</label>
                <div class="form-check form-switch d-inline-block me-3">
                    <input class="form-check-input" type="checkbox" id="is_available" name="is_available"
                           <?= (!$editItem || $editItem['is_available']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_available">Available</label>
                </div>
                <div class="form-check form-switch d-inline-block">
                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured"
                           <?= ($editItem && $editItem['is_featured']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_featured">Featured</label>
                </div>
            </div>
            
            <div class="col-12">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= $editItem ? sanitize($editItem['description']) : '' ?></textarea>
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> <?= $action === 'add' ? 'Add' : 'Update' ?> Item
                </button>
                <?php if ($action === 'edit' && $editItem): ?>
                <a href="variations.php?item=<?= $editItem['id'] ?>" class="btn btn-outline-info">
                    <i class="bi bi-layers"></i> Manage Variations
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<!-- Items List -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">No menu items found.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?php if ($item['image']): ?>
                            <img src="../uploads/<?= $item['image'] ?>" alt="<?= sanitize($item['name']) ?>" 
                                 class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                            <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center" 
                                 style="width: 60px; height: 60px;">
                                <i class="bi bi-image text-muted"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= sanitize($item['name']) ?></strong></td>
                        <td><?= sanitize($item['category_name']) ?></td>
                        <td><?= formatPrice($item['price']) ?></td>
                        <td>
                            <span class="badge bg-<?= $item['is_available'] ? 'success' : 'danger' ?>">
                                <?= $item['is_available'] ? 'Available' : 'Unavailable' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($item['is_featured']): ?>
                            <i class="bi bi-star-fill text-warning"></i>
                            <?php else: ?>
                            <i class="bi bi-star text-muted"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="items.php?action=edit&id=<?= $item['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="toggle_available">
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn btn-outline-<?= $item['is_available'] ? 'warning' : 'success' ?>" 
                                            title="<?= $item['is_available'] ? 'Mark Unavailable' : 'Mark Available' ?>">
                                        <i class="bi bi-<?= $item['is_available'] ? 'pause' : 'play' ?>-circle"></i>
                                    </button>
                                </form>
                                <a href="variations.php?item=<?= $item['id'] ?>" class="btn btn-outline-info" title="Variations">
                                    <i class="bi bi-layers"></i>
                                </a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this item?')">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
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
