<?php
/**
 * Item Variations Management
 * Variations allow items to have options like size, extras, etc.
 */

session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Item Variations';
$itemId = $_GET['item'] ?? null;
$action = $_GET['action'] ?? 'list';
$variationId = $_GET['id'] ?? null;

// Get item details
if (!$itemId) {
    setFlashMessage('error', 'No item specified');
    redirect('items.php');
}

$item = dbFetchOne("SELECT * FROM menu_items WHERE id = ?", [$itemId]);
if (!$item) {
    setFlashMessage('error', 'Item not found');
    redirect('items.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token'] ?? '') {
        setFlashMessage('error', 'Invalid security token');
        redirect('variations.php?item=' . $itemId);
    }
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
            case 'edit':
                $name = sanitize($_POST['name']);
                $priceModifier = (float)$_POST['price_modifier'];
                $isDefault = isset($_POST['is_default']) ? 1 : 0;
                $sortOrder = (int)($_POST['sort_order'] ?? 0);
                
                if (empty($name)) {
                    setFlashMessage('error', 'Variation name is required');
                } else {
                    // If setting as default, unset other defaults
                    if ($isDefault) {
                        dbExecute("UPDATE item_variations SET is_default = 0 WHERE item_id = ?", [$itemId]);
                    }
                    
                    if ($_POST['action'] === 'add') {
                        dbInsert(
                            "INSERT INTO item_variations (item_id, name, price_modifier, is_default, sort_order) 
                             VALUES (?, ?, ?, ?, ?)",
                            [$itemId, $name, $priceModifier, $isDefault, $sortOrder]
                        );
                        setFlashMessage('success', 'Variation added successfully');
                    } else {
                        dbExecute(
                            "UPDATE item_variations SET name = ?, price_modifier = ?, is_default = ?, sort_order = ? 
                             WHERE id = ?",
                            [$name, $priceModifier, $isDefault, $sortOrder, $_POST['id']]
                        );
                        setFlashMessage('success', 'Variation updated successfully');
                    }
                }
                redirect('variations.php?item=' . $itemId);
                break;
                
            case 'delete':
                dbExecute("DELETE FROM item_variations WHERE id = ?", [$_POST['id']]);
                setFlashMessage('success', 'Variation deleted successfully');
                redirect('variations.php?item=' . $itemId);
                break;
                
            case 'set_default':
                dbExecute("UPDATE item_variations SET is_default = 0 WHERE item_id = ?", [$itemId]);
                dbExecute("UPDATE item_variations SET is_default = 1 WHERE id = ?", [$_POST['id']]);
                setFlashMessage('success', 'Default variation updated');
                redirect('variations.php?item=' . $itemId);
                break;
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get variations for this item
$variations = dbFetchAll(
    "SELECT * FROM item_variations WHERE item_id = ? ORDER BY sort_order, name",
    [$itemId]
);

// Get variation for edit
$editVariation = null;
if ($action === 'edit' && $variationId) {
    $editVariation = dbFetchOne("SELECT * FROM item_variations WHERE id = ?", [$variationId]);
    if (!$editVariation || $editVariation['item_id'] != $itemId) {
        setFlashMessage('error', 'Variation not found');
        redirect('variations.php?item=' . $itemId);
    }
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-layers"></i> Variations for: <?= sanitize($item['name']) ?></h2>
        <p class="text-muted">Base Price: <?= formatPrice($item['price']) ?></p>
    </div>
    <div class="col text-end">
        <?php if ($action === 'add' || $action === 'edit'): ?>
        <a href="variations.php?item=<?= $itemId ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
        <?php else: ?>
        <a href="items.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Items
        </a>
        <a href="variations.php?item=<?= $itemId ?>&action=add" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Variation
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- Add/Edit Form -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><?= $action === 'add' ? 'Add New' : 'Edit' ?> Variation</h5>
    </div>
    <div class="card-body">
        <form method="POST" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="<?= $action ?>">
            <input type="hidden" name="item_id" value="<?= $itemId ?>">
            <?php if ($action === 'edit'): ?>
            <input type="hidden" name="id" value="<?= $editVariation['id'] ?>">
            <?php endif; ?>
            
            <div class="col-md-6">
                <label for="name" class="form-label">Variation Name</label>
                <input type="text" class="form-control" id="name" name="name" 
                       placeholder="e.g., Small, Medium, Large, Extra Cheese"
                       value="<?= $editVariation ? sanitize($editVariation['name']) : '' ?>" required>
            </div>
            
            <div class="col-md-3">
                <label for="price_modifier" class="form-label">Price Modifier ($)</label>
                <input type="number" class="form-control" id="price_modifier" name="price_modifier" 
                       step="0.01" min="-999.99"
                       value="<?= $editVariation ? $editVariation['price_modifier'] : '0.00' ?>">
                <div class="form-text">Negative to reduce price</div>
            </div>
            
            <div class="col-md-3">
                <label for="sort_order" class="form-label">Sort Order</label>
                <input type="number" class="form-control" id="sort_order" name="sort_order" 
                       value="<?= $editVariation ? (int)$editVariation['sort_order'] : 0 ?>" min="0">
            </div>
            
            <div class="col-md-6">
                <label class="form-label d-block">Default Variation</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_default" name="is_default"
                           <?= ($editVariation && $editVariation['is_default']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_default">
                        Set as default selection
                    </label>
                </div>
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> <?= $action === 'add' ? 'Add' : 'Update' ?> Variation
                </button>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<!-- Variations List -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Item Variations</h5>
    </div>
    <div class="card-body">
        <?php if (empty($variations)): ?>
        <p class="text-muted text-center mb-0">
            No variations yet. 
            <a href="variations.php?item=<?= $itemId ?>&action=add">Add your first variation</a>.
        </p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Name</th>
                        <th>Price Modifier</th>
                        <th>Final Price</th>
                        <th>Default</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($variations as $variation): ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?= $variation['sort_order'] ?></span></td>
                        <td><strong><?= sanitize($variation['name']) ?></strong></td>
                        <td>
                            <span class="text-<?= $variation['price_modifier'] >= 0 ? 'success' : 'danger' ?>">
                                <?= $variation['price_modifier'] >= 0 ? '+' : '' ?><?= formatPrice($variation['price_modifier']) ?>
                            </span>
                        </td>
                        <td><strong><?= formatPrice($item['price'] + $variation['price_modifier']) ?></strong></td>
                        <td>
                            <?php if ($variation['is_default']): ?>
                            <span class="badge bg-primary"><i class="bi bi-check-circle-fill"></i> Default</span>
                            <?php else: ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="set_default">
                                <input type="hidden" name="id" value="<?= $variation['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                    Set Default
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="variations.php?item=<?= $itemId ?>&action=edit&id=<?= $variation['id'] ?>" 
                                   class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this variation?')">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $variation['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
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

<!-- Examples Card -->
<div class="card mt-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="bi bi-lightbulb"></i> Variation Examples</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <h6>Sizes</h6>
                <ul class="small">
                    <li>Small (+$0.00)</li>
                    <li>Medium (+$2.00)</li>
                    <li>Large (+$4.00)</li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6>Spice Level</h6>
                <ul class="small">
                    <li>Mild (+$0.00)</li>
                    <li>Medium (+$0.00)</li>
                    <li>Hot (+$0.00)</li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6>Extras</h6>
                <ul class="small">
                    <li>Extra Cheese (+$1.50)</li>
                    <li>Add Bacon (+$2.00)</li>
                    <li>Gluten-Free (-$1.00)</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
