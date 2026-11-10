<?php
session_start();
include 'config/database.php';

// Get the currently logged-in user
$userId = $_SESSION['user_id'];

// --- Today's sales total ---
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) AS total FROM Sales WHERE DATE(sale_date) = ? AND user_ID = ?");
$stmt->bind_param("si", $today, $userId);
$stmt->execute();
$todaySales = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// --- Today's transaction count ---
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM Sales WHERE DATE(sale_date) = ? AND user_ID = ?");
$stmt->bind_param("si", $today, $userId);
$stmt->execute();
$todayCount = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

// --- Total products ---
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM Products WHERE user_ID = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$totalProducts = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

// --- Chart Data: Sunday to Saturday ---
$chartData = [];
$chartLabels = [];
$maxSale = 0;

// Find the date of the most recent Sunday
$currentDayOfWeek = date('w'); // 0 (Sun) to 6 (Sat)
$startOfWeek = strtotime("-$currentDayOfWeek days");

for ($i = 0; $i < 7; $i++) {
    $timestamp = strtotime("+$i days", $startOfWeek);
    $date = date('Y-m-d', $timestamp);
    $displayDate = date('D', $timestamp); // e.g., "Sun", "Mon"
    
    // Get total for this specific day
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) AS total FROM Sales WHERE DATE(sale_date) = ? AND user_ID = ?");
    $stmt->bind_param("si", $date, $userId);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    $chartData[] = $total;
    $chartLabels[] = $displayDate;
    
    if ($total > $maxSale) {
        $maxSale = $total;
    }
}

// Prevent division by zero if there are no sales yet
if ($maxSale == 0) $maxSale = 1; 

