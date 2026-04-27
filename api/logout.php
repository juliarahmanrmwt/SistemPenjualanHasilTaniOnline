<?php
ini_set('session.save_path', '/tmp');
if (session_status() === PHP_SESSION_NONE) session_start();

session_unset();
session_destroy();

// Hapus Cookie Vercel
setcookie('user_session', '', time() - 3600, "/");

header("Location: /login");
exit;
?>