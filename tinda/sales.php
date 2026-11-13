<?php
session_start();
include 'config/database.php';

$message = "";
$messageType = "";
$userId = $_SESSION['user_id']; // Get the logged-in user

// Rows shown in the form (one empty row by default)
$formRows = [['product_ID' => 0, 'quantity' => 1]];

// Handle sale submit (one sale can have many items)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids  = is_array($_POST['product_ID'] ?? null) ? $_POST['product_ID'] : [];
    $qtys = is_array($_POST['quantity'] ?? null) ? $_POST['quantity'] : [];

    $formRows = [];   // remembered so the form is refilled if something goes wrong
    $wanted   = [];   // product_ID => total quantity (same product in 2 rows is combined)
    $badQty   = false;

    foreach ($ids as $i => $rawId) {
        $pid = (int)$rawId;
        $q   = (int)($qtys[$i] ?? 0);
        $formRows[] = ['product_ID' => $pid, 'quantity' => $q > 0 ? $q : 1];

        if ($pid <= 0) continue;                  // empty row, ignore it
        if ($q <= 0) { $badQty = true; continue; }
        $wanted[$pid] = ($wanted[$pid] ?? 0) + $q;
    }
    if (!$formRows) {
        $formRows = [['product_ID' => 0, 'quantity' => 1]];
    }

    if ($badQty || !$wanted) {
        $message = "Please choose at least one product and a quantity greater than zero.";
        $messageType = "error";
    } else {
        // Check every item (ONLY this user's products)
        $lines = [];
        $total = 0;
        $error = "";

        foreach ($wanted as $pid => $qty) {
            $stmt = $conn->prepare("SELECT product_name, price, stock_quantity FROM Products WHERE product_ID = ? AND user_ID = ?");
            $stmt->bind_param("ii", $pid, $userId);
            $stmt->execute();
            $prod = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$prod) {
                $error = "Product not found.";
                break;
            }
            if ($prod['stock_quantity'] < $qty) {
                $error = "Not enough stock for " . $prod['product_name'] . ". Only " . $prod['stock_quantity'] . " left.";
                break;
            }

            $lines[] = ['id' => $pid, 'name' => $prod['product_name'], 'price' => $prod['price'], 'qty' => $qty];
            $total += $prod['price'] * $qty;
        }

        if ($error !== "") {
            $message = $error;
            $messageType = "error";
        } else {
            // Transaction: ONE sale, a detail row per item, deduct stock per item
            $conn->begin_transaction();
            try {
                // 1. Insert Sale (with user_ID)
                $stmt = $conn->prepare("INSERT INTO Sales (total_amount, user_ID) VALUES (?, ?)");
                $stmt->bind_param("di", $total, $userId);
                $stmt->execute();
                $saleId = $conn->insert_id;
                $stmt->close();

                $detail = $conn->prepare("INSERT INTO Sale_Details (sale_ID, product_ID, quantity, price) VALUES (?, ?, ?, ?)");
                $deduct = $conn->prepare("UPDATE Products SET stock_quantity = stock_quantity - ? WHERE product_ID = ? AND user_ID = ? AND stock_quantity >= ?");

                foreach ($lines as $l) {
                    // 2. Insert Sale Detail
                    $detail->bind_param("iiid", $saleId, $l['id'], $l['qty'], $l['price']);
                    $detail->execute();

                    // 3. Deduct Stock (only if there is still enough)
                    $deduct->bind_param("iiii", $l['qty'], $l['id'], $userId, $l['qty']);
                    $deduct->execute();
                    if ($deduct->affected_rows < 1) {
                        throw new Exception("Not enough stock for " . $l['name'] . ".");
                    }
                }
                $detail->close();
                $deduct->close();

                $conn->commit();

                if (count($lines) === 1) {
                    $message = "Sale recorded: {$lines[0]['qty']} × {$lines[0]['name']} — ₱" . number_format($total, 2);
                } else {
                    $message = "Sale recorded: " . count($lines) . " items — ₱" . number_format($total, 2);
                }
                $messageType = "success";
                $formRows = [['product_ID' => 0, 'quantity' => 1]];   // fresh form
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Sale failed: " . $e->getMessage();
                $messageType = "error";
            }
        }
    }
}