// --- Low stock count ---
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM Products WHERE stock_quantity <= low_stock_threshold AND user_ID = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$lowStock = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>TINDA | Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="dashboard page-anim">

    <!-- WELCOME BANNER -->
    <section class="welcome-banner">
        <div class="banner-text">
            <span class="banner-eyebrow">Magandang araw!</span>
            <h3>Welcome back to TINDA.</h3>
            <p>Your store is running smoothly. Here's your snapshot for today.</p>
        </div>
        <div class="banner-actions">
            <a href="sales.php" class="btn btn-primary">Record Sale</a>
            <a href="products.php" class="btn btn-outline">Add Product</a>
        </div>
    </section>

    <section class="page-head">
        <div>
            <h2>Dashboard</h2>
            <p>Overview of your store — <?php echo date('F j, Y'); ?></p>
        </div>
        <a href="sales.php" class="btn btn-primary">+ Record Sale</a>
    </section>

        <section class="stat-cards">
            <div class="stat-card">
                <div class="stat-card-icon" aria-hidden="true">💰</div>
                <div class="stat-card-body">
                    <span class="stat-card-label">Today's Sales</span>
                    <span class="stat-card-value">₱ <?php echo number_format($todaySales, 2); ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon" aria-hidden="true">📦</div>
                <div class="stat-card-body">
                    <span class="stat-card-label">Total Products</span>
                    <span class="stat-card-value"><?php echo $totalProducts; ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon" aria-hidden="true">⚠️</div>
                <div class="stat-card-body">
                    <span class="stat-card-label">Low Stock</span>
                    <span class="stat-card-value"><?php echo $lowStock; ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon" aria-hidden="true">🧾</div>
                <div class="stat-card-body">
                    <span class="stat-card-label">Transactions Today</span>
                    <span class="stat-card-value"><?php echo $todayCount; ?></span>
                </div>
            </div>
        </section>

        <section class="quick-actions">
            <h3>Quick Actions</h3>
            <div class="action-grid">
                <a href="products.php" class="action-card">
                    <span class="action-icon" aria-hidden="true">➕</span>
                    <span class="action-label">Add Product</span>
                </a>
                <a href="sales.php" class="action-card">
                    <span class="action-icon" aria-hidden="true">🧾</span>
                    <span class="action-label">Record Sale</span>
                </a>
                <a href="reports.php" class="action-card">
                    <span class="action-icon" aria-hidden="true">📊</span>
                    <span class="action-label">View Reports</span>
                </a>
            </div>
        </section>

        <!-- WEEKLY SALES CHART -->
        <section class="list-card" style="margin-bottom: 30px;">
            <h3>Sales - Last 7 Days</h3>
            <div class="chart-container">
                <?php foreach ($chartData as $index => $amount): ?>
                    <?php 
                        // Calculate the height of the bar (max height is 150px)
                        $height = ($amount / $maxSale) * 150; 
                        if ($height < 4) $height = 4; // Minimum height for visibility
                    ?>
                    <div class="chart-bar-wrap">
                        <div class="chart-tooltip">₱<?php echo number_format($amount, 2); ?></div>
                        <!-- THIS IS THE BAR -->
                        <div class="chart-bar <?php echo ($chartLabels[$index] === date('D')) ? 'today' : ''; ?>" style="height: <?php echo $height; ?>px;"></div>
                        <div class="chart-label"><?php echo $chartLabels[$index]; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- LOWER GRID: Recent + Top Selling -->
        <section class="dash-lower">

            <!-- RECENT TRANSACTIONS -->
            <div class="list-card">
                <div class="recent-head">
                    <h3>Recent Transactions</h3>
                    <a href="sales.php" class="link">View all →</a>
                </div>
                <div class="table-wrap" style="box-shadow:none;border:none;border-radius:0;">
                    <table class="data-table">
                        <thead>
                            <tr><th>Sale #</th><th>Date</th><th>Total</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            // NEW: Prepared statement for Recent Transactions
                            $stmt = $conn->prepare("SELECT sale_ID, sale_date, total_amount FROM Sales WHERE user_ID = ? ORDER BY sale_date DESC LIMIT 5");
                            $stmt->bind_param("i", $userId);
                            $stmt->execute();
                            $recent = $stmt->get_result();
                            
                            if ($recent && $recent->num_rows > 0):
                                while ($row = $recent->fetch_assoc()):
                            ?>
                                <tr>
                                    <td data-label="Sale #">#<?php echo $row['sale_ID']; ?></td>
                                    <td data-label="Date"><?php echo date('M j, g:i A', strtotime($row['sale_date'])); ?></td>
                                    <td data-label="Total">₱ <?php echo number_format($row['total_amount'], 2); ?></td>
                                </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                                <tr><td colspan="3" class="empty-row">No transactions yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TOP SELLING -->
            <div class="list-card">
                <div class="recent-head">
                    <h3>Top Selling</h3>
                    <a href="reports.php" class="link">Reports →</a>
                </div>
                <div class="top-selling">
                    <?php
                    // NEW: Prepared statement for Top Selling
                    $stmt = $conn->prepare("
                        SELECT p.product_name, SUM(sd.quantity) AS sold
                        FROM Sale_Details sd
                        JOIN Products p ON sd.product_ID = p.product_ID
                        JOIN Sales s ON sd.sale_ID = s.sale_ID
                        WHERE s.user_ID = ?
                        GROUP BY sd.product_ID
                        ORDER BY sold DESC
                        LIMIT 5
                    ");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $top = $stmt->get_result();

                    if ($top && $top->num_rows > 0):
                        $rank = 1;
                        while ($t = $top->fetch_assoc()):
                    ?>
                        <div class="top-row">
                            <span class="top-rank"><?php echo $rank++; ?></span>
                            <span class="top-name"><?php echo htmlspecialchars($t['product_name']); ?></span>
                            <span class="top-count"><?php echo $t['sold']; ?> sold</span>
                        </div>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <div class="empty-row" style="border:none;">No sales data yet.</div>
                    <?php endif; ?>
                </div>
            </div>

        </section>

    </main>

    <?php include 'includes/footer.php'; ?>

</body>
</html>