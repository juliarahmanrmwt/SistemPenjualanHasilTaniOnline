<?php
// katalog.php — Halaman katalog publik (tanpa perlu login)
// Letakkan di api/user/, route: /katalog

require_once dirname(__DIR__) . '/koneksi.php';

// Ambil produk aktif dari database
$kategori_filter = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
$search          = isset($_GET['cari'])     ? trim($_GET['cari'])     : '';

$where = "WHERE p.status = 'aktif'";
$params = [];
$types  = '';

if ($kategori_filter !== '') {
    $where   .= " AND p.kategori = ?";
    $params[] = $kategori_filter;
    $types   .= 's';
}
if ($search !== '') {
    $where   .= " AND (p.nama LIKE ? OR p.deskripsi LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types   .= 'ss';
}

$sql = "SELECT * FROM produk p $where ORDER BY p.dibuat_at DESC";
$stmt = $koneksi->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$produk_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Ambil daftar kategori unik
$kat_res   = $koneksi->query("SELECT DISTINCT kategori FROM produk WHERE status='aktif' ORDER BY kategori");
$kategori_list = $kat_res ? $kat_res->fetch_all(MYSQLI_ASSOC) : [];

// Cek status login
ini_set('session.save_path', '/tmp');
if (session_status() === PHP_SESSION_NONE) session_start();

