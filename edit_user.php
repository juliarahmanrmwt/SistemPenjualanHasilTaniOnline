<?php
require_once __DIR__ . '/auth_check.php';

include 'koneksi.php';
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }

$id = $_GET['id'];
$query = mysqli_query($conn, "SELECT * FROM users WHERE id = '$id'");
$u = mysqli_fetch_assoc($query);

if (!$u) { header("Location: admin_dashboard.php"); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Edit User - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow border-0">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Edit Data User</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="../api/proses_aksi.php" method="POST">
                            <input type="hidden" name="id" value="<?= $u['id']; ?>">
                            <div class="mb-3">
                                 <label class="form-label">Nama Lengkap</label>
                                 <input type="text" name="nama_lengkap" class="form-control" value="<?= $u['nama_lengkap']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= $u['email']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select">
                                    <option value="user" <?= $u['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?= $u['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            <div class="alert alert-info py-2" style="font-size: 0.8rem;">
                                <i class="bi bi-info-circle"></i> Password tidak dapat diubah di sini demi keamanan.
                            </div>
                            <hr>
                            <button type="submit" name="edit_user" class="btn btn-warning w-100">Update Data</button>
                            <a href="admin_dashboard.php" class="btn btn-light w-100 mt-2">Batal</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>