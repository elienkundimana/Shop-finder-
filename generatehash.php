<?php
// =============================================
// PASSWORD HASH GENERATOR
// =============================================
// HOW TO USE:
//   1. Open this file in your browser:
//      http://localhost/final_project/generate_hash.php
//   2. Copy the hash shown on screen.
//   3. Paste it into admindashboard.php as the
//      value of ADMIN_PASSWORD_HASH.
//   4. DELETE this file immediately after use!
//      Leaving it on the server is a security risk.
// =============================================

// <-- CHANGE THIS to your desired password -->
$new_password = 'YourNewStrongPassword123!';

$hash = password_hash($new_password, PASSWORD_BCRYPT);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hash Generator</title>
    <style>
        body { font-family: monospace; padding: 40px; background: #1a1f2e; color: #e8f4fc; }
        .box { background: #0f1422; border: 1px solid #2d98da; border-radius: 10px; padding: 24px; max-width: 700px; }
        h2   { color: #2d98da; margin-bottom: 16px; }
        p    { font-size: 13px; color: #6b7280; margin-bottom: 10px; }
        .hash { word-break: break-all; font-size: 15px; color: #20bf6b; background: #0a0d14; padding: 14px; border-radius: 6px; }
        .warn { margin-top: 20px; color: #fc5c65; font-size: 13px; }
    </style>
</head>
<body>
<div class="box">
    <h2>&#x1F511; Password Hash Generator</h2>
    <p>Password entered: <strong style="color:#f7b731"><?php echo htmlspecialchars($new_password); ?></strong></p>
    <p>Copy this hash and paste it into <code>admindashboard.php</code> as <code>ADMIN_PASSWORD_HASH</code>:</p>
    <div class="hash"><?php echo htmlspecialchars($hash); ?></div>
    <p class="warn">&#x26A0; DELETE this file immediately after use!</p>
</div>
</body>
</html>
