<?php
session_start();

// =============================================
// AUTH CHECK - Must be logged in as shop
// =============================================
if (!isset($_SESSION['shop_id'])) {
    header("Location: shoplogin.html");
    exit();
}

$shop_id    = $_SESSION['shop_id'];
$shop_email = $_SESSION['shop_email'];

// =============================================
// DATABASE CONNECTION
// =============================================
$conn = new mysqli("localhost", "root", "", "shop_finder");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// =============================================
// HANDLE ACTIONS
// =============================================

// --- Save / Update Profile ---
if (isset($_POST['save_profile'])) {
    $shop_name    = mysqli_real_escape_string($conn, $_POST['shop_name']);
    $phone        = mysqli_real_escape_string($conn, $_POST['phone']);
    $address      = mysqli_real_escape_string($conn, $_POST['address']);
    $description  = mysqli_real_escape_string($conn, $_POST['description']);

    $check = $conn->query("SELECT id FROM shop_profiles WHERE shop_id = $shop_id");
    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE shop_profiles SET shop_name='$shop_name', phone='$phone', address='$address', description='$description' WHERE shop_id=$shop_id");
    } else {
        $conn->query("INSERT INTO shop_profiles (shop_id, shop_name, phone, address, description) VALUES ($shop_id, '$shop_name', '$phone', '$address', '$description')");
    }
    $profile_success = "Profile updated successfully!";
}

// --- Add Product ---
if (isset($_POST['add_product'])) {
    $product_name  = mysqli_real_escape_string($conn, $_POST['product_name']);
    $product_price = floatval($_POST['product_price']);
    $product_desc  = mysqli_real_escape_string($conn, $_POST['product_desc']);
    $product_stock = intval($_POST['product_stock']);
    $image_filename = '';

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
        $file_type     = $_FILES['product_image']['type'];
        $file_size     = $_FILES['product_image']['size'];
        $max_size      = 3 * 1024 * 1024;

        if (!in_array($file_type, $allowed_types)) {
            $product_error = "Only JPG, PNG, GIF, and WEBP images are allowed.";
        } elseif ($file_size > $max_size) {
            $product_error = "Image must be smaller than 3MB.";
        } else {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $ext            = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $image_filename = 'product_' . time() . '_' . $shop_id . '.' . $ext;
            $upload_path    = $upload_dir . $image_filename;

            if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                $product_error   = "Failed to upload image. Check that the uploads/ folder exists.";
                $image_filename  = '';
            }
        }
    }

    if (!isset($product_error)) {
        $safe_img = mysqli_real_escape_string($conn, $image_filename);
        $conn->query("INSERT INTO products (shop_id, name, price, description, stock, image) VALUES ($shop_id, '$product_name', $product_price, '$product_desc', $product_stock, '$safe_img')");
        $product_success = "Product added successfully!";
    }
}

// --- Delete Product ---
if (isset($_GET['delete_product'])) {
    $pid = intval($_GET['delete_product']);
    $conn->query("DELETE FROM products WHERE id=$pid AND shop_id=$shop_id");
    header("Location: shopdashboard.php?section=products");
    exit();
}

// =============================================
// FETCH DATA
// =============================================

$profile_res = $conn->query("SELECT * FROM shop_profiles WHERE shop_id = $shop_id");
$profile = ($profile_res && $profile_res->num_rows > 0) ? $profile_res->fetch_assoc() : array();

