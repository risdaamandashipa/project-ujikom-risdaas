<?php
session_start();
include 'koneksi.php';

if(isset($_POST['email'])){
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validasi panjang password
    if(strlen($password) !== 8) {
        echo '<script>alert("Password harus tepat 8 karakter."); window.location.href="register.php";</script>';
        exit();
    }

    $query = mysqli_query($koneksi, "INSERT INTO users(username, email, password) VALUES('$username','$email','$password')");
    if($query) {
        echo '<script>alert("Register Berhasil"); window.location.href="index.php";</script>';
        exit();
    }else{
        echo '<script>alert("Register Gagal"); window.location.href="register.php";</script>';
        exit();
    }
}
?>
