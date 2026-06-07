<?php
// api/penjual/produk_edit.php
require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/koneksi.php';

if ($_SESSION['role'] !== 'penjual') {
    header("Location: /user/dashboard"); exit;
}

$id_penjual = $_SESSION['user_id'];
$id_produk  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_produk <= 0) { header("Location: /penjual/dashboard"); exit; }

// Ambil produk — pastikan milik penjual ini
$stmt = $koneksi->prepare("SELECT * FROM produk WHERE id = ? AND id_penjual = ? LIMIT 1");
$stmt->bind_param("ii", $id_produk, $id_penjual);
$stmt->execute();
$produk = $stmt->get_result()->fetch_assoc();
if (!$produk) { header("Location: /penjual/dashboard"); exit; }

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
        $upd = $koneksi->prepare(
            "UPDATE produk SET nama=?, kategori=?, deskripsi=?, harga=?, stok=?, satuan=?, foto_url=?, status=?
             WHERE id=? AND id_penjual=?"
        );
        $upd->bind_param("sssdiissii",
            $nama, $kategori, $deskripsi, $harga, $stok, $satuan, $foto_url, $status,
            $id_produk, $id_penjual
        );
        if ($upd->execute()) {
            $success = true;
            // Refresh data
            $stmt->execute();
            $produk = $stmt->get_result()->fetch_assoc();
            // Re-fetch after update
            $r = $koneksi->prepare("SELECT * FROM produk WHERE id = ? AND id_penjual = ? LIMIT 1");
            $r->bind_param("ii", $id_produk, $id_penjual);
            $r->execute();
            $produk = $r->get_result()->fetch_assoc();
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
<title>Edit Produk - Petani GenZ</title>
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
  .alert-success-custom { background:var(--hijau-muda); border:1.5px solid #a8d5b8; color:var(--hijau); border-radius:12px; padding:12px 18px; font-weight:600; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm">
  <div class="container">
    <a class="navbar-brand text-white" href="/penjual/dashboard"><i class="bi bi-arrow-left me-2"></i>Petani<span class="text-warning">GenZ</span></a>
    <span class="text-white opacity-75">Edit Produk</span>
  </div>
</nav>

<div class="container py-4" style="max-width:800px">

  <h2 class="page-title mb-4">✏️ Edit Produk</h2>

  <?php if ($success): ?>
    <div class="alert-success-custom mb-3"><i class="bi bi-check-circle me-2"></i>Produk berhasil diperbarui!</div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger rounded-3"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="form-card">
    <form method="POST">
      <div class="row g-3">

        <div class="col-12">
          <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
          <input type="text" name="nama" class="form-control" required
                 value="<?= htmlspecialchars($produk['nama']) ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label">Kategori <span class="text-danger">*</span></label>
          <input type="text" name="kategori" class="form-control" required
                 value="<?= htmlspecialchars($produk['kategori']) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Satuan <span class="text-danger">*</span></label>
          <select name="satuan" class="form-select" required>
            <?php foreach (['kg','gram','ikat','buah','liter','pcs','karung'] as $s): ?>
              <option value="<?= $s ?>" <?= $produk['satuan'] === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
          <input type="number" name="harga" class="form-control" min="0" required
                 value="<?= (int)$produk['harga'] ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Stok</label>
          <input type="number" name="stok" class="form-control" min="0"
                 value="<?= (int)$produk['stok'] ?>">
        </div>

        <div class="col-12">
          <label class="form-label">Deskripsi</label>
          <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($produk['deskripsi']) ?></textarea>
        </div>

        <div class="col-12">
          <label class="form-label">URL Foto</label>
          <input type="url" name="foto_url" id="foto_url" class="form-control"
                 placeholder="https://..." oninput="previewFoto(this.value)"
                 value="<?= htmlspecialchars($produk['foto_url'] ?? '') ?>">
          <div class="mt-2" id="preview-wrap">
            <?php if ($produk['foto_url']): ?>
              <img src="<?= htmlspecialchars($produk['foto_url']) ?>" class="preview-foto" alt="Preview">
            <?php else: ?>
              <div class="preview-foto">🌿</div>
            <?php endif; ?>
          </div>
        </div>

        <div class="col-12">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="aktif"    <?= $produk['status'] === 'aktif'    ? 'selected' : '' ?>>Aktif</option>
            <option value="nonaktif" <?= $produk['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
          </select>
        </div>

        <div class="col-md-6">
          <button type="submit" class="btn-simpan"><i class="bi bi-check-lg me-2"></i>Simpan Perubahan</button>
        </div>
        <div class="col-md-6">
          <a href="/penjual/dashboard" class="btn-batal"><i class="bi bi-x-lg me-2"></i>Batal</a>
        </div>

      </div>
    </form>
  </div>
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