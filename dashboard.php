<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php';

$namaUser = $_SESSION['nama'];

// Periksa apakah file BPS ada sebelum dipanggil
$fileBps = __DIR__ . '/ambil_bps.php';
if (file_exists($fileBps)) {
    include_once $fileBps;
    // Cek apakah fungsinya ada
    $statusBPS = function_exists('getStatistikBPS') ? getStatistikBPS('54') : false;
} else {
    $statusBPS = false;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog - Petani GenZ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .product-card:hover { transform: translateY(-5px); transition: 0.3s; }
        .bps-indicator { 
            font-size: 0.75rem; padding: 2px 10px; border-radius: 50px; 
            background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); 
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">Petani GenZ</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Home</a></li>
                    <li class="nav-item">
                        <a class="nav-link" href="api_bps.php"><i class="bi bi-table"></i> Statistik BPS</a>
                    </li>
                </ul>
                
                <div class="ms-auto d-flex align-items-center">
                    <div class="bps-indicator text-white me-3 d-none d-md-block">
                        <i class="bi bi-broadcast"></i> API BPS: 
                        <?= $statusBPS ? "<span class='text-warning fw-bold'>Online</span>" : "<span class='text-light opacity-75'>Offline</span>"; ?>
                    </div>
                    <span class="text-white me-3 d-none d-lg-inline">Halo, <strong><?= htmlspecialchars($namaUser); ?></strong>!</span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill">Keluar</a>
                </div>
            </div>
        </div>
    </nav>

    <header class="bg-success-subtle py-5 text-center">
        <div class="container">
            <h1 class="display-5 fw-bold text-success">Pasar Tani Digital</h1>
            <p class="lead">Produk segar langsung dari tangan petani lokal untuk <strong><?= htmlspecialchars($namaUser); ?></strong>.</p>
        </div>
    </header>
        
    <main class="container my-5">
        <div class="row g-4">
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm product-card">
                    <img src="https://sukamenak-cikeusal.desa.id/wp-content/uploads/2023/08/62bb1bb0b7595.jpg" class="card-img-top" alt="Cabai" style="height: 200px; object-fit: cover;">
                    <div class="card-body d-flex flex-column">
                        <small class="text-success fw-bold text-uppercase">Bumbu</small>
                        <h5 class="card-title">Cabai Merah Segar</h5>
                        <p class="card-text text-danger fw-bold">Rp 18.000</p>
                        <form action="beli.php" method="POST" class="mt-auto">
                            <input type="hidden" name="nama_produk" value="Cabai Merah Segar">
                            <input type="hidden" name="harga" value="18000">
                            <button type="submit" name="proses_beli" class="btn btn-success w-100 rounded-pill">Beli Sekarang</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm product-card">
                    <img src="https://images.unsplash.com/photo-1592924357228-91a4daadcfea?auto=format&fit=crop&w=500&q=80" class="card-img-top" alt="Tomat" style="height: 200px; object-fit: cover;">
                    <div class="card-body d-flex flex-column">
                        <small class="text-success fw-bold text-uppercase">Sayuran</small>
                        <h5 class="card-title">Tomat Merah</h5>
                        <p class="card-text text-danger fw-bold">Rp 18.000</p>
                        <form action="beli.php" method="POST" class="mt-auto">
                            <input type="hidden" name="nama_produk" value="Tomat Merah">
                            <input type="hidden" name="harga" value="18000">
                            <button type="submit" name="proses_beli" class="btn btn-success w-100 rounded-pill">Beli Sekarang</button>
                        </form>
                    </div>
                </div>
            </div>
            </div> 
    </main>

    <footer class="text-center py-4 text-muted border-top">
        <small>&copy; 2026 Petani GenZ - Dashboard Pengguna</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>