<?php
$conn = new mysqli("localhost", "root", "", "shop_finder");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_POST['email'];
$password = $_POST['password'];

if (empty($email) || empty($password)) {
    die("All fields are required!");
}

// Check if email already exists
$check = $conn->prepare("SELECT id FROM customers WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo "Email already registered!";
    exit();
}

// Hash password
$hashed_password = md5($password);

// Insert into database
$stmt = $conn->prepare("INSERT INTO customers (email, password) VALUES (?, ?)");
$stmt->bind_param("ss", $email, $hashed_password);

if ($stmt->execute()) {
    // Redirect after successful registration
    header("Location: customerlogin.html");
    exit();
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>