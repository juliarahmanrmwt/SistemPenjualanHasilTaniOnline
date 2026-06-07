<?php
// user/dashboard.php — Dashboard utama pengguna
require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/koneksi.php';

$namaUser = $_SESSION['nama'];

// Status API BPS
$fileBps   = dirname(__DIR__) . '/ambil_bps.php';
$statusBPS = false;
if (file_exists($fileBps)) {
    include_once $fileBps;
    $statusBPS = function_exists('getDataBPSFull');
}

// Ambil produk aktif dari database
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

// Helper: warna & ikon placeholder per kategori
function getKategoriStyle(string $kat): array {
    return match(strtolower(trim($kat))) {
        'sayuran'   => ['bg'=>'#EAF3DE','color'=>'#27500A','icon'=>'ti-leaf',    'badge_bg'=>'#EAF3DE','badge_color'=>'#27500A'],
        'buah'      => ['bg'=>'#FAEEDA','color'=>'#633806','icon'=>'ti-apple',   'badge_bg'=>'#FAEEDA','badge_color'=>'#633806'],
        'rempah'    => ['bg'=>'#FAECE7','color'=>'#712B13','icon'=>'ti-flame',   'badge_bg'=>'#FAECE7','badge_color'=>'#712B13'],
        'beras'     => ['bg'=>'#E6F1FB','color'=>'#0C447C','icon'=>'ti-grain',   'badge_bg'=>'#E6F1FB','badge_color'=>'#0C447C'],
        'umbi'      => ['bg'=>'#FBEAF0','color'=>'#72243E','icon'=>'ti-plant-2', 'badge_bg'=>'#FBEAF0','badge_color'=>'#72243E'],
        default     => ['bg'=>'#F1EFE8','color'=>'#444441','icon'=>'ti-package', 'badge_bg'=>'#F1EFE8','badge_color'=>'#444441'],
    };
}
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
  :root {
    --hijau: #1a6b3c;
    --hijau-muda: #EAF3DE;
    --aksen: #f4a322;
  }
  body { font-family: 'DM Sans', sans-serif; background: #fafaf8; }
  .navbar { background: var(--hijau) !important; }
  .navbar-brand { font-family: 'Playfair Display', serif; font-size: 1.5rem; }
  .bps-indicator {
    font-size: .75rem; padding: 3px 12px;
    border-radius: 50px; background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.3);
  }

  /* WELCOME BANNER */
  .welcome-banner {
    background: var(--hijau);
    color: #fff; border-radius: 20px;
    padding: 32px 36px; margin-bottom: 28px;
  }
  .welcome-banner h4 { font-family: 'Playfair Display', serif; font-size: 1.6rem; }

  /* PRODUK CARD */
  .prod-card {
    background: #fff; border-radius: 16px; overflow: hidden;
    border: 1px solid #eee;
    transition: .25s; height: 100%; display: flex; flex-direction: column;
    position: relative;
  }
  .prod-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,.10); }

  /* GAMBAR & PLACEHOLDER */
  .card-img-top {
    width: 100%; height: 190px; object-fit: cover; display: block;
  }
  .card-img-top.img-habis { filter: grayscale(.5); }
  .card-placeholder {
    width: 100%; height: 190px;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center; gap: 8px;
  }
  .card-placeholder.img-habis { filter: grayscale(.5); }
  .card-placeholder i { font-size: 2.5rem; }
  .card-placeholder span { font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }

  /* BADGE OVERLAY */
  .badge-overlay {
    position: absolute; top: 10px; left: 10px;
    font-size: .68rem; font-weight: 600;
    padding: 3px 10px; border-radius: 20px;
    text-transform: uppercase; letter-spacing: .04em;
    z-index: 1;
  }
  .badge-new   { background: #FAEEDA; color: #633806; }
  .badge-habis { background: rgba(0,0,0,.5); color: #fff; }

  /* BADGE KATEGORI */
  .badge-kat {
    display: inline-block; font-size: .68rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .04em;
    padding: 3px 10px; border-radius: 30px; margin-bottom: 5px;
  }

  /* BODY KARTU */
  .prod-card .card-body { padding: 14px 16px 16px; flex: 1; display: flex; flex-direction: column; }
  .prod-card .prod-name { font-size: .9rem; font-weight: 600; margin: 0 0 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .prod-card .prod-desc { font-size: .78rem; color: #888; margin: 0 0 8px; line-height: 1.4; }
  .prod-card .harga { color: var(--hijau); font-weight: 700; font-size: 1.05rem; margin-bottom: 4px; }
  .prod-card .harga small { font-size: .75rem; font-weight: 400; color: #999; }

  /* STOK INDIKATOR */
  .stok-row { display: flex; align-items: center; gap: 6px; font-size: .78rem; color: #888; margin-bottom: 10px; }
  .stok-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
  .dot-ok   { background: #3B6D11; }
  .dot-low  { background: #BA7517; }
  .dot-out  { background: #A32D2D; }

  /* TOMBOL */
  .btn-pesan {
    background: var(--hijau); color: #fff; border: none;
    border-radius: 30px; padding: 9px 0; font-weight: 600; font-size: .85rem;
    width: 100%; margin-top: auto; transition: .2s;
    text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px;
  }
  .btn-pesan:hover { background: #145a31; color: #fff; }
  .btn-habis {
    background: #e8e8e8; color: #aaa; border: none;
    border-radius: 30px; padding: 9px 0; font-weight: 600; font-size: .85rem;
    width: 100%; margin-top: auto; cursor: not-allowed;
  }
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
        <li class="nav-item">
          <a class="nav-link" href="/user/transaksi">
            <i class="bi bi-receipt me-1"></i>Pesanan Saya
            <?php if ($psr_aktif > 0): ?>
              <span class="badge bg-warning text-dark rounded-pill"><?= $psr_aktif ?></span>
            <?php endif; ?>
          </a>
        </li>
        <li class="nav-item"><a class="nav-link" href="/api_bps"><i class="bi bi-bar-chart me-1"></i>Statistik BPS</a></li>
      </ul>
      <div class="ms-auto d-flex align-items-center gap-2">
        <div class="bps-indicator text-white d-none d-md-block">
          <i class="bi bi-broadcast me-1"></i>BPS:
          <?= $statusBPS
            ? "<span class='text-warning fw-bold'>Online</span>"
            : "<span class='opacity-75'>Offline</span>" ?>
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
    <?php foreach ($produk_list as $p):
      $ks      = getKategoriStyle($p['kategori']);
      $isNew   = strtotime($p['dibuat_at']) > strtotime('-7 days');
      $stokLow = $p['stok'] > 0 && $p['stok'] <= 10;
      $habis   = (int)$p['stok'] <= 0;
    ?>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="prod-card">

        <?php if ($habis): ?>
          <span class="badge-overlay badge-habis">Habis</span>
        <?php elseif ($isNew): ?>
          <span class="badge-overlay badge-new">Baru</span>
        <?php endif; ?>

        <?php if ($p['foto_url']): ?>
          <img
            src="<?= htmlspecialchars($p['foto_url']) ?>"
            alt="<?= htmlspecialchars($p['nama']) ?>"
            class="card-img-top<?= $habis ? ' img-habis' : '' ?>"
            loading="lazy"
            onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
          <div class="card-placeholder<?= $habis ? ' img-habis' : '' ?>"
               style="display:none;background:<?= $ks['bg'] ?>;color:<?= $ks['color'] ?>">
            <i class="ti <?= $ks['icon'] ?>" aria-hidden="true"></i>
            <span><?= htmlspecialchars($p['kategori']) ?></span>
          </div>
        <?php else: ?>
          <div class="card-placeholder<?= $habis ? ' img-habis' : '' ?>"
               style="background:<?= $ks['bg'] ?>;color:<?= $ks['color'] ?>">
            <i class="ti <?= $ks['icon'] ?>" aria-hidden="true"></i>
            <span><?= htmlspecialchars($p['kategori']) ?></span>
          </div>
        <?php endif; ?>

        <div class="card-body">
          <span class="badge-kat" style="background:<?= $ks['badge_bg'] ?>;color:<?= $ks['badge_color'] ?>">
            <?= htmlspecialchars($p['kategori']) ?>
          </span>
          <p class="prod-name"><?= htmlspecialchars($p['nama']) ?></p>
          <p class="prod-desc"><?= htmlspecialchars(mb_substr($p['deskripsi'], 0, 60)) ?>...</p>
          <div class="harga">
            Rp <?= number_format($p['harga'], 0, ',', '.') ?>
            <small>/ <?= htmlspecialchars($p['satuan']) ?></small>
          </div>
          <div class="stok-row">
            <?php if ($habis): ?>
              <span class="stok-dot dot-out"></span> Stok habis
            <?php elseif ($stokLow): ?>
              <span class="stok-dot dot-low"></span> Sisa <?= (int)$p['stok'] ?> <?= htmlspecialchars($p['satuan']) ?> (menipis)
            <?php else: ?>
              <span class="stok-dot dot-ok"></span> Stok: <?= (int)$p['stok'] ?> <?= htmlspecialchars($p['satuan']) ?>
            <?php endif; ?>
          </div>

          <?php if ($habis): ?>
            <button class="btn-habis" disabled>Stok Habis</button>
          <?php else: ?>
            <a href="/pesan?id=<?= (int)$p['id'] ?>" class="btn-pesan">
              <i class="ti ti-shopping-cart" aria-hidden="true"></i> Pesan Sekarang
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