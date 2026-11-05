<?php
session_start();

// =============================================
// AUTH CHECK
// =============================================
if (!isset($_SESSION['customer_email'])) {
    header("Location: customerlogin.html");
    exit();
}

$customer_email = $_SESSION['customer_email'];

// =============================================
// DATABASE CONNECTION
// =============================================
$conn = new mysqli("localhost", "root", "", "shop_finder");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// =============================================
// CREATE TABLES IF THEY DON'T EXIST YET
// =============================================
$conn->query("CREATE TABLE IF NOT EXISTS customer_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    full_name VARCHAR(150),
    phone VARCHAR(30),
    address VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS shop_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    shop_name VARCHAR(150),
    phone VARCHAR(30),
    address VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// =============================================
// GET CUSTOMER ID SAFELY
// =============================================
$customer_id = 0;
$safe_email  = mysqli_real_escape_string($conn, $customer_email);
$cid_res     = $conn->query("SELECT id FROM customers WHERE email = '$safe_email'");
if ($cid_res && $cid_res->num_rows > 0) {
    $cid_row     = $cid_res->fetch_assoc();
    $customer_id = $cid_row['id'];
}

// =============================================
// HANDLE ACTIONS
// =============================================

// Save / Update Customer Profile
if (isset($_POST['save_profile']) && $customer_id > 0) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $phone     = mysqli_real_escape_string($conn, $_POST['phone']);
    $address   = mysqli_real_escape_string($conn, $_POST['address']);

    $check = $conn->query("SELECT id FROM customer_profiles WHERE customer_id = $customer_id");
    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE customer_profiles SET full_name='$full_name', phone='$phone', address='$address' WHERE customer_id=$customer_id");
    } else {
        $conn->query("INSERT INTO customer_profiles (customer_id, full_name, phone, address) VALUES ($customer_id, '$full_name', '$phone', '$address')");
    }
    $profile_success = "Profile updated successfully!";
}

// =============================================
// FETCH DATA SAFELY
// =============================================

// Customer profile
$profile = array();
if ($customer_id > 0) {
    $profile_res = $conn->query("SELECT * FROM customer_profiles WHERE customer_id = $customer_id");
    if ($profile_res && $profile_res->num_rows > 0) {
        $profile = $profile_res->fetch_assoc();
    }
}

// Total shops
$total_shops = 0;
$ts_res = $conn->query("SELECT COUNT(*) AS total FROM shops");
if ($ts_res) {
    $ts_row      = $ts_res->fetch_assoc();
    $total_shops = $ts_row['total'];
}

// Total products
$total_products = 0;
$tp_res = $conn->query("SELECT COUNT(*) AS total FROM products");
if ($tp_res) {
    $tp_row         = $tp_res->fetch_assoc();
    $total_products = $tp_row['total'];
}

// Messages sent by this customer
$total_messages = 0;
$messages_res   = null;
if ($customer_id > 0) {
    $messages_res = $conn->query("SELECT * FROM contacts WHERE email = '$safe_email' ORDER BY id DESC");
    $msg_count    = $conn->query("SELECT COUNT(*) AS total FROM contacts WHERE email = '$safe_email'");
    if ($msg_count) {
        $msg_row        = $msg_count->fetch_assoc();
        $total_messages = $msg_row['total'];
    }
}

// All shops with profiles
$shops_res = $conn->query("SELECT s.id, s.email, sp.shop_name, sp.phone, sp.address, sp.description FROM shops s LEFT JOIN shop_profiles sp ON s.id = sp.shop_id ORDER BY s.id DESC");

// All products with shop info
$products_res = $conn->query("SELECT p.*, sp.shop_name FROM products p LEFT JOIN shop_profiles sp ON p.shop_id = sp.shop_id ORDER BY p.id DESC");

// Search
$search_query            = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
$search_results_shops    = array();
$search_results_products = array();

