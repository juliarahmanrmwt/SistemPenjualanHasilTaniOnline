<?php
ini_set('session.save_path', '/tmp');
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . '/koneksi.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama_lengkap'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirm  = $_POST['konfirm'] ?? '';

    if (empty($nama) || empty($email) || empty($password)) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif ($password !== $konfirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $cek = $koneksi->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $cek->bind_param("s", $email);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $error = 'Email sudah terdaftar. Gunakan email lain.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user';
            $stmt = $koneksi->prepare("INSERT INTO users (nama_lengkap, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nama, $email, $hash, $role);
            if ($stmt->execute()) {
                $success = 'Akun berhasil dibuat! <a href="login.php" class="alert-link">Masuk sekarang</a>.';
            } else {
                $error = 'Gagal membuat akun: ' . $koneksi->error;
            }
            $stmt->close();
        }
        $cek->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar - Petani GenZ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Poppins', sans-serif; background: #f0f9f0; }
  .card { border-radius: 16px; }
</style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
<div class="container" style="max-width:440px">
  <div class="text-center mb-4">
    <h2 class="fw-bold text-success">Petani<span class="text-dark">GenZ</span></h2>
    <p class="text-muted small">Buat akun baru</p>
  </div>
  <div class="card shadow-sm p-4">
    <?php if ($error): ?>
      <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success small"><?= $success ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="mb-3">
        <label class="form-label fw-semibold">Nama Lengkap</label>
        <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama lengkap kamu" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" name="email" class="form-control" placeholder="email@contoh.com" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Konfirmasi Password</label>
        <input type="password" name="konfirm" class="form-control" placeholder="Ulangi password" required>
      </div>
      <button type="submit" class="btn btn-success w-100 rounded-pill fw-semibold">Daftar</button>
    </form>
    <hr>
    <p class="text-center small mb-0">Sudah punya akun? <a href="login.php" class="text-success fw-semibold">Masuk</a></p>
  </div>
</div>
</body>
</html>