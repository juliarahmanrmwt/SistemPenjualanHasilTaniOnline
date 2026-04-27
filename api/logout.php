<?php
ini_set('session.save_path', '/tmp');
if (session_status() === PHP_SESSION_NONE) session_start();

session_unset();
session_destroy();

// PERBAIKAN: Arahkan ke root /login (sesuai vercel.json)
header("Location: /login");
exit;
?>