if ($search_query !== '') {
    $sr_shops = $conn->query("SELECT s.id, s.email, sp.shop_name, sp.address, sp.phone, sp.description FROM shops s LEFT JOIN shop_profiles sp ON s.id = sp.shop_id WHERE sp.shop_name LIKE '%$search_query%' OR sp.address LIKE '%$search_query%' OR sp.description LIKE '%$search_query%'");
    if ($sr_shops) {
        while ($row = $sr_shops->fetch_assoc()) {
            $search_results_shops[] = $row;
        }
    }

    $sr_products = $conn->query("SELECT p.*, sp.shop_name FROM products p LEFT JOIN shop_profiles sp ON p.shop_id = sp.shop_id WHERE p.name LIKE '%$search_query%' OR p.description LIKE '%$search_query%'");
    if ($sr_products) {
        while ($row = $sr_products->fetch_assoc()) {
            $search_results_products[] = $row;
        }
    }
}

// Active section
$section = isset($_GET['section']) ? $_GET['section'] : 'overview';

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Dashboard | Shop Finder</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
*, *::before, *::after {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary:      #2d98da;
    --secondary:    #3867d6;
    --success:      #20bf6b;
    --warning:      #f7b731;
    --danger:       #fc5c65;
    --dark:         #0d2b4e;
    --sidebar-w:    260px;
    --bg:           #f0f4f8;
    --white:        #ffffff;
    --text:         #333;
    --text-light:   #6b7280;
    --border:       #e5e7eb;
    --card-shadow:  0 4px 20px rgba(0,0,0,0.08);

    /* Sidebar blue palette */
    --sidebar-bg:        #1565c0;
    --sidebar-darker:    #0d47a1;
    --sidebar-active-bg: rgba(255,255,255,0.15);
    --sidebar-hover-bg:  rgba(255,255,255,0.10);
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

/* ===== LAYOUT ===== */
.dashboard { display: flex; min-height: 100vh; }

/* ===== SIDEBAR ===== */
.sidebar {
    width: var(--sidebar-w);
    background: linear-gradient(180deg, var(--sidebar-bg) 0%, var(--sidebar-darker) 100%);
    position: fixed;
    top: 0; left: 0;
    height: 100vh;
    display: flex;
    flex-direction: column;
    z-index: 100;
    box-shadow: 4px 0 20px rgba(13, 71, 161, 0.25);
}

.sidebar-brand {
    padding: 25px 25px 18px;
    border-bottom: 1px solid var(--sidebar-border);
    background: rgba(0,0,0,0.10);
}

.sidebar-brand h2 {
    color: #ffffff;
    font-size: 19px;
    font-weight: 700;
    letter-spacing: 0.3px;
}

.sidebar-brand h2 i {
    color: #90caf9;
}

.sidebar-brand .customer-email {
    color: var(--sidebar-text-dim);
    font-size: 11px;
    margin-top: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-nav {
    flex: 1;
    padding: 18px 0;
    overflow-y: auto;
}

.nav-section-title {
    padding: 8px 25px;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--sidebar-text-dim);
    margin-top: 8px;
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
}

.sidebar-nav a:hover {
    background: var(--sidebar-hover-bg);
    color: #ffffff;
    border-left-color: #90caf9;
}

.sidebar-nav a.active {
    background: var(--sidebar-active-bg);
    color: #ffffff;
    border-left-color: #90caf9;
    font-weight: 600;
}

.sidebar-nav a i { width: 18px; text-align: center; font-size: 15px; }

.sidebar-footer {
    padding: 18px 25px;
    border-top: 1px solid var(--sidebar-border);
    background: rgba(0,0,0,0.08);
}

.sidebar-footer a {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--sidebar-text);
    font-size: 13px;
    padding: 10px;
    border-radius: 8px;
    transition: 0.2s;
}

.sidebar-footer a:hover {
    background: rgba(252,92,101,0.20);
    color: #ff8a80;
}

/* ===== MAIN ===== */
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
    padding: 16px 35px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    position: sticky;
    top: 0;
    z-index: 50;
    gap: 15px;
}

.topbar h1 {
    font-size: 19px;
    font-weight: 600;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 10px;
    white-space: nowrap;
}

.topbar h1 i { color: var(--primary); }
.topbar-right { display: flex; align-items: center; gap: 14px; }

.customer-badge {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 7px 16px;
    background: var(--bg);
    border-radius: 50px;
}

.customer-badge .avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 13px;
    font-weight: 700;
}

.customer-badge span { font-size: 12px; font-weight: 500; color: var(--text-light); }

.btn-site {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--primary);
    font-size: 12px;
    font-weight: 500;
    padding: 7px 14px;
    background: rgba(45,152,218,0.08);
    border-radius: 8px;
    transition: 0.2s;
}