// Products for dropdown (ONLY this user's products)
$stmt = $conn->prepare("SELECT product_ID, product_name, price, stock_quantity FROM Products WHERE stock_quantity > 0 AND user_ID = ? AND product_name NOT LIKE '[Archived]%' ORDER BY product_name");
$stmt->bind_param("i", $userId);
$stmt->execute();
$productResult = $stmt->get_result();
$productList = $productResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Recent sales (ONLY this user's sales)
$stmt = $conn->prepare("SELECT s.sale_ID, s.sale_date, s.total_amount, (SELECT COUNT(*) FROM Sales s2 WHERE s2.user_ID = s.user_ID AND s2.sale_ID <= s.sale_ID) AS sale_no FROM Sales s WHERE s.user_ID = ? ORDER BY s.sale_date DESC LIMIT 10");
$stmt->bind_param("i", $userId);
$stmt->execute();
$recent = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TINDA | Sales</title>
    <link rel="stylesheet" href="css/style.css?v=8">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="dashboard <?php echo ($_SERVER['REQUEST_METHOD'] === 'POST') ? '' : 'page-anim'; ?>">

        <section class="page-head">
            <div>
                <h2>Sales</h2>
                <p>Record a sale with one or more items — stock is deducted automatically.</p>
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

                    <div class="sale-rows" id="sale-rows">
                        <?php foreach ($formRows as $fr): ?>
                            <div class="sale-row">
                                <div class="sale-row-product">
                                    <label>Product</label>
                                    <select name="product_ID[]" required>
                                        <option value="">— Choose a product —</option>
                                        <?php foreach ($productList as $p): ?>
                                            <option value="<?php echo $p['product_ID']; ?>"
                                                    data-price="<?php echo $p['price']; ?>"
                                                    data-stock="<?php echo $p['stock_quantity']; ?>"
                                                    <?php echo ((int)$fr['product_ID'] === (int)$p['product_ID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($p['product_name']); ?>
                                                (₱<?php echo number_format($p['price'], 2); ?> · <?php echo $p['stock_quantity']; ?> left)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="sale-row-qty">
                                    <label>Quantity</label>
                                    <input type="number" name="quantity[]" min="1" value="<?php echo (int)$fr['quantity']; ?>" required>
                                </div>
                                <button type="button" class="sale-row-remove" aria-label="Remove this item">✕</button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" class="btn btn-outline" id="add-row">+ Add another item</button>

                    <div class="sale-total">
                        <span>Total</span>
                        <strong id="sale-total">₱ 0.00</strong>
                    </div>

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
                                        <td data-label="Sale #">#<?php echo $r['sale_no']; ?></td>
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

    <!-- Add / remove item rows and show the running total -->
    <script>
    (function () {
        var rows    = document.getElementById('sale-rows');
        var addBtn  = document.getElementById('add-row');
        var totalEl = document.getElementById('sale-total');
        if (!rows || !addBtn || !totalEl) return;

        function money(n) {
            return '₱ ' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function refresh() {
            var all = rows.querySelectorAll('.sale-row');
            var total = 0;

            all.forEach(function (row) {
                var sel = row.querySelector('select');
                var qty = row.querySelector('input[type="number"]');
                var opt = sel.options[sel.selectedIndex];
                var price = opt ? parseFloat(opt.dataset.price || 0) : 0;
                var stock = opt ? parseInt(opt.dataset.stock || 0, 10) : 0;

                // Don't allow more than what's in stock for that product
                if (stock > 0) { qty.max = stock; } else { qty.removeAttribute('max'); }

                total += price * (parseInt(qty.value, 10) || 0);
            });

            rows.classList.toggle('single', all.length === 1);   // hide the ✕ when only one row
            totalEl.textContent = money(total);
        }

        addBtn.addEventListener('click', function () {
            var clone = rows.querySelector('.sale-row').cloneNode(true);
            clone.querySelector('select').selectedIndex = 0;
            clone.querySelector('input[type="number"]').value = 1;
            rows.appendChild(clone);
            refresh();
            clone.querySelector('select').focus();
        });

        rows.addEventListener('click', function (e) {
            var btn = e.target.closest('.sale-row-remove');
            if (!btn) return;
            if (rows.querySelectorAll('.sale-row').length > 1) {
                btn.closest('.sale-row').remove();
                refresh();
            }
        });

        rows.addEventListener('input', refresh);
        rows.addEventListener('change', refresh);
        refresh();
    })();
    </script>

</body>
</html>