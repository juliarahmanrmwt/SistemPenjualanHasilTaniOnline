<?php
// api/penjual/produk_tambah.php
require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/koneksi.php';

if ($_SESSION['role'] !== 'penjual') {
    header("Location: /user/dashboard"); exit;
}

$id_penjual = $_SESSION['user_id'];
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama      = trim($_POST['nama'] ?? '');
    $kategori  = trim($_POST['kategori'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $harga     = (int)($_POST['harga'] ?? 0);
    $stok      = (int)($_POST['stok'] ?? 0);
    $satuan    = trim($_POST['satuan'] ?? '');
    $foto_url  = trim($_POST['foto_url'] ?? '');
    $status    = $_POST['status'] ?? 'aktif';

    if (empty($nama) || empty($kategori) || empty($satuan) || $harga <= 0) {
        $error = 'Nama, kategori, satuan, dan harga wajib diisi.';
    } else {
        $stmt = $koneksi->prepare(
            "INSERT INTO produk (nama, kategori, deskripsi, harga, stok, satuan, foto_url, status, id_penjual)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssdiissi",
            $nama, $kategori, $deskripsi, $harga, $stok, $satuan, $foto_url, $status, $id_penjual
        );
        if ($stmt->execute()) {
            $success = true;
        } else {
            $error = 'Gagal menyimpan: ' . $koneksi->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Produk - Petani GenZ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root { --hijau:#1a6b3c; --hijau-muda:#e8f5ee; --aksen:#f4a322; }
  body { font-family:'DM Sans',sans-serif; background:#fafaf8; }
  .navbar { background:var(--hijau)!important; }
  .navbar-brand { font-family:'Playfair Display',serif; font-size:1.5rem; }
  .form-card { background:#fff; border-radius:20px; padding:36px; box-shadow:0 2px 20px rgba(0,0,0,.07); }
  .form-label { font-weight:600; font-size:.9rem; }
  .form-control, .form-select { border-radius:10px; border:1.5px solid #e0e0e0; padding:10px 14px; }
  .form-control:focus, .form-select:focus { border-color:var(--hijau); box-shadow:0 0 0 3px rgba(26,107,60,.1); }
  .btn-simpan { background:var(--hijau); color:#fff; border:none; border-radius:30px; padding:12px 0; font-weight:700; width:100%; font-size:1rem; transition:.2s; }
  .btn-simpan:hover { background:#145a31; color:#fff; }
  .btn-batal { background:#f5f5f5; color:#555; border:none; border-radius:30px; padding:12px 0; font-weight:600; width:100%; font-size:1rem; text-decoration:none; display:block; text-align:center; transition:.2s; }
  .btn-batal:hover { background:#e0e0e0; color:#333; }
  .preview-foto { width:100%; height:200px; border-radius:14px; object-fit:cover; background:var(--hijau-muda); display:flex; align-items:center; justify-content:center; font-size:3rem; }
  .page-title { font-family:'Playfair Display',serif; font-size:1.6rem; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm">
  <div class="container">
    <a class="navbar-brand text-white" href="/penjual/dashboard"><i class="bi bi-arrow-left me-2"></i>Petani<span class="text-warning">GenZ</span></a>
    <span class="text-white opacity-75">Tambah Produk</span>
  </div>
</nav>

<div class="container py-4" style="max-width:800px">

  <?php if ($success): ?>
  <div class="text-center py-5">
    <div style="width:80px;height:80px;background:var(--hijau-muda);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;margin:0 auto 20px;">✅</div>
    <h3 class="fw-bold text-success mb-2">Produk Berhasil Ditambahkan!</h3>
    <p class="text-muted mb-4">Produk kamu sudah tampil di katalog.</p>
    <div class="d-flex gap-3 justify-content-center">
      <a href="/penjual/produk_tambah" class="btn btn-success rounded-pill px-4">Tambah Lagi</a>
      <a href="/penjual/dashboard"     class="btn btn-outline-success rounded-pill px-4">Kembali ke Dashboard</a>
    </div>
  </div>
  <?php else: ?>

  <h2 class="page-title mb-4">➕ Tambah Produk Baru</h2>

  <?php if ($error): ?>
    <div class="alert alert-danger rounded-3"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="form-card">
    <form method="POST">
      <div class="row g-3">

        <!-- Nama Produk -->
        <div class="col-12">
          <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
          <input type="text" name="nama" class="form-control" placeholder="Contoh: Tomat Segar, Cabai Merah..." required
                 value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
        </div>

        <!-- Kategori & Satuan -->
        <div class="col-md-6">
          <label class="form-label">Kategori <span class="text-danger">*</span></label>
          <input type="text" name="kategori" class="form-control" placeholder="Sayuran, Buah, Rempah..."
                 value="<?= htmlspecialchars($_POST['kategori'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Satuan <span class="text-danger">*</span></label>
          <select name="satuan" class="form-select" required>
            <option value="">-- Pilih Satuan --</option>
            <?php foreach (['kg','gram','ikat','buah','liter','pcs','karung'] as $s): ?>
              <option value="<?= $s ?>" <?= ($_POST['satuan'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Harga & Stok -->
        <div class="col-md-6">
          <label class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
          <input type="number" name="harga" class="form-control" placeholder="Contoh: 15000" min="0"
                 value="<?= htmlspecialchars($_POST['harga'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Stok Awal</label>
          <input type="number" name="stok" class="form-control" placeholder="Contoh: 50" min="0"
                 value="<?= htmlspecialchars($_POST['stok'] ?? '0') ?>">
        </div>

        <!-- Deskripsi -->
        <div class="col-12">
          <label class="form-label">Deskripsi Produk</label>
          <textarea name="deskripsi" class="form-control" rows="3"
                    placeholder="Ceritakan keunggulan produkmu..."><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
        </div>

        <!-- Foto URL -->
        <div class="col-12">
          <label class="form-label">URL Foto Produk</label>
          <input type="url" name="foto_url" id="foto_url" class="form-control"
                 placeholder="https://..." oninput="previewFoto(this.value)"
                 value="<?= htmlspecialchars($_POST['foto_url'] ?? '') ?>">
          <div class="mt-2" id="preview-wrap">
            <?php if (!empty($_POST['foto_url'])): ?>
              <img src="<?= htmlspecialchars($_POST['foto_url']) ?>" class="preview-foto" alt="Preview">
            <?php else: ?>
              <div class="preview-foto">🌿</div>
            <?php endif; ?>
          </div>
          <small class="text-muted">Masukkan URL gambar dari internet. Contoh: dari Google Drive, Imgur, dll.</small>
        </div>

        <!-- Status -->
        <div class="col-12">
          <label class="form-label">Status Produk</label>
          <select name="status" class="form-select">
            <option value="aktif"    <?= ($_POST['status'] ?? 'aktif') === 'aktif'    ? 'selected' : '' ?>>Aktif (tampil di katalog)</option>
            <option value="nonaktif" <?= ($_POST['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif (sembunyikan)</option>
          </select>
        </div>

        <!-- Submit -->
        <div class="col-md-6">
          <button type="submit" class="btn-simpan"><i class="bi bi-check-lg me-2"></i>Simpan Produk</button>
        </div>
        <div class="col-md-6">
          <a href="/penjual/dashboard" class="btn-batal"><i class="bi bi-x-lg me-2"></i>Batal</a>
        </div>

      </div>
    </form>
  </div>
  <?php endif; ?>
</div>

<footer class="text-center py-4 text-muted border-top mt-4">
  <small>&copy; 2026 Petani GenZ - Platform Pertanian Modern</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewFoto(url) {
  const wrap = document.getElementById('preview-wrap');
  if (url) {
    wrap.innerHTML = `<img src="${url}" class="preview-foto" onerror="this.replaceWith(makePlaceholder())" alt="Preview">`;
  } else {
    wrap.innerHTML = '<div class="preview-foto">🌿</div>';
  }
}
function makePlaceholder() {
  const d = document.createElement('div');
  d.className = 'preview-foto';
  d.textContent = '🌿';
  return d;
}
</script>
</body>
</html>