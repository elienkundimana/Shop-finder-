<?php
session_start();

// =============================================
// DETERMINE WHO IS LOGGED IN
// =============================================
$user_type  = '';
$user_email = '';

if (isset($_SESSION['customer_email'])) {
    $user_type  = 'customer';
    $user_email = $_SESSION['customer_email'];
} elseif (isset($_SESSION['shop_email'])) {
    $user_type  = 'shop';
    $user_email = $_SESSION['shop_email'];
} else {
    header("Location: customerlogin.html");
    exit();
}

// =============================================
// DATABASE
// =============================================
$conn = new mysqli("localhost", "root", "", "shop_finder");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error   = '';
$success = '';

// =============================================
// HANDLE FORM SUBMISSION
// =============================================
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        $hashed_current = md5($current_password);
        $safe_email     = mysqli_real_escape_string($conn, $user_email);

        // Check current password against correct table
        $table = ($user_type === 'customer') ? 'customers' : 'shops';
        $res   = $conn->query("SELECT id FROM $table WHERE email='$safe_email' AND password='$hashed_current'");

        if ($res && $res->num_rows > 0) {
            $hashed_new = md5($new_password);
            $conn->query("UPDATE $table SET password='$hashed_new' WHERE email='$safe_email'");
            $success = "Password changed successfully!";
        } else {
            $error = "Current password is incorrect.";
        }
    }
}

$conn->close();

$dashboard_link = ($user_type === 'customer') ? 'customerdashboard.php' : 'shopdashboard.php';
$logout_link    = ($user_type === 'customer') ? 'customerlogout.php'    : 'shoplogout.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Change Password | Shop Finder</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

:root {
    --primary:   #2d98da;
    --secondary: #3867d6;
    --success:   #20bf6b;
    --danger:    #fc5c65;
    --dark:      #1a1f2e;
    --bg:        #f0f4f8;
    --white:     #ffffff;
    --text:      #333;
    --text-light:#6b7280;
    --border:    #e5e7eb;
}

body {
    font-family: 'Poppins', sans-serif;
    background: var(--bg);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* NAVBAR */
.navbar {
    background: var(--white);
    padding: 15px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.07);
}

.navbar h2 { color: var(--primary); font-size: 20px; font-weight: 700; }

.navbar-links { display: flex; align-items: center; gap: 12px; }

.nav-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    transition: 0.2s;
    color: var(--text-light);
}

.nav-link:hover { background: var(--bg); color: var(--primary); }
.nav-link.primary { background: var(--primary); color: white; }
.nav-link.primary:hover { background: var(--secondary); color: white; }

/* PAGE */
.page-wrap {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 50px 20px;
}

.card {
    background: var(--white);
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 480px;
    overflow: hidden;
    animation: slideUp 0.4s ease;
}

@keyframes slideUp {
    from { opacity:0; transform:translateY(20px); }
    to   { opacity:1; transform:translateY(0); }
}

.card-top {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    padding: 32px 35px;
    color: white;
    text-align: center;
}

.card-top .icon {
    width: 64px;
    height: 64px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    margin: 0 auto 16px;
}

.card-top h2 { font-size: 22px; font-weight: 700; margin-bottom: 6px; }
.card-top p  { font-size: 13px; opacity: 0.85; }

.card-body { padding: 35px; }

/* ALERTS */
.alert {
    padding: 13px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    gap: 9px;
}

.alert.error   { background: #fee2e2; color: #b91c1c; }
.alert.success { background: #d1fae5; color: #065f46; }

/* FORM */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 7px;
}

.input-wrap {
    position: relative;
}

.input-wrap input {
    width: 100%;
    padding: 12px 44px 12px 16px;
    border: 2px solid var(--border);
    border-radius: 10px;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
    color: var(--text);
    outline: none;
    transition: 0.2s;
}

.input-wrap input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(45,152,218,0.12);
}

.toggle-pw {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-light);
    cursor: pointer;
    font-size: 14px;
    transition: 0.2s;
}

.toggle-pw:hover { color: var(--primary); }

.hint {
    font-size: 11px;
    color: var(--text-light);
    margin-top: 5px;
}

/* PASSWORD STRENGTH */
.strength-bar {
    height: 4px;
    border-radius: 4px;
    margin-top: 8px;
    background: var(--border);
    overflow: hidden;
}

.strength-fill {
    height: 100%;
    border-radius: 4px;
    width: 0%;
    transition: 0.3s;
}

.strength-label {
    font-size: 11px;
    margin-top: 4px;
    font-weight: 600;
}

