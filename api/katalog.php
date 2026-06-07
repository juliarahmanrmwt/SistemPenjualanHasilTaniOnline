<?php
// katalog.php — Halaman katalog publik (tanpa perlu login)
// Route: /katalog

require_once __DIR__ . '/koneksi.php';

// Filter & pencarian
$kategori_filter = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
$search          = isset($_GET['cari'])     ? trim($_GET['cari'])     : '';

$where  = "WHERE p.status = 'aktif'";
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

$sql  = "SELECT * FROM produk p $where ORDER BY p.dibuat_at DESC";
$stmt = $koneksi->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$produk_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Kategori unik untuk filter pill
$kat_res       = $koneksi->query("SELECT DISTINCT kategori FROM produk WHERE status='aktif' ORDER BY kategori");
$kategori_list = $kat_res ? $kat_res->fetch_all(MYSQLI_ASSOC) : [];

// Cek status login
ini_set('session.save_path', '/tmp');
if (session_status() === PHP_SESSION_NONE) session_start();

$secret = 'petanigenz_rahasia_123';
if (isset($_COOKIE['user_session'])) {
    $parts = explode('|', $_COOKIE['user_session']);
    if (count($parts) === 2) {
        [$data, $signature] = $parts;
        if (hash_hmac('sha256', $data, $secret) === $signature) {
            $_SESSION = json_decode($data, true);
        }
    }
}
$sudah_login = isset($_SESSION['login']) && $_SESSION['login'] === true;

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
<title>Katalog Produk - Petani GenZ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
  :root {
    --hijau:      #1a6b3c;
    --hijau-muda: #EAF3DE;
    --aksen:      #f4a322;
    --teks:       #1c1c1c;
  }
  * { box-sizing: border-box; }
  body { font-family: 'DM Sans', sans-serif; background: #fafaf8; color: var(--teks); }

  /* NAV */
  .navbar-brand { font-family: 'Playfair Display', serif; font-size: 1.6rem; }
  .navbar { background: var(--hijau) !important; }

  /* HERO STRIP */
  .hero-strip {
    background: var(--hijau);
    color: #fff; padding: 48px 0 36px;
  }
  .hero-strip h1 { font-family: 'Playfair Display', serif; font-size: clamp(1.8rem, 4vw, 2.6rem); }

  /* FILTER BAR */
  .filter-bar {
    background: #fff; border-bottom: 1px solid #e5e5e5;
    position: sticky; top: 56px; z-index: 100;
  }
  .btn-kat {
    border: 1px solid #ccc; background: #fff; border-radius: 30px;
    padding: 5px 16px; font-size: .85rem; transition: .2s; cursor: pointer;
    text-decoration: none; color: var(--teks);
  }
  .btn-kat.active, .btn-kat:hover {
    background: var(--hijau); color: #fff; border-color: var(--hijau);
  }

  /* PRODUK CARD */
  .prod-card {
    background: #fff; border-radius: 16px; overflow: hidden;
    border: 1px solid #eee;
    transition: transform .25s, box-shadow .25s;
    display: flex; flex-direction: column; height: 100%;
    position: relative;
  }
  .prod-card:hover { transform: translateY(-6px); box-shadow: 0 12px 32px rgba(0,0,0,.10); }

  /* GAMBAR & PLACEHOLDER */
  .card-img-top {
    width: 100%; height: 200px; object-fit: cover; display: block;
  }
  .card-img-top.img-habis { filter: grayscale(.5); }
  .card-placeholder {
    width: 100%; height: 200px;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center; gap: 8px;
  }
  .card-placeholder.img-habis { filter: grayscale(.5); }
  .card-placeholder i { font-size: 2.8rem; }
  .card-placeholder span { font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }

  /* BADGE OVERLAY */
  .badge-overlay {
    position: absolute; top: 10px; left: 10px;
    font-size: .68rem; font-weight: 600;
    padding: 3px 10px; border-radius: 20px;
    text-transform: uppercase; letter-spacing: .04em; z-index: 1;
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
  .prod-card .prod-name { font-size: .92rem; font-weight: 600; margin: 0 0 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .prod-card .prod-desc { font-size: .78rem; color: #888; margin: 0 0 8px; line-height: 1.4; }
  .prod-card .harga { color: var(--hijau); font-weight: 700; font-size: 1.1rem; margin-bottom: 4px; }
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
  .btn-login {
    background: var(--aksen); color: #412402; border: none;
    border-radius: 30px; padding: 9px 0; font-weight: 600; font-size: .85rem;
    width: 100%; margin-top: auto; transition: .2s;
    text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px;
  }
  .btn-login:hover { background: #d48f1a; color: #412402; }
  .btn-habis {
    background: #e8e8e8; color: #aaa; border: none;
    border-radius: 30px; padding: 9px 0; font-weight: 600; font-size: .85rem;
    width: 100%; margin-top: auto; cursor: not-allowed;
  }

  /* KOSONG */
  .kosong { text-align: center; padding: 80px 0; color: #aaa; }
  .kosong i { font-size: 4rem; display: block; margin-bottom: 16px; }
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
    <p class="text-muted mb-4">
      Menampilkan <strong><?= count($produk_list) ?></strong> produk
      <?= $kategori_filter ? ' — Kategori: <strong>'.htmlspecialchars($kategori_filter).'</strong>' : '' ?>
      <?= $search ? ' — Pencarian: <strong>'.htmlspecialchars($search).'</strong>' : '' ?>
    </p>
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
            <p class="prod-desc"><?= htmlspecialchars(mb_substr($p['deskripsi'], 0, 70)) ?>...</p>
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
            <?php elseif ($sudah_login): ?>
              <a href="/pesan?id=<?= (int)$p['id'] ?>" class="btn-pesan">
                <i class="ti ti-shopping-cart" aria-hidden="true"></i> Pesan Sekarang
              </a>
            <?php else: ?>
              <a href="/login?redirect=/katalog" class="btn-login">
                <i class="ti ti-lock" aria-hidden="true"></i> Login untuk Memesan
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