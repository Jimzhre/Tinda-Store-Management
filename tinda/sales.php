<?php
session_start();
include 'config/database.php';

$message = "";
$messageType = "";
$userId = $_SESSION['user_id']; // Get the logged-in user

// Handle sale submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_ID'] ?? 0);
    $qty       = (int)($_POST['quantity'] ?? 0);

    if ($productId <= 0 || $qty <= 0) {
        $message = "Please choose a product and quantity greater than zero.";
        $messageType = "error";
    } else {
        // Check stock (ONLY for this user's products)
        $stmt = $conn->prepare("SELECT product_name, price, stock_quantity FROM Products WHERE product_ID = ? AND user_ID = ?");
        $stmt->bind_param("ii", $productId, $userId);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$prod) {
            $message = "Product not found.";
            $messageType = "error";
        } elseif ($prod['stock_quantity'] < $qty) {
            $message = "Not enough stock. Only " . $prod['stock_quantity'] . " left.";
            $messageType = "error";
        } else {
            $total = $prod['price'] * $qty;

            // Transaction: insert sale, insert detail, deduct stock
            $conn->begin_transaction();
            try {
                // 1. Insert Sale (with user_ID)
                $stmt = $conn->prepare("INSERT INTO Sales (total_amount, user_ID) VALUES (?, ?)");
                $stmt->bind_param("di", $total, $userId);
                $stmt->execute();
                $saleId = $conn->insert_id;
                $stmt->close();

                // 2. Insert Sale Detail
                $stmt = $conn->prepare("INSERT INTO Sale_Details (sale_ID, product_ID, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $saleId, $productId, $qty, $prod['price']);
                $stmt->execute();
                $stmt->close();

                // 3. Deduct Stock
                $stmt = $conn->prepare("UPDATE Products SET stock_quantity = stock_quantity - ? WHERE product_ID = ? AND user_ID = ?");
                $stmt->bind_param("iii", $qty, $productId, $userId);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $message = "Sale recorded: {$qty} × {$prod['product_name']} — ₱" . number_format($total, 2);
                $messageType = "success";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Sale failed: " . $e->getMessage();
                $messageType = "error";
            }
        }
    }
}

// Products for dropdown (ONLY this user's products)
$stmt = $conn->prepare("SELECT product_ID, product_name, price, stock_quantity FROM Products WHERE stock_quantity > 0 AND user_ID = ? AND product_name NOT LIKE '[Deleted]%' ORDER BY product_name");
$stmt->bind_param("i", $userId);
$stmt->execute();
$products = $stmt->get_result();

// Recent sales (ONLY this user's sales)
$stmt = $conn->prepare("SELECT sale_ID, sale_date, total_amount FROM Sales WHERE user_ID = ? ORDER BY sale_date DESC LIMIT 10");
$stmt->bind_param("i", $userId);
$stmt->execute();
$recent = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>TINDA | Sales</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="dashboard <?php echo ($_SERVER['REQUEST_METHOD'] === 'POST') ? '' : 'page-anim'; ?>">

        <section class="page-head">
            <div>
                <h2>Sales</h2>
                <p>Record a new sale — stock is deducted automatically.</p>
            </div>
        </section>

        <?php if ($message): ?>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                showToast('<?php echo addslashes($message); ?>', '<?php echo $messageType; ?>');
            });
        </script>
        <?php endif; ?>

        <div class="products-layout">

            <!-- RECORD SALE FORM -->
            <section class="form-card">
                <h3>Record a Sale</h3>
                <form method="POST" action="sales.php">

                    <label>Product</label>
                    <select name="product_ID" required>
                        <option value="">— Choose a product —</option>
                        <?php while ($p = $products->fetch_assoc()): ?>
                            <option value="<?php echo $p['product_ID']; ?>">
                                <?php echo htmlspecialchars($p['product_name']); ?>
                                (₱<?php echo number_format($p['price'], 2); ?> · <?php echo $p['stock_quantity']; ?> left)
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <label>Quantity</label>
                    <input type="number" name="quantity" min="1" value="1" required>

                    <button type="submit" class="btn btn-primary">Record Sale</button>
                </form>
            </section>

            <!-- RECENT SALES -->
            <section class="list-card">
                <h3>Recent Sales</h3>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Sale #</th>
                                <th>Date</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent && $recent->num_rows > 0): ?>
                                <?php while ($r = $recent->fetch_assoc()): ?>
                                    <tr>
                                        <td data-label="Sale #">#<?php echo $r['sale_ID']; ?></td>
                                        <td data-label="Date"><?php echo date('M j, g:i A', strtotime($r['sale_date'])); ?></td>
                                        <td data-label="Total">₱ <?php echo number_format($r['total_amount'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="empty-row">No sales recorded yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>

    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/toast.js"></script>

</body>
</html>