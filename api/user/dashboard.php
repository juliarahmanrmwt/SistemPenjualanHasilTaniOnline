<?php
// user/dashboard.php — Dashboard utama pengguna (diperbarui)
require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/koneksi.php';

$namaUser = $_SESSION['nama'];

// Status API BPS
$fileBps  = dirname(__DIR__) . '/ambil_bps.php';
$statusBPS = false;
if (file_exists($fileBps)) {
    include_once $fileBps;
    $statusBPS = function_exists('getDataBPSFull');
}

// Ambil produk aktif dari database (ganti produk hardcoded)
$produk_res  = $koneksi->query("SELECT * FROM produk WHERE status='aktif' ORDER BY dibuat_at DESC LIMIT 8");
$produk_list = $produk_res ? $produk_res->fetch_all(MYSQLI_ASSOC) : [];

// Ringkasan pesanan user
$stmt_psr = $koneksi->prepare("SELECT COUNT(*) c, status FROM pesanan WHERE nama_pembeli=? GROUP BY status");
$stmt_psr->bind_param("s", $namaUser);
$stmt_psr->execute();
$psr_raw  = $stmt_psr->get_result()->fetch_all(MYSQLI_ASSOC);
$psr_stat = [];
foreach ($psr_raw as $r) $psr_stat[$r['status']] = $r['c'];
$psr_aktif   = ($psr_stat['Proses'] ?? 0) + ($psr_stat['Dikemas'] ?? 0) + ($psr_stat['Dikirim'] ?? 0);
$psr_selesai = $psr_stat['Selesai'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Petani GenZ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root { --hijau:#1a6b3c; --hijau-muda:#e8f5ee; --aksen:#f4a322; }
  body { font-family:'DM Sans',sans-serif; background:#fafaf8; }
  .navbar { background:var(--hijau)!important; }
  .navbar-brand { font-family:'Playfair Display',serif; font-size:1.5rem; }
  .bps-indicator { font-size:.75rem;padding:3px 12px;border-radius:50px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3); }

  /* WELCOME BANNER */
  .welcome-banner {
    background: linear-gradient(135deg, var(--hijau) 55%, #2d9e5f);
    color:#fff; border-radius:20px; padding:32px 36px; margin-bottom:28px;
  }
  .welcome-banner h4 { font-family:'Playfair Display',serif; font-size:1.6rem; }

  /* STAT MINI */
  .mini-stat { background:#fff;border-radius:14px;padding:18px;box-shadow:0 2px 10px rgba(0,0,0,.06);text-align:center; }
  .mini-stat .angka { font-size:1.8rem;font-weight:700; }

  /* PRODUK CARD */
  .prod-card { background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.06);transition:.25s;height:100%;display:flex;flex-direction:column; }
  .prod-card:hover { transform:translateY(-5px);box-shadow:0 10px 30px rgba(0,0,0,.12); }
  .prod-card img { width:100%;height:190px;object-fit:cover; }
  .prod-card .badge-kat { background:var(--hijau-muda);color:var(--hijau);font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;padding:3px 10px;border-radius:30px; }
  .prod-card .harga { color:var(--hijau);font-weight:700;font-size:1.1rem; }
  .prod-card .card-body { padding:16px;flex:1;display:flex;flex-direction:column; }
  .btn-pesan { background:var(--hijau);color:#fff;border:none;border-radius:30px;padding:9px 0;font-weight:600;width:100%;margin-top:auto;transition:.2s;text-decoration:none;display:block;text-align:center; }
  .btn-pesan:hover { background:#145a31;color:#fff; }
  .btn-habis { background:#e0e0e0;color:#999;border:none;border-radius:30px;padding:9px 0;font-weight:600;width:100%;margin-top:auto;cursor:not-allowed; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm">
  <div class="container">
    <a class="navbar-brand text-white" href="/user/dashboard">Petani<span class="text-warning">GenZ</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link active" href="/user/dashboard"><i class="bi bi-house me-1"></i>Home</a></li>
        <li class="nav-item"><a class="nav-link" href="/katalog"><i class="bi bi-shop me-1"></i>Katalog</a></li>
        <li class="nav-item"><a class="nav-link" href="/user/transaksi"><i class="bi bi-receipt me-1"></i>Pesanan Saya
          <?php if ($psr_aktif > 0): ?>
            <span class="badge bg-warning text-dark rounded-pill"><?= $psr_aktif ?></span>
          <?php endif; ?>
        </a></li>
        <li class="nav-item"><a class="nav-link" href="/api_bps"><i class="bi bi-bar-chart me-1"></i>Statistik BPS</a></li>
      </ul>
      <div class="ms-auto d-flex align-items-center gap-2">
        <div class="bps-indicator text-white d-none d-md-block">
          <i class="bi bi-broadcast me-1"></i>BPS: <?= $statusBPS ? "<span class='text-warning fw-bold'>Online</span>" : "<span class='opacity-75'>Offline</span>" ?>
        </div>
        <span class="text-white d-none d-lg-inline">Halo, <strong><?= htmlspecialchars($namaUser) ?></strong>!</span>
        <a href="/logout" class="btn btn-outline-light btn-sm rounded-pill">Keluar</a>
      </div>
    </div>
  </div>
</nav>

<div class="container my-4">

  <!-- WELCOME BANNER -->
  <div class="welcome-banner">
    <div class="row align-items-center">
      <div class="col-md-8">
        <h4 class="mb-1">👋 Selamat datang, <?= htmlspecialchars($namaUser) ?>!</h4>
        <p class="opacity-75 mb-3">Temukan produk segar dari petani lokal, langsung ke meja makan Anda.</p>
        <a href="/katalog" class="btn btn-warning fw-bold rounded-pill px-4 me-2">
          <i class="bi bi-shop me-1"></i>Lihat Semua Produk
        </a>
        <a href="/user/transaksi" class="btn btn-outline-light rounded-pill px-4">
          <i class="bi bi-receipt me-1"></i>Pesanan Saya
        </a>
      </div>
      <div class="col-md-4 text-end d-none d-md-block" style="font-size:5rem">🌿</div>
    </div>
  </div>

  <!-- MINI STAT PESANAN -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="angka text-warning"><?= $psr_aktif ?></div>
        <div class="text-muted small">Pesanan Aktif</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="angka text-success"><?= $psr_selesai ?></div>
        <div class="text-muted small">Selesai</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="angka text-success"><?= count($produk_list) ?>+</div>
        <div class="text-muted small">Produk Tersedia</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="angka" style="color:var(--aksen)">🚜</div>
        <div class="text-muted small">Dari Petani Lokal</div>
      </div>
    </div>
  </div>

  <!-- PRODUK UNGGULAN -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">🛒 Produk Unggulan</h5>
    <a href="/katalog" class="btn btn-outline-success btn-sm rounded-pill">Lihat Semua →</a>
  </div>

  <?php if (empty($produk_list)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-basket fs-1 d-block mb-2"></i>
      Belum ada produk. Admin akan segera menambahkan produk.
    </div>
  <?php else: ?>
  <div class="row g-4">
    <?php foreach ($produk_list as $p): ?>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="prod-card">
        <?php if ($p['foto_url']): ?>
          <img src="<?= htmlspecialchars($p['foto_url']) ?>" alt="<?= htmlspecialchars($p['nama']) ?>" loading="lazy">
        <?php else: ?>
          <div style="height:190px;background:var(--hijau-muda);display:flex;align-items:center;justify-content:center;font-size:3rem;">🌿</div>
        <?php endif; ?>
        <div class="card-body">
          <span class="badge-kat"><?= htmlspecialchars($p['kategori']) ?></span>
          <h6 class="fw-bold mt-1 mb-1"><?= htmlspecialchars($p['nama']) ?></h6>
          <p class="text-muted small mb-2" style="line-height:1.3"><?= htmlspecialchars(mb_substr($p['deskripsi'],0,55)) ?>...</p>
          <div class="harga mb-1">Rp <?= number_format($p['harga'],0,',','.') ?> <small class="text-muted fw-normal fs-6">/ <?= htmlspecialchars($p['satuan']) ?></small></div>
          <p class="text-muted small mb-3"><i class="bi bi-box-seam me-1"></i>Stok: <?= (int)$p['stok'] ?> <?= htmlspecialchars($p['satuan']) ?></p>

          <?php if ($p['stok'] <= 0): ?>
            <button class="btn-habis" disabled>Stok Habis</button>
          <?php else: ?>
            <a href="/pesan?id=<?= (int)$p['id'] ?>" class="btn-pesan">
              <i class="bi bi-cart-plus me-1"></i>Pesan Sekarang
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<footer class="text-center py-4 text-muted border-top mt-4">
  <small>&copy; 2026 Petani GenZ - Platform Pertanian Modern</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>