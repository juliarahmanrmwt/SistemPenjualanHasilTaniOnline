<?php
require_once __DIR__ . '/auth_check.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

// Pastikan menggunakan variabel koneksi yang benar dari file koneksi.php
$db = isset($koneksi) ? $koneksi : $conn;

$resUser = mysqli_query($db, "SELECT * FROM users");
$totalUser = ($resUser) ? mysqli_num_rows($resUser) : 0;

$resSelesai = mysqli_query($db, "SELECT * FROM pesanan WHERE status='Selesai'");
$pesananSelesai = ($resSelesai) ? mysqli_num_rows($resSelesai) : 0;

$resPending = mysqli_query($db, "SELECT * FROM pesanan WHERE status='Proses'");
$pesananPending = ($resPending) ? mysqli_num_rows($resPending) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Petani GenZ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --primary-green: #198754; --dark-green: #146c43; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .sidebar { min-height: 100vh; background: #212529; color: white; position: fixed; z-index: 100; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); border-radius: 5px; margin: 5px 10px; transition: 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: var(--primary-green); color: white; }
        .main-content { margin-left: 16.666667%; padding: 30px; }
        .card-stats { border: none; border-radius: 15px; transition: transform 0.3s; }
        .card-stats:hover { transform: translateY(-5px); }
        .table-container { background: white; border-radius: 15px; padding: 25px; margin-bottom: 30px; }
        @media (max-width: 768px) { .sidebar { position: relative; min-height: auto; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar shadow">
            <div class="pt-4 text-center mb-4">
                <h4 class="fw-bold text-success"><i class="bi bi-leaf"></i> Petani GenZ</h4>
                <small class="text-muted">Administrator</small>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link active" href="admin_dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="#kelola-user"><i class="bi bi-people me-2"></i> Kelola User</a></li>
                <li class="nav-item"><a class="nav-link" href="#kelola-pesanan"><i class="bi bi-cart-check me-2"></i> Kelola Pesanan</a></li>
                <hr class="mx-3 text-secondary">
                <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Keluar</a></li>
            </ul>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <?php if(isset($_GET['status'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php 
                        $msg = [
                            'sukses_tambah' => "User baru berhasil ditambahkan!",
                            'sukses_edit' => "Data user berhasil diperbarui!",
                            'sukses_hapus' => "User telah berhasil dihapus!",
                            'pesanan_update' => "Status pesanan telah diselesaikan!",
                            'pesanan_hapus' => "Riwayat pesanan berhasil dihapus!"
                        ];
                        echo $msg[$_GET['status']] ?? "Aksi Berhasil!";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom">
                <h1 class="h2">Ringkasan Dashboard</h1>
                <span class="text-muted"><?= date('l, d F Y'); ?></span>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="card card-stats bg-primary text-white p-3 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><p class="mb-0 text-white-50">Total User</p><h3 class="fw-bold"><?= $totalUser; ?></h3></div>
                            <i class="bi bi-people fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-stats bg-success text-white p-3 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><p class="mb-0 text-white-50">Pesanan Selesai</p><h3 class="fw-bold"><?= $pesananSelesai; ?></h3></div>
                            <i class="bi bi-bag-check fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-stats bg-warning text-dark p-3 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><p class="mb-0 text-black-50">Pesanan Pending</p><h3 class="fw-bold"><?= $pesananPending; ?></h3></div>
                            <i class="bi bi-clock-history fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-container shadow-sm" id="kelola-user">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0 text-success"><i class="bi bi-people-fill me-2"></i> Kelola User</h5>
                    <a href="tambah_user.php" class="btn btn-success btn-sm px-3 rounded-pill"><i class="bi bi-plus-lg me-1"></i> Tambah User</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $queryUser = mysqli_query($db, "SELECT * FROM users ORDER BY id DESC");
                            while ($u = mysqli_fetch_assoc($queryUser)) :
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($u['nama_lengkap']); ?></td>
                                <td><?= htmlspecialchars($u['email']); ?></td>
                                <td><span class="badge bg-info text-dark"><?= ucfirst($u['role']); ?></span></td>
                                <td class="text-center">
                                    <a href="edit_user.php?id=<?= $u['id']; ?>" class="btn btn-outline-warning btn-sm me-1"><i class="bi bi-pencil-square"></i></a>
                                    <a href="proses_aksi.php?hapus_user=<?= $u['id']; ?>" onclick="return confirm('Hapus user ini?')" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="table-container shadow-sm" id="kelola-pesanan">
                <h5 class="fw-bold mb-4 text-success"><i class="bi bi-cart-fill me-2"></i> Kelola Pesanan</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID Order</th>
                                <th>Pelanggan</th>
                                <th>Produk</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $queryPesanan = mysqli_query($db, "SELECT * FROM pesanan ORDER BY id_pesanan DESC");
                            if (!$queryPesanan) {
                                echo "<tr><td colspan='5' class='text-center text-danger'>Error: " . mysqli_error($db) . "</td></tr>";
                            } else {
                                while ($p = mysqli_fetch_assoc($queryPesanan)) :
                            ?>
                            <tr>
                                <td class="fw-bold text-primary">#ORD-<?= $p['id_pesanan']; ?></td>
                                <td><?= htmlspecialchars($p['nama_pembeli'] ?? 'Pelanggan'); ?></td>
                                <td><?= htmlspecialchars($p['nama_produk']); ?></td>
                                <td>
                                    <span class="badge <?= ($p['status'] == 'Proses') ? 'bg-warning text-dark' : 'bg-success' ?>">
                                        <i class="bi <?= ($p['status'] == 'Proses') ? 'bi-hourglass-split' : 'bi-check-all' ?> me-1"></i> <?= $p['status']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if($p['status'] == 'Proses') : ?>
                                        <a href="proses_aksi.php?selesai_pesanan=<?= $p['id_pesanan']; ?>" class="btn btn-success btn-sm me-1">Selesaikan</a>
                                    <?php endif; ?>
                                    <a href="proses_aksi.php?hapus_pesanan=<?= $p['id_pesanan']; ?>" onclick="return confirm('Hapus riwayat?')" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>