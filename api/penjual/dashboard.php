<?php
// api/penjual/dashboard.php
require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/koneksi.php';

// Hanya penjual yang boleh akses
if ($_SESSION['role'] !== 'penjual') {
    header("Location: /user/dashboard");
    exit;
}

$id_penjual = $_SESSION['user_id'];
$namaPenjual = $_SESSION['nama'];

// Produk milik penjual ini
$produk_res  = $koneksi->prepare("SELECT * FROM produk WHERE id_penjual = ? ORDER BY dibuat_at DESC");
$produk_res->bind_param("i", $id_penjual);
$produk_res->execute();
$produk_list = $produk_res->get_result()->fetch_all(MYSQLI_ASSOC);

$total_produk = count($produk_list);
$produk_aktif = array_filter($produk_list, fn($p) => $p['status'] === 'aktif');

// Pesanan untuk produk milik penjual ini
$pesanan_res = $koneksi->prepare("
    SELECT p.*, pr.nama AS nama_produk_asli 
    FROM pesanan p
    JOIN produk pr ON p.id_produk = pr.id
    WHERE pr.id_penjual = ?
    ORDER BY p.dibuat_at DESC
    LIMIT 10
");
$pesanan_res->bind_param("i", $id_penjual);
$pesanan_res->execute();
$pesanan_list = $pesanan_res->get_result()->fetch_all(MYSQLI_ASSOC);

// Hitung stat pesanan
$stmt_stat = $koneksi->prepare("
    SELECT p.status, COUNT(*) c FROM pesanan p
    JOIN produk pr ON p.id_produk = pr.id
    WHERE pr.id_penjual = ?
    GROUP BY p.status
");
$stmt_stat->bind_param("i", $id_penjual);
$stmt_stat->execute();
$stat_raw = $stmt_stat->get_result()->fetch_all(MYSQLI_ASSOC);
$stat = [];
foreach ($stat_raw as $s) $stat[$s['status']] = $s['c'];
$pesanan_aktif   = ($stat['Proses'] ?? 0) + ($stat['Dikemas'] ?? 0) + ($stat['Dikirim'] ?? 0);
$pesanan_selesai = $stat['Selesai'] ?? 0;

// Total pendapatan
$stmt_income = $koneksi->prepare("
    SELECT SUM(p.total_harga) total FROM pesanan p
    JOIN produk pr ON p.id_produk = pr.id
    WHERE pr.id_penjual = ? AND p.status = 'Selesai'
");
$stmt_income->bind_param("i", $id_penjual);
$stmt_income->execute();
$total_pendapatan = $stmt_income->get_result()->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Penjual - Petani GenZ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root { --hijau:#1a6b3c; --hijau-muda:#e8f5ee; --aksen:#f4a322; --merah:#e53935; }
  body { font-family:'DM Sans',sans-serif; background:#fafaf8; }
  .navbar { background:var(--hijau)!important; }
  .navbar-brand { font-family:'Playfair Display',serif; font-size:1.5rem; }

  /* WELCOME BANNER */
  .welcome-banner {
    background:linear-gradient(135deg,var(--hijau) 55%,#2d9e5f);
    color:#fff; border-radius:20px; padding:32px 36px; margin-bottom:28px;
  }
  .welcome-banner h4 { font-family:'Playfair Display',serif; font-size:1.6rem; }
  .role-badge { background:var(--aksen); color:#fff; font-size:.75rem; font-weight:700; padding:3px 12px; border-radius:30px; letter-spacing:.04em; }

  /* STAT CARD */
  .stat-card { background:#fff; border-radius:16px; padding:20px 24px; box-shadow:0 2px 12px rgba(0,0,0,.06); display:flex; align-items:center; gap:16px; }
  .stat-icon { width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; flex-shrink:0; }
  .stat-card .angka { font-size:1.7rem; font-weight:700; line-height:1; }
  .stat-card .label { font-size:.82rem; color:#888; margin-top:3px; }

  /* PRODUK CARD */
  .prod-card { background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.06); transition:.25s; height:100%; display:flex; flex-direction:column; }
  .prod-card:hover { transform:translateY(-4px); box-shadow:0 10px 28px rgba(0,0,0,.11); }
  .prod-card img { width:100%; height:170px; object-fit:cover; }
  .prod-card .badge-kat { background:var(--hijau-muda); color:var(--hijau); font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em; padding:3px 10px; border-radius:30px; }
  .prod-card .harga { color:var(--hijau); font-weight:700; font-size:1rem; }
  .prod-card .card-body { padding:14px; flex:1; display:flex; flex-direction:column; }
  .badge-status-aktif { background:#e8f5ee; color:var(--hijau); font-size:.7rem; padding:3px 10px; border-radius:30px; font-weight:600; }
  .badge-status-nonaktif { background:#f5f5f5; color:#999; font-size:.7rem; padding:3px 10px; border-radius:30px; font-weight:600; }

  /* ACTION BUTTONS */
  .btn-edit { background:var(--hijau-muda); color:var(--hijau); border:none; border-radius:20px; padding:6px 14px; font-size:.8rem; font-weight:600; text-decoration:none; transition:.2s; }
  .btn-edit:hover { background:var(--hijau); color:#fff; }
  .btn-hapus { background:#ffeaea; color:var(--merah); border:none; border-radius:20px; padding:6px 14px; font-size:.8rem; font-weight:600; text-decoration:none; transition:.2s; }
  .btn-hapus:hover { background:var(--merah); color:#fff; }
  .btn-tambah { background:var(--hijau); color:#fff; border:none; border-radius:30px; padding:9px 22px; font-weight:600; text-decoration:none; transition:.2s; }
  .btn-tambah:hover { background:#145a31; color:#fff; }

  /* PESANAN TABLE */
  .tbl-pesanan { background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.06); }
  .tbl-pesanan thead { background:var(--hijau); color:#fff; }
  .tbl-pesanan th { font-weight:600; padding:12px 16px; font-size:.85rem; }
  .tbl-pesanan td { padding:12px 16px; font-size:.88rem; vertical-align:middle; }
  .badge-proses   { background:#fff3cd; color:#856404; padding:4px 12px; border-radius:20px; font-size:.75rem; font-weight:600; }
  .badge-dikemas  { background:#cce5ff; color:#004085; padding:4px 12px; border-radius:20px; font-size:.75rem; font-weight:600; }
  .badge-dikirim  { background:#d4edda; color:#155724; padding:4px 12px; border-radius:20px; font-size:.75rem; font-weight:600; }
  .badge-selesai  { background:#e8f5ee; color:var(--hijau); padding:4px 12px; border-radius:20px; font-size:.75rem; font-weight:600; }

  .section-title { font-family:'Playfair Display',serif; font-size:1.25rem; font-weight:700; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm">
  <div class="container">
    <a class="navbar-brand text-white" href="/penjual/dashboard">Petani<span class="text-warning">GenZ</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link active" href="/penjual/dashboard"><i class="bi bi-house me-1"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="/penjual/produk_tambah"><i class="bi bi-plus-circle me-1"></i>Tambah Produk</a></li>
        <li class="nav-item"><a class="nav-link" href="/penjual/pesanan"><i class="bi bi-receipt me-1"></i>Semua Pesanan
          <?php if ($pesanan_aktif > 0): ?>
            <span class="badge bg-warning text-dark rounded-pill"><?= $pesanan_aktif ?></span>
          <?php endif; ?>
        </a></li>
      </ul>
      <div class="ms-auto d-flex align-items-center gap-2">
        <span class="role-badge">PENJUAL</span>
        <span class="text-white d-none d-lg-inline">Halo, <strong><?= htmlspecialchars($namaPenjual) ?></strong>!</span>
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
        <div class="d-flex align-items-center gap-2 mb-2">
          <h4 class="mb-0">👋 Selamat datang, <?= htmlspecialchars($namaPenjual) ?>!</h4>
          <span class="role-badge">Penjual</span>
        </div>
        <p class="opacity-75 mb-3">Kelola produk dan pantau pesanan dari pembeli di sini.</p>
        <a href="/penjual/produk_tambah" class="btn btn-warning fw-bold rounded-pill px-4 me-2">
          <i class="bi bi-plus-circle me-1"></i>Tambah Produk
        </a>
        <a href="/penjual/pesanan" class="btn btn-outline-light rounded-pill px-4">
          <i class="bi bi-receipt me-1"></i>Lihat Pesanan
        </a>
      </div>
      <div class="col-md-4 text-end d-none d-md-block" style="font-size:5rem">🌾</div>
    </div>
  </div>

  <!-- STAT CARDS -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5ee;">📦</div>
        <div>
          <div class="angka text-success"><?= $total_produk ?></div>
          <div class="label">Total Produk</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="stat-icon" style="background:#fff3cd;">🛒</div>
        <div>
          <div class="angka text-warning"><?= $pesanan_aktif ?></div>
          <div class="label">Pesanan Aktif</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5ee;">✅</div>
        <div>
          <div class="angka text-success"><?= $pesanan_selesai ?></div>
          <div class="label">Pesanan Selesai</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="stat-icon" style="background:#fff3cd;">💰</div>
        <div>
          <div class="angka" style="color:var(--aksen);font-size:1.2rem;">Rp <?= number_format($total_pendapatan,0,',','.') ?></div>
          <div class="label">Total Pendapatan</div>
        </div>
      </div>
    </div>
  </div>

  <!-- PRODUK SAYA -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="section-title mb-0">📦 Produk Saya</h5>
    <a href="/penjual/produk_tambah" class="btn-tambah"><i class="bi bi-plus-lg me-1"></i>Tambah Produk</a>
  </div>

  <?php if (empty($produk_list)): ?>
    <div class="text-center py-5 text-muted bg-white rounded-4 shadow-sm mb-4">
      <i class="bi bi-box-seam fs-1 d-block mb-2"></i>
      Belum ada produk. Mulai tambahkan produk pertamamu!
    </div>
  <?php else: ?>
  <div class="row g-3 mb-4">
    <?php foreach ($produk_list as $p): ?>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="prod-card">
        <?php if ($p['foto_url']): ?>
          <img src="<?= htmlspecialchars($p['foto_url']) ?>" alt="<?= htmlspecialchars($p['nama']) ?>" loading="lazy">
        <?php else: ?>
          <div style="height:170px;background:var(--hijau-muda);display:flex;align-items:center;justify-content:center;font-size:3rem;">🌿</div>
        <?php endif; ?>
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-1">
            <span class="badge-kat"><?= htmlspecialchars($p['kategori']) ?></span>
            <?php if ($p['status'] === 'aktif'): ?>
              <span class="badge-status-aktif">Aktif</span>
            <?php else: ?>
              <span class="badge-status-nonaktif">Nonaktif</span>
            <?php endif; ?>
          </div>
          <h6 class="fw-bold mt-1 mb-1"><?= htmlspecialchars($p['nama']) ?></h6>
          <div class="harga mb-1">Rp <?= number_format($p['harga'],0,',','.') ?> <small class="text-muted fw-normal" style="font-size:.8rem">/ <?= htmlspecialchars($p['satuan']) ?></small></div>
          <p class="text-muted mb-3" style="font-size:.8rem"><i class="bi bi-box-seam me-1"></i>Stok: <?= (int)$p['stok'] ?></p>
          <div class="d-flex gap-2 mt-auto">
            <a href="/penjual/produk_edit?id=<?= (int)$p['id'] ?>" class="btn-edit flex-fill text-center"><i class="bi bi-pencil me-1"></i>Edit</a>
            <a href="/penjual/produk_hapus?id=<?= (int)$p['id'] ?>" class="btn-hapus flex-fill text-center"
               onclick="return confirm('Hapus produk ini?')"><i class="bi bi-trash me-1"></i>Hapus</a>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- PESANAN TERBARU -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="section-title mb-0">🛒 Pesanan Terbaru</h5>
    <a href="/penjual/pesanan" class="btn btn-outline-success btn-sm rounded-pill">Lihat Semua →</a>
  </div>

  <?php if (empty($pesanan_list)): ?>
    <div class="text-center py-4 text-muted bg-white rounded-4 shadow-sm">
      <i class="bi bi-inbox fs-1 d-block mb-2"></i>
      Belum ada pesanan masuk.
    </div>
  <?php else: ?>
  <div class="tbl-pesanan">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>ID Pesanan</th>
          <th>Pembeli</th>
          <th>Produk</th>
          <th class="text-center">Jumlah</th>
          <th class="text-end">Total</th>
          <th class="text-center">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pesanan_list as $ps): ?>
        <tr>
          <td><code class="text-success"><?= htmlspecialchars($ps['id_pesanan']) ?></code></td>
          <td><?= htmlspecialchars($ps['nama_pembeli']) ?></td>
          <td><?= htmlspecialchars($ps['nama_produk']) ?></td>
          <td class="text-center"><?= (int)$ps['jumlah'] ?></td>
          <td class="text-end fw-semibold">Rp <?= number_format($ps['total_harga'],0,',','.') ?></td>
          <td class="text-center">
            <?php
              $cls = match($ps['status']) {
                'Proses'  => 'badge-proses',
                'Dikemas' => 'badge-dikemas',
                'Dikirim' => 'badge-dikirim',
                'Selesai' => 'badge-selesai',
                default   => ''
              };
            ?>
            <span class="<?= $cls ?>"><?= htmlspecialchars($ps['status']) ?></span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

</div>

<footer class="text-center py-4 text-muted border-top mt-4">
  <small>&copy; 2026 Petani GenZ - Platform Pertanian Modern</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>