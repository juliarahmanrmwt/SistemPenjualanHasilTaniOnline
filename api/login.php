<?php
ini_set('session.save_path', '/tmp');
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: dashboard.php");
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

                // Arahkan berdasarkan role
                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: dashboard.php");
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
    <p class="text-center small mb-0">Belum punya akun? <a href="register.php" class="text-success fw-semibold">Daftar</a></p>
  </div>
</div>
</body>
</html>