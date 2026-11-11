<?php
session_start();
include 'config/database.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullname && $username && $password) {
        // Check if username exists
        $check = $conn->prepare("SELECT user_ID FROM Users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Username is already taken.";
        } else {
            // Hash the password for security!
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO Users (full_name, username, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $fullname, $username, $hashedPassword);
            
            if ($stmt->execute()) {
                $success = "Account created! You can now log in.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
            $stmt->close();
        }
        $check->close();
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>TINDA | Create Account</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-body">

    <div class="auth-card">
        <div class="auth-header">
            <h1>TINDA<span>.</span></h1>
            <p>Store Management System</p>
        </div>

        <h2>Create an Account</h2>
        <p class="auth-sub">Set up your store owner profile.</p>

        <?php if ($error): ?>
            <div class="alert error" style="margin-bottom: 20px; padding: 10px; font-size: 13px;"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success" style="margin-bottom: 20px; padding: 15px; font-size: 14px; text-align: center;">
                <?php echo $success; ?>
                <p style="margin-top: 8px; font-size: 12px; color: #686D63;">Redirecting you to the login page...</p>
            </div>
            
            <!-- This automatically redirects to the login page after 2 seconds -->
            <meta http-equiv="refresh" content="2;url=login.php">
        <?php endif; ?>

        <form method="POST" action="register.php">
            <label>Full Name</label>
            <input type="text" name="full_name" required>

            <label>Username</label>
            <input type="text" name="username" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 24px;">Sign Up</button>
        </form>

        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">Log in</a></p>
        </div>
    </div>

</body>
</html>