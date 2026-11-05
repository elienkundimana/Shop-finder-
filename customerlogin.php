<?php
session_start();

$conn = new mysqli("localhost", "root", "", "shop_finder");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_POST['email'];
$password = $_POST['password'];

// Hash entered password
$hashed_password = md5($password);

// Check user
$stmt = $conn->prepare("SELECT id FROM customers WHERE email = ? AND password = ?");
$stmt->bind_param("ss", $email, $hashed_password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    // You must store the ID for the chat logic to work
    $_SESSION['customer_id'] = $row['id']; 
    $_SESSION['customer_email'] = $email;

    header("Location: customerdashboard.php");
    exit();
}
else {
    // ❌ Login failed
    echo "Invalid email or password!";
}

$stmt->close();
$conn->close();
?>