/* BUTTON */
.btn-submit {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transition: 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 8px;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(45,152,218,0.35);
}

.divider {
    text-align: center;
    margin: 20px 0 0;
    font-size: 13px;
    color: var(--text-light);
}

.divider a { color: var(--primary); font-weight: 600; }
.divider a:hover { text-decoration: underline; }

/* FOOTER */
.footer {
    text-align: center;
    padding: 16px;
    background: var(--primary);
    color: white;
    font-size: 13px;
}

.footer span { color: #ffeaa7; font-weight: 600; }
</style>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <h2><i class="fas fa-store"></i> Shop Finder</h2>
    <div class="navbar-links">
        <a href="<?php echo $dashboard_link; ?>" class="nav-link">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="forgot-password.php" class="nav-link">
            <i class="fas fa-key"></i> Forgot Password
        </a>
        <a href="<?php echo $logout_link; ?>" class="nav-link primary">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- PAGE -->
<div class="page-wrap">
    <div class="card">
        <div class="card-top">
            <div class="icon"><i class="fas fa-lock"></i></div>
            <h2>Change Password</h2>
            <p>Logged in as: <?php echo htmlspecialchars($user_email); ?> (<?php echo ucfirst($user_type); ?>)</p>
        </div>
        <div class="card-body">

            <?php if ($error): ?>
                <div class="alert error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" id="changeForm">
                <div class="form-group">
                    <label>Current Password</label>
                    <div class="input-wrap">
                        <input type="password" name="current_password" id="current_pw" placeholder="Enter current password" required>
                        <span class="toggle-pw" onclick="togglePw('current_pw', this)"><i class="fas fa-eye"></i></span>
                    </div>
                </div>

                <div class="form-group">
                    <label>New Password</label>
                    <div class="input-wrap">
                        <input type="password" name="new_password" id="new_pw" placeholder="Enter new password" required oninput="checkStrength(this.value)">
                        <span class="toggle-pw" onclick="togglePw('new_pw', this)"><i class="fas fa-eye"></i></span>
                    </div>
                    <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                    <div class="strength-label" id="strengthLabel" style="color:var(--text-light);">Enter a password</div>
                    <div class="hint">Minimum 6 characters.</div>
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <div class="input-wrap">
                        <input type="password" name="confirm_password" id="confirm_pw" placeholder="Re-enter new password" required>
                        <span class="toggle-pw" onclick="togglePw('confirm_pw', this)"><i class="fas fa-eye"></i></span>
                    </div>
                    <div class="hint" id="matchHint"></div>
                </div>

                <button type="submit" name="change_password" class="btn-submit">
                    <i class="fas fa-save"></i> Update Password
                </button>
            </form>

            <div class="divider">
                <a href="<?php echo $dashboard_link; ?>"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>

<div class="footer">
    &copy; 2026 <span>Elie</span> | Shop Finder
</div>

<script>
// Toggle password visibility
function togglePw(id, el) {
    var input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        el.innerHTML = '<i class="fas fa-eye-slash"></i>';
    } else {
        input.type = 'password';
        el.innerHTML = '<i class="fas fa-eye"></i>';
    }
}

// Password strength checker
function checkStrength(val) {
    var fill  = document.getElementById('strengthFill');
    var label = document.getElementById('strengthLabel');
    var score = 0;

    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    var levels = [
        { pct: '0%',   color: '#e5e7eb', text: 'Enter a password',  textColor: '#6b7280' },
        { pct: '25%',  color: '#fc5c65', text: 'Weak',              textColor: '#fc5c65' },
        { pct: '50%',  color: '#f7b731', text: 'Fair',              textColor: '#f7b731' },
        { pct: '75%',  color: '#2d98da', text: 'Good',              textColor: '#2d98da' },
        { pct: '90%',  color: '#20bf6b', text: 'Strong',            textColor: '#20bf6b' },
        { pct: '100%', color: '#20bf6b', text: 'Very Strong',       textColor: '#20bf6b' }
    ];

    var lvl = levels[score] || levels[0];
    fill.style.width      = lvl.pct;
    fill.style.background = lvl.color;
    label.textContent     = lvl.text;
    label.style.color     = lvl.textColor;
}

// Confirm password match hint
document.getElementById('confirm_pw').addEventListener('input', function() {
    var hint = document.getElementById('matchHint');
    if (this.value === document.getElementById('new_pw').value) {
        hint.textContent = '✔ Passwords match';
        hint.style.color = '#20bf6b';
    } else {
        hint.textContent = '✘ Passwords do not match';
        hint.style.color = '#fc5c65';
    }
});
</script>

</body>
</html>
