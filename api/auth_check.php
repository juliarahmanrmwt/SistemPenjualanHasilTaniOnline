<?php
ini_set('session.save_path', '/tmp');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- SOLUSI KHUSUS VERCEL SERVERLESS SESSION ---
$secret = 'petanigenz_rahasia_123'; // Kunci enkripsi
if (isset($_COOKIE['user_session'])) {
    $parts = explode('|', $_COOKIE['user_session']);
    if (count($parts) === 2) {
        list($data, $signature) = $parts;
        if (hash_hmac('sha256', $data, $secret) === $signature) {
            $_SESSION = json_decode($data, true);
        }
    }
}
// -----------------------------------------------

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: /login");
    exit;
}

$timeout = 7200; // 2 jam
if (isset($_SESSION['last_activity'])) {
    if ((time() - $_SESSION['last_activity']) > $timeout) {
        session_unset();
        session_destroy();
        setcookie('user_session', '', time() - 3600, "/");
        header("Location: /login?timeout=1");
        exit;
    }
}
$_SESSION['last_activity'] = time();

// Perbarui cookie agar sesi tetap hidup
$data = json_encode($_SESSION);
$signature = hash_hmac('sha256', $data, $secret);
setcookie('user_session', $data . '|' . $signature, time() + 7200, "/");
?>