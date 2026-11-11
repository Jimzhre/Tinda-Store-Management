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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TINDA | Reports</title>
    <link rel="stylesheet" href="css/style.css?v=4">
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
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="empty-row">No sales in this range.</td></tr>
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

</body>
</html>