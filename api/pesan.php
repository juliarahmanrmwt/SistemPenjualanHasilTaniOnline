<?php
// pesan.php — Halaman detail & form pemesanan
// Letakkan di api/user/, route: /pesan?id={id_produk}

require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/koneksi.php';

$id_produk = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_produk <= 0) { header("Location: /katalog"); exit; }

// Ambil data produk
$stmt = $koneksi->prepare("SELECT * FROM produk WHERE id = ? AND status = 'aktif' LIMIT 1");
$stmt->bind_param("i", $id_produk);
$stmt->execute();
$produk = $stmt->get_result()->fetch_assoc();

if (!$produk) { header("Location: /katalog"); exit; }

$error   = '';
$success = '';

// Proses POST pemesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pesan'])) {
    $jumlah  = max(1, (int)($_POST['jumlah'] ?? 1));
    $catatan = trim($_POST['catatan'] ?? '');

    // Validasi stok
    if ($jumlah > $produk['stok']) {
        $error = "Jumlah melebihi stok tersedia ({$produk['stok']} {$produk['satuan']}).";
    } else {
        $total       = $produk['harga'] * $jumlah;
        $id_pesanan  = 'PG' . date('ymd') . rand(1000,9999);
        $nama_user   = $_SESSION['nama'];
        $nama_produk = $produk['nama'];
        $harga_unit  = $produk['harga'];
        $status      = 'Proses';

        // Insert pesanan
        $ins = $koneksi->prepare(
            "INSERT INTO pesanan (id_pesanan, nama_pembeli, id_produk, nama_produk, harga, jumlah, total_harga, catatan, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $ins->bind_param("ssiidiisd",
            $id_pesanan, $nama_user, $id_produk, $nama_produk,
            $harga_unit, $jumlah, $total, $catatan, $status
        );

        if ($ins->execute()) {
            // Kurangi stok
            $koneksi->query("UPDATE produk SET stok = stok - $jumlah WHERE id = $id_produk");
            $success = $id_pesanan;
        } else {
            $error = "Gagal membuat pesanan: " . $koneksi->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesan <?= htmlspecialchars($produk['nama']) ?> - Petani GenZ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root { --hijau:#1a6b3c; --hijau-muda:#e8f5ee; --aksen:#f4a322; }
  body { font-family:'DM Sans',sans-serif; background:#fafaf8; }
  .navbar { background:var(--hijau)!important; }
  .navbar-brand { font-family:'Playfair Display',serif; }

  .prod-foto { border-radius:20px; object-fit:cover; width:100%; max-height:360px; }
  .info-box  { background:#fff; border-radius:20px; padding:32px; box-shadow:0 2px 20px rgba(0,0,0,.07); }
  .harga-besar { font-size:2rem; font-weight:700; color:var(--hijau); }
  .badge-kat { background:var(--hijau-muda); color:var(--hijau); border-radius:30px; padding:4px 14px; font-size:.78rem; font-weight:600; }

  .qty-wrap { display:flex; align-items:center; gap:12px; }
  .qty-btn { width:38px;height:38px;border-radius:50%;border:2px solid var(--hijau);background:#fff;
             color:var(--hijau);font-size:1.2rem;font-weight:700;cursor:pointer;transition:.15s; }
  .qty-btn:hover { background:var(--hijau);color:#fff; }
  .qty-input { width:70px;text-align:center;border:2px solid #ddd;border-radius:10px;padding:6px;font-size:1rem;font-weight:600; }

  .ringkasan { background:var(--hijau-muda);border-radius:14px;padding:20px;margin:20px 0; }
  .ringkasan .row { font-size:.95rem; }
  .total-row { font-size:1.1rem;font-weight:700;color:var(--hijau);border-top:2px solid #c3e0ce;padding-top:10px;margin-top:8px; }

  .btn-pesan { background:var(--hijau);color:#fff;border:none;border-radius:30px;
               padding:13px 0;font-size:1rem;font-weight:700;width:100%;transition:.2s; }
  .btn-pesan:hover { background:#145a31;color:#fff; }

  /* SUCCESS MODAL */
  .sukses-card { text-align:center;background:#fff;border-radius:24px;padding:48px 32px;box-shadow:0 8px 40px rgba(0,0,0,.12); }
  .check-icon { width:80px;height:80px;background:var(--hijau-muda);border-radius:50%;
                display:flex;align-items:center;justify-content:center;font-size:2.5rem;margin:0 auto 20px; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
  <div class="container">
    <a class="navbar-brand text-white" href="/"><i class="bi bi-arrow-left me-2"></i>Petani<span class="text-warning">GenZ</span></a>
    <span class="text-white opacity-75 d-none d-md-inline">Form Pemesanan</span>
    <a href="/user/transaksi" class="btn btn-outline-light btn-sm rounded-pill ms-auto">
      <i class="bi bi-receipt me-1"></i>Pesanan Saya
    </a>
  </div>
</nav>

<div class="container py-5">

  <?php if ($success): ?>
  <!-- SUKSES -->
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="sukses-card">
        <div class="check-icon">✅</div>
        <h3 class="fw-bold text-success mb-2">Pesanan Berhasil!</h3>
        <p class="text-muted mb-1">ID Pesanan Anda:</p>
        <div class="bg-light rounded-pill px-4 py-2 d-inline-block mb-4">
          <strong class="fs-5 text-success"><?= htmlspecialchars($success) ?></strong>
        </div>
        <p class="text-muted mb-4">Simpan ID pesanan ini untuk melacak status pengiriman Anda.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
          <a href="/user/transaksi" class="btn btn-success rounded-pill px-4">Lihat Pesanan</a>
          <a href="/katalog"        class="btn btn-outline-success rounded-pill px-4">Lanjut Belanja</a>
        </div>
      </div>
    </div>
  </div>

  <?php else: ?>
  <!-- FORM PESAN -->
  <?php if ($error): ?>
    <div class="alert alert-danger rounded-pill text-center"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="row g-4 align-items-start">
    <!-- Foto Produk -->
    <div class="col-md-5">
      <?php if ($produk['foto_url']): ?>
        <img src="<?= htmlspecialchars($produk['foto_url']) ?>" class="prod-foto" alt="<?= htmlspecialchars($produk['nama']) ?>">
      <?php else: ?>
        <div class="prod-foto d-flex align-items-center justify-content-center" style="background:#e8f5ee;height:320px;font-size:5rem;">🌿</div>
      <?php endif; ?>
    </div>

    <!-- Form -->
    <div class="col-md-7">
      <div class="info-box">
        <span class="badge-kat"><?= htmlspecialchars($produk['kategori']) ?></span>
        <h2 class="fw-bold mt-2 mb-1"><?= htmlspecialchars($produk['nama']) ?></h2>
        <p class="text-muted mb-3"><?= nl2br(htmlspecialchars($produk['deskripsi'])) ?></p>

        <div class="harga-besar mb-1">
          Rp <?= number_format($produk['harga'],0,',','.') ?>
          <small class="fs-6 fw-normal text-muted">/ <?= htmlspecialchars($produk['satuan']) ?></small>
        </div>
        <p class="text-muted small mb-4">
          <i class="bi bi-box-seam me-1"></i>
          Stok tersedia: <strong><?= (int)$produk['stok'] ?> <?= htmlspecialchars($produk['satuan']) ?></strong>
        </p>

        <form method="POST" id="formPesan">
          <!-- Jumlah -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Jumlah</label>
            <div class="qty-wrap">
              <button type="button" class="qty-btn" onclick="ubahJumlah(-1)">−</button>
              <input type="number" name="jumlah" id="jumlah" class="qty-input"
                     value="1" min="1" max="<?= (int)$produk['stok'] ?>"
                     oninput="updateTotal()">
              <button type="button" class="qty-btn" onclick="ubahJumlah(1)">+</button>
              <span class="text-muted"><?= htmlspecialchars($produk['satuan']) ?></span>
            </div>
          </div>

          <!-- Catatan -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Catatan <span class="text-muted fw-normal">(opsional)</span></label>
            <textarea name="catatan" class="form-control" rows="3" placeholder="Contoh: pilih yang sudah matang, kirim pagi hari..."></textarea>
          </div>

          <!-- Ringkasan Harga -->
          <div class="ringkasan">
            <div class="row mb-1">
              <div class="col">Harga satuan</div>
              <div class="col-auto fw-semibold">Rp <?= number_format($produk['harga'],0,',','.') ?></div>
            </div>
            <div class="row mb-1">
              <div class="col">Jumlah</div>
              <div class="col-auto fw-semibold" id="qty-display">1 <?= htmlspecialchars($produk['satuan']) ?></div>
            </div>
            <div class="row total-row">
              <div class="col">Total</div>
              <div class="col-auto" id="total-display">Rp <?= number_format($produk['harga'],0,',','.') ?></div>
            </div>
          </div>

          <!-- Pembeli -->
          <p class="text-muted small mb-3">
            <i class="bi bi-person-circle me-1"></i>
            Memesan sebagai: <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong>
          </p>

          <button type="submit" name="pesan" class="btn-pesan">
            <i class="bi bi-cart-check me-2"></i>Konfirmasi Pesanan
          </button>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const hargaSatuan = <?= (int)$produk['harga'] ?>;
const satuan      = "<?= htmlspecialchars($produk['satuan']) ?>";
const stokMax     = <?= (int)$produk['stok'] ?>;

function formatRp(n) {
  return 'Rp ' + n.toLocaleString('id-ID');
}

function ubahJumlah(delta) {
  const inp = document.getElementById('jumlah');
  let val = Math.max(1, Math.min(stokMax, (parseInt(inp.value)||1) + delta));
  inp.value = val;
  updateTotal();
}

function updateTotal() {
  const qty = Math.max(1, Math.min(stokMax, parseInt(document.getElementById('jumlah').value)||1));
  document.getElementById('jumlah').value = qty;
  document.getElementById('qty-display').textContent  = qty + ' ' + satuan;
  document.getElementById('total-display').textContent = formatRp(qty * hargaSatuan);
}
</script>
</body>
</html>