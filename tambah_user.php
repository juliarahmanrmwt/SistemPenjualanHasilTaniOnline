<?php
require_once __DIR__ . '/auth_check.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Tambah User - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow border-0">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Tambah User Baru</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="../api/proses_aksi.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" class="form-control" placeholder="Masukkan nama" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="email@contoh.com" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role / Hak Akses</label>
                                <select name="role" class="form-select">
                                    <option value="user">User / Pelanggan</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <hr>
                            <button type="submit" name="tambah_user" class="btn btn-success w-100">Simpan User</button>
                            <a href="admin_dashboard.php" class="btn btn-light w-100 mt-2">Kembali</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>