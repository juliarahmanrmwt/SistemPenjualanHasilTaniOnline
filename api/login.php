<?php
ini_set('session.save_path', '/tmp');
if (session_status() === PHP_SESSION_NONE) session_start();

// Cek Cookie Vercel Serverless
$secret = 'petanigenz_rahasia_123';
if (isset($_COOKIE['user_session'])) {
    $parts = explode('|', $_COOKIE['user_session']);
    if (count($parts) === 2) {
        list($data, $signature) = $parts;
        if (hash_hmac('sha256', $data, $secret) === $signature) {
            $_SESSION = json_decode($data, true);
        }
    }
}

// Redirect jika sudah login
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: /admin/dashboard");
    } else {
        header("Location: /user/dashboard");
    }
    exit;
}

require_once __DIR__ . '/koneksi.php'; 

$error   = '';
$timeout = isset($_GET['timeout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $koneksi->prepare("SELECT id, nama_lengkap, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['login']         = true;
                $_SESSION['user_id']       = $user['id'];
                $_SESSION['nama']          = $user['nama_lengkap'];
                $_SESSION['role']          = $user['role'];
                $_SESSION['last_activity'] = time();

                // --- SET COOKIE UNTUK VERCEL ---
                $data = json_encode($_SESSION);
                $signature = hash_hmac('sha256', $data, $secret);
                setcookie('user_session', $data . '|' . $signature, time() + 7200, "/");

                if ($user['role'] === 'admin') {
                    header("Location: /admin/dashboard"); 
                } else {
                    header("Location: /user/dashboard");  
                }
                exit;
            } else {
                $error = 'Password salah.';
            }
        } else {
            $error = 'Email tidak ditemukan.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Petani GenZ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Poppins', sans-serif; background: #f0f9f0; }
  .card { border-radius: 16px; }
</style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
<div class="container" style="max-width:420px">
  <div class="text-center mb-4">
    <h2 class="fw-bold text-success">Petani<span class="text-dark">GenZ</span></h2>
    <p class="text-muted small">Masuk ke akun Anda</p>
  </div>
  <div class="card shadow-sm p-4">
    <?php if ($timeout): ?>
      <div class="alert alert-warning small">Sesi habis. Silakan login ulang.</div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="mb-3">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" name="email" class="form-control" placeholder="email@contoh.com" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
      </div>
      <button type="submit" class="btn btn-success w-100 rounded-pill fw-semibold">Masuk</button>
    </form>
    <hr>
    <p class="text-center small mb-0">Belum punya akun? <a href="/register" class="text-success fw-semibold">Daftar</a></p>
  </div>
</div>
</body>
</html>