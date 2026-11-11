<?php $showNav = false; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TINDA | Your Store. Your Stocks. Your Sales.</title>
    <link rel="stylesheet" href="css/style.css?v=4">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="landing">

        <!-- HERO -->
        <section class="hero">
            <div class="hero-inner">
                <p class="eyebrow">Store Management System</p>
                <h2>Your Store.<br>Your Stocks.<br>Your Sales.</h2>
                <p class="hero-desc">
                    TINDA helps small Filipino stores manage products, track stock,
                    record sales, and view reports — all in one simple place.
                </p>
                <div class="hero-buttons">
                    <a href="dashboard.php" class="btn btn-primary">Open Dashboard</a>
                    <a href="#how" class="btn btn-outline">How It Works</a>
                </div>
                <p class="hero-note">Built for sari-sari stores and small Filipino businesses.</p>
            </div>
        </section>

        <!-- STATS STRIP -->
        <section class="stats reveal">
            <div class="stat">
                <span class="stat-num" data-count="1">0</span>
                <span class="stat-label">Place for everything</span>
            </div>
            <div class="stat">
                <span class="stat-num" data-count="0">0</span>
                <span class="stat-label">Manual stock counting</span>
            </div>
            <div class="stat">
                <span class="stat-num" data-count="5">0</span>
                <span class="stat-label">Core tools built in</span>
            </div>
            <div class="stat">
                <span class="stat-num" data-count="100" data-suffix="%">0</span>
                <span class="stat-label">Made for sari-sari stores</span>
            </div>
        </section>

        <!-- FEATURES -->
        <section id="features" class="features reveal">
            <h3>Everything your tindahan needs</h3>
            <p class="section-sub">Simple tools built for sari-sari stores and small businesses.</p>

            <div class="feature-grid">

                <div class="feature-card">
                    <div class="feature-icon">📦</div>
                    <h4>Inventory</h4>
                    <p>See all your products and know exactly how many are left in stock.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🧾</div>
                    <h4>Sales</h4>
                    <p>Record every transaction and automatically deduct sold items from stock.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🏷️</div>
                    <h4>Products</h4>
                    <p>Add, edit, or remove products and organize them by category.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h4>Reports</h4>
                    <p>View daily, weekly, and monthly sales summaries at a glance.</p>
                </div>

            </div>
        </section>

        <!-- HOW IT WORKS -->
        <section id="how" class="how reveal">
            <h3>How it works</h3>
            <p class="section-sub">Three simple steps — from adding products to seeing your reports.</p>

            <div class="how-grid">

                <div class="how-step">
                    <div class="how-num">1</div>
                    <h4>Add your products</h4>
                    <p>List what you sell — name, price, category, and starting stock.</p>
                </div>

                <div class="how-step">
                    <div class="how-num">2</div>
                    <h4>Record your sales</h4>
                    <p>Every sale updates your stock automatically. No counting by hand.</p>
                </div>

                <div class="how-step">
                    <div class="how-num">3</div>
                    <h4>See your reports</h4>
                    <p>View daily, weekly, and monthly totals — know how your store is doing.</p>
                </div>

            </div>
        </section>

        <!-- WHY TINDA -->
        <section class="why reveal">
            <div class="why-text">
                <h3>Why TINDA?</h3>
                <p class="section-sub">Built around the everyday needs of a sari-sari store.</p>

                <ul class="check-list">
                    <li><span class="check">✓</span> No more notebook-style stock counting</li>
                    <li><span class="check">✓</span> Every sale is saved — nothing gets lost</li>
                    <li><span class="check">✓</span> Works on a laptop or desktop with XAMPP</li>
                    <li><span class="check">✓</span> Clean, simple design — easy to learn in minutes</li>
                </ul>
            </div>

            <div class="why-card">
                <h4>Built for small stores</h4>
                <p>
                    TINDA was designed for traditional Filipino sari-sari stores
                    and small businesses. No complicated setup, no unnecessary
                    features — just the tools you actually need.
                </p>
                <a href="dashboard.php" class="btn btn-primary">Try the Dashboard</a>
            </div>
        </section>

        <!-- CTA -->
        <section class="cta reveal">
            <h3>Ready to manage your store?</h3>
            <p>Start with your dashboard — everything is one click away.</p>
            <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
        </section>

    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/script.js"></script>

</body>
</html>