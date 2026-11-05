<?php
session_start();

// =============================================
// ADMIN AUTHENTICATION
// =============================================
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');

// Handle login
if (isset($_POST['admin_login'])) {
    if ($_POST['username'] === ADMIN_USERNAME && $_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admindashboard.php");
        exit();
    } else {
        $login_error = "Invalid admin credentials!";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admindashboard.php");
    exit();
}

// Show login if not logged in
if (!isset($_SESSION['admin_logged_in'])) {
    $show_login = true;
}

// =============================================
// DATABASE CONNECTION
// =============================================
$conn = new mysqli("localhost", "root", "", "shop_finder");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// =============================================
// HANDLE DELETE ACTIONS & FETCH DATA
// =============================================
if (isset($_SESSION['admin_logged_in'])) {

    if (isset($_GET['delete_customer'])) {
        $id = intval($_GET['delete_customer']);
        $conn->query("DELETE FROM customers WHERE id = $id");
        header("Location: admindashboard.php?section=customers");
        exit();
    }

    if (isset($_GET['delete_shop'])) {
        $id = intval($_GET['delete_shop']);
        $conn->query("DELETE FROM shops WHERE id = $id");
        header("Location: admindashboard.php?section=shops");
        exit();
    }

    if (isset($_GET['delete_contact'])) {
        $id = intval($_GET['delete_contact']);
        $conn->query("DELETE FROM contacts WHERE id = $id");
        header("Location: admindashboard.php?section=contacts");
        exit();
    }

    // =============================================
    // FETCH STATS
    // =============================================
    $r1 = $conn->query("SELECT COUNT(*) AS total FROM customers");
    $row1 = $r1->fetch_assoc();
    $total_customers = $row1['total'];

    $r2 = $conn->query("SELECT COUNT(*) AS total FROM shops");
    $row2 = $r2->fetch_assoc();
    $total_shops = $row2['total'];

    $r3 = $conn->query("SELECT COUNT(*) AS total FROM contacts");
    $row3 = $r3->fetch_assoc();
    $total_contacts = $row3['total'];

    $new_customers = 0;
    $res_nc = $conn->query("SELECT COUNT(*) AS total FROM customers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    if ($res_nc) {
        $row_nc = $res_nc->fetch_assoc();
        $new_customers = $row_nc['total'];
    }

    $new_shops = 0;
    $res_ns = $conn->query("SELECT COUNT(*) AS total FROM shops WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    if ($res_ns) {
        $row_ns = $res_ns->fetch_assoc();
        $new_shops = $row_ns['total'];
    }

    // Fetch all records
    $customers = $conn->query("SELECT * FROM customers ORDER BY id DESC");
    $shops     = $conn->query("SELECT * FROM shops ORDER BY id DESC");
    $contacts  = $conn->query("SELECT * FROM contacts ORDER BY id DESC");

    // Active section
    $section = isset($_GET['section']) ? $_GET['section'] : 'overview';
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | Shop Finder</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
*, *::before, *::after {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary:    #2d98da;
    --secondary:  #3867d6;
    --success:    #20bf6b;
    --warning:    #f7b731;
    --danger:     #fc5c65;
    --dark:       #1a1f2e;
    --sidebar-w:  260px;
    --bg:         #f0f4f8;
    --white:      #ffffff;
    --text:       #333;
    --text-light: #6b7280;
    --border:     #e5e7eb;
    --card-shadow: 0 4px 20px rgba(0,0,0,0.08);

    /* Sidebar blue palette */
    --sidebar-bg:        #1565c0;
    --sidebar-darker:    #0d47a1;
    --sidebar-border:    rgba(255,255,255,0.15);
    --sidebar-text:      rgba(255,255,255,0.70);
    --sidebar-text-dim:  rgba(255,255,255,0.35);
}

body {
    font-family: 'Poppins', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
}

a { text-decoration: none; color: inherit; }

/* ===== LOGIN PAGE ===== */
.login-page {
    min-height: 100vh;
    background: linear-gradient(135deg, var(--dark) 0%, #2c3e6e 50%, var(--primary) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.login-card {
    background: var(--white);
    border-radius: 20px;
    padding: 50px 45px;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 25px 60px rgba(0,0,0,0.3);
    text-align: center;
    animation: slideUp 0.5s ease;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to   { opacity: 1; transform: translateY(0); }
}

.login-card .brand {
    font-size: 28px;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 5px;
}

.login-card .brand-sub {
    font-size: 13px;
    color: var(--text-light);
    margin-bottom: 35px;
}

.login-card h2 {
    font-size: 22px;
    font-weight: 600;
    margin-bottom: 25px;
    color: var(--dark);
}

.form-group {
    position: relative;
    margin-bottom: 18px;
    text-align: left;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: var(--text-light);
    margin-bottom: 6px;
}

.form-group input {
    width: 100%;
    padding: 13px 16px 13px 44px;
    border: 2px solid var(--border);
    border-radius: 10px;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
    transition: 0.3s;
    outline: none;
}

.form-group input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(45,152,218,0.15);
}

.form-group i {
    position: absolute;
    left: 15px;
    top: 39px;
    color: var(--text-light);
    font-size: 14px;
}

.login-btn {
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
    transition: 0.3s;
    margin-top: 8px;
}

.login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(45,152,218,0.4);
}

.error-msg {
    background: #fee2e2;
    color: var(--danger);
    padding: 12px;
    border-radius: 8px;
    font-size: 13px;
    margin-bottom: 15px;
}

/* ===== DASHBOARD LAYOUT ===== */
.dashboard {
    display: flex;
    min-height: 100vh;
}

/* ===== SIDEBAR ===== */
.sidebar {
    width: var(--sidebar-w);
    background: linear-gradient(180deg, var(--sidebar-bg) 0%, var(--sidebar-darker) 100%);
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    display: flex;
    flex-direction: column;
    z-index: 100;
    box-shadow: 4px 0 20px rgba(13, 71, 161, 0.25);
}

.sidebar-brand {
    padding: 28px 25px 20px;
    border-bottom: 1px solid var(--sidebar-border);
    background: rgba(0,0,0,0.10);
}

.sidebar-brand h2 {
    color: #ffffff;
    font-size: 20px;
    font-weight: 700;
    letter-spacing: 0.3px;
}

.sidebar-brand h2 i {
    color: #90caf9;
}

.sidebar-brand p {
    color: var(--sidebar-text-dim);
    font-size: 11px;
    margin-top: 3px;
}

.sidebar-nav {
    flex: 1;
    padding: 20px 0;
    overflow-y: auto;
}

.nav-section-title {
    padding: 8px 25px;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--sidebar-text-dim);
    margin-top: 10px;
}

.sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 13px 25px;
    color: var(--sidebar-text);
    font-size: 14px;
    font-weight: 500;
    transition: 0.2s;
    border-left: 3px solid transparent;
    cursor: pointer;
}

.sidebar-nav a:hover {
    background: rgba(255,255,255,0.10);
    color: white;
    border-left-color: #90caf9;
}i

.sidebar-nav a.active {
    background: rgba(255,255,255,0.15);
    color: white;
    border-left-color: #90caf9;
    font-weight: 600;
}

.sidebar-nav a i {
    width: 18px;
    text-align: center;
    font-size: 15px;
}

.nav-count {
    margin-left: auto;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 10px;
}

.nav-count.blue   { background: rgba(45,152,218,0.25);  color: #90caf9; }
.nav-count.indigo { background: rgba(56,103,214,0.25);  color: #a5b4fc; }
.nav-count.green  { background: rgba(32,191,107,0.25);  color: #6ee7b7; }

.sidebar-footer {
    padding: 20px 25px;
    border-top: 1px solid var(--sidebar-border);
    background: rgba(0,0,0,0.08);
}

.sidebar-footer a {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--sidebar-text);
    font-size: 13px;
    transition: 0.2s;
    padding: 10px;
    border-radius: 8px;
    cursor: pointer;
}

.sidebar-footer a:hover {
    background: rgba(252,92,101,0.20);
    color: #ff8a80;
}

/* ===== MAIN CONTENT ===== */
.main {
    margin-left: var(--sidebar-w);
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* ===== TOPBAR ===== */
.topbar {
    background: var(--white);
    padding: 18px 35px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    position: sticky;
    top: 0;
    z-index: 50;
}

.topbar h1 {
    font-size: 20px;
    font-weight: 600;
    color: var(--dark);
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.back-home {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--primary);
    font-size: 13px;
    font-weight: 500;
    padding: 8px 16px;
    background: rgba(45,152,218,0.1);
    border-radius: 8px;
    transition: 0.2s;
}

.back-home:hover { background: rgba(45,152,218,0.2); }

.admin-badge {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 16px;
    background: var(--bg);
    border-radius: 50px;
}

.admin-badge .avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    font-weight: 700;
}

.admin-badge span {
    font-size: 13px;
    font-weight: 500;
    color: var(--text);
}

/* ===== PAGE CONTENT ===== */
.content {
    padding: 35px;
    flex: 1;
}

/* ===== STATS CARDS ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 22px;
    margin-bottom: 35px;
}

.stat-card {
    background: var(--white);
    border-radius: 16px;
    padding: 25px;
    box-shadow: var(--card-shadow);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.2s;
    animation: fadeIn 0.5s ease both;
}

.stat-card:nth-child(1) { animation-delay: 0.05s; }
.stat-card:nth-child(2) { animation-delay: 0.10s; }
.stat-card:nth-child(3) { animation-delay: 0.15s; }
.stat-card:nth-child(4) { animation-delay: 0.20s; }

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to   { opacity: 1; transform: translateY(0); }
}

.stat-card:hover { transform: translateY(-3px); }

.stat-icon {
    width: 58px;
    height: 58px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}

.stat-icon.blue   { background: rgba(45,152,218,0.12); color: var(--primary); }
.stat-icon.indigo { background: rgba(56,103,214,0.12); color: var(--secondary); }
.stat-icon.green  { background: rgba(32,191,107,0.12); color: var(--success); }
.stat-icon.yellow { background: rgba(247,183,49,0.12); color: var(--warning); }

.stat-info h3 {
    font-size: 28px;
    font-weight: 700;
    color: var(--dark);
    line-height: 1;
}

.stat-info p {
    font-size: 13px;
    color: var(--text-light);
    margin-top: 4px;
}

.badge-new {
    font-size: 11px;
    color: var(--success);
    font-weight: 600;
    margin-top: 4px;
}

/* ===== CARD ===== */
.card {
    background: var(--white);
    border-radius: 16px;
    box-shadow: var(--card-shadow);
    overflow: hidden;
    animation: fadeIn 0.4s ease;
}

.card-header {
    padding: 22px 28px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-header h2 {
    font-size: 16px;
    font-weight: 600;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-header h2 i { color: var(--primary); }

.count-badge {
    background: var(--bg);
    color: var(--text-light);
    font-size: 12px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
}

/* ===== TABLE ===== */
.table-wrap { overflow-x: auto; }

table { width: 100%; border-collapse: collapse; }

thead th {
    background: var(--bg);
    padding: 14px 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-light);
    text-align: left;
    white-space: nowrap;
}

tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background 0.15s;
}

tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #f8fafc; }

tbody td {
    padding: 15px 20px;
    font-size: 14px;
    color: var(--text);
    vertical-align: middle;
}

.id-chip {
    background: var(--bg);
    color: var(--text-light);
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.email-link { color: var(--primary); font-weight: 500; }
.date-text  { color: var(--text-light); font-size: 13px; }

.msg-preview {
    max-width: 280px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: var(--text-light);
    font-size: 13px;
    display: block;
}

.btn-delete {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    background: #fee2e2;
    color: var(--danger);
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    transition: 0.2s;
    white-space: nowrap;
}

.btn-delete:hover { background: var(--danger); color: white; }

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-light);
}

.empty-state i {
    font-size: 40px;
    margin-bottom: 12px;
    opacity: 0.3;
    display: block;
}

.empty-state p { font-size: 14px; }

/* ===== QUICK LINKS ===== */
.quick-links {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

.quick-link-card {
    background: var(--bg);
    border-radius: 14px;
    padding: 28px 20px;
    text-align: center;
    transition: 0.2s;
    border: 2px solid transparent;
    display: block;
    animation: fadeIn 0.5s ease both;
}

.quick-link-card:nth-child(1) { animation-delay: 0.10s; }
.quick-link-card:nth-child(2) { animation-delay: 0.15s; }
.quick-link-card:nth-child(3) { animation-delay: 0.20s; }
.quick-link-card:nth-child(4) { animation-delay: 0.25s; }

.quick-link-card:hover {
    border-color: var(--primary);
    transform: translateY(-3px);
    background: var(--white);
}

.quick-link-card i {
    font-size: 32px;
    margin-bottom: 12px;
    display: block;
}

.quick-link-card h3 {
    font-size: 14px;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 5px;
}

.quick-link-card p {
    font-size: 12px;
    color: var(--text-light);
}

/* ===== FOOTER ===== */
.main-footer {
    background: var(--white);
    border-top: 1px solid var(--border);
    padding: 16px 35px;
    text-align: center;
    font-size: 13px;
    color: var(--text-light);
}

.main-footer span { color: var(--primary); font-weight: 600; }

/* ===== RESPONSIVE ===== */
@media (max-width: 1100px) {
    .stats-grid  { grid-template-columns: repeat(2, 1fr); }
    .quick-links { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .sidebar  { width: 0; overflow: hidden; }
    .main     { margin-left: 0; }
    .content  { padding: 20px; }
    .topbar   { padding: 15px 20px; }
    .stats-grid  { grid-template-columns: repeat(2, 1fr); gap: 14px; }
    .quick-links { grid-template-columns: repeat(2, 1fr); }
}
</style>
</head>
<body>

<?php if (isset($show_login)): ?>
<!-- ============================================================
     LOGIN PAGE
============================================================ -->
<div class="login-page">
    <div class="login-card">
        <div class="brand"><i class="fas fa-store"></i> Shop Finder</div>
        <div class="brand-sub">Admin Control Panel</div>
        <h2>Sign In</h2>

        <?php if (isset($login_error)): ?>
            <div class="error-msg">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $login_error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="admindashboard.php">
            <div class="form-group">
                <label>Username</label>
                <i class="fas fa-user"></i>
                <input type="text" name="username" placeholder="admin" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button class="login-btn" type="submit" name="admin_login">
                <i class="fas fa-sign-in-alt"></i> Sign In to Dashboard
            </button>
        </form>

        <div style="margin-top:20px;">
            <a href="index.php" style="color:#6b7280;font-size:13px;">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ============================================================
     DASHBOARD
============================================================ -->
<div class="dashboard">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <h2><i class="fas fa-store"></i> Shop Finder</h2>
            <p>Admin Control Panel</p>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-title">Main</div>
            <a href="admindashboard.php?section=overview"
               class="<?php echo ($section === 'overview') ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Overview
            </a>

            <div class="nav-section-title">Management</div>
            <a href="admindashboard.php?section=customers"
               class="<?php echo ($section === 'customers') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Customers
                <span class="nav-count blue"><?php echo $total_customers; ?></span>
            </a>
            <a href="admindashboard.php?section=shops"
               class="<?php echo ($section === 'shops') ? 'active' : ''; ?>">
                <i class="fas fa-store-alt"></i> Shops
                <span class="nav-count indigo"><?php echo $total_shops; ?></span>
            </a>
            <a href="admindashboard.php?section=contacts"
               class="<?php echo ($section === 'contacts') ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i> Messages
                <span class="nav-count green"><?php echo $total_contacts; ?></span>
            </a>

            <div class="nav-section-title">Site</div>
            <a href="index.php">
                <i class="fas fa-home"></i> View Website
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="admindashboard.php?logout=1">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <div class="main">

        <!-- TOPBAR -->
        <div class="topbar">
            <h1>
                <?php
                if ($section === 'overview') {
                    echo '<i class="fas fa-th-large" style="color:var(--primary);margin-right:10px;"></i> Overview';
                } elseif ($section === 'customers') {
                    echo '<i class="fas fa-users" style="color:var(--primary);margin-right:10px;"></i> Manage Customers';
                } elseif ($section === 'shops') {
                    echo '<i class="fas fa-store-alt" style="color:var(--primary);margin-right:10px;"></i> Manage Shops';
                } elseif ($section === 'contacts') {
                    echo '<i class="fas fa-envelope" style="color:var(--primary);margin-right:10px;"></i> Contact Messages';
                }
                ?>
            </h1>
            <div class="topbar-right">
                <a href="index.php" class="back-home">
                    <i class="fas fa-external-link-alt"></i> View Site
                </a>
                <div class="admin-badge">
                    <div class="avatar">A</div>
                    <span>Administrator</span>
                </div>
            </div>
        </div>

        <!-- PAGE CONTENT -->
        <div class="content">

        <?php if ($section === 'overview'): ?>
        <!-- ===== OVERVIEW ===== -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_customers; ?></h3>
                    <p>Total Customers</p>
                    <?php if ($new_customers > 0): ?>
                        <div class="badge-new">
                            <i class="fas fa-arrow-up"></i>
                            <?php echo $new_customers; ?> new this week
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon indigo"><i class="fas fa-store-alt"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_shops; ?></h3>
                    <p>Registered Shops</p>
                    <?php if ($new_shops > 0): ?>
                        <div class="badge-new">
                            <i class="fas fa-arrow-up"></i>
                            <?php echo $new_shops; ?> new this week
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-envelope"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_contacts; ?></h3>
                    <p>Contact Messages</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow"><i class="fas fa-chart-line"></i></div>
                <div class="stat-info">
                    <h3><?php echo ($total_customers + $total_shops); ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            </div>
            <div style="padding:25px;">
                <div class="quick-links">
                    <a href="admindashboard.php?section=customers" class="quick-link-card">
                        <i class="fas fa-users" style="color:var(--primary);"></i>
                        <h3>Customers</h3>
                        <p>View &amp; manage all customers</p>
                    </a>
                    <a href="admindashboard.php?section=shops" class="quick-link-card">
                        <i class="fas fa-store-alt" style="color:var(--secondary);"></i>
                        <h3>Shops</h3>
                        <p>View &amp; manage all shops</p>
                    </a>
                    <a href="admindashboard.php?section=contacts" class="quick-link-card">
                        <i class="fas fa-envelope" style="color:var(--success);"></i>
                        <h3>Messages</h3>
                        <p>Read contact messages</p>
                    </a>
                    <a href="index.php" class="quick-link-card">
                        <i class="fas fa-globe" style="color:var(--warning);"></i>
                        <h3>Website</h3>
                        <p>View the public site</p>
                    </a>
                </div>
            </div>
        </div>

        <?php elseif ($section === 'customers'): ?>
        <!-- ===== CUSTOMERS ===== -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-users"></i> All Customers</h2>
                <span class="count-badge"><?php echo $total_customers; ?> total</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($customers && $customers->num_rows > 0): ?>
                        <?php while ($row = $customers->fetch_assoc()): ?>
                        <tr>
                            <td><span class="id-chip">#<?php echo $row['id']; ?></span></td>
                            <td><span class="email-link"><?php echo htmlspecialchars($row['email']); ?></span></td>
                            <td>
                                <span class="date-text">
                                <?php
                                if (isset($row['created_at']) && $row['created_at'] != '') {
                                    echo date('M d, Y', strtotime($row['created_at']));
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                                </span>
                            </td>
                            <td>
                                <a href="admindashboard.php?delete_customer=<?php echo $row['id']; ?>"
                                   class="btn-delete"
                                   onclick="return confirm('Delete this customer?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4">
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <p>No customers registered yet.</p>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif ($section === 'shops'): ?>
        <!-- ===== SHOPS ===== -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-store-alt"></i> All Shops</h2>
                <span class="count-badge"><?php echo $total_shops; ?> total</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($shops && $shops->num_rows > 0): ?>
                        <?php while ($row = $shops->fetch_assoc()): ?>
                        <tr>
                            <td><span class="id-chip">#<?php echo $row['id']; ?></span></td>
                            <td><span class="email-link"><?php echo htmlspecialchars($row['email']); ?></span></td>
                            <td>
                                <span class="date-text">
                                <?php
                                if (isset($row['created_at']) && $row['created_at'] != '') {
                                    echo date('M d, Y', strtotime($row['created_at']));
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                                </span>
                            </td>
                            <td>
                                <a href="admindashboard.php?delete_shop=<?php echo $row['id']; ?>"
                                   class="btn-delete"
                                   onclick="return confirm('Delete this shop?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4">
                            <div class="empty-state">
                                <i class="fas fa-store-alt"></i>
                                <p>No shops registered yet.</p>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif ($section === 'contacts'): ?>
        <!-- ===== CONTACTS ===== -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-envelope"></i> Contact Messages</h2>
                <span class="count-badge"><?php echo $total_contacts; ?> messages</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($contacts && $contacts->num_rows > 0): ?>
                        <?php while ($row = $contacts->fetch_assoc()): ?>
                        <tr>
                            <td><span class="id-chip">#<?php echo $row['id']; ?></span></td>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><span class="email-link"><?php echo htmlspecialchars($row['email']); ?></span></td>
                            <td>
                                <span class="msg-preview" title="<?php echo htmlspecialchars($row['message']); ?>">
                                    <?php echo htmlspecialchars($row['message']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="date-text">
                                <?php
                                if (isset($row['created_at']) && $row['created_at'] != '') {
                                    echo date('M d, Y', strtotime($row['created_at']));
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                                </span>
                            </td>
                            <td>
                                <a href="admindashboard.php?delete_contact=<?php echo $row['id']; ?>"
                                   class="btn-delete"
                                   onclick="return confirm('Delete this message?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-envelope-open"></i>
                                <p>No contact messages yet.</p>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php endif; ?>

        </div><!-- /content -->

        <div class="main-footer">
            &copy; 2026 <span>Elie</span> | Shop Finder &mdash; Admin Panel
        </div>
    </div><!-- /main -->

</div><!-- /dashboard -->
<?php endif; ?>

</body>
</html>