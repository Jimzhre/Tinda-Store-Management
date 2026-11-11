<?php
session_start();
include 'config/database.php';

$userId = $_SESSION['user_id']; // Get the logged-in user

// --- Default range: this month ---
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

// --- Summary: today ---
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount),0) AS t, COUNT(*) AS c FROM Sales WHERE DATE(sale_date) = ? AND user_ID = ?");
$stmt->bind_param("si", $today, $userId);
$stmt->execute();
$todayRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- Summary: this week (last 7 days) ---
$weekStart = date('Y-m-d', strtotime('-6 days'));
$stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount),0) AS t, COUNT(*) AS c FROM Sales WHERE DATE(sale_date) BETWEEN ? AND ? AND user_ID = ?");
$stmt->bind_param("ssi", $weekStart, $today, $userId);
$stmt->execute();
$weekRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- Summary: this month ---
$monthStart = date('Y-m-01');
$stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount),0) AS t, COUNT(*) AS c FROM Sales WHERE DATE(sale_date) BETWEEN ? AND ? AND user_ID = ?");
$stmt->bind_param("ssi", $monthStart, $today, $userId);
$stmt->execute();
$monthRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- Range sales list ---
$stmt = $conn->prepare("
    SELECT s.sale_ID, s.sale_date, s.total_amount,
           COALESCE(SUM(sd.quantity), 0) AS items
    FROM Sales s
    LEFT JOIN Sale_Details sd ON s.sale_ID = sd.sale_ID
    WHERE DATE(s.sale_date) BETWEEN ? AND ? AND s.user_ID = ?
    GROUP BY s.sale_ID
    ORDER BY s.sale_date DESC
");
$stmt->bind_param("ssi", $from, $to, $userId);
$stmt->execute();
$sales = $stmt->get_result();
$stmt->close();

// --- Range totals ---
$stmt2 = $conn->prepare("SELECT COALESCE(SUM(total_amount),0) AS total, COUNT(*) AS count FROM Sales WHERE DATE(sale_date) BETWEEN ? AND ? AND user_ID = ?");
$stmt2->bind_param("ssi", $from, $to, $userId);
$stmt2->execute();
$rangeTotals = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

// --- Top selling in range ---
$stmt3 = $conn->prepare("
    SELECT p.product_name, SUM(sd.quantity) AS sold, SUM(sd.quantity * sd.price) AS revenue
    FROM Sale_Details sd
    JOIN Products p ON sd.product_ID = p.product_ID
    JOIN Sales s ON sd.sale_ID = s.sale_ID
    WHERE DATE(s.sale_date) BETWEEN ? AND ? AND s.user_ID = ?
    GROUP BY sd.product_ID
    ORDER BY sold DESC
    LIMIT 5
");
$stmt3->bind_param("ssi", $from, $to, $userId);
$stmt3->execute();
$topProducts = $stmt3->get_result();
$stmt3->close();

// --- Items bought in each sale in range (for the "View" popup) ---
$saleItems = [];
$stmt4 = $conn->prepare("
    SELECT sd.sale_ID, p.product_name, sd.quantity, sd.price
    FROM Sale_Details sd
    JOIN Sales s ON sd.sale_ID = s.sale_ID
    JOIN Products p ON sd.product_ID = p.product_ID
    WHERE DATE(s.sale_date) BETWEEN ? AND ? AND s.user_ID = ?
    ORDER BY sd.sale_ID, p.product_name
");
$stmt4->bind_param("ssi", $from, $to, $userId);
$stmt4->execute();
$itemRows = $stmt4->get_result();
while ($it = $itemRows->fetch_assoc()) {
    $saleItems[$it['sale_ID']][] = [
        'name'     => preg_replace('/^\[Deleted\]\s*/', '', $it['product_name']),
        'qty'      => (int)$it['quantity'],
        'price'    => number_format($it['price'], 2),
        'subtotal' => number_format($it['price'] * $it['quantity'], 2),
    ];
}
$stmt4->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TINDA | Reports</title>
    <link rel="stylesheet" href="css/style.css?v=7">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="dashboard page-anim">

        <section class="page-head">
            <div>
                <h2>Reports</h2>
                <p>Sales summaries for your store.</p>
            </div>
        </section>

        <!-- SUMMARY CARDS -->
        <section class="stat-cards">
            <div class="stat-card">
                <div class="stat-card-icon" aria-hidden="true">📅</div>
                <div class="stat-card-body">
                    <span class="stat-card-label">Today</span>
                    <span class="stat-card-value">₱ <?php echo number_format($todayRow['t'], 2); ?></span>
                    <span class="stat-card-sub"><?php echo $todayRow['c']; ?> transaction<?php echo $todayRow['c'] == 1 ? '' : 's'; ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon" aria-hidden="true">🗓️</div>
                <div class="stat-card-body">
                    <span class="stat-card-label">Last 7 Days</span>
                    <span class="stat-card-value">₱ <?php echo number_format($weekRow['t'], 2); ?></span>
                    <span class="stat-card-sub"><?php echo $weekRow['c']; ?> transaction<?php echo $weekRow['c'] == 1 ? '' : 's'; ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon" aria-hidden="true">📆</div>
                <div class="stat-card-body">
                    <span class="stat-card-label">This Month</span>
                    <span class="stat-card-value">₱ <?php echo number_format($monthRow['t'], 2); ?></span>
                    <span class="stat-card-sub"><?php echo $monthRow['c']; ?> transaction<?php echo $monthRow['c'] == 1 ? '' : 's'; ?></span>
                </div>
            </div>
        </section>

        <!-- DATE FILTER -->
        <section class="filter-bar">
            <form method="GET" action="reports.php" class="filter-form">

                <div class="filter-group">
                    <div class="filter-field">
                        <label>From</label>
                        <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>">
                    </div>
                    <div class="filter-field">
                        <label>To</label>
                        <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>">
                    </div>
                </div>

                <div class="filter-group filter-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="reports.php" class="btn btn-outline">Reset</a>
                </div>

            </form>
        </section>

        <!-- RANGE SUMMARY BAR -->
        <div class="range-summary">
            <span>
                <strong><?php echo htmlspecialchars($from); ?></strong>
                to
                <strong><?php echo htmlspecialchars($to); ?></strong>
            </span>
            <span class="range-totals">
                <span>₱ <?php echo number_format($rangeTotals['total'], 2); ?></span>
                <span class="dot">·</span>
                <span><?php echo $rangeTotals['count']; ?> transaction<?php echo $rangeTotals['count'] == 1 ? '' : 's'; ?></span>
            </span>
        </div>

        <!-- LOWER GRID -->
        <div class="dash-lower">

            <!-- SALES TABLE -->
            <div class="list-card">
                <h3>Sales in Range</h3>
                <div class="table-wrap" style="box-shadow:none;border:none;border-radius:0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Sale #</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th style="text-align:center;">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($sales->num_rows > 0): ?>
                                <?php while ($s = $sales->fetch_assoc()): ?>
                                    <tr>
                                        <td data-label="Sale #">#<?php echo $s['sale_ID']; ?></td>
                                        <td data-label="Date"><?php echo date('M j, g:i A', strtotime($s['sale_date'])); ?></td>
                                        <td data-label="Items"><?php echo $s['items']; ?></td>
                                        <td data-label="Total">₱ <?php echo number_format($s['total_amount'], 2); ?></td>
                                        <td data-label="Actions" style="text-align:center; white-space:nowrap;">
                                            <button type="button" class="action-btn edit view-sale"
                                                    data-sale="<?php echo (int)$s['sale_ID']; ?>"
                                                    data-date="<?php echo htmlspecialchars(date('M j, Y · g:i A', strtotime($s['sale_date']))); ?>"
                                                    data-total="₱ <?php echo number_format($s['total_amount'], 2); ?>">View</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="empty-row">No sales in this range.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TOP PRODUCTS -->
            <div class="list-card">
                <h3>Top Products in Range</h3>
                <div class="top-selling">
                    <?php if ($topProducts->num_rows > 0): ?>
                        <?php $rank = 1; while ($t = $topProducts->fetch_assoc()): ?>
                            <div class="top-row">
                                <span class="top-rank"><?php echo $rank++; ?></span>
                                <span class="top-name"><?php echo htmlspecialchars($t['product_name']); ?></span>
                                <span class="top-count"><?php echo $t['sold']; ?> sold</span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-row" style="border:none;">No product sales in this range.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- SALE DETAILS POPUP -->
    <div id="sale-modal" class="modal-overlay">
        <div class="modal-card receipt" role="dialog" aria-modal="true" aria-labelledby="sale-modal-title">
            <h3 id="sale-modal-title">Sale</h3>
            <div class="receipt-date" id="sale-modal-date"></div>
            <div class="receipt-items" id="sale-modal-items"></div>
            <div class="receipt-total">
                <span>Total</span>
                <strong id="sale-modal-total"></strong>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-primary" id="sale-modal-close">Close</button>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var saleItems = <?php echo json_encode($saleItems, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

        var modal   = document.getElementById('sale-modal');
        var titleEl = document.getElementById('sale-modal-title');
        var dateEl  = document.getElementById('sale-modal-date');
        var itemsEl = document.getElementById('sale-modal-items');
        var totalEl = document.getElementById('sale-modal-total');

        function el(cls, text) {
            var d = document.createElement('div');
            if (cls) d.className = cls;
            if (text !== undefined) d.textContent = text;
            return d;
        }

        function openSale(btn) {
            var id = btn.dataset.sale;
            var items = saleItems[id] || [];

            titleEl.textContent = 'Sale #' + id;
            dateEl.textContent  = btn.dataset.date;
            totalEl.textContent = btn.dataset.total;
            itemsEl.textContent = '';

            if (!items.length) {
                itemsEl.appendChild(el('receipt-empty', 'No item details for this sale.'));
            }

            items.forEach(function (it) {
                var row  = el('receipt-item');
                var left = el();
                left.appendChild(el('receipt-item-name', it.name));
                left.appendChild(el('receipt-item-meta', it.qty + ' × ₱' + it.price));
                row.appendChild(left);
                row.appendChild(el('receipt-item-sub', '₱' + it.subtotal));
                itemsEl.appendChild(row);
            });

            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeSale() {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.view-sale');
            if (btn) openSale(btn);
        });
        document.getElementById('sale-modal-close').addEventListener('click', closeSale);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeSale(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSale(); });
    })();
    </script>

</body>
</html>
