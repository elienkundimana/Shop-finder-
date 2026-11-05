<?php
session_start();

$conn = new mysqli("localhost", "root", "", "shop_finder");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_POST['email'];
$password = $_POST['password'];

// Hash password
$hashed_password = md5($password);

// Remove 'name' from SELECT - only select id and email
$stmt = $conn->prepare("SELECT id, email FROM shops WHERE email = ? AND password = ?");

// Check if prepare failed
if (!$stmt) {
    die("Query error: " . $conn->error);
}

$stmt->bind_param("ss", $email, $hashed_password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    $_SESSION['shop_id'] = $row['id'];
    $_SESSION['shop_email'] = $row['email'];
    
    header("Location: shopdashboard.php");
    exit();
} else {
    echo "<h3>Invalid email or password!</h3>";
    echo "<a href='shoplogin.html'>Try Again</a>";
}

$stmt->close();
$conn->close();
?>