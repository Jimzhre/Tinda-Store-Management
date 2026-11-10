<?php
session_start();
include 'config/database.php';

// If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        // Note: Since we inserted 'admin123' directly into the DB, we check for it plainly first.
        // In a real app, you would use password_verify() here.
        $stmt = $conn->prepare("SELECT user_ID, username, password, full_name FROM Users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && ($password === $user['password'] || password_verify($password, $user['password']))) {
            $_SESSION['user_id'] = $user['user_ID'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>TINDA | Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-body">

    <div class="auth-card">
        <!-- Back to Home Link -->
        <a href="index.php" class="back-home">← Back to Home</a>

        <div class="auth-header">
            <h1>TINDA<span>.</span></h1>
            <p>Store Management System</p>
        </div>

        <h2>Welcome</h2>
        <p class="auth-sub">Log in to manage your store.</p>

        <?php if ($error): ?>
            <div class="alert error" style="margin-bottom: 20px; padding: 10px; font-size: 13px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label>Username</label>
            <input type="text" name="username" required autofocus>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 24px;">Log In</button>
        </form>

        <div class="auth-footer">
            <p>Don't have an account? <a href="register.php">Create one</a></p>
        </div>
    </div>

</body>
</html>