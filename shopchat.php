<?php
// shopchat.php — PHP 5.3+ compatible
session_start();

if (!isset($_SESSION['shop_id'])) {
    header("Location: shoplogin.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "shop_finder");
if ($conn->connect_error) die("DB error: " . $conn->connect_error);

$shop_id    = intval($_SESSION['shop_id']);
$shop_email = $_SESSION['shop_email'];

// ── Handle send reply ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $customer_id = intval($_POST['customer_id']);
    $message     = trim($_POST['message']);

    if ($customer_id > 0 && !empty($message) && strlen($message) <= 2000) {
        $safe_msg = $conn->real_escape_string($message);
        $conn->query("INSERT INTO chat_messages (shop_id, customer_id, sender_type, message)
                      VALUES ($shop_id, $customer_id, 'shop', '$safe_msg')");
    }
    header("Location: shopchat.php?customer_id=$customer_id&sent=1");
    exit();
}

// ── Active conversation ───────────────────────────────────────
$active_customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$just_sent          = isset($_GET['sent']);

// Fetch active customer info
$active_customer = null;
if ($active_customer_id > 0) {
    $r = $conn->query("SELECT c.id, c.email,
                              COALESCE(cp.full_name, c.email) AS display_name
                       FROM customers c
                       LEFT JOIN customer_profiles cp ON cp.customer_id = c.id
                       WHERE c.id = $active_customer_id");
    if ($r && $r->num_rows > 0) $active_customer = $r->fetch_assoc();
}

// Fetch thread
$thread = array();                            // FIX 1: array() not []
if ($active_customer_id > 0) {
    $r = $conn->query("SELECT * FROM chat_messages
                       WHERE shop_id=$shop_id AND customer_id=$active_customer_id
                       ORDER BY created_at ASC");
    if ($r) {
        while ($row = $r->fetch_assoc()) $thread[] = $row;
    }
    // Mark customer messages as read
    $conn->query("UPDATE chat_messages SET is_read=1
                  WHERE shop_id=$shop_id AND customer_id=$active_customer_id
                    AND sender_type='customer' AND is_read=0");
}

// ── Conversation list ─────────────────────────────────────────
$conversations = array();                     // FIX 2: array() not []
$r = $conn->query("
    SELECT
        c.id AS customer_id,
        c.email AS customer_email,
        COALESCE(cp.full_name, c.email) AS display_name,
        (SELECT message FROM chat_messages
         WHERE shop_id=$shop_id AND customer_id=c.id
         ORDER BY created_at DESC LIMIT 1) AS last_msg,
        (SELECT created_at FROM chat_messages
         WHERE shop_id=$shop_id AND customer_id=c.id
         ORDER BY created_at DESC LIMIT 1) AS last_time,
        (SELECT sender_type FROM chat_messages
         WHERE shop_id=$shop_id AND customer_id=c.id
         ORDER BY created_at DESC LIMIT 1) AS last_sender,
        (SELECT COUNT(*) FROM chat_messages
         WHERE shop_id=$shop_id AND customer_id=c.id
           AND sender_type='customer' AND is_read=0) AS unread
    FROM chat_messages cm
    JOIN customers c ON c.id = cm.customer_id
    LEFT JOIN customer_profiles cp ON cp.customer_id = c.id
    WHERE cm.shop_id = $shop_id
    GROUP BY c.id, c.email, cp.full_name
    ORDER BY last_time DESC
");
if ($r) {
    while ($row = $r->fetch_assoc()) $conversations[] = $row;
}

// Total unread
$total_unread = 0;
foreach ($conversations as $cv) $total_unread += intval($cv['unread']);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Messages | Shop Finder</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
:root {
    --primary:      #3867d6;
    --primary-lt:   #4facfe;
    --success:      #20bf6b;
    --danger:       #fc5c65;
    --dark:         #1a1f2e;
    --bg:           #f0f4f8;
    --white:        #ffffff;
    --text:         #333;
    --text-light:   #6b7280;
    --border:       #e5e7eb;
    --sidebar-bg:   #1565c0;
    --sidebar-dk:   #0d47a1;
    --shadow:       0 4px 20px rgba(0,0,0,0.08);
}
body { font-family:'Poppins',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
a { text-decoration:none; color:inherit; }
.page { display:flex; min-height:100vh; }

/* Sidebar */
.sidebar { width:280px; background:linear-gradient(180deg,var(--sidebar-bg),var(--sidebar-dk)); display:flex; flex-direction:column; position:fixed; top:0; left:0; height:100vh; z-index:100; box-shadow:4px 0 20px rgba(13,71,161,0.25); }
.sidebar-brand { padding:22px 22px 16px; border-bottom:1px solid rgba(255,255,255,0.12); }
.sidebar-brand h2 { color:#fff; font-size:17px; font-weight:700; display:flex; align-items:center; gap:9px; flex-wrap:wrap; }
.sidebar-brand h2 i { color:#90caf9; }
.unread-total { background:var(--danger); color:#fff; font-size:11px; font-weight:700; padding:2px 7px; border-radius:10px; }
.sidebar-brand p { color:rgba(255,255,255,0.45); font-size:11px; margin-top:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.conv-list { flex:1; overflow-y:auto; padding:10px 0; }
.conv-list::-webkit-scrollbar { width:3px; }
.conv-list::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.2); border-radius:3px; }
.conv-list-title { padding:7px 22px 5px; font-size:10px; font-weight:600; letter-spacing:1.5px; text-transform:uppercase; color:rgba(255,255,255,0.35); }
.conv-item { display:flex; align-items:center; gap:12px; padding:13px 22px; border-left:3px solid transparent; transition:0.18s; }
.conv-item:hover { background:rgba(255,255,255,0.08); }
.conv-item.active { background:rgba(255,255,255,0.16); border-left-color:#90caf9; }
.conv-av { width:40px; height:40px; border-radius:50%; flex-shrink:0; background:rgba(255,255,255,0.18); display:flex; align-items:center; justify-content:center; color:#fff; font-size:15px; font-weight:700; }
.conv-av.has-unread { background:rgba(252,92,101,0.3); }
.conv-info { flex:1; min-width:0; }
.conv-name { color:#fff; font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.conv-preview { color:rgba(255,255,255,0.45); font-size:11px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px; }
.conv-right { text-align:right; flex-shrink:0; }
.conv-time { color:rgba(255,255,255,0.35); font-size:10px; }
.conv-badge { background:var(--danger); color:#fff; font-size:10px; font-weight:700; padding:2px 6px; border-radius:10px; margin-top:3px; display:inline-block; }
.conv-empty { padding:30px 22px; text-align:center; color:rgba(255,255,255,0.35); font-size:13px; }
.conv-empty i { font-size:32px; opacity:0.3; display:block; margin-bottom:10px; }
.sidebar-footer { padding:16px 22px; border-top:1px solid rgba(255,255,255,0.12); display:flex; flex-direction:column; gap:4px; }
.sidebar-footer a { display:flex; align-items:center; gap:9px; color:rgba(255,255,255,0.6); font-size:13px; padding:9px; border-radius:8px; transition:0.18s; }
.sidebar-footer a:hover { background:rgba(255,255,255,0.1); color:#fff; }
.sidebar-footer a.logout:hover { background:rgba(252,92,101,0.18); color:#ff8a80; }

/* Main */
.main { margin-left:280px; flex:1; display:flex; flex-direction:column; }
.topbar { background:var(--white); padding:15px 28px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 2px 10px rgba(0,0,0,0.06); position:sticky; top:0; z-index:50; gap:12px; }
.topbar h1 { font-size:18px; font-weight:600; color:var(--dark); display:flex; align-items:center; gap:9px; }
.topbar h1 i { color:var(--primary); }
.topbar-right { display:flex; align-items:center; gap:10px; }
.badge-pill { display:flex; align-items:center; gap:8px; padding:6px 14px; background:var(--bg); border-radius:50px; }
.badge-pill .av { width:30px; height:30px; border-radius:50%; background:linear-gradient(135deg,var(--primary),var(--primary-lt)); display:flex; align-items:center; justify-content:center; color:#fff; font-size:12px; font-weight:700; }
.badge-pill span { font-size:12px; font-weight:500; color:var(--text-light); }
.btn-back { display:inline-flex; align-items:center; gap:7px; padding:7px 14px; background:rgba(56,103,214,0.08); color:var(--primary); border-radius:8px; font-size:12px; font-weight:500; transition:0.2s; }
.btn-back:hover { background:rgba(56,103,214,0.16); }
.content { padding:28px; flex:1; }

/* Thread */
.thread-card { background:var(--white); border-radius:16px; box-shadow:var(--shadow); overflow:hidden; }
.thread-hd { padding:18px 24px; border-bottom:1px solid var(--border); background:linear-gradient(135deg,var(--sidebar-dk),var(--primary)); display:flex; align-items:center; gap:14px; }
.thread-av { width:46px; height:46px; border-radius:50%; background:rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px; font-weight:700; flex-shrink:0; }
.thread-hd-info h3 { color:#fff; font-size:15px; font-weight:700; }
.thread-hd-info p { color:rgba(255,255,255,0.7); font-size:12px; margin-top:2px; }
.thread-body { padding:20px 24px; max-height:480px; overflow-y:auto; display:flex; flex-direction:column; gap:12px; }
.thread-body::-webkit-scrollbar { width:4px; }
.thread-body::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:4px; }
.msg-wrap { display:flex; flex-direction:column; max-width:72%; }
.msg-wrap.mine   { align-self:flex-end;  align-items:flex-end; }
.msg-wrap.theirs { align-self:flex-start; align-items:flex-start; }
.msg-bubble { padding:11px 16px; border-radius:18px; font-size:13.5px; line-height:1.55; word-break:break-word; }
.msg-wrap.mine   .msg-bubble { background:linear-gradient(135deg,var(--sidebar-dk),var(--primary)); color:#fff; border-bottom-right-radius:5px; }
.msg-wrap.theirs .msg-bubble { background:var(--bg); color:var(--dark); border:1px solid var(--border); border-bottom-left-radius:5px; }
.msg-meta { font-size:10.5px; color:#9ca3af; margin-top:4px; display:flex; align-items:center; gap:5px; }
.date-sep { text-align:center; font-size:11px; color:#9ca3af; margin:4px 0; }
.date-sep::before,.date-sep::after { content:''; display:inline-block; width:25%; height:1px; background:var(--border); vertical-align:middle; margin:0 8px; }
.thread-empty { text-align:center; padding:50px 20px; color:var(--text-light); }
.thread-empty i { font-size:40px; opacity:0.15; display:block; margin-bottom:12px; }

/* Reply box */
.reply-box { padding:18px 24px; border-top:1px solid var(--border); background:var(--bg); }
.reply-box textarea { width:100%; padding:11px 16px; border:2px solid var(--border); border-radius:10px; font-size:13.5px; font-family:'Poppins',sans-serif; resize:none; min-height:90px; outline:none; transition:0.2s; }
.reply-box textarea:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(56,103,214,0.12); background:#fff; }
.reply-actions { display:flex; justify-content:space-between; align-items:center; margin-top:10px; flex-wrap:wrap; gap:8px; }
.reply-hint { font-size:11px; color:var(--text-light); display:flex; align-items:center; gap:5px; }
.alert { padding:12px 16px; border-radius:9px; font-size:13px; font-weight:500; margin-bottom:16px; display:flex; align-items:center; gap:9px; }
.alert.success { background:#d1fae5; color:#065f46; }
.btn-reply { display:inline-flex; align-items:center; gap:7px; padding:10px 24px; background:linear-gradient(135deg,var(--sidebar-dk),var(--primary)); color:#fff; border:none; border-radius:9px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer; transition:0.2s; }
.btn-reply:hover { transform:translateY(-2px); box-shadow:0 5px 16px rgba(56,103,214,0.35); }

/* No conversation */
.no-convo { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:var(--text-light); gap:12px; padding:60px 20px; }
.no-convo i { font-size:64px; opacity:0.1; }
.no-convo p { font-size:16px; font-weight:600; }
.no-convo small { font-size:13px; }

.main-footer { background:var(--white); border-top:1px solid var(--border); padding:14px 28px; text-align:center; font-size:12px; color:var(--text-light); }
.main-footer span { color:var(--primary); font-weight:600; }

@media(max-width:768px) { .sidebar{width:0;overflow:hidden;} .main{margin-left:0;} .content{padding:16px;} }
</style>
</head>
<body>
<div class="page">

<!-- ══ SIDEBAR ══════════════════════════════════════════════ -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <h2>
            <i class="fas fa-inbox"></i> Messages
            <?php if ($total_unread > 0): ?>
            <span class="unread-total"><?php echo $total_unread; ?></span>
            <?php endif; ?>
        </h2>
        <p><?php echo htmlspecialchars($shop_email); ?></p>
    </div>

    <div class="conv-list">
        <?php if (empty($conversations)): ?>
        <div class="conv-empty">
            <i class="fas fa-inbox"></i>
            No messages yet.<br>Customers will appear here when they message you.
        </div>
        <?php else: ?>
        <div class="conv-list-title">Conversations (<?php echo count($conversations); ?>)</div>
        <?php foreach ($conversations as $cv):
            $cid      = intval($cv['customer_id']);
            $init     = strtoupper(substr($cv['display_name'], 0, 1));
            $unread   = intval($cv['unread']);
            $isActive = ($active_customer_id === $cid);

            // Format time — FIX 3: no ?? operator
            $time_str = '';
            if (isset($cv['last_time']) && $cv['last_time']) {
                $dt   = new DateTime($cv['last_time']);
                $now  = new DateTime();
                $diff = $now->diff($dt);
                if ($diff->days === 0)     $time_str = $dt->format('H:i');
                elseif ($diff->days === 1) $time_str = 'Yesterday';
                else                       $time_str = $dt->format('M d');
            }

            // FIX 4: isset() for last_msg instead of ??
            $last_msg_text = isset($cv['last_msg']) ? $cv['last_msg'] : '';
            $preview = ($cv['last_sender'] === 'shop' ? 'You: ' : '') . substr($last_msg_text, 0, 30) . '...';
        ?>
        <a href="shopchat.php?customer_id=<?php echo $cid; ?>"
           class="conv-item <?php echo $isActive ? 'active' : ''; ?>">
            <div class="conv-av <?php echo $unread > 0 ? 'has-unread' : ''; ?>"><?php echo $init; ?></div>
            <div class="conv-info">
                <div class="conv-name"><?php echo htmlspecialchars($cv['display_name']); ?></div>
                <div class="conv-preview"><?php echo htmlspecialchars($preview); ?></div>
            </div>
            <div class="conv-right">
                <div class="conv-time"><?php echo $time_str; ?></div>
                <?php if ($unread > 0): ?>
                <span class="conv-badge"><?php echo $unread; ?></span>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="sidebar-footer">
        <a href="shopdashboard.php">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="shoplogout.php" class="logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>

<!-- ══ MAIN ═════════════════════════════════════════════════ -->
<div class="main">
    <div class="topbar">
        <h1><i class="fas fa-comments"></i>
            <?php echo $active_customer ? 'Chat with ' . htmlspecialchars($active_customer['display_name']) : 'Customer Messages'; ?>
        </h1>
        <div class="topbar-right">
            <a href="shopdashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <div class="badge-pill">
                <div class="av"><?php echo strtoupper(substr($shop_email, 0, 1)); ?></div>
                <span>Shop Owner</span>
            </div>
        </div>
    </div>

    <div class="content">

    <?php if ($active_customer): ?>

        <?php if ($just_sent): ?>
        <div class="alert success"><i class="fas fa-check-circle"></i> Reply sent successfully!</div>
        <?php endif; ?>

        <div class="thread-card">
            <div class="thread-hd">
                <div class="thread-av"><?php echo strtoupper(substr($active_customer['display_name'], 0, 1)); ?></div>
                <div class="thread-hd-info">
                    <h3><?php echo htmlspecialchars($active_customer['display_name']); ?></h3>
                    <p><?php echo htmlspecialchars($active_customer['email']); ?> &nbsp;&middot;&nbsp;
                       <?php echo count($thread); ?> message<?php echo count($thread) !== 1 ? 's' : ''; ?></p>
                </div>
            </div>

            <div class="thread-body" id="threadBody">
            <?php if (empty($thread)): ?>
                <div class="thread-empty">
                    <i class="fas fa-comment-slash"></i>
                    <p>No messages in this conversation yet.</p>
                </div>
            <?php else:
                $last_date = '';
                foreach ($thread as $msg):
                    $isMine = ($msg['sender_type'] === 'shop');

                    // FIX 5: same DateTime fix as customer side
                    $dt = new DateTime($msg['created_at']);

                    $today = new DateTime();
                    $today->setTime(0, 0, 0);

                    $yest = new DateTime();
                    $yest->setTime(0, 0, 0);
                    $yest->modify('-1 day');

                    $msgDay = new DateTime($msg['created_at']);
                    $msgDay->setTime(0, 0, 0);

                    if ($msgDay == $today)     $dl = 'Today';
                    elseif ($msgDay == $yest)  $dl = 'Yesterday';
                    else                       $dl = $dt->format('M j, Y');

                    if ($dl !== $last_date): $last_date = $dl;
            ?>
                <div class="date-sep"><?php echo $dl; ?></div>
            <?php endif; ?>
                <div class="msg-wrap <?php echo $isMine ? 'mine' : 'theirs'; ?>">
                    <div class="msg-bubble"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                    <div class="msg-meta">
                        <?php if ($isMine): ?>
                            <i class="fas fa-store" style="font-size:9px;"></i> You
                        <?php else: ?>
                            <i class="fas fa-user" style="font-size:9px;"></i> <?php echo htmlspecialchars($active_customer['display_name']); ?>
                        <?php endif; ?>
                        &nbsp;&middot;&nbsp; <?php echo $dt->format('H:i'); ?>
                        <?php if ($isMine && $msg['is_read']): ?>
                            &nbsp;<i class="fas fa-check-double" style="color:var(--success);font-size:9px;" title="Read by customer"></i>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php endif; ?>
            </div>

            <div class="reply-box">
                <form method="POST" action="shopchat.php?customer_id=<?php echo $active_customer_id; ?>">
                    <input type="hidden" name="customer_id" value="<?php echo $active_customer_id; ?>">
                    <textarea name="message"
                              placeholder="Write your reply to <?php echo htmlspecialchars($active_customer['display_name']); ?>..."
                              maxlength="2000" required></textarea>
                    <div class="reply-actions">
                        <span class="reply-hint">
                            <i class="fas fa-info-circle"></i>
                            The customer will see your reply on their chat page.
                        </span>
                        <button type="submit" name="send_reply" class="btn-reply">
                            <i class="fas fa-reply"></i> Send Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <div class="no-convo">
            <i class="fas fa-inbox"></i>
            <p><?php echo empty($conversations) ? 'No messages yet' : 'Select a conversation'; ?></p>
            <small><?php echo empty($conversations) ? 'Customers will message you from your shop page.' : 'Choose a customer from the left panel.'; ?></small>
        </div>
    <?php endif; ?>

    </div><!-- /content -->
    <div class="main-footer">&copy; 2026 <span>Elie</span> | Shop Finder</div>
</div><!-- /main -->
</div><!-- /page -->

<script>
var tb = document.getElementById('threadBody');
if (tb) tb.scrollTop = tb.scrollHeight;
</script>
</body>
</html>
