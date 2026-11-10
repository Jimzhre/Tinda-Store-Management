<?php
session_start();
include 'config/database.php';

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT p.product_ID, p.product_name, p.price, p.stock_quantity,
            p.low_stock_threshold, c.category_name
    FROM Products p
    LEFT JOIN Categories c ON p.category_ID = c.category_ID
    WHERE p.user_ID = ?
    ORDER BY p.product_name
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$products = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>TINDA | Inventory</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="dashboard page-anim">

        <section class="page-head">
            <div>
                <h2>Inventory</h2>
                <p>All products and current stock levels.</p>
            </div>
            <a href="products.php" class="btn btn-primary">+ Add Product</a>
        </section>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products && $products->num_rows > 0): ?>
                        <?php while ($row = $products->fetch_assoc()): ?>
                            <?php
                            $isLow = $row['stock_quantity'] <= $row['low_stock_threshold'];
                            $status = $isLow ? 'Low Stock' : 'In Stock';
                            $cls    = $isLow ? 'badge-low' : 'badge-ok';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['category_name'] ?? '—'); ?></td>
                                <td>₱ <?php echo number_format($row['price'], 2); ?></td>
                                <td><?php echo $row['stock_quantity']; ?></td>
                                <td><span class="badge <?php echo $cls; ?>"><?php echo $status; ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-row">
                                No products yet. Add your first one on the Products page.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

    <?php include 'includes/footer.php'; ?>

</body>
</html>