// Baca cookie session Vercel
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
$sudah_login = isset($_SESSION['login']) && $_SESSION['login'] === true;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Katalog Produk - Petani GenZ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --hijau:     #1a6b3c;
    --hijau-muda:#e8f5ee;
    --aksen:     #f4a322;
    --teks:      #1c1c1c;
  }
  * { box-sizing: border-box; }
  body { font-family: 'DM Sans', sans-serif; background: #fafaf8; color: var(--teks); }

  /* NAV */
  .navbar-brand { font-family: 'Playfair Display', serif; font-size: 1.6rem; }
  .navbar { background: var(--hijau) !important; }

  /* HERO STRIP */
  .hero-strip {
    background: linear-gradient(135deg, var(--hijau) 60%, #2d9e5f);
    color: #fff; padding: 56px 0 40px;
  }
  .hero-strip h1 { font-family:'Playfair Display',serif; font-size: clamp(2rem,5vw,3rem); }

  /* FILTER BAR */
  .filter-bar { background:#fff; border-bottom: 1px solid #e5e5e5; position: sticky; top: 56px; z-index: 100; }
  .btn-kat {
    border: 1.5px solid #ccc; background:#fff; border-radius: 30px;
    padding: 5px 18px; font-size:.875rem; transition:.2s;
  }
  .btn-kat.active, .btn-kat:hover { background:var(--hijau); color:#fff; border-color:var(--hijau); }

  /* CARD PRODUK */
  .prod-card {
    background: #fff; border-radius: 16px; overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,.06); transition: transform .25s, box-shadow .25s;
    display: flex; flex-direction: column; height: 100%;
  }
  .prod-card:hover { transform: translateY(-6px); box-shadow: 0 12px 32px rgba(0,0,0,.13); }
  .prod-card img { width:100%; height:200px; object-fit:cover; }
  .prod-card .badge-kat {
    display: inline-block; background: var(--hijau-muda); color: var(--hijau);
    font-size:.72rem; font-weight:600; letter-spacing:.04em; text-transform:uppercase;
    padding:3px 12px; border-radius:30px; margin-bottom:6px;
  }
  .prod-card .harga { font-size:1.2rem; font-weight:700; color: var(--hijau); }
  .prod-card .stok  { font-size:.8rem; color:#888; }
  .prod-card .card-body { padding:18px; flex:1; display:flex; flex-direction:column; }
  .btn-pesan {
    background: var(--hijau); color:#fff; border:none; border-radius:30px;
    padding:9px 0; font-weight:600; width:100%; margin-top:auto; transition:.2s;
  }
  .btn-pesan:hover { background: #145a31; color:#fff; }
  .btn-pesan-login {
    background: var(--aksen); color:#fff; border:none; border-radius:30px;
    padding:9px 0; font-weight:600; width:100%; margin-top:auto; transition:.2s;
  }

  /* KOSONG */
  .kosong { text-align:center; padding: 80px 0; color:#aaa; }
  .kosong i { font-size: 4rem; display:block; margin-bottom:16px; }
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm">
  <div class="container">
    <a class="navbar-brand text-white" href="/">Petani<span class="text-warning">GenZ</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="/">Beranda</a></li>
        <li class="nav-item"><a class="nav-link active" href="/katalog">Katalog</a></li>
        <?php if ($sudah_login): ?>
        <li class="nav-item"><a class="nav-link" href="/user/transaksi">Pesanan Saya</a></li>
        <?php endif; ?>
      </ul>
      <div class="d-flex gap-2">
        <?php if ($sudah_login): ?>
          <a href="/user/dashboard" class="btn btn-outline-light btn-sm rounded-pill">Dashboard</a>
          <a href="/logout" class="btn btn-warning btn-sm rounded-pill fw-bold">Keluar</a>
        <?php else: ?>
          <a href="/login"    class="btn btn-outline-light btn-sm rounded-pill">Masuk</a>
          <a href="/register" class="btn btn-warning btn-sm rounded-pill fw-bold">Daftar</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- HERO STRIP -->
<div class="hero-strip">
  <div class="container">
    <h1 class="mb-2">🌿 Katalog Produk Segar</h1>
    <p class="opacity-75 mb-4">Temukan produk pertanian berkualitas langsung dari petani lokal.</p>

    <!-- Search -->
    <form method="GET" action="/katalog" class="d-flex gap-2" style="max-width:500px;">
      <?php if ($kategori_filter): ?>
        <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori_filter) ?>">
      <?php endif; ?>
      <input type="text" name="cari" value="<?= htmlspecialchars($search) ?>"
             class="form-control rounded-pill border-0 shadow-sm"
             placeholder="Cari produk... (contoh: tomat, cabai)">
      <button class="btn btn-warning rounded-pill fw-bold px-4">Cari</button>
    </form>
  </div>
</div>

<!-- FILTER KATEGORI -->
<div class="filter-bar py-2">
  <div class="container d-flex gap-2 flex-wrap">
    <a href="/katalog<?= $search ? '?cari='.urlencode($search) : '' ?>"
       class="btn-kat <?= $kategori_filter==='' ? 'active' : '' ?>">Semua</a>
    <?php foreach ($kategori_list as $k): ?>
      <a href="/katalog?kategori=<?= urlencode($k['kategori']) ?><?= $search ? '&cari='.urlencode($search) : '' ?>"
         class="btn-kat <?= $kategori_filter===$k['kategori'] ? 'active' : '' ?>">
        <?= htmlspecialchars($k['kategori']) ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- DAFTAR PRODUK -->
<div class="container py-5">
  <?php if (empty($produk_list)): ?>
    <div class="kosong">
      <i class="bi bi-basket"></i>
      <p class="fw-semibold fs-5">Produk tidak ditemukan</p>
      <a href="/katalog" class="btn btn-outline-success rounded-pill">Lihat Semua Produk</a>
    </div>
  <?php else: ?>
    <p class="text-muted mb-4">Menampilkan <strong><?= count($produk_list) ?></strong> produk<?= $kategori_filter ? ' — Kategori: <strong>'.htmlspecialchars($kategori_filter).'</strong>' : '' ?><?= $search ? ' — Pencarian: <strong>'.htmlspecialchars($search).'</strong>' : '' ?></p>
    <div class="row g-4">
      <?php foreach ($produk_list as $p): ?>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="prod-card">
          <?php if ($p['foto_url']): ?>
            <img src="<?= htmlspecialchars($p['foto_url']) ?>" alt="<?= htmlspecialchars($p['nama']) ?>" loading="lazy">
          <?php else: ?>
            <div style="height:200px;background:#e8f5ee;display:flex;align-items:center;justify-content:center;font-size:3rem;">🌿</div>
          <?php endif; ?>

          <div class="card-body">
            <span class="badge-kat"><?= htmlspecialchars($p['kategori']) ?></span>
            <h5 class="fw-bold mb-1"><?= htmlspecialchars($p['nama']) ?></h5>
            <p class="text-muted small mb-2" style="line-height:1.4"><?= htmlspecialchars(mb_substr($p['deskripsi'],0,70)) ?>...</p>
            <div class="harga mb-1">Rp <?= number_format($p['harga'],0,',','.') ?> <small class="text-muted fw-normal fs-6">/ <?= htmlspecialchars($p['satuan']) ?></small></div>
            <p class="stok mb-3"><i class="bi bi-box-seam me-1"></i>Stok: <?= (int)$p['stok'] ?> <?= htmlspecialchars($p['satuan']) ?></p>

            <?php if ($p['stok'] <= 0): ?>
              <button class="btn-pesan" disabled style="background:#ccc;cursor:not-allowed;">Stok Habis</button>
            <?php elseif ($sudah_login): ?>
              <a href="/pesan?id=<?= (int)$p['id'] ?>" class="btn-pesan text-center text-decoration-none d-block">
                <i class="bi bi-cart-plus me-1"></i> Pesan Sekarang
              </a>
            <?php else: ?>
              <a href="/login?redirect=/katalog" class="btn-pesan-login text-center text-decoration-none d-block">
                <i class="bi bi-person-lock me-1"></i> Login untuk Memesan
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<footer class="text-center py-4 text-muted border-top">
  <small>&copy; 2026 Petani GenZ &mdash; Produk Segar dari Petani Lokal</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>