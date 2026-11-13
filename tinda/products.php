<?php
session_start();
include 'config/database.php';

$message = "";
$messageType = "";
$editProduct = null;

// --- 1. HANDLE DELETE ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Check if it already has the [Archived] tag to prevent double-tagging
    $check = $conn->prepare("SELECT product_name FROM Products WHERE product_ID = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();
    
    if ($res && strpos($res['product_name'], '[Archived]') === false) {
        $newName = '[Archived] ' . $res['product_name'];
        $stmt = $conn->prepare("UPDATE Products SET product_name = ?, stock_quantity = 0 WHERE product_ID = ? AND user_ID = ?");
        $stmt->bind_param("sii", $newName, $id, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
    }
    $check->close();

    $message = "Product archived. You can restore it from the Archived Products section.";
    $messageType = "success";
}

// --- 2. HANDLE EDIT MODE ---
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM Products WHERE product_ID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editProduct = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// --- 2b. RESTORE / PERMANENTLY DELETE (from the Archived Products section) ---
$action = $_POST['archive_action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'restore' || $action === 'purge')) {
    $id = (int)($_POST['product_ID'] ?? 0);

    // Only this user's archived products
    $stmt = $conn->prepare("SELECT product_name FROM Products WHERE product_ID = ? AND user_ID = ? AND product_name LIKE '[Archived]%'");
    $stmt->bind_param("ii", $id, $_SESSION['user_id']);
    $stmt->execute();
    $archived = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$archived) {
        $message = "That product was not found in the archive.";
        $messageType = "error";

    } elseif ($action === 'restore') {
        // Take the [Archived] tag off the name
        $restoredName = trim(preg_replace('/^\[Archived\]\s*/', '', $archived['product_name']));
        $stmt = $conn->prepare("UPDATE Products SET product_name = ? WHERE product_ID = ? AND user_ID = ?");
        $stmt->bind_param("sii", $restoredName, $id, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();

        $message = "Product restored. Its stock is 0, so edit it to add stock back.";
        $messageType = "success";

    } else {
        // PERMANENT DELETE — a product that is part of past sales can't be erased
        // without breaking the sales history, so it stays in the archive.
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM Sale_Details WHERE product_ID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $usedInSales = $stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();

        if ($usedInSales > 0) {
            $message = "This product can't be permanently deleted because it appears in past sales. You can restore it or leave it in the archive.";
            $messageType = "error";
        } else {
            $deleted = 0;
            try {
                $stmt = $conn->prepare("DELETE FROM Products WHERE product_ID = ? AND user_ID = ? AND product_name LIKE '[Archived]%'");
                $stmt->bind_param("ii", $id, $_SESSION['user_id']);
                if ($stmt->execute()) {
                    $deleted = $stmt->affected_rows;
                }
                $stmt->close();
            } catch (Exception $e) {
                $deleted = 0;
            }

            if ($deleted > 0) {
                $message = "Product permanently deleted.";
                $messageType = "success";
            } else {
                $message = "Could not permanently delete this product.";
                $messageType = "error";
            }
        }
    }
}

// --- 3. HANDLE FORM SUBMIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === '') {
    $name      = trim($_POST['product_name'] ?? '');
    $price     = (float)($_POST['price'] ?? 0);
    $stock     = (int)($_POST['stock_quantity'] ?? 0);
    $category  = (int)($_POST['category_ID'] ?? 0);
    $threshold = (int)($_POST['low_stock_threshold'] ?? 5);
    $id        = (int)($_POST['product_ID'] ?? 0);

    if ($name === '' || $price <= 0) {
        $message = "Please enter a product name and a price greater than zero.";
        $messageType = "error";
    } else {
        $catVal = $category > 0 ? $category : null;
        
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE Products SET product_name=?, price=?, stock_quantity=?, category_ID=?, low_stock_threshold=? WHERE product_ID=?");
            $stmt->bind_param("sdiisi", $name, $price, $stock, $catVal, $threshold, $id);
            $successMsg = "Product updated successfully.";
        } else {
            $stmt = $conn->prepare("INSERT INTO Products (product_name, price, stock_quantity, category_ID, low_stock_threshold, user_ID) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sdiisi", $name, $price, $stock, $catVal, $threshold, $_SESSION['user_id']);
            $successMsg = "Product added successfully.";
        }

        if ($stmt->execute()) {
            $message = $successMsg;
            $messageType = "success";
            $editProduct = null; 
        } else {
            $message = "Error saving product: " . $stmt->error;
            $messageType = "error";
        }
        $stmt->close();
    }
}

