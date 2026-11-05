<?php
session_start();

$conn = new mysqli("localhost", "root", "", "shop_finder");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$step    = isset($_GET['step'])  ? intval($_GET['step'])  : 1;
$error   = '';
$success = '';

// =============================================
// STEP 1 — Find account by email
// =============================================
if (isset($_POST['find_account'])) {
    $email     = mysqli_real_escape_string($conn, trim($_POST['email']));
    $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
    $table     = ($user_type === 'customer') ? 'customers' : 'shops';

    $res = $conn->query("SELECT id FROM $table WHERE email='$email'");
    if ($res && $res->num_rows > 0) {
        // Skip security questions and go straight to password reset (Step 3)
        $_SESSION['reset_email']     = $email;
        $_SESSION['reset_user_type'] = $user_type;
        $_SESSION['reset_verified']  = true; // Auto-verify since questions are removed
        header("Location: forgotpassword.php?step=3");
        exit();
    } else {
        $error = "No account found with that email address.";
    }
}

// =============================================
// STEP 3 — Set new password
// =============================================
if (isset($_POST['reset_password'])) {
    if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
        header("Location: forgotpassword.php");
        exit();
    }

    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $email            = mysqli_real_escape_string($conn, $_SESSION['reset_email']);
    $user_type        = $_SESSION['reset_user_type'];
    $table            = ($user_type === 'customer') ? 'customers' : 'shops';

    if (empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
        $step  = 3;
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
        $step  = 3;
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters.";
        $step  = 3;
    } else {
        $hashed = md5($new_password);
        $conn->query("UPDATE $table SET password='$hashed' WHERE email='$email'");

        // Clear session reset data
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_user_type']);
        unset($_SESSION['reset_verified']);

        $success = "Password reset successfully! You can now log in.";
        $step    = 4;
    }
}

$conn->close();

$is_logged_in = isset($_SESSION['customer_email']) || isset($_SESSION['shop_email']);
$dashboard_link = isset($_SESSION['customer_email']) ? 'customerdashboard.php' : (isset($_SESSION['shop_email']) ? 'shopdashboard.php' : 'customerlogin.html');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password | Shop Finder</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ... (Existing CSS remains the same as provided in your previous version) ... */
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
:root { --primary: #2d98da; --secondary: #3867d6; --success: #20bf6b; --danger: #fc5c65; --dark: #1a1f2e; --bg: #f0f4f8; --white: #ffffff; --text: #333; --text-light:#6b7280; --border: #e5e7eb; }
body { font-family: 'Poppins', sans-serif; background: var(--bg); min-height: 100vh; display: flex; flex-direction: column; }
.navbar { background: var(--white); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.07); }
.navbar h2 { color: var(--primary); font-size: 20px; font-weight: 700; }
.nav-link { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; transition: 0.2s; color: var(--text-light); text-decoration: none; }
.nav-link:hover { background: var(--bg); color: var(--primary); }
.nav-link.primary { background: var(--primary); color: white; }
.page-wrap { flex: 1; display: flex; align-items: flex-start; justify-content: center; padding: 50px 20px; }
.card { background: var(--white); border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); width: 100%; max-width: 480px; overflow: hidden; }
.card-top { background: linear-gradient(135deg, var(--primary), var(--secondary)); padding: 30px 35px; color: white; text-align: center; }
.card-top .icon { width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 14px; }
.card-top h2 { font-size: 20px; font-weight: 700; margin-bottom: 5px; }
.card-top p  { font-size: 12px; opacity: 0.85; }
.steps { display: flex; align-items: center; justify-content: center; padding: 20px 35px 0; }
.step-item { display: flex; flex-direction: column; align-items: center; flex: 1; }
.step-circle { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; background: var(--border); color: var(--text-light); }
.step-circle.active { background: var(--primary); color: white; }
.step-circle.done { background: var(--success); color: white; }
.step-label { font-size: 10px; color: var(--text-light); margin-top: 5px; }
.step-line { flex: 1; height: 2px; background: var(--border); margin-bottom: 20px; }
.step-line.done { background: var(--success); }
.card-body { padding: 28px 35px 35px; }
.alert { padding: 13px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 9px; }
.alert.error { background: #fee2e2; color: #b91c1c; }
.alert.success { background: #d1fae5; color: #065f46; }
.form-group { margin-bottom: 18px; }
.form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--text-light); margin-bottom: 7px; text-transform: uppercase; }
.form-group input, .form-group select { width: 100%; padding: 12px 16px; border: 2px solid var(--border); border-radius: 10px; outline: none; }
.btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
.footer { text-align: center; padding: 16px; background: var(--primary); color: white; font-size: 13px; }
</style>
</head>
<body>

<div class="navbar">
    <h2><i class="fas fa-store"></i> Shop Finder</h2>
    <div style="display:flex;gap:10px;">
        <a href="customerlogin.html" class="nav-link"><i class="fas fa-user"></i> Login</a>
    </div>
</div>

<div class="page-wrap">
    <div class="card">
        <div class="card-top">
            <div class="icon"><i class="fas fa-key"></i></div>
            <h2>Reset Password</h2>
            <p>Enter your email to set a new password</p>
        </div>

        <div class="steps">
            <div class="step-item">
                <div class="step-circle <?php echo ($step == 1) ? 'active' : 'done'; ?>">
                    <?php echo ($step > 1) ? '<i class="fas fa-check"></i>' : '1'; ?>
                </div>
                <div class="step-label">Find Account</div>
            </div>
            <div class="step-line <?php echo ($step > 1) ? 'done' : ''; ?>"></div>
            <div class="step-item">
                <div class="step-circle <?php echo ($step == 3) ? 'active' : (($step > 3) ? 'done' : ''); ?>">
                    <?php echo ($step > 3) ? '<i class="fas fa-check"></i>' : '2'; ?>
                </div>
                <div class="step-label">New Password</div>
            </div>
        </div>

        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($step === 4): ?>
                <div class="alert success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <div style="text-align:center;">
                    <a href="customerlogin.html" class="btn-submit" style="text-decoration:none; display:inline-block;">Go to Login</a>
                </div>

            <?php elseif ($step === 1): ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Account Type</label>
                        <select name="user_type" required>
                            <option value="customer">Customer</option>
                            <option value="shop">Shop Owner</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="Enter registered email" required>
                    </div>
                    <button type="submit" name="find_account" class="btn-submit">Next Step</button>
                </form>

            <?php elseif ($step === 3): ?>
                <form method="POST">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" placeholder="Min. 6 characters" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" placeholder="Repeat password" required>
                    </div>
                    <button type="submit" name="reset_password" class="btn-submit">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="footer">
    &copy; 2026 <span>Elie</span> | Shop Finder
</div>

</body>
</html>