<?php
$koneksi = mysqli_connect("localhost", "root", "", "todo_app");

// Cek koneksi
if (mysqli_connect_errno()) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>