$products_res  = $conn->query("
    SELECT p.*, sp.phone AS shop_phone
    FROM products p
    LEFT JOIN shop_profiles sp ON sp.shop_id = p.shop_id
    WHERE p.shop_id = $shop_id
    ORDER BY p.id DESC
");
$total_products_res = $conn->query("SELECT COUNT(*) AS total FROM products WHERE shop_id = $shop_id");
$tp = $total_products_res->fetch_assoc();
$total_products = $tp['total'];

$messages_res = $conn->query("SELECT * FROM contacts ORDER BY id DESC LIMIT 20");
$total_msg_res = $conn->query("SELECT COUNT(*) AS total FROM contacts");
$tm = $total_msg_res->fetch_assoc();
$total_messages = $tm['total'];

$total_stock_res = $conn->query("SELECT SUM(stock) AS total FROM products WHERE shop_id = $shop_id");
$ts = $total_stock_res->fetch_assoc();
$total_stock = $ts['total'] ? $ts['total'] : 0;

$section = isset($_GET['section']) ? $_GET['section'] : 'overview';

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shop Dashboard | Shop Finder</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
*, *::before, *::after {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary:       #3867d6;
    --primary-light: #4facfe;
    --success:       #20bf6b;
    --warning:       #f7b731;
    --danger:        #fc5c65;
    --dark:          #1a1f2e;
    --sidebar-w:     260px;
    --bg:            #f0f4f8;
    --white:         #ffffff;
    --text:          #333;
    --text-light:    #6b7280;
    --border:        #e5e7eb;
    --card-shadow:   0 4px 20px rgba(0,0,0,0.08);

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

/* ===== DASHBOARD LAYOUT ===== */
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

.sidebar-brand .shop-email {
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
    transition: 0.2s;
    padding: 10px;
    border-radius: 8px;
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
}

.topbar h1 {
    font-size: 19px;
    font-weight: 600;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 10px;
}

.topbar h1 i { color: var(--primary); }

.topbar-right { display: flex; align-items: center; gap: 14px; }

.shop-badge {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 7px 16px;
    background: var(--bg);
    border-radius: 50px;
}

.shop-badge .avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 13px;
    font-weight: 700;
}

.shop-badge span { font-size: 12px; font-weight: 500; color: var(--text-light); }

.btn-home {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--primary);
    font-size: 12px;
    font-weight: 500;
    padding: 7px 14px;
    background: rgba(56,103,214,0.08);
    border-radius: 8px;
    transition: 0.2s;
}

.btn-home:hover { background: rgba(56,103,214,0.16); }

/* ===== CONTENT ===== */
.content { padding: 30px 35px; flex: 1; }

/* ===== STATS ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
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

.stat-icon.blue   { background: rgba(56,103,214,0.1);  color: var(--primary); }
.stat-icon.cyan   { background: rgba(79,172,254,0.12); color: var(--primary-light); }
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

/* ===== FORM STYLES ===== */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
}

.form-grid.full { grid-template-columns: 1fr; }

.form-group-f {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group-f.span2 { grid-column: span 2; }

.form-group-f label {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-light);
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.form-group-f input,
.form-group-f textarea,
.form-group-f select {
    padding: 11px 14px;
    border: 2px solid var(--border);
    border-radius: 9px;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
    color: var(--text);
    transition: 0.2s;
    outline: none;
    background: var(--white);
}

.form-group-f input:focus,
.form-group-f textarea:focus,
.form-group-f select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(56,103,214,0.12);
}

.form-group-f textarea { resize: vertical; min-height: 90px; }

.btn-save {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
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
    box-shadow: 0 6px 18px rgba(56,103,214,0.35);
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

/* ===== TABLE ===== */
.table-wrap { overflow-x: auto; }

table { width: 100%; border-collapse: collapse; }

thead th {
    background: var(--bg);
    padding: 13px 18px;
    font-size: 11px;
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
    padding: 14px 18px;
    font-size: 13px;
    color: var(--text);
    vertical-align: middle;
}

.id-chip {
    background: var(--bg);
    color: var(--text-light);
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}

.price-tag {
    background: rgba(32,191,107,0.1);
    color: var(--success);
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 13px;
}

.stock-tag {
    background: rgba(247,183,49,0.1);
    color: #b45309;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 12px;
}

.stock-tag.low {
    background: rgba(252,92,101,0.1);
    color: var(--danger);
}

.phone-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(56,103,214,0.08);
    color: var(--primary);
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 500;
    font-size: 12px;
    white-space: nowrap;
}

.phone-tag i { font-size: 11px; }

.phone-none {
    color: var(--text-light);
    font-size: 12px;
    font-style: italic;
}

.btn-delete {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    background: #fee2e2;
    color: var(--danger);
    border-radius: 7px;
    font-size: 12px;
    font-weight: 600;
    transition: 0.2s;
}

.btn-delete:hover { background: var(--danger); color: white; }

.empty-state {
    text-align: center;
    padding: 50px 20px;
    color: var(--text-light);
}

.empty-state i {
    font-size: 38px;
    margin-bottom: 10px;
    opacity: 0.25;
    display: block;
}

