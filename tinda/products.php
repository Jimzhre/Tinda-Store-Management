<?php
session_start();
include 'config/database.php';

$message = "";
$messageType = "";
$editProduct = null;

// --- 1. HANDLE DELETE ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Check if it already has the [Deleted] tag to prevent double-tagging
    $check = $conn->prepare("SELECT product_name FROM Products WHERE product_ID = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();
    
    if ($res && strpos($res['product_name'], '[Deleted]') === false) {
        $newName = '[Deleted] ' . $res['product_name'];
        $stmt = $conn->prepare("UPDATE Products SET product_name = ?, stock_quantity = 0 WHERE product_ID = ? AND user_ID = ?");
        $stmt->bind_param("sii", $newName, $id, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
    }
    }
    $check->close();

    $message = "Product removed and moved to archive.";
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

// --- 3. HANDLE FORM SUBMIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

$stmt = $conn->prepare("SELECT p.*, c.category_name FROM Products p LEFT JOIN Categories c ON p.category_ID = c.category_ID WHERE p.product_name NOT LIKE '[Deleted]%' AND p.user_ID = ? ORDER BY p.product_ID DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$activeProducts = $stmt->get_result();

$stmt = $conn->prepare("SELECT p.*, c.category_name FROM Products p LEFT JOIN Categories c ON p.category_ID = c.category_ID WHERE p.product_name LIKE '[Deleted]%' AND p.user_ID = ? ORDER BY p.product_ID DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$deletedProducts = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>TINDA | Products</title>
    <link rel="stylesheet" href="css/style.css">
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
                                            <span style="display:block; font-size:11px; color:#88907F; font-weight:normal;">
                                                <?php echo htmlspecialchars($p['category_name'] ?? '—'); ?>
                                            </span>
                                        </td>
                                        <td data-label="Price">₱ <?php echo number_format($p['price'], 2); ?></td>
                                        <td data-label="Stock"><?php echo $p['stock_quantity']; ?></td>
                                        <td data-label="Actions" style="text-align:center; white-space:nowrap;">
                                            <a href="products.php?edit=<?php echo $p['product_ID']; ?>" class="action-btn edit">Edit</a>
                                            <a href="#" class="action-btn delete" onclick="openModal(<?php echo $p['product_ID']; ?>); return false;">Delete</a>
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

        <!-- ARCHIVED PRODUCTS SECTION (Below the main layout) -->
        <section class="list-card" style="margin-top: 30px; opacity: 0.8;">
            <h3>Deleted Products Archive (<?php echo $deletedProducts->num_rows; ?>)</h3>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($deletedProducts->num_rows > 0): ?>
                            <?php while ($dp = $deletedProducts->fetch_assoc()): ?>
                                <tr>
                                    <td class="deleted-product"><?php echo htmlspecialchars($dp['product_name']); ?></td>
                                    <td>₱ <?php echo number_format($dp['price'], 2); ?></td>
                                    <td>0</td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="empty-row">No deleted products.</td></tr>
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
            <div class="modal-icon" aria-hidden="true">🗑️</div>
            <h3>Delete Product?</h3>
            <p>Are you sure you want to archive this product? Past sales records will still be kept safe.</p>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <a href="#" id="confirm-delete-btn" class="btn btn-primary" style="background-color: var(--accent); border-color: var(--accent);">Yes, Delete</a>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="js/toast.js"></script>
    <script src="js/modal.js"></script>

</body>
</html>
