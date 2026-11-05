<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shop Finder | Discover Local Shops</title>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

<style>
/* ---------- RESET & GLOBAL ---------- */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    line-height: 1.6;
    color: #1e293b;
    background: #ffffff;
}

/* ---------- NAVBAR (sticky) ---------- */
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 50px;
    background: #ffffff;
    box-shadow: 0 8px 20px rgba(0,0,0,0.05);
    position: sticky;
    top: 0;
    z-index: 100;
    backdrop-filter: blur(0px);
}

.navbar h2 {
    color: #2d98da;
    font-weight: 700;
    font-size: 26px;
    letter-spacing: -0.3px;
}

.nav-links a {
    margin: 0 12px;
    text-decoration: none;
    color: #2c3e66;
    font-weight: 600;
    transition: all 0.2s ease;
    font-size: 0.95rem;
    padding: 8px 16px;
    border-radius: 40px;
    background: transparent;
}

.nav-links a:hover {
    color: #2d98da;
    background: #eef2ff;
}

/* ---------- HERO SECTION (ONLY title remains) ---------- */
.hero {
    height: 75vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    background: linear-gradient(135deg, #0b2b3b 0%, #1a4c6e 100%);
}

.hero::before {
    content: "";
    position: absolute;
    top:0; left:0; right:0; bottom:0;
    background: radial-gradient(circle at 20% 30%, rgba(45,152,218,0.15), transparent 70%);
    pointer-events: none;
}

.hero-overlay {
    position: relative;
    text-align: center;
    color: #ffffff;
    max-width: 800px;
    padding: 20px 32px;
    border-radius: 48px;
    background: rgba(0,0,0,0.2);
    backdrop-filter: blur(4px);
}

.hero h1 {
    font-size: 64px;
    font-weight: 800;
    margin-bottom: 8px;
    letter-spacing: -1px;
    background: linear-gradient(120deg, #ffffff, #c0e4ff);
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
    text-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

/* all extra text completely removed — no extra paragraphs, no buttons inside hero overlay */
.hero p, .hero .btn, .hero .hero-overlay p, .hero .hero-overlay .btn {
    display: none;
}

/* ensure absolutely nothing extra shows */
.hero-overlay p,
.hero-overlay .btn,
.hero-overlay .btn-primary {
    display: none !important;
}

/* ---------- BUTTONS (global styles) ---------- */
.btn {
    display: inline-block;
    padding: 10px 24px;
    margin: 4px;
    border-radius: 60px;
    text-decoration: none;
    font-weight: 600;
    transition: 0.2s;
    font-size: 0.9rem;
}

.btn-primary {
    background: #2d98da;
    color: white;
    border: none;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.btn-secondary {
    background: #3867d6;
    color: white;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(0,0,0,0.1);
}

/* ---------- FEATURES (now moved up naturally) ---------- */
.features {
    display: flex;
    justify-content: center;
    align-items: stretch;
    padding: 70px 20px 50px;
    background: #f8fafc;
    flex-wrap: wrap;
    gap: 40px;
}

.feature-box {
    text-align: center;
    max-width: 280px;
    padding: 32px 24px;
    background: #fff;
    border-radius: 28px;
    box-shadow: 0 20px 35px -8px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    border: 1px solid rgba(45,152,218,0.1);
}

.feature-box:hover {
    transform: translateY(-8px);
    box-shadow: 0 28px 40px -12px rgba(45,152,218,0.2);
    border-color: #2d98da30;
}

.feature-box h3 {
    margin-bottom: 14px;
    color: #2d98da;
    font-size: 24px;
    font-weight: 700;
}

.feature-box p {
    font-size: 15px;
    color: #475569;
}

/* ---------- PRODUCTS SECTION (clean & modern) ---------- */
.products-section {
    padding: 60px 40px 80px;
    background: #ffffff;
}

.products-section h2 {
    text-align: center;
    font-size: 36px;
    font-weight: 700;
    color: #0f2b3d;
    margin-bottom: 10px;
    letter-spacing: -0.3px;
}

.products-section .subtitle {
    text-align: center;
    color: #5b6e8c;
    font-size: 1rem;
    margin-bottom: 48px;
    font-weight: 400;
    border-bottom: 2px solid #eef2ff;
    display: inline-block;
    width: auto;
    margin-left: auto;
    margin-right: auto;
    padding-bottom: 8px;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 32px;
    max-width: 1300px;
    margin: 0 auto;
}

.product-card {
    background: #ffffff;
    border-radius: 24px;
    border: 1px solid #e9edf2;
    overflow: hidden;
    transition: all 0.25s ease;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(0,0,0,0.02);
}

.product-card:hover {
    border-color: #2d98da;
    transform: translateY(-6px);
    box-shadow: 0 18px 32px -10px rgba(45,152,218,0.2);
}

.product-card .prod-img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    background: #f1f5f9;
    transition: transform 0.4s;
}

.product-card:hover .prod-img {
    transform: scale(1.02);
}

.product-card .prod-img-placeholder {
    width: 100%;
    height: 180px;
    background: #eef2ff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 52px;
    color: #9aa9c1;
}

.product-card .prod-body {
    padding: 18px 20px 22px;
}

.product-card .prod-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 6px;
    line-height: 1.4;
}

.product-card .prod-price {
    font-size: 1.35rem;
    font-weight: 800;
    color: #1f9a5e;
    margin-bottom: 8px;
}

.product-card .prod-shop {
    font-size: 0.75rem;
    color: #4b556b;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.product-card .prod-shop i {
    color: #2d98da;
    font-size: 12px;
}

.product-card .prod-stock {
    display: inline-block;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    letter-spacing: 0.3px;
}

.prod-stock.ok  { background: #e1f7ed; color: #1e7b4c; }
.prod-stock.low { background: #ffe6e5; color: #c2412c; }

.product-card .prod-desc {
    font-size: 0.75rem;
    color: #5f6c84;
    margin-top: 12px;
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.no-products {
    text-align: center;
    color: #7e8aa2;
    padding: 48px 0;
    font-size: 1rem;
}

/* ---------- LOGIN MODAL (elegant) ---------- */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(10, 20, 35, 0.7);
    backdrop-filter: blur(5px);
    z-index: 999;
    align-items: center;
    justify-content: center;
}

.modal-overlay.active {
    display: flex;
}

.modal-box {
    background: #ffffff;
    border-radius: 32px;
    padding: 36px 32px;
    max-width: 400px;
    width: 90%;
    text-align: center;
    box-shadow: 0 40px 70px rgba(0,0,0,0.2);
    animation: fadeSlideUp 0.3s ease;
    position: relative;
}

@keyframes fadeSlideUp {
    from { opacity:0; transform: translateY(20px) scale(0.96);}
    to   { opacity:1; transform: translateY(0) scale(1);}
}

.modal-box .modal-icon {
    font-size: 52px;
    margin-bottom: 12px;
}

.modal-box h3 {
    font-size: 26px;
    font-weight: 700;
    color: #0b2b3b;
    margin-bottom: 12px;
}

.modal-box p {
    font-size: 15px;
    color: #4a5b7a;
    margin-bottom: 28px;
    line-height: 1.5;
}

.modal-btns {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
}

.modal-btns a {
    padding: 12px 28px;
    border-radius: 60px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    transition: 0.2s;
}

.mbtn-login {
    background: #2d98da;
    color: white;
    box-shadow: 0 2px 8px rgba(45,152,218,0.2);
}

.mbtn-login:hover {
    background: #1f7cb3;
    transform: translateY(-2px);
}

.mbtn-register {
    background: #f1f4f9;
    color: #1f2a44;
    border: 1px solid #dcf0fe;
}

.mbtn-register:hover {
    background: #e6edf8;
    border-color: #2d98da;
    color: #2d98da;
}

.modal-close {
    position: absolute;
    top: 18px;
    right: 22px;
    font-size: 26px;
    cursor: pointer;
    color: #8f9bb3;
    transition: all 0.2s;
    line-height: 1;
}

.modal-close:hover {
    color: #e24c4c;
    transform: scale(1.1);
}

/* ---------- FOOTER ---------- */
.footer {
    text-align: center;
    padding: 28px 20px;
    background: #0f2f3f;
    color: #eaf6ff;
    font-size: 0.9rem;
}

.footer span {
    color: #ffdd99;
    font-weight: 600;
}

/* ---------- RESPONSIVE TOUCH ---------- */
@media (max-width: 768px) {
    .navbar {
        padding: 12px 24px;
        flex-direction: column;
        gap: 12px;
    }
    .nav-links a {
        margin: 0 6px;
        font-size: 0.8rem;
        padding: 6px 12px;
    }
    .hero {
        height: 55vh;
    }
    .hero h1 {
        font-size: 42px;
    }
    .features {
        padding: 50px 20px 40px;
        gap: 24px;
    }
    .products-section {
        padding: 40px 20px 60px;
    }
    .products-grid {
        gap: 20px;
    }
    .product-card .prod-body {
        padding: 14px;
    }
}

/* force hiding any residual text inside hero overlay (just extra safety) */
.hero-overlay > *:not(h1) {
    display: none !important;
}
</style>
</head>
<body>

<!-- ========== NAVBAR ========== -->
<div class="navbar">
    <h2>🔍 Shop Finder</h2>
    <div class="nav-links">
        <a href="admindashboard.php?logout=1" class="btn btn-secondary">Admin</a>
        <a href="Customerregister.html" class="btn btn-primary">Register</a>
        <a href="customerlogin.html" class="btn btn-secondary">Login</a>
        <a href="aboutus.html" class="btn btn-primary">About Us</a>
        <a href="contact.html" class="btn btn-secondary">Contact Us</a>
    </div>
</div>

<!-- ========== HERO SECTION: ONLY "Find Shops Easily" REMAINS ========== -->
<div class="hero">
    <div class="hero-overlay">
        <h1>Find Shops Easily</h1>
        <!-- no other text, no paragraph, no buttons, no extra lines — everything removed as requested -->
    </div>
</div>

<!-- ========== FEATURES SECTION (moved up naturally) ========== -->
<div class="features">
    <div class="feature-box">
        <h3>🏪 Trusted Shops</h3>
        <p>All shops are verified for your safety and reliability.</p>
    </div>
    <div class="feature-box">
        <h3>💰 Best Prices</h3>
        <p>Compare prices and save money effortlessly.</p>
    </div>
    <div class="feature-box">
        <h3>⚡ Easy Search</h3>
        <p>Find exactly what you need in seconds, from any shop.</p>
    </div>
</div>

<!-- ========== PRODUCTS SECTION ========== -->
<div class="products-section">
    <h2>✨ Our Products</h2>
    <p class="subtitle">Browse products from our shops. Login to view full details.</p>

    <?php
    // Database connection and fetch products (embedded inline, follows original logic)
    $conn = new mysqli("localhost", "root", "", "shop_finder");
    $products_res = null;
    if (!$conn->connect_error) {
        $products_res = $conn->query("SELECT p.*, sp.shop_name FROM products p LEFT JOIN shop_profiles sp ON p.shop_id = sp.shop_id ORDER BY p.id DESC");
    }
    ?>

    <?php if ($products_res && $products_res->num_rows > 0): ?>
    <div class="products-grid">
        <?php while ($product = $products_res->fetch_assoc()): ?>
        <div class="product-card" onclick="showLoginModal()">
            <?php 
                $image_path = '';
                $has_image = !empty($product['image']) && file_exists('uploads/' . $product['image']);
                if ($has_image):
            ?>
                <img class="prod-img" src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            <?php else: ?>
                <div class="prod-img-placeholder">
                    🛍️
                </div>
            <?php endif; ?>

            <div class="prod-body">
                <div class="prod-name"><?php echo htmlspecialchars($product['name']); ?></div>
                <div class="prod-price">RWF <?php echo number_format($product['price'], 0); ?></div>
                <div class="prod-shop">
                    <i class="fas fa-store"></i> 
                    <?php echo !empty($product['shop_name']) ? htmlspecialchars($product['shop_name']) : 'Local Shop'; ?>
                </div>
                <span class="prod-stock <?php echo ($product['stock'] <= 5) ? 'low' : 'ok'; ?>">
                    <?php echo ($product['stock'] <= 5) ? '⚠️ Low Stock: ' : '✅ In Stock: '; echo $product['stock']; ?> units
                </span>
                <?php if (!empty($product['description'])): ?>
                <div class="prod-desc"><?php echo htmlspecialchars($product['description']); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
        <p class="no-products">🌟 No products available yet. Check back soon!</p>
    <?php endif; ?>
    <?php if (isset($conn) && $conn) $conn->close(); ?>
</div>

<!-- ========== FOOTER ========== -->
<div class="footer">
    <p>© 2026 <span>Elie</span> | Shop Finder — Discover local shops, smart prices</p>
</div>

<!-- ========== LOGIN MODAL (for product click) ========== -->
<div class="modal-overlay" id="loginModal" onclick="closeModalOnOverlay(event)">
    <div class="modal-box" style="position:relative;">
        <span class="modal-close" onclick="closeLoginModal()">&times;</span>
        <div class="modal-icon">🔐</div>
        <h3>Login Required</h3>
        <p>Please login or create an account to view full product details, shop inventories and exclusive prices.</p>
        <div class="modal-btns">
            <a href="customerlogin.html" class="mbtn-login">Login</a>
            <a href="Customerregister.html" class="mbtn-register">Register</a>
        </div>
    </div>
</div>

<!-- FontAwesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- JavaScript for Modal -->
<script>
function showLoginModal() {
    const modal = document.getElementById('loginModal');
    if (modal) modal.classList.add('active');
}

function closeLoginModal() {
    const modal = document.getElementById('loginModal');
    if (modal) modal.classList.remove('active');
}

function closeModalOnOverlay(e) {
    if (e.target === document.getElementById('loginModal')) {
        closeLoginModal();
    }
}

// Escape key closes modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLoginModal();
    }
});

// ensure that any accidental click propagation doesn't cause weirdness
document.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('click', (e) => {
        e.stopPropagation();
        showLoginModal();
    });
});
</script>

<!-- additional note: The hero section has been fully cleaned.
     Only "Find Shops Easily" text appears. No "Get Started" button, no description paragraphs.
     The feature section and product section flow seamlessly to the top, as expected. -->
</body>
</html>