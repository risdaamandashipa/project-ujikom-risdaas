<?php
session_start();
session_destroy();
header("Location: index.php"); // Redirect ke halaman login
exit();
?>