// Fetch categories and products (Active vs Archived)
$categories = $conn->query("SELECT category_ID, category_name FROM Categories ORDER BY category_name");

$stmt = $conn->prepare("SELECT p.*, c.category_name FROM Products p LEFT JOIN Categories c ON p.category_ID = c.category_ID WHERE p.product_name NOT LIKE '[Archived]%' AND p.user_ID = ? ORDER BY p.product_ID DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$activeProducts = $stmt->get_result();

$stmt = $conn->prepare("SELECT p.*, c.category_name FROM Products p LEFT JOIN Categories c ON p.category_ID = c.category_ID WHERE p.product_name LIKE '[Archived]%' AND p.user_ID = ? ORDER BY p.product_ID DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$deletedProducts = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TINDA | Products</title>
    <link rel="stylesheet" href="css/style.css?v=9">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <!-- Removed the animation if we are editing or submitting -->
    <main class="dashboard <?php echo ($editProduct || $_SERVER['REQUEST_METHOD'] === 'POST') ? '' : 'page-anim'; ?>">

        <section class="page-head">
            <div>
                <h2>Products</h2>
                <p>Add, edit, and manage your product catalog.</p>
            </div>
            <a href="inventory.php" class="btn btn-outline">View Inventory</a>
        </section>

        <!-- Trigger the Toast if there's a message -->
        <?php if ($message): ?>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    showToast('<?php echo addslashes($message); ?>', '<?php echo $messageType; ?>');
                });
            </script>
        <?php endif; ?>

        <div class="products-layout">

            <!-- FORM CARD -->
            <section class="form-card">
                <h3><?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?></h3>
                <form method="POST" action="products.php">
                    
                    <input type="hidden" name="product_ID" value="<?php echo $editProduct ? $editProduct['product_ID'] : ''; ?>">

                    <label>Product Name</label>
                    <input type="text" name="product_name" required value="<?php echo $editProduct ? htmlspecialchars($editProduct['product_name']) : ''; ?>">

                    <label>Category</label>
                    <select name="category_ID">
                        <option value="0">— No category —</option>
                        <?php 
                        $categories->data_seek(0); 
                        while ($c = $categories->fetch_assoc()): 
                            $selected = ($editProduct && $editProduct['category_ID'] == $c['category_ID']) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $c['category_ID']; ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($c['category_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <label>Price (₱)</label>
                    <input type="number" name="price" step="0.01" min="0.01" required value="<?php echo $editProduct ? $editProduct['price'] : ''; ?>">

                    <label>Stock Quantity</label>
                    <input type="number" name="stock_quantity" min="0" value="<?php echo $editProduct ? $editProduct['stock_quantity'] : '0'; ?>">

                    <label>Low Stock Threshold</label>
                    <input type="number" name="low_stock_threshold" min="0" value="<?php echo $editProduct ? $editProduct['low_stock_threshold'] : '5'; ?>">

                    <button type="submit" class="btn btn-primary">
                        <?php echo $editProduct ? 'Update Product' : 'Save Product'; ?>
                    </button>
                    
                    <?php if ($editProduct): ?>
                        <a href="products.php" class="btn btn-outline" style="display:block; text-align:center; margin-top:10px;">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </section>

            <!-- LIST CARD (ACTIVE PRODUCTS) -->
            <section class="list-card">
                <h3>Active Products (<?php echo $activeProducts->num_rows; ?>)</h3>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($activeProducts->num_rows > 0): ?>
                                <?php while ($p = $activeProducts->fetch_assoc()): ?>
                                    <tr>
                                        <td data-label="Name">
                                            <?php echo htmlspecialchars($p['product_name']); ?>
                                            <span class="cell-sub"><?php echo htmlspecialchars($p['category_name'] ?? '—'); ?></span>
                                        </td>
                                        <td data-label="Price">₱ <?php echo number_format($p['price'], 2); ?></td>
                                        <td data-label="Stock"><?php echo $p['stock_quantity']; ?></td>
                                        <td data-label="Actions" style="text-align:center; white-space:nowrap;">
                                            <a href="products.php?edit=<?php echo $p['product_ID']; ?>" class="action-btn edit">Edit</a>
                                            <a href="#" class="action-btn delete archive" onclick="openModal(<?php echo $p['product_ID']; ?>); return false;">Archive</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="empty-row">No active products.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div> <!-- CLOSE products-layout -->

        <!-- ARCHIVED PRODUCTS SECTION (Below the main layout) — the only place a product can be permanently deleted -->
        <section class="list-card" style="margin-top: 30px;">
            <h3>Archived Products (<?php echo $deletedProducts->num_rows; ?>)</h3>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($deletedProducts->num_rows > 0): ?>
                            <?php while ($dp = $deletedProducts->fetch_assoc()): ?>
                                <?php $shownName = trim(preg_replace('/^\[Archived\]\s*/', '', $dp['product_name'])); ?>
                                <tr>
                                    <td data-label="Name" class="deleted-product"><?php echo htmlspecialchars($shownName); ?></td>
                                    <td data-label="Price">₱ <?php echo number_format($dp['price'], 2); ?></td>
                                    <td data-label="Stock">0</td>
                                    <td data-label="Actions" style="text-align:center; white-space:nowrap;">
                                        <button type="button" class="action-btn edit archive-restore"
                                                data-id="<?php echo (int)$dp['product_ID']; ?>">Restore</button>
                                        <button type="button" class="action-btn delete archive-purge"
                                                data-id="<?php echo (int)$dp['product_ID']; ?>"
                                                data-name="<?php echo htmlspecialchars($shownName); ?>">Delete</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="empty-row">No archived products.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- DELETE MODAL -->
    <div id="delete-modal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-icon" aria-hidden="true">📦</div>
            <h3>Archive Product?</h3>
            <p>This product will be moved to Archived Products. Past sales records stay safe, and you can restore it anytime.</p>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <a href="#" id="confirm-delete-btn" class="btn btn-primary" style="background-color: var(--accent); border-color: var(--accent);">Yes, Archive</a>
            </div>
        </div>
    </div>

    <!-- ARCHIVE: hidden form + "delete forever" confirm -->
    <form id="archive-form" method="POST" action="products.php" style="display:none;">
        <input type="hidden" name="archive_action" value="">
        <input type="hidden" name="product_ID" value="">
    </form>

    <div id="purge-modal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-icon" aria-hidden="true">⚠️</div>
            <h3>Delete forever?</h3>
            <p><strong id="purge-name"></strong> will be permanently removed and can't be restored.</p>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" id="purge-cancel">Cancel</button>
                <button type="button" class="btn btn-primary" id="purge-confirm" style="background-color: var(--accent); border-color: var(--accent);">Yes, Delete</button>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="js/toast.js"></script>
    <script src="js/modal.js"></script>
    <script>
    (function () {
        var form    = document.getElementById('archive-form');
        var modal   = document.getElementById('purge-modal');
        var nameEl  = document.getElementById('purge-name');
        var pending = null;

        function send(action, id) {
            form.elements['archive_action'].value = action;
            form.elements['product_ID'].value = id;
            form.submit();
        }
        function closePurge() { modal.classList.remove('show'); pending = null; }

        document.addEventListener('click', function (e) {
            var r = e.target.closest('.archive-restore');
            if (r) { send('restore', r.dataset.id); return; }

            var d = e.target.closest('.archive-purge');
            if (d) {
                pending = d.dataset.id;
                nameEl.textContent = d.dataset.name;
                modal.classList.add('show');
            }
        });

        document.getElementById('purge-confirm').addEventListener('click', function () {
            if (pending) send('purge', pending);
        });
        document.getElementById('purge-cancel').addEventListener('click', closePurge);
        modal.addEventListener('click', function (e) { if (e.target === modal) closePurge(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closePurge(); });
    })();
    </script>

</body>
</html>