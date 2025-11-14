<?php
// Luôn bắt đầu session trước khi thao tác với nó
session_start();

// Hủy tất cả các biến session
$_SESSION = array();

// Hủy session
session_destroy();

// Chuyển hướng người dùng về trang đăng nhập
header("Location: login.php");
exit;
?>