.btn-site:hover { background: rgba(45,152,218,0.16); }

/* ===== CONTENT ===== */
.content { padding: 30px 35px; flex: 1; }

/* ===== STATS ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 28px;
}

.stat-card {
    background: var(--white);
    border-radius: 16px;
    padding: 22px;
    box-shadow: var(--card-shadow);
    display: flex;
    align-items: center;
    gap: 18px;
    transition: transform 0.2s;
    animation: fadeIn 0.5s ease both;
}

.stat-card:nth-child(1) { animation-delay: 0.05s; }
.stat-card:nth-child(2) { animation-delay: 0.10s; }
.stat-card:nth-child(3) { animation-delay: 0.15s; }
.stat-card:nth-child(4) { animation-delay: 0.20s; }

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
}

.stat-card:hover { transform: translateY(-3px); }

.stat-icon {
    width: 54px;
    height: 54px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.stat-icon.blue   { background: rgba(45,152,218,0.1);  color: var(--primary); }
.stat-icon.indigo { background: rgba(56,103,214,0.1);  color: var(--secondary); }
.stat-icon.green  { background: rgba(32,191,107,0.1);  color: var(--success); }
.stat-icon.yellow { background: rgba(247,183,49,0.1);  color: var(--warning); }

.stat-info h3 { font-size: 26px; font-weight: 700; color: var(--dark); line-height: 1; }
.stat-info p  { font-size: 12px; color: var(--text-light); margin-top: 4px; }

/* ===== CARD ===== */
.card {
    background: var(--white);
    border-radius: 16px;
    box-shadow: var(--card-shadow);
    overflow: hidden;
    margin-bottom: 25px;
    animation: fadeIn 0.4s ease;
}

.card-header {
    padding: 20px 26px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}

.card-header h2 {
    font-size: 15px;
    font-weight: 600;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 9px;
}

.card-header h2 i { color: var(--primary); }
.card-body { padding: 26px; }

/* ===== SEARCH ===== */
.search-wrap { margin-bottom: 28px; }

.search-form {
    display: flex;
    gap: 10px;
    width: 100%;
}

.search-input {
    flex: 1;
    padding: 13px 18px 13px 44px;
    border: 2px solid var(--border);
    border-radius: 10px;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
    outline: none;
    transition: 0.2s;
    background-color: var(--white);
}

.search-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(45,152,218,0.12);
}

.search-btn {
    padding: 13px 24px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transition: 0.2s;
    white-space: nowrap;
}

.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(45,152,218,0.35);
}

.search-results-label {
    font-size: 13px;
    color: var(--text-light);
    margin-bottom: 16px;
    padding: 10px 14px;
    background: var(--bg);
    border-radius: 8px;
}

.search-results-label strong { color: var(--primary); }

/* ===== SHOP GRID ===== */
.shop-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}

.shop-card {
    border: 2px solid var(--border);
    border-radius: 14px;
    padding: 20px;
    transition: 0.2s;
    animation: fadeIn 0.4s ease both;
}

.shop-card:hover {
    border-color: var(--primary);
    box-shadow: 0 6px 20px rgba(45,152,218,0.12);
    transform: translateY(-2px);
}

.shop-card-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    margin-bottom: 14px;
}

.shop-card h3 { font-size: 15px; font-weight: 600; color: var(--dark); margin-bottom: 6px; }

.shop-meta {
    font-size: 12px;
    color: var(--text-light);
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.shop-meta i { color: var(--primary); width: 14px; }

.shop-desc {
    font-size: 12px;
    color: var(--text-light);
    margin-top: 10px;
    line-height: 1.5;
}

.no-profile-tag {
    font-size: 11px;
    color: var(--warning);
    background: rgba(247,183,49,0.1);
    padding: 3px 9px;
    border-radius: 6px;
    display: inline-block;
    margin-top: 6px;
}

/* ===== PRODUCT GRID ===== */
.product-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}

.product-card {
    border: 2px solid var(--border);
    border-radius: 14px;
    padding: 20px;
    transition: 0.2s;
    animation: fadeIn 0.4s ease both;
}

.product-card:hover {
    border-color: var(--success);
    box-shadow: 0 6px 20px rgba(32,191,107,0.1);
    transform: translateY(-2px);
}

.product-card h3 { font-size: 14px; font-weight: 600; color: var(--dark); margin-bottom: 8px; }

.product-price { font-size: 16px; font-weight: 700; color: var(--success); margin-bottom: 6px; }

.product-shop {
    font-size: 12px;
    color: var(--primary);
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 6px;
}

.product-stock {
    font-size: 11px;
    padding: 3px 9px;
    border-radius: 6px;
    display: inline-block;
    font-weight: 600;
}

.product-stock.ok  { background: rgba(32,191,107,0.1); color: var(--success); }
.product-stock.low { background: rgba(252,92,101,0.1); color: var(--danger); }

.product-desc {
    font-size: 12px;
    color: var(--text-light);
    margin-top: 10px;
    line-height: 1.5;
}

/* ===== MESSAGES ===== */
.message-card {
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 14px;
    transition: 0.2s;
}

.message-card:hover { border-color: var(--primary); box-shadow: 0 4px 12px rgba(45,152,218,0.1); }

.message-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    flex-wrap: wrap;
    gap: 8px;
}

.message-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--primary);
    background: rgba(45,152,218,0.08);
    padding: 3px 10px;
    border-radius: 6px;
}

.message-date { font-size: 11px; color: var(--text-light); }
.message-body { font-size: 13px; color: var(--text); line-height: 1.6; }

/* ===== FORM ===== */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
}

.form-group-f { display: flex; flex-direction: column; gap: 6px; }
.form-group-f.span2 { grid-column: span 2; }

.form-group-f label {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-light);
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.form-group-f input,
.form-group-f textarea {
    padding: 11px 14px;
    border: 2px solid var(--border);
    border-radius: 9px;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
    color: var(--text);
    transition: 0.2s;
    outline: none;
}

.form-group-f input:focus,
.form-group-f textarea:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(45,152,218,0.12);
}

.btn-save {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    border: none;
    border-radius: 9px;
    font-size: 14px;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transition: 0.2s;
    margin-top: 8px;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(45,152,218,0.35);
}

.success-alert {
    background: #d1fae5;
    color: #065f46;
    padding: 12px 16px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* ===== QUICK LINKS ===== */
.quick-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

.quick-card {
    border-radius: 14px;
    padding: 24px 18px;
    text-align: center;
    transition: 0.2s;
    border: 2px solid transparent;
    background: var(--bg);
    display: block;
    animation: fadeIn 0.5s ease both;
}

.quick-card:hover { border-color: var(--primary); background: var(--white); transform: translateY(-3px); }
.quick-card i     { font-size: 28px; display: block; margin-bottom: 10px; }
.quick-card h3    { font-size: 13px; font-weight: 600; color: var(--dark); margin-bottom: 4px; }
.quick-card p     { font-size: 11px; color: var(--text-light); }

/* ===== EMPTY STATE ===== */
.empty-state {
    text-align: center;
    padding: 50px 20px;
    color: var(--text-light);
}

.empty-state i { font-size: 40px; margin-bottom: 12px; opacity: 0.25; display: block; }
.empty-state p { font-size: 14px; }

/* ===== COUNT BADGE ===== */
.count-badge {
    background: var(--bg);
    color: var(--text-light);
    font-size: 12px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
}

/* ===== FOOTER ===== */
.main-footer {
    background: var(--white);
    border-top: 1px solid var(--border);
    padding: 15px 35px;
    text-align: center;
    font-size: 12px;
    color: var(--text-light);
}

.main-footer span { color: var(--primary); font-weight: 600; }

/* ===== RESPONSIVE ===== */
@media (max-width: 1100px) {
    .stats-grid   { grid-template-columns: repeat(2, 1fr); }
    .shop-grid    { grid-template-columns: repeat(2, 1fr); }
    .product-grid { grid-template-columns: repeat(2, 1fr); }
    .quick-grid   { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .sidebar  { width: 0; overflow: hidden; }
    .main     { margin-left: 0; }
    .content  { padding: 18px; }
    .topbar   { padding: 14px 18px; }
    .stats-grid   { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .shop-grid    { grid-template-columns: 1fr; }
    .product-grid { grid-template-columns: 1fr; }
    .form-grid    { grid-template-columns: 1fr; }
    .form-group-f.span2 { grid-column: span 1; }
    .quick-grid   { grid-template-columns: repeat(2, 1fr); }
}
</style>
</head>
<body>

<div class="dashboard">

    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <h2><i class="fas fa-user-circle"></i> My Dashboard</h2>
            <div class="customer-email"><?php echo htmlspecialchars($customer_email); ?></div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-title">Menu</div>
            <a href="customerdashboard.php?section=overview"
               class="<?php echo ($section === 'overview') ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Overview
            </a>

            <div class="nav-section-title">Discover</div>
            <a href="customerdashboard.php?section=search"
               class="<?php echo ($section === 'search') ? 'active' : ''; ?>">
                <i class="fas fa-search"></i> Search
            </a>
            <a href="customerdashboard.php?section=shops"
               class="<?php echo ($section === 'shops') ? 'active' : ''; ?>">
                <i class="fas fa-store-alt"></i> Browse Shops
            </a>
            <a href="customerdashboard.php?section=products"
               class="<?php echo ($section === 'products') ? 'active' : ''; ?>">
                <i class="fas fa-box-open"></i> Browse Products
            </a>

            <div class="nav-section-title">My Account</div>
            <a href="customerdashboard.php?section=messages">
                    <div class="nav-section-title">Chat</div>
    <a href="customerchat.php"
       style="display:flex;align-items:center;gap:13px;padding:13px 25px;
              color:rgba(255,255,255,0.7);font-size:14px;font-weight:500;
              border-left:3px solid transparent;transition:0.2s;">
        <i class="fas fa-comments" style="width:18px;text-align:center;"></i>
        Chat with Shops
    </a>





              <a class="<?php echo ($section === 'messages') ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i> My Messages
            </a>
            <a href="customerdashboard.php?section=profile"
               class="<?php echo ($section === 'profile') ? 'active' : ''; ?>">
                <i class="fas fa-user-edit"></i> My Profile
            </a>

            <div class="nav-section-title">Site</div>
            <a href="index.php">
                <i class="fas fa-home"></i> View Website
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="customerlogout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <!-- ===== MAIN ===== -->
    <div class="main">

        <!-- TOPBAR -->
        <div class="topbar">
            <h1>
                <?php
                if ($section === 'overview')       { echo '<i class="fas fa-th-large"></i> Overview'; }
                elseif ($section === 'search')    { echo '<i class="fas fa-search"></i> Search'; }
                elseif ($section === 'shops')     { echo '<i class="fas fa-store-alt"></i> Browse Shops'; }
                elseif ($section === 'products')  { echo '<i class="fas fa-box-open"></i> Browse Products'; }
                elseif ($section === 'messages')  { echo '<i class="fas fa-envelope"></i> My Messages'; }
                elseif ($section === 'profile')   { echo '<i class="fas fa-user-edit"></i> My Profile'; }
                ?>
            </h1>
            <div class="topbar-right">
                <a href="index.php" class="btn-site">
                    <i class="fas fa-external-link-alt"></i> View Site
                </a>
                <div class="customer-badge">
                    <div class="avatar"><?php echo strtoupper(substr($customer_email, 0, 1)); ?></div>
                    <span>Customer</span>
                </div>
            </div>
        </div>

        <!-- PAGE CONTENT -->
        <div class="content">

        <?php if ($section === 'overview'): ?>
        <!-- ===================================================
             OVERVIEW
        =================================================== -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-store-alt"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_shops; ?></h3>
                    <p>Available Shops</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-box-open"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_products; ?></h3>
                    <p>Available Products</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow"><i class="fas fa-envelope"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_messages; ?></h3>
                    <p>Messages Sent</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon indigo"><i class="fas fa-user-check"></i></div>
                <div class="stat-info">
                    <h3><?php echo (empty($profile) ? 'Incomplete' : 'Complete'); ?></h3>
                    <p>Profile Status</p>
                </div>
            </div>
        </div>

        <!-- Welcome Banner -->
        <div style="background:linear-gradient(135deg,var(--primary),var(--secondary));border-radius:16px;padding:30px;margin-bottom:25px;">
            <div style="color:white;">
                <h2 style="font-size:20px;font-weight:700;margin-bottom:8px;">
                    Welcome back,
                    <?php
                    if (!empty($profile) && isset($profile['full_name']) && $profile['full_name'] != '') {
                        echo htmlspecialchars($profile['full_name']);
                    } else {
                        echo 'Customer';
                    }
                    ?>!
                </h2>
                <p style="font-size:13px;opacity:0.85;margin-bottom:20px;">
                    Discover <?php echo $total_shops; ?> shops and <?php echo $total_products; ?> products on Shop Finder.
                </p>
                <a href="customerdashboard.php?section=search"
                   style="display:inline-flex;align-items:center;gap:8px;padding:10px 22px;background:white;color:var(--primary);border-radius:9px;font-size:13px;font-weight:700;">
                    <i class="fas fa-search"></i> Search Now
                </a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            </div>
            <div class="card-body">
                <div class="quick-grid">
                    <a href="customerdashboard.php?section=search" class="quick-card">
                        <i class="fas fa-search" style="color:var(--primary);"></i>
                        <h3>Search</h3>
                        <p>Find shops &amp; products</p>
                    </a>
                    <a href="customerdashboard.php?section=shops" class="quick-card">
                        <i class="fas fa-store-alt" style="color:var(--secondary);"></i>
                        <h3>Shops</h3>
                        <p>Browse all shops</p>
                    </a>
                    <a href="customerdashboard.php?section=products" class="quick-card">
                        <i class="fas fa-box-open" style="color:var(--success);"></i>
                        <h3>Products</h3>
                        <p>Browse all products</p>
                    </a>
                    <a href="customerdashboard.php?section=messages" class="quick-card">
                        <i class="fas fa-envelope" style="color:var(--warning);"></i>
                        <h3>Messages</h3>
                        <p>View sent messages</p>
                    </a>
                </div>
            </div>
        </div>

        <?php elseif ($section === 'search'): ?>
        <!-- ===================================================
             SEARCH
        =================================================== -->
        <div class="search-wrap">
            <form method="GET" action="customerdashboard.php" class="search-form">
                <input type="hidden" name="section" value="search">
                <input type="text" name="q" class="search-input"
                       placeholder="Search shops or products by name, location..."
                       value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>

        <?php if ($search_query !== ''): ?>
            <div class="search-results-label">
                Results for: <strong><?php echo htmlspecialchars($search_query); ?></strong>
                &mdash;
                <?php echo count($search_results_shops); ?> shops,
                <?php echo count($search_results_products); ?> products found
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-store-alt"></i> Shops Found</h2>
                    <span class="count-badge"><?php echo count($search_results_shops); ?></span>
                </div>
                <div class="card-body">
                    <?php if (count($search_results_shops) > 0): ?>
                        <div class="shop-grid">
                            <?php foreach ($search_results_shops as $shop): ?>
                            <div class="shop-card">
                                <div class="shop-card-icon"><i class="fas fa-store"></i></div>
                                <h3><?php echo $shop['shop_name'] ? htmlspecialchars($shop['shop_name']) : 'Unnamed Shop'; ?></h3>
                                <?php if (!empty($shop['address'])): ?>
                                <div class="shop-meta"><i class="fas fa-map-marker-alt"></i><?php echo htmlspecialchars($shop['address']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($shop['phone'])): ?>
                                <div class="shop-meta"><i class="fas fa-phone"></i><?php echo htmlspecialchars($shop['phone']); ?></div>
                                <?php endif; ?>
                                <div class="shop-meta"><i class="fas fa-envelope"></i><?php echo htmlspecialchars($shop['email']); ?></div>
                                <?php if (!empty($shop['description'])): ?>
                                <div class="shop-desc"><?php echo htmlspecialchars($shop['description']); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-store-alt"></i>
                            <p>No shops matched your search.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-box-open"></i> Products Found</h2>
                    <span class="count-badge"><?php echo count($search_results_products); ?></span>
                </div>
                <div class="card-body">
                    <?php if (count($search_results_products) > 0): ?>
                        <div class="product-grid">
                            <?php foreach ($search_results_products as $product): ?>
                            <div class="product-card">
                                <?php if (!empty($product['image']) && file_exists('uploads/' . $product['image'])): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>"
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         style="width:100%;height:160px;object-fit:cover;border-radius:10px;margin-bottom:12px;border:2px solid var(--border);">
                                <?php else: ?>
                                    <div style="width:100%;height:110px;border-radius:10px;background:var(--bg);display:flex;align-items:center;justify-content:center;color:var(--text-light);margin-bottom:12px;font-size:32px;border:2px solid var(--border);">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <div class="product-price">RWF <?php echo number_format($product['price'], 0); ?></div>
                                <div class="product-shop">
                                    <i class="fas fa-store"></i>
                                    <?php echo !empty($product['shop_name']) ? htmlspecialchars($product['shop_name']) : 'Unknown Shop'; ?>
                                </div>
                                <span class="product-stock <?php echo ($product['stock'] <= 5) ? 'low' : 'ok'; ?>">
                                    <?php echo ($product['stock'] <= 5) ? 'Low Stock: ' : 'In Stock: '; echo $product['stock']; ?> units
                                </span>
                                <?php if (!empty($product['description'])): ?>
                                <div class="product-desc"><?php echo htmlspecialchars($product['description']); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <p>No products matched your search.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <p style="font-size:15px;font-weight:600;color:var(--dark);margin-bottom:6px;">Search for shops or products</p>
                        <p>Type a shop name, product name, or location above to get started.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php elseif ($section === 'shops'): ?>
        <!-- ===================================================
             BROWSE SHOPS
        =================================================== -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-store-alt"></i> All Shops</h2>
                <span class="count-badge"><?php echo $total_shops; ?> shops</span>
            </div>
            <div class="card-body">
                <?php if ($shops_res && $shops_res->num_rows > 0): ?>
                    <div class="shop-grid">
                        <?php while ($shop = $shops_res->fetch_assoc()): ?>
                        <div class="shop-card">
                            <div class="shop-card-icon"><i class="fas fa-store"></i></div>
                            <?php if (!empty($shop['shop_name'])): ?>
                                <h3><?php echo htmlspecialchars($shop['shop_name']); ?></h3>
                                <?php if (!empty($shop['address'])): ?>
                                <div class="shop-meta"><i class="fas fa-map-marker-alt"></i><?php echo htmlspecialchars($shop['address']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($shop['phone'])): ?>
                                <div class="shop-meta"><i class="fas fa-phone"></i><?php echo htmlspecialchars($shop['phone']); ?></div>
                                <?php endif; ?>
                                <div class="shop-meta"><i class="fas fa-envelope"></i><?php echo htmlspecialchars($shop['email']); ?></div>
                                <?php if (!empty($shop['description'])): ?>
                                <div class="shop-desc"><?php echo htmlspecialchars($shop['description']); ?></div>
                                <?php endif; ?>
                                <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">
                                    <a href="shopdetail.php?id=<?php echo $shop['id']; ?>"
                                       style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 12px;background:var(--primary);color:white;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;transition:0.2s;"
                                       onmouseover="this.style.background='#1a7bb8'" onmouseout="this.style.background='var(--primary)'">
                                        <i class="fas fa-store"></i> View Shop
                                    </a>
                                    <?php if (!empty($shop['address'])): ?>
                                    <a href="https://www.openstreetmap.org/directions?to=<?php echo urlencode($shop['address']); ?>"
                                       target="_blank"
                                       style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 12px;background:#20bf6b;color:white;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;transition:0.2s;"
                                       onmouseover="this.style.background='#17a358'" onmouseout="this.style.background='#20bf6b'">
                                        <i class="fas fa-directions"></i> Get Directions
                                    </a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <h3>Shop #<?php echo $shop['id']; ?></h3>
                                <div class="shop-meta"><i class="fas fa-envelope"></i><?php echo htmlspecialchars($shop['email']); ?></div>
                                <span class="no-profile-tag"><i class="fas fa-clock"></i> Profile not set up yet</span>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-store-alt"></i>
                        <p>No shops registered yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($section === 'products'): ?>
        <!-- ===================================================
             BROWSE PRODUCTS
        =================================================== -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-box-open"></i> All Products</h2>
                <span class="count-badge"><?php echo $total_products; ?> products</span>
            </div>
            <div class="card-body">
                <?php if ($products_res && $products_res->num_rows > 0): ?>
                    <div class="product-grid">
                        <?php while ($product = $products_res->fetch_assoc()): ?>
                        <div class="product-card">
                            <?php if (!empty($product['image']) && file_exists('uploads/' . $product['image'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>"
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     style="width:100%;height:160px;object-fit:cover;border-radius:10px;margin-bottom:12px;border:2px solid var(--border);">
                            <?php else: ?>
                                <div style="width:100%;height:110px;border-radius:10px;background:var(--bg);display:flex;align-items:center;justify-content:center;color:var(--text-light);margin-bottom:12px;font-size:32px;border:2px solid var(--border);">
                                    <i class="fas fa-image"></i>
                                </div>
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="product-price">RWF <?php echo number_format($product['price'], 0); ?></div>
                            <div class="product-shop">
                                <i class="fas fa-store"></i>
                                <?php echo !empty($product['shop_name']) ? htmlspecialchars($product['shop_name']) : 'Unknown Shop'; ?>
                            </div>
                            <span class="product-stock <?php echo ($product['stock'] <= 5) ? 'low' : 'ok'; ?>">
                                <?php echo ($product['stock'] <= 5) ? 'Low Stock: ' : 'In Stock: '; echo $product['stock']; ?> units
                            </span>
                            <?php if (!empty($product['description'])): ?>
                            <div class="product-desc"><?php echo htmlspecialchars($product['description']); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <p>No products available yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($section === 'messages'): ?>
        <!-- ===================================================
             MY MESSAGES
        =================================================== -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-envelope"></i> My Sent Messages</h2>
                <span class="count-badge"><?php echo $total_messages; ?> sent</span>
            </div>
            <div class="card-body">
                <?php if ($messages_res && $messages_res->num_rows > 0): ?>
                    <?php while ($row = $messages_res->fetch_assoc()): ?>
                    <div class="message-card">
                        <div class="message-meta">
                            <span class="message-label"><i class="fas fa-paper-plane"></i> Sent by you</span>
                            <span class="message-date">
                            <?php
                            if (isset($row['created_at']) && $row['created_at'] != '') {
                                echo date('M d, Y', strtotime($row['created_at']));
                            } else {
                                echo 'N/A';
                            }
                            ?>
                            </span>
                        </div>
                        <div class="message-body"><?php echo htmlspecialchars($row['message']); ?></div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-envelope-open"></i>
                        <p>You have not sent any messages yet.</p>
                        <a href="contact.php"
                           style="display:inline-flex;align-items:center;gap:7px;margin-top:14px;padding:10px 20px;background:var(--primary);color:white;border-radius:9px;font-size:13px;font-weight:600;">
                            <i class="fas fa-paper-plane"></i> Send a Message
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($section === 'profile'): ?>
        <!-- ===================================================
             MY PROFILE
        =================================================== -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-user-edit"></i> Edit My Profile</h2>
            </div>
            <div class="card-body">
                <?php if (isset($profile_success)): ?>
                    <div class="success-alert">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $profile_success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group-f">
                            <label>Full Name</label>
                            <input type="text" name="full_name"
                                   placeholder="e.g. Jean Pierre"
                                   value="<?php echo (!empty($profile) && isset($profile['full_name'])) ? htmlspecialchars($profile['full_name']) : ''; ?>">
                        </div>
                        <div class="form-group-f">
                            <label>Phone Number</label>
                            <input type="text" name="phone"
                                   placeholder="e.g. +250 786 799 347"
                                   value="<?php echo (!empty($profile) && isset($profile['phone'])) ? htmlspecialchars($profile['phone']) : ''; ?>">
                        </div>
                        <div class="form-group-f span2">
                            <label>Address / Location</label>
                            <input type="text" name="address"
                                   placeholder="e.g. Kigali, Rwanda"
                                   value="<?php echo (!empty($profile) && isset($profile['address'])) ? htmlspecialchars($profile['address']) : ''; ?>">
                        </div>
                        <div class="form-group-f span2">
                            <label>Email (cannot be changed)</label>
                            <input type="text"
                                   value="<?php echo htmlspecialchars($customer_email); ?>"
                                   disabled
                                   style="background:#f5f5f5;cursor:not-allowed;color:var(--text-light);">
                        </div>
                    </div>
                    <button type="submit" name="save_profile" class="btn-save">
                        <i class="fas fa-save"></i> Save Profile
                    </button>
                </form>
            </div>
        </div>

        <?php endif; ?>

        </div><!-- /content -->

        <div class="main-footer">
            &copy; 2026 <span>Elie</span> | Shop Finder &mdash; Customer Dashboard
        </div>
    </div><!-- /main -->

</div><!-- /dashboard -->

</body>
</html>