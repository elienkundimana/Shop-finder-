<?php
$conn = new mysqli("localhost", "root", "", "shop_finder");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Remove 'name' - only get email and password
$email = $_POST['email'];
$password = $_POST['password'];

// Check if fields are empty
if (empty($email) || empty($password)) {
    die("All fields are required!");
}

// Check if email already exists
$check = $conn->prepare("SELECT id FROM shops WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    die("Email already registered!");
}

// Hash password
$hashed_password = md5($password);

// Insert only email and password (no name)
$stmt = $conn->prepare("INSERT INTO shops (email, password) VALUES (?, ?)");
$stmt->bind_param("ss", $email, $hashed_password);

if ($stmt->execute()) {
    header("Location: shoplogin.html");
    exit();
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>