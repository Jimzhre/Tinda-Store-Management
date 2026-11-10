<?php include 'config/database.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>TINDA | Connection Test</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <h2>Database Connection Test</h2>
        <p>If the results below appear, PHP is talking to MySQL.</p>

        <div style="background:#fff;padding:20px;border-radius:12px;margin-top:20px;">
            <?php
            echo "<p style='color:green;font-weight:bold;'>✅ Connected to tinda_db successfully.</p>";

            $result = $conn->query("SELECT COUNT(*) AS c FROM Products");
            $row = $result->fetch_assoc();
            echo "<p>Products in database: <strong>" . $row['c'] . "</strong></p>";

            $result = $conn->query("SELECT COUNT(*) AS c FROM Categories");
            $row = $result->fetch_assoc();
            echo "<p>Categories in database: <strong>" . $row['c'] . "</strong></p>";
            ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

</body>
</html>