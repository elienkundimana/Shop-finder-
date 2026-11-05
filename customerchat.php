<?php
// customerchat.php — PHP 5.3+ compatible
session_start();

if (!isset($_SESSION['customer_email'])) {
    header("Location: customerlogin.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "shop_finder");
if ($conn->connect_error) die("DB error: " . $conn->connect_error);

$customer_email = $_SESSION['customer_email'];

// Resolve customer ID from email
$safe_email  = $conn->real_escape_string($customer_email);
$res         = $conn->query("SELECT id FROM customers WHERE email='$safe_email'");
if (!$res || $res->num_rows === 0) { header("Location: customerlogout.php"); exit(); }
$cid_row     = $res->fetch_assoc();          // FIX 1: split fetch so no chained []
$customer_id = intval($cid_row['id']);

// ── Handle send message ───────────────────────────────────────
$send_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $shop_id = intval($_POST['shop_id']);
    $message = trim($_POST['message']);

    if ($shop_id <= 0) {
        $send_error = "Please select a shop.";
    } elseif (empty($message)) {
        $send_error = "Message cannot be empty.";
    } elseif (strlen($message) > 2000) {
        $send_error = "Message is too long (max 2000 characters).";
    } else {
        $safe_msg = $conn->real_escape_string($message);
        $conn->query("INSERT INTO chat_messages (shop_id, customer_id, sender_type, message)
                      VALUES ($shop_id, $customer_id, 'customer', '$safe_msg')");
        header("Location: customerchat.php?shop_id=$shop_id&sent=1");
        exit();
    }
}

// ── Active shop (from URL) ────────────────────────────────────
$active_shop_id = isset($_GET['shop_id']) ? intval($_GET['shop_id']) : 0;
$just_sent      = isset($_GET['sent']);

// Fetch the active shop info
$active_shop = null;
if ($active_shop_id > 0) {
    $r = $conn->query("SELECT s.id, s.email, COALESCE(sp.shop_name, s.email) AS shop_name
                       FROM shops s
                       LEFT JOIN shop_profiles sp ON sp.shop_id = s.id
                       WHERE s.id = $active_shop_id");
    if ($r && $r->num_rows > 0) $active_shop = $r->fetch_assoc();
}

// ── Fetch conversation thread ─────────────────────────────────
$thread = array();                            // FIX 2: array() not []
if ($active_shop_id > 0) {
    $r = $conn->query("SELECT * FROM chat_messages
                       WHERE shop_id = $active_shop_id AND customer_id = $customer_id
                       ORDER BY created_at ASC");
    if ($r) {
        while ($row = $r->fetch_assoc()) $thread[] = $row;
    }
    // Mark shop replies as read
    $conn->query("UPDATE chat_messages SET is_read=1
                  WHERE shop_id=$active_shop_id AND customer_id=$customer_id
                    AND sender_type='shop' AND is_read=0");
}

// ── All shops list ────────────────────────────────────────────
$all_shops = array();                         // FIX 3: array() not []
$r = $conn->query("SELECT s.id, s.email, COALESCE(sp.shop_name, s.email) AS shop_name,
                          sp.address, sp.phone
                   FROM shops s
                   LEFT JOIN shop_profiles sp ON sp.shop_id = s.id
                   ORDER BY shop_name ASC");
if ($r) {
    while ($row = $r->fetch_assoc()) $all_shops[] = $row;
}

// ── Shops this customer has already chatted with ──────────────
$chat_shops = array();                        // FIX 4: array() not []
$r = $conn->query("SELECT DISTINCT cm.shop_id,
                          COALESCE(sp.shop_name, s.email) AS shop_name,
                          (SELECT message FROM chat_messages
                           WHERE shop_id=cm.shop_id AND customer_id=$customer_id
                           ORDER BY created_at DESC LIMIT 1) AS last_msg,
                          (SELECT created_at FROM chat_messages
                           WHERE shop_id=cm.shop_id AND customer_id=$customer_id
                           ORDER BY created_at DESC LIMIT 1) AS last_time,
                          (SELECT COUNT(*) FROM chat_messages
                           WHERE shop_id=cm.shop_id AND customer_id=$customer_id
                             AND sender_type='shop' AND is_read=0) AS unread
                   FROM chat_messages cm
                   JOIN shops s ON s.id = cm.shop_id
                   LEFT JOIN shop_profiles sp ON sp.shop_id = cm.shop_id
                   WHERE cm.customer_id = $customer_id
                   ORDER BY last_time DESC");
if ($r) {
    while ($row = $r->fetch_assoc()) $chat_shops[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chat with Shops | Shop Finder</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
:root {
    --primary:      #2d98da;
    --secondary:    #1565c0;
    --success:      #20bf6b;
    --danger:       #fc5c65;
    --dark:         #0d2b4e;
    --bg:           #f0f4f8;
    --white:        #ffffff;
    --text:         #333;
    --text-light:   #6b7280;
    --border:       #e5e7eb;
    --sidebar-bg:   #1565c0;
    --sidebar-dark: #0d47a1;
    --shadow:       0 4px 20px rgba(0,0,0,0.08);
}
body { font-family:'Poppins',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
a { text-decoration:none; color:inherit; }
.page { display:flex; min-height:100vh; }

/* Sidebar */
.sidebar { width:260px; background:linear-gradient(180deg,var(--sidebar-bg),var(--sidebar-dark)); display:flex; flex-direction:column; position:fixed; top:0; left:0; height:100vh; z-index:100; box-shadow:4px 0 20px rgba(13,71,161,0.25); }
.sidebar-brand { padding:22px 22px 16px; border-bottom:1px solid rgba(255,255,255,0.12); }
.sidebar-brand h2 { color:#fff; font-size:17px; font-weight:700; display:flex; align-items:center; gap:8px; }
.sidebar-brand h2 i { color:#90caf9; }
.sidebar-brand p { color:rgba(255,255,255,0.45); font-size:11px; margin-top:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.sidebar-nav { flex:1; padding:14px 0; overflow-y:auto; }
.nav-label { padding:7px 22px; font-size:10px; font-weight:600; letter-spacing:1.5px; text-transform:uppercase; color:rgba(255,255,255,0.35); margin-top:6px; }
.nav-link { display:flex; align-items:center; gap:11px; padding:12px 22px; color:rgba(255,255,255,0.7); font-size:13.5px; font-weight:500; transition:0.18s; border-left:3px solid transparent; }
.nav-link:hover { background:rgba(255,255,255,0.1); color:#fff; border-left-color:#90caf9; }
.nav-link.active { background:rgba(255,255,255,0.15); color:#fff; border-left-color:#90caf9; font-weight:600; }
.nav-link i { width:17px; text-align:center; font-size:14px; flex-shrink:0; }
.conv-item { display:flex; align-items:center; gap:11px; padding:11px 22px; border-left:3px solid transparent; transition:0.18s; }
.conv-item:hover { background:rgba(255,255,255,0.08); }
.conv-item.active { background:rgba(255,255,255,0.15); border-left-color:#90caf9; }
.conv-avatar { width:36px; height:36px; border-radius:50%; flex-shrink:0; background:rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; color:#fff; font-size:14px; font-weight:700; }
.conv-info { flex:1; min-width:0; }
.conv-name { color:#fff; font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.conv-preview { color:rgba(255,255,255,0.45); font-size:11px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px; }
.conv-unread { background:var(--danger); color:#fff; font-size:10px; font-weight:700; padding:2px 6px; border-radius:10px; flex-shrink:0; }
.sidebar-footer { padding:16px 22px; border-top:1px solid rgba(255,255,255,0.12); }
.sidebar-footer a { display:flex; align-items:center; gap:9px; color:rgba(255,255,255,0.6); font-size:13px; padding:9px; border-radius:8px; transition:0.18s; }
.sidebar-footer a:hover { background:rgba(252,92,101,0.18); color:#ff8a80; }

/* Main */
.main { margin-left:260px; flex:1; display:flex; flex-direction:column; }
.topbar { background:var(--white); padding:15px 30px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 2px 10px rgba(0,0,0,0.06); position:sticky; top:0; z-index:50; gap:12px; }
.topbar h1 { font-size:18px; font-weight:600; color:var(--dark); display:flex; align-items:center; gap:9px; }
.topbar h1 i { color:var(--primary); }
.topbar-right { display:flex; align-items:center; gap:10px; }
.badge-pill { display:flex; align-items:center; gap:8px; padding:6px 14px; background:var(--bg); border-radius:50px; }
.badge-pill .av { width:30px; height:30px; border-radius:50%; background:linear-gradient(135deg,var(--primary),var(--secondary)); display:flex; align-items:center; justify-content:center; color:#fff; font-size:12px; font-weight:700; }
.badge-pill span { font-size:12px; font-weight:500; color:var(--text-light); }
.btn-back { display:inline-flex; align-items:center; gap:7px; padding:7px 14px; background:rgba(45,152,218,0.08); color:var(--primary); border-radius:8px; font-size:12px; font-weight:500; transition:0.2s; }
.btn-back:hover { background:rgba(45,152,218,0.16); }
.content { padding:28px 30px; flex:1; }

/* New chat card */
.new-chat-card { background:var(--white); border-radius:16px; box-shadow:var(--shadow); overflow:hidden; margin-bottom:24px; }
.card-hd { padding:18px 24px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:9px; }
.card-hd h2 { font-size:15px; font-weight:600; color:var(--dark); display:flex; align-items:center; gap:9px; }
.card-hd h2 i { color:var(--primary); }
.card-body { padding:24px; }
.shop-selector { width:100%; padding:12px 16px; border:2px solid var(--border); border-radius:10px; font-size:14px; font-family:'Poppins',sans-serif; color:var(--text); outline:none; transition:0.2s; }
.shop-selector:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(45,152,218,0.12); }
.msg-textarea { width:100%; padding:12px 16px; border:2px solid var(--border); border-radius:10px; font-size:14px; font-family:'Poppins',sans-serif; color:var(--text); resize:vertical; min-height:110px; outline:none; transition:0.2s; margin-top:14px; }
.msg-textarea:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(45,152,218,0.12); }
.char-count { font-size:11px; color:var(--text-light); margin-top:5px; text-align:right; }
.btn-send { display:inline-flex; align-items:center; gap:8px; padding:11px 26px; background:linear-gradient(135deg,var(--secondary),var(--primary)); color:#fff; border:none; border-radius:10px; font-size:14px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer; transition:0.2s; margin-top:14px; }
.btn-send:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(45,152,218,0.35); }
.alert { padding:12px 16px; border-radius:9px; font-size:13px; font-weight:500; margin-bottom:16px; display:flex; align-items:center; gap:9px; }
.alert.success { background:#d1fae5; color:#065f46; }
.alert.error   { background:#fee2e2; color:#b91c1c; }

/* Thread card */
.thread-card { background:var(--white); border-radius:16px; box-shadow:var(--shadow); overflow:hidden; }
.thread-hd { padding:18px 24px; border-bottom:1px solid var(--border); background:linear-gradient(135deg,var(--secondary),var(--primary)); display:flex; align-items:center; gap:14px; }
.thread-av { width:46px; height:46px; border-radius:50%; background:rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px; font-weight:700; flex-shrink:0; }
.thread-hd-info h3 { color:#fff; font-size:15px; font-weight:700; }
.thread-hd-info p { color:rgba(255,255,255,0.75); font-size:12px; margin-top:2px; }
.thread-body { padding:20px 24px; max-height:500px; overflow-y:auto; display:flex; flex-direction:column; gap:12px; }
.thread-body::-webkit-scrollbar { width:4px; }
.thread-body::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:4px; }
.msg-wrap { display:flex; flex-direction:column; max-width:72%; }
.msg-wrap.mine   { align-self:flex-end;  align-items:flex-end; }
.msg-wrap.theirs { align-self:flex-start; align-items:flex-start; }
.msg-bubble { padding:11px 16px; border-radius:18px; font-size:13.5px; line-height:1.55; word-break:break-word; }
.msg-wrap.mine   .msg-bubble { background:linear-gradient(135deg,var(--secondary),var(--primary)); color:#fff; border-bottom-right-radius:5px; }
.msg-wrap.theirs .msg-bubble { background:var(--bg); color:var(--dark); border:1px solid var(--border); border-bottom-left-radius:5px; }
.msg-meta { font-size:10.5px; color:#9ca3af; margin-top:4px; display:flex; align-items:center; gap:5px; }
.date-sep { text-align:center; font-size:11px; color:#9ca3af; margin:4px 0; }
.date-sep::before,.date-sep::after { content:''; display:inline-block; width:25%; height:1px; background:var(--border); vertical-align:middle; margin:0 8px; }
.thread-empty { text-align:center; padding:40px 20px; color:var(--text-light); }
.thread-empty i { font-size:36px; opacity:0.2; display:block; margin-bottom:10px; }

/* Reply box */
.reply-box { padding:18px 24px; border-top:1px solid var(--border); background:var(--bg); }
.reply-box textarea { width:100%; padding:11px 16px; border:2px solid var(--border); border-radius:10px; font-size:13.5px; font-family:'Poppins',sans-serif; resize:none; min-height:80px; outline:none; transition:0.2s; }
.reply-box textarea:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(45,152,218,0.12); background:#fff; }
.reply-actions { display:flex; justify-content:space-between; align-items:center; margin-top:10px; flex-wrap:wrap; gap:8px; }
.reply-hint { font-size:11px; color:var(--text-light); }
.btn-reply { display:inline-flex; align-items:center; gap:7px; padding:10px 22px; background:linear-gradient(135deg,var(--secondary),var(--primary)); color:#fff; border:none; border-radius:9px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer; transition:0.2s; }
.btn-reply:hover { transform:translateY(-2px); box-shadow:0 5px 16px rgba(45,152,218,0.35); }

/* Empty state */
.empty-state { text-align:center; padding:60px 20px; color:var(--text-light); }
.empty-state i { font-size:52px; opacity:0.12; display:block; margin-bottom:14px; }
.empty-state p { font-size:15px; font-weight:500; }
.empty-state small { font-size:13px; }

.main-footer { background:var(--white); border-top:1px solid var(--border); padding:14px 30px; text-align:center; font-size:12px; color:var(--text-light); }
.main-footer span { color:var(--primary); font-weight:600; }

@media(max-width:768px){
    .sidebar { width:0; overflow:hidden; }
    .main { margin-left:0; }
    .content { padding:16px; }
}
</style>
</head>
<body>
<div class="page">

<!-- ══ SIDEBAR ══════════════════════════════════════════════ -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <h2><i class="fas fa-comments"></i> Messages</h2>
        <p><?php echo htmlspecialchars($customer_email); ?></p>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Navigation</div>
        <a href="customerdashboard.php" class="nav-link">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="customerchat.php" class="nav-link active">
            <i class="fas fa-comments"></i> My Chats
        </a>
        <a href="customerdashboard.php?section=shops" class="nav-link">
            <i class="fas fa-store-alt"></i> Browse Shops
        </a>

        <?php if (!empty($chat_shops)): ?>
        <div class="nav-label" style="margin-top:10px;">Recent Chats</div>
        <?php foreach ($chat_shops as $cs):
            $init         = strtoupper(substr($cs['shop_name'], 0, 1));
            $is_active    = ($active_shop_id === intval($cs['shop_id']));
            // FIX 5: isset() instead of ?? (null coalescing not available before PHP 7)
            $last_preview = isset($cs['last_msg']) ? substr($cs['last_msg'], 0, 32) : '';
        ?>
        <a href="customerchat.php?shop_id=<?php echo $cs['shop_id']; ?>"
           class="conv-item <?php echo $is_active ? 'active' : ''; ?>">
            <div class="conv-avatar"><?php echo htmlspecialchars($init); ?></div>
            <div class="conv-info">
                <div class="conv-name"><?php echo htmlspecialchars($cs['shop_name']); ?></div>
                <div class="conv-preview"><?php echo htmlspecialchars($last_preview); ?>...</div>
            </div>
            <?php if ($cs['unread'] > 0): ?>
            <span class="conv-unread"><?php echo $cs['unread']; ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="customerlogout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>

<!-- ══ MAIN ═════════════════════════════════════════════════ -->
<div class="main">
    <div class="topbar">
        <h1><i class="fas fa-comments"></i>
            <?php echo $active_shop ? 'Chat with ' . htmlspecialchars($active_shop['shop_name']) : 'Chat with Shops'; ?>
        </h1>
        <div class="topbar-right">
            <a href="customerdashboard.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
            <div class="badge-pill">
                <div class="av"><?php echo strtoupper(substr($customer_email, 0, 1)); ?></div>
                <span>Customer</span>
            </div>
        </div>
    </div>

    <div class="content">

        <!-- Send new message form -->
        <div class="new-chat-card">
            <div class="card-hd">
                <h2><i class="fas fa-plus-circle"></i> Send a Message to a Shop</h2>
            </div>
            <div class="card-body">

                <?php if ($send_error): ?>
                <div class="alert error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($send_error); ?></div>
                <?php endif; ?>

                <?php if ($just_sent): ?>
                <div class="alert success"><i class="fas fa-check-circle"></i> Message sent successfully!</div>
                <?php endif; ?>

                <form method="POST" action="customerchat.php<?php echo $active_shop_id ? '?shop_id='.$active_shop_id : ''; ?>">
                    <label style="font-size:13px;font-weight:600;color:var(--text-light);display:block;margin-bottom:7px;">
                        Choose a Shop
                    </label>
                    <select name="shop_id" class="shop-selector" required>
                        <option value="">-- Select a shop --</option>
                        <?php foreach ($all_shops as $sh): ?>
                        <option value="<?php echo $sh['id']; ?>"
                            <?php echo ($active_shop_id === intval($sh['id'])) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sh['shop_name']); ?>
                            <?php if (!empty($sh['address'])): ?> - <?php echo htmlspecialchars($sh['address']); ?><?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <textarea name="message" class="msg-textarea"
                              placeholder="Write your message here..."
                              maxlength="2000"
                              id="msgTextarea"
                              oninput="document.getElementById('charCount').textContent=this.value.length+'/2000'"
                              required></textarea>
                    <div class="char-count" id="charCount">0/2000</div>

                    <button type="submit" name="send_message" class="btn-send">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>

        <!-- Conversation thread -->
        <?php if ($active_shop && !empty($thread)): ?>

        <div class="thread-card">
            <div class="thread-hd">
                <div class="thread-av"><?php echo strtoupper(substr($active_shop['shop_name'], 0, 1)); ?></div>
                <div class="thread-hd-info">
                    <h3><?php echo htmlspecialchars($active_shop['shop_name']); ?></h3>
                    <p><?php echo htmlspecialchars($active_shop['email']); ?> &nbsp;&middot;&nbsp;
                       <?php echo count($thread); ?> message<?php echo count($thread) !== 1 ? 's' : ''; ?></p>
                </div>
            </div>

            <div class="thread-body" id="threadBody">
            <?php
            $last_date = '';
            foreach ($thread as $msg):
                $isMine = ($msg['sender_type'] === 'customer');

                // FIX 6: build DateTime objects without chained ->setTime on constructor
                // (clone syntax on constructor result not supported in PHP 5.3)
                $dt = new DateTime($msg['created_at']);

                $today = new DateTime();
                $today->setTime(0, 0, 0);

                $yest = new DateTime();
                $yest->setTime(0, 0, 0);
                $yest->modify('-1 day');

                $msgDay = new DateTime($msg['created_at']);
                $msgDay->setTime(0, 0, 0);

                if ($msgDay == $today)     $date_label = 'Today';
                elseif ($msgDay == $yest)  $date_label = 'Yesterday';
                else                       $date_label = $dt->format('M j, Y');

                if ($date_label !== $last_date):
                    $last_date = $date_label;
            ?>
                <div class="date-sep"><?php echo $date_label; ?></div>
            <?php endif; ?>

                <div class="msg-wrap <?php echo $isMine ? 'mine' : 'theirs'; ?>">
                    <div class="msg-bubble"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                    <div class="msg-meta">
                        <?php if ($isMine): ?>
                            <i class="fas fa-user" style="font-size:9px;"></i> You
                        <?php else: ?>
                            <i class="fas fa-store" style="font-size:9px;"></i> <?php echo htmlspecialchars($active_shop['shop_name']); ?>
                        <?php endif; ?>
                        &nbsp;&middot;&nbsp; <?php echo $dt->format('H:i'); ?>
                        <?php if ($isMine && $msg['is_read']): ?>
                            &nbsp;<i class="fas fa-check-double" style="color:var(--success);font-size:9px;" title="Read by shop"></i>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endforeach; ?>
            </div>

            <!-- Reply -->
            <div class="reply-box">
                <form method="POST" action="customerchat.php?shop_id=<?php echo $active_shop_id; ?>">
                    <input type="hidden" name="shop_id" value="<?php echo $active_shop_id; ?>">
                    <textarea name="message" placeholder="Write a reply..." maxlength="2000" required></textarea>
                    <div class="reply-actions">
                        <span class="reply-hint"><i class="fas fa-info-circle"></i> The shop will see your message in their dashboard</span>
                        <button type="submit" name="send_message" class="btn-reply">
                            <i class="fas fa-reply"></i> Send Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php elseif ($active_shop && empty($thread)): ?>

        <div class="thread-card">
            <div class="thread-hd">
                <div class="thread-av"><?php echo strtoupper(substr($active_shop['shop_name'], 0, 1)); ?></div>
                <div class="thread-hd-info">
                    <h3><?php echo htmlspecialchars($active_shop['shop_name']); ?></h3>
                    <p>Start the conversation</p>
                </div>
            </div>
            <div class="thread-empty">
                <i class="fas fa-comments"></i>
                <p>No messages yet</p>
                <small>Use the form above to send your first message to this shop.</small>
            </div>
        </div>

        <?php elseif (empty($chat_shops)): ?>

        <div class="empty-state">
            <i class="fas fa-comments"></i>
            <p>No conversations yet</p>
            <small>Use the form above to message any shop!</small>
        </div>

        <?php endif; ?>

    </div><!-- /content -->
    <div class="main-footer">&copy; 2026 <span>Elie</span> | Shop Finder</div>
</div><!-- /main -->
</div><!-- /page -->

<script>
var tb = document.getElementById('threadBody');
if (tb) tb.scrollTop = tb.scrollHeight;

document.querySelector('select[name="shop_id"]').addEventListener('change', function() {
    if (this.value) {
        window.location.href = 'customerchat.php?shop_id=' + this.value;
    }
});
</script>
</body>
</html>
