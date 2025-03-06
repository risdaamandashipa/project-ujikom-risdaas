<?php
session_start();
include 'koneksi.php';

$email = trim($_POST['email']);
$password = trim($_POST['password']);

$stmt = $koneksi->prepare("SELECT * FROM users WHERE email=? AND password=?");
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $email;
    $_SESSION['status'] = "login";

    // Cek apakah header berhasil
    header("Location: todoapp.php");
    exit();
} else {
    echo '<script>alert("Password atau email salah."); window.location.href="index.php";</script>';
    exit();
}


?>