.empty-state p { font-size: 14px; }

/* ===== MESSAGES ===== */
.message-card {
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 14px;
    transition: 0.2s;
    animation: fadeIn 0.4s ease both;
}

.message-card:hover { border-color: var(--primary); box-shadow: 0 4px 12px rgba(56,103,214,0.1); }

.message-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    flex-wrap: wrap;
    gap: 8px;
}

.message-sender { font-weight: 600; font-size: 14px; color: var(--dark); }
.message-email  { font-size: 12px; color: var(--primary); }
.message-date   { font-size: 11px; color: var(--text-light); }
.message-body   { font-size: 13px; color: var(--text-light); line-height: 1.6; }

/* ===== OVERVIEW QUICK ACTIONS ===== */
.quick-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
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

.quick-card:nth-child(1) { animation-delay: 0.1s; }
.quick-card:nth-child(2) { animation-delay: 0.15s; }
.quick-card:nth-child(3) { animation-delay: 0.2s; }

.quick-card:hover { border-color: var(--primary); background: var(--white); transform: translateY(-3px); }

.quick-card i { font-size: 28px; display: block; margin-bottom: 10px; }
.quick-card h3 { font-size: 13px; font-weight: 600; color: var(--dark); margin-bottom: 4px; }
.quick-card p  { font-size: 11px; color: var(--text-light); }

/* ===== PROFILE INFO DISPLAY ===== */
.profile-display {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 22px;
}

.profile-item {
    background: var(--bg);
    border-radius: 10px;
    padding: 14px 18px;
}

.profile-item .label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-light);
    margin-bottom: 4px;
}

.profile-item .value {
    font-size: 14px;
    font-weight: 500;
    color: var(--dark);
}

.profile-item.full { grid-column: span 2; }

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
    .stats-grid  { grid-template-columns: repeat(2, 1fr); }
    .quick-grid  { grid-template-columns: repeat(2, 1fr); }
    .form-grid   { grid-template-columns: 1fr; }
    .form-group-f.span2 { grid-column: span 1; }
}

@media (max-width: 768px) {
    .sidebar { width: 0; overflow: hidden; }
    .main    { margin-left: 0; }
    .content { padding: 20px; }
    .topbar  { padding: 14px 18px; }
    .stats-grid    { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .profile-display { grid-template-columns: 1fr; }
    .profile-item.full { grid-column: span 1; }
}
</style>
</head>
<body>

<div class="dashboard">

    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <h2><i class="fas fa-store"></i> Shop Panel</h2>
            <div class="shop-email"><?php echo htmlspecialchars($shop_email); ?></div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-title">Menu</div>
            <a href="shopdashboard.php?section=overview"
               class="<?php echo ($section === 'overview') ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Overview
            </a>

            <div class="nav-section-title">My Shop</div>
            <a href="shopdashboard.php?section=profile"
               class="<?php echo ($section === 'profile') ? 'active' : ''; ?>">
                <i class="fas fa-store-alt"></i> Shop Profile
            </a>
            <a href="shopdashboard.php?section=products"
               class="<?php echo ($section === 'products') ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> My Products
            </a>
            <a href="shopdashboard.php?section=add_product"
               class="<?php echo ($section === 'add_product') ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i> Add Product
            </a>
            <a href="shopdashboard.php?section=messages"
               class="<?php echo ($section === 'messages') ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i> Messages
            </a>

            <?php
            // Show unread chat badge
            $conn2 = new mysqli("localhost", "root", "", "shop_finder");
            $unread_chat = 0;
            if (!$conn2->connect_error) {
                $uc_res = $conn2->query("SELECT COUNT(*) AS total FROM chat_messages
                                        WHERE shop_id=$shop_id AND sender_type='customer' AND is_read=0");
                if ($uc_res) {
                    $uc_row      = $uc_res->fetch_assoc();
                    $unread_chat = intval($uc_row['total']);
                }
                $conn2->close();
            }
            ?>
            <a href="shopchat.php" style="display:flex;align-items:center;gap:13px;padding:13px 25px;color:var(--sidebar-text);font-size:14px;font-weight:500;transition:0.2s;border-left:3px solid transparent;">
                <i class="fas fa-comments" style="width:18px;text-align:center;font-size:15px;"></i>
                Customer Chat
                <?php if ($unread_chat > 0): ?>
                <span style="margin-left:auto;background:rgba(252,92,101,0.25);color:#fc5c65;font-size:11px;font-weight:700;padding:2px 8px;border-radius:10px;">
                    <?php echo $unread_chat; ?>
                </span>
                <?php endif; ?>
            </a>

            <div class="nav-section-title">Account</div>
            <a href="index.php">
                <i class="fas fa-home"></i> View Website
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="shoplogin.html">
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
                if ($section === 'overview')         { echo '<i class="fas fa-th-large"></i> Overview'; }
                elseif ($section === 'profile')      { echo '<i class="fas fa-store-alt"></i> Shop Profile'; }
                elseif ($section === 'products')     { echo '<i class="fas fa-box"></i> My Products'; }
                elseif ($section === 'add_product')  { echo '<i class="fas fa-plus-circle"></i> Add Product'; }
                elseif ($section === 'messages')     { echo '<i class="fas fa-envelope"></i> Messages'; }
                ?>
            </h1>
            <div class="topbar-right">
                <a href="index.php" class="btn-home">
                    <i class="fas fa-external-link-alt"></i> View Site
                </a>
                <div class="shop-badge">
                    <div class="avatar"><?php echo strtoupper(substr($shop_email, 0, 1)); ?></div>
                    <span>Shop Owner</span>
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
                <div class="stat-icon blue"><i class="fas fa-box"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_products; ?></h3>
                    <p>Total Products</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-cubes"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_stock; ?></h3>
                    <p>Items in Stock</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon cyan"><i class="fas fa-envelope"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_messages; ?></h3>
                    <p>Customer Messages</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow"><i class="fas fa-store-alt"></i></div>
                <div class="stat-info">
                    <h3><?php echo (empty($profile) ? 'Incomplete' : 'Active'); ?></h3>
                    <p>Profile Status</p>
                </div>
            </div>
        </div>

        <!-- Profile Summary -->
        <?php if (!empty($profile)): ?>
        <div class="card" style="margin-bottom:25px;">
            <div class="card-header">
                <h2><i class="fas fa-store-alt"></i> Your Shop</h2>
                <a href="shopdashboard.php?section=profile" style="font-size:13px;color:var(--primary);">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
            </div>
            <div class="card-body">
                <div class="profile-display">
                    <div class="profile-item">
                        <div class="label">Shop Name</div>
                        <div class="value"><?php echo htmlspecialchars($profile['shop_name']); ?></div>
                    </div>
                    <div class="profile-item">
                        <div class="label">Phone</div>
                        <div class="value"><?php echo htmlspecialchars($profile['phone']); ?></div>
                    </div>
                    <div class="profile-item">
                        <div class="label">Address</div>
                        <div class="value">
                            <?php if (!empty($profile['address'])): ?>
                                <?php echo htmlspecialchars($profile['address']); ?>
                            <?php else: ?>
                                <span style="color:#f59e0b;font-size:12px;font-weight:600;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    No address set — customers cannot see your map!
                                    <a href="shopdashboard.php?section=profile" style="color:#3867d6;margin-left:6px;">Fix it</a>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="profile-item">
                        <div class="label">Email</div>
                        <div class="value"><?php echo htmlspecialchars($shop_email); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card" style="margin-bottom:25px;">
            <div class="card-body">
                <div class="empty-state">
                    <i class="fas fa-store-alt"></i>
                    <p style="font-size:15px;font-weight:600;color:var(--dark);margin-bottom:8px;">Your shop profile is incomplete!</p>
                    <p>Please fill in your shop details so customers can find you.</p>
                    <a href="shopdashboard.php?section=profile" style="display:inline-flex;align-items:center;gap:7px;margin-top:16px;padding:10px 22px;background:var(--primary);color:white;border-radius:9px;font-size:13px;font-weight:600;">
                        <i class="fas fa-edit"></i> Complete Profile
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            </div>
            <div class="card-body">
                <div class="quick-grid">
                    <a href="shopdashboard.php?section=add_product" class="quick-card">
                        <i class="fas fa-plus-circle" style="color:var(--primary);"></i>
                        <h3>Add Product</h3>
                        <p>List a new product</p>
                    </a>
                    <a href="shopdashboard.php?section=products" class="quick-card">
                        <i class="fas fa-box" style="color:var(--success);"></i>
                        <h3>My Products</h3>
                        <p>Manage your listings</p>
                    </a>
                    <a href="shopdashboard.php?section=messages" class="quick-card">
                        <i class="fas fa-envelope" style="color:var(--warning);"></i>
                        <h3>Messages</h3>
                        <p>Read customer messages</p>
                    </a>
                </div>
            </div>
        </div>

        <?php elseif ($section === 'profile'): ?>
        <!-- ===================================================
             SHOP PROFILE
        =================================================== -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-store-alt"></i> Edit Shop Profile</h2>
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
                            <label>Shop Name</label>
                            <input type="text" name="shop_name" placeholder="e.g. Elie Electronics"
                                   value="<?php echo isset($profile['shop_name']) ? htmlspecialchars($profile['shop_name']) : ''; ?>"
                                   required>
                        </div>
                        <div class="form-group-f">
                            <label>Phone Number</label>
                            <input type="text" name="phone" placeholder="e.g. +250 786 799 347"
                                   value="<?php echo isset($profile['phone']) ? htmlspecialchars($profile['phone']) : ''; ?>">
                        </div>
                        <div class="form-group-f span2">
                            <label>Address / Location <span style="color:#fc5c65;font-size:11px;text-transform:none;letter-spacing:0;">* Required for map</span></label>
                            <input type="text" name="address" placeholder="e.g. Kimironko, Kigali, Rwanda"
                                   value="<?php echo isset($profile['address']) ? htmlspecialchars($profile['address']) : ''; ?>">
                            <div style="display:flex;align-items:flex-start;gap:8px;margin-top:7px;background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:10px 13px;">
                                <i class="fas fa-map-marker-alt" style="color:#f59e0b;margin-top:2px;flex-shrink:0;"></i>
                                <div style="font-size:12px;color:#92400e;line-height:1.6;">
                                    <strong>This address powers the map on your shop page.</strong><br>
                                    Use the format: <code style="background:#fef3c7;padding:1px 5px;border-radius:4px;">Neighborhood, City, Country</code><br>
                                    Good example: <em>Kimironko, Kigali, Rwanda</em><br>
                                    Avoid dashes like: <em>Kigali - Kimironko</em> (confuses the map)
                                </div>
                            </div>
                        </div>
                        <div class="form-group-f span2">
                            <label>Shop Description</label>
                            <textarea name="description" placeholder="Tell customers what your shop sells..."><?php echo isset($profile['description']) ? htmlspecialchars($profile['description']) : ''; ?></textarea>
                        </div>
                    </div>
                    <button type="submit" name="save_profile" class="btn-save">
                        <i class="fas fa-save"></i> Save Profile
                    </button>
                </form>
            </div>
        </div>

        <?php elseif ($section === 'products'): ?>
        <!-- ===================================================
             MY PRODUCTS
        =================================================== -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-box"></i> My Products</h2>
                <a href="shopdashboard.php?section=add_product"
                   style="display:inline-flex;align-items:center;gap:7px;padding:8px 16px;background:var(--primary);color:white;border-radius:8px;font-size:13px;font-weight:600;">
                    <i class="fas fa-plus"></i> Add Product
                </a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Shop Phone</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($products_res && $products_res->num_rows > 0): ?>
                        <?php while ($row = $products_res->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if (!empty($row['image']) && file_exists('uploads/' . $row['image'])): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>"
                                         alt="<?php echo htmlspecialchars($row['name']); ?>"
                                         style="width:54px;height:54px;object-fit:cover;border-radius:9px;border:2px solid var(--border);">
                                <?php else: ?>
                                    <div style="width:54px;height:54px;border-radius:9px;background:var(--bg);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-light);">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><span class="price-tag">RWF <?php echo number_format($row['price'], 0); ?></span></td>
                            <td>
                                <span class="stock-tag <?php echo ($row['stock'] <= 5) ? 'low' : ''; ?>">
                                    <?php echo $row['stock']; ?> units
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($row['shop_phone'])): ?>
                                    <span class="phone-tag">
                                        <i class="fas fa-phone"></i>
                                        <?php echo htmlspecialchars($row['shop_phone']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="phone-none">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td style="color:var(--text-light);font-size:12px;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?php echo htmlspecialchars($row['description']); ?>
                            </td>
                            <td>
                                <a href="shopdashboard.php?delete_product=<?php echo $row['id']; ?>"
                                   class="btn-delete"
                                   onclick="return confirm('Delete this product?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7">
                            <div class="empty-state">
                                <i class="fas fa-box-open"></i>
                                <p>No products added yet. <a href="shopdashboard.php?section=add_product" style="color:var(--primary);font-weight:600;">Add your first product</a></p>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif ($section === 'add_product'): ?>
        <!-- ===================================================
             ADD PRODUCT
        =================================================== -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-plus-circle"></i> Add New Product</h2>
            </div>
            <div class="card-body">
                <?php if (isset($product_success)): ?>
                    <div class="success-alert">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $product_success; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($product_error)): ?>
                    <div class="success-alert" style="background:#fee2e2;color:#b91c1c;">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $product_error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group-f">
                            <label>Product Name</label>
                            <input type="text" name="product_name" placeholder="e.g. Samsung TV 43 inch" required>
                        </div>
                        <div class="form-group-f">
                            <label>Price (RWF)</label>
                            <input type="number" name="product_price" placeholder="e.g. 150000" min="0" step="any" required>
                        </div>
                        <div class="form-group-f">
                            <label>Stock Quantity</label>
                            <input type="number" name="product_stock" placeholder="e.g. 10" min="0" required>
                        </div>
                        <div class="form-group-f">
                            <label>Product Image <span style="color:var(--text-light);font-weight:400;">(optional, max 3MB)</span></label>
                            <input type="file" name="product_image" accept="image/*" id="imageInput"
                                   style="padding:9px 14px;border:2px dashed var(--border);border-radius:9px;font-family:'Poppins',sans-serif;font-size:13px;cursor:pointer;background:var(--bg);"
                                   onchange="previewImage(this)">
                            <div id="imagePreview" style="display:none;margin-top:10px;">
                                <img id="previewImg" src="" alt="Preview"
                                     style="max-width:160px;max-height:160px;border-radius:10px;border:2px solid var(--border);object-fit:cover;">
                                <div style="font-size:11px;color:var(--text-light);margin-top:5px;" id="previewName"></div>
                            </div>
                        </div>
                        <div class="form-group-f span2">
                            <label>Product Description</label>
                            <textarea name="product_desc" placeholder="Describe your product..."></textarea>
                        </div>
                    </div>
                    <button type="submit" name="add_product" class="btn-save">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                </form>

                <script>
                function previewImage(input) {
                    var preview = document.getElementById('imagePreview');
                    var img     = document.getElementById('previewImg');
                    var name    = document.getElementById('previewName');
                    if (input.files && input.files[0]) {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            img.src          = e.target.result;
                            name.textContent = input.files[0].name + ' (' + (input.files[0].size / 1024).toFixed(1) + ' KB)';
                            preview.style.display = 'block';
                        };
                        reader.readAsDataURL(input.files[0]);
                    }
                }
                </script>
            </div>
        </div>

        <?php elseif ($section === 'messages'): ?>
        <!-- ===================================================
             MESSAGES
        =================================================== -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-envelope"></i> Customer Messages</h2>
                <span style="background:var(--bg);color:var(--text-light);font-size:12px;font-weight:600;padding:4px 12px;border-radius:20px;">
                    <?php echo $total_messages; ?> total
                </span>
            </div>
            <div class="card-body">
                <?php if ($messages_res && $messages_res->num_rows > 0): ?>
                    <?php $i = 0; while ($row = $messages_res->fetch_assoc()): $i++; ?>
                    <div class="message-card" style="animation-delay: <?php echo ($i * 0.05); ?>s;">
                        <div class="message-meta">
                            <div>
                                <span class="message-sender"><?php echo htmlspecialchars($row['name']); ?></span>
                                &nbsp;&mdash;&nbsp;
                                <span class="message-email"><?php echo htmlspecialchars($row['email']); ?></span>
                            </div>
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
                        <p>No messages yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php endif; ?>

        </div><!-- /content -->

        <div class="main-footer">
            &copy; 2026 <span>Elie</span> | Shop Finder &mdash; Shop Dashboard
        </div>
    </div><!-- /main -->

</div><!-- /dashboard -->

</body>
</html>