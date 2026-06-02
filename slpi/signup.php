<?php

require_once "db_connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $nic = trim($_POST['nic']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check passwords
    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }

    // Check email already exists
    $checkEmail = $conn->prepare(
        "SELECT id FROM users WHERE email = ?"
    );

    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
        die("Email already registered.");
    }

    // Check NIC already exists
    $checkNic = $conn->prepare(
        "SELECT id FROM users WHERE nic = ?"
    );

    $checkNic->bind_param("s", $nic);
    $checkNic->execute();
    $checkNic->store_result();

    if ($checkNic->num_rows > 0) {
        die("NIC already registered.");
    }

    // Hash password
    $password_hash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    // Insert user
    $stmt = $conn->prepare("
        INSERT INTO users
        (
            full_name,
            email,
            phone,
            password_hash,
            nic,
            is_active
        )
        VALUES
        (
            ?, ?, ?, ?, ?, 1
        )
    ");

    $stmt->bind_param(
        "sssss",
        $full_name,
        $email,
        $phone,
        $password_hash,
        $nic
    );

    if ($stmt->execute()) {

        echo "
        <script>
            alert('Account created successfully!');
            window.location='login.html';
        </script>
        ";

    } else {

        echo "
        <script>
            alert('Registration failed!');
            window.history.back();
        </script>
        ";
    }

    $stmt->close();
}

$conn->close();

?>