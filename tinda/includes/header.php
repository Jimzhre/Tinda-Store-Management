<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the current page name (e.g., 'index.php' or 'dashboard.php')
$current_page = basename($_SERVER['PHP_SELF']);

// List of pages that DO NOT require a login
$public_pages = ['index.php', 'login.php', 'register.php', 'logout.php'];

// If the user is NOT logged in, and they are trying to access a protected page, send them to login
if (!isset($_SESSION['user_id']) && !in_array($current_page, $public_pages)) {
    header("Location: login.php");
    exit;
}
?>

<?php
// Default: show nav unless a page explicitly says otherwise
if (!isset($showNav)) {
    $showNav = true;
}
?>

<header>
    <a href="index.php" class="logo-link">
        <h1>TINDA<span>.</span></h1>
    </a>

    <?php if ($showNav): ?>
    <nav>
        <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Home</a>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Only show these if logged in -->
            <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="inventory.php" class="<?php echo $current_page == 'inventory.php' ? 'active' : ''; ?>">Inventory</a>
            <a href="sales.php" class="<?php echo $current_page == 'sales.php' ? 'active' : ''; ?>">Sales</a>
            <a href="products.php" class="<?php echo $current_page == 'products.php' ? 'active' : ''; ?>">Products</a>
            <a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">Reports</a>
            
            <a href="logout.php" class="nav-logout">Logout (<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>)</a>
        <?php else: ?>
            <!-- Only show this if logged out -->
            <a href="login.php" class="<?php echo $current_page == 'login.php' ? 'active' : ''; ?>">Log In</a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
</header>

<?php if ($showNav): ?>
    <!-- Adds the phone menu button (3 lines) that slides the menu in and out -->
    <script src="js/menu.js?v=2"></script>
<?php endif; ?>