<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"] . "/dbconf.php";

$error = null;
$success = null;

// Ensure database connection exists
if (!isset($conn)) {
    error_log("Database connection not initialized.");
    $error = "Database connection failed.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {

    // Sanitize input
    $adminUsername   = trim(strip_tags($_POST['username'] ?? ''));
    $adminPassword   = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    // Username validation
    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $adminUsername)) {

        $error = "Username must contain only letters, numbers and underscore.";

    }
    // Password validation
    elseif (strlen($adminPassword) < 8 || strlen($adminPassword) > 100) {

        $error = "Password must be between 8 and 100 characters.";

    }
    // Confirm password
    elseif ($adminPassword !== $confirmPassword) {

        $error = "Passwords do not match.";

    }
    else {

        try {

            // Check duplicate username
            $checkStmt = $conn->prepare("
                SELECT id
                FROM admin_users
                WHERE username = ?
                LIMIT 1
            ");

            $checkStmt->execute([$adminUsername]);

            if ($checkStmt->fetch()) {

                $error = "Username already exists.";

            } else {

                // Hash password
                $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);

                // Insert new admin
                $insertStmt = $conn->prepare("
                    INSERT INTO admin_users
                    (username, password)
                    VALUES (?, ?)
                ");

                $insertStmt->execute([
                    $adminUsername,
                    $hashedPassword
                ]);

                $success = "Admin registered successfully.";

            }

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $error = "Something went wrong. Please try again.";

        }

    }

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Registration</title>
</head>
<body>
<div style="padding-top:10%;">
    <div style="max-width:400px;margin:auto;background:#d7f7cdff;padding:50px;border-radius:5px;">
        <h2>Admin Registration</h2>

        <?php if ($error): ?>
            <p style="color:red;font-weight:bold;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p style="color:green;font-weight:bold;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="POST" style="display:flex;flex-direction:column;">
            <label>Username</label>
            <input type="text" name="username" maxlength="30" autocomplete="username" required value="<?= htmlspecialchars($adminUsername ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   style="padding:10px;margin-bottom:20px;">
            <label>Password</label>
            <input type="password" name="password" maxlength="100" autocomplete="new-password" required
                   style="padding:10px;margin-bottom:20px;">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required autocomplete="new-password" style="padding:10px;margin-bottom:20px;">
            <button type="submit"
                    style="padding:10px;background:#4CAF50;color:white;border:none;">
                Register
            </button>
        </form>
    </div>
</div>
</body>
</html>
