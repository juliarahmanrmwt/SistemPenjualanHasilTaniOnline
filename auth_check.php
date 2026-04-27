<?php
ini_set('session.save_path', '/tmp');

// Mulai session hanya jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Cek apakah sudah login ────────────────────────────
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    // Belum login → redirect ke halaman login
    header("Location: login.php");
    exit;
}

// ── Cek session timeout (2 jam tidak aktif) ───────────
$timeout = 7200; // 2 jam dalam detik
if (isset($_SESSION['last_activity'])) {
    if ((time() - $_SESSION['last_activity']) > $timeout) {
        // Session kedaluwarsa → hancurkan dan redirect
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit;
    }
}

// Perbarui waktu aktivitas terakhir
$_SESSION['last_activity'] = time();

// ── Selesai. Halaman boleh dilanjutkan ────────────────