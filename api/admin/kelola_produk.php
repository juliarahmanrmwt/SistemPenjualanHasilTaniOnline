<?php
// admin/kelola_produk.php — CRUD produk untuk admin/penjual
// Route: /admin/produk

require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/koneksi.php';

// Hanya admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /user/dashboard"); exit;
}

$pesan_ok  = '';
$pesan_err = '';

// ======================== PROSES AKSI ========================

// TAMBAH
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {
    $nama      = trim($_POST['nama']);
    $deskripsi = trim($_POST['deskripsi']);
    $harga     = (int)str_replace(['.','Rp',' '], '', $_POST['harga']);
    $stok      = (int)$_POST['stok'];
    $satuan    = trim($_POST['satuan']);
    $kategori  = trim($_POST['kategori']);
    $foto_url  = trim($_POST['foto_url']);
    $status    = $_POST['status'];

    $st = $koneksi->prepare(
        "INSERT INTO produk (nama,deskripsi,harga,stok,satuan,kategori,foto_url,status)
         VALUES (?,?,?,?,?,?,?,?)"
    );
    $st->bind_param("ssdiisss", $nama,$deskripsi,$harga,$stok,$satuan,$kategori,$foto_url,$status);
    if ($st->execute()) $pesan_ok  = "Produk berhasil ditambahkan.";
    else                $pesan_err = "Gagal tambah: ".$koneksi->error;
}

// EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'edit') {
    $id        = (int)$_POST['id'];
    $nama      = trim($_POST['nama']);
    $deskripsi = trim($_POST['deskripsi']);
    $harga     = (int)str_replace(['.','Rp',' '], '', $_POST['harga']);
    $stok      = (int)$_POST['stok'];
    $satuan    = trim($_POST['satuan']);
    $kategori  = trim($_POST['kategori']);
    $foto_url  = trim($_POST['foto_url']);
    $status    = $_POST['status'];

    $st = $koneksi->prepare(
        "UPDATE produk SET nama=?,deskripsi=?,harga=?,stok=?,satuan=?,kategori=?,foto_url=?,status=? WHERE id=?"
    );
    $st->bind_param("ssdiisssi", $nama,$deskripsi,$harga,$stok,$satuan,$kategori,$foto_url,$status,$id);
    if ($st->execute()) $pesan_ok  = "Produk berhasil diperbarui.";
    else                $pesan_err = "Gagal edit: ".$koneksi->error;
}

// HAPUS
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $koneksi->query("DELETE FROM produk WHERE id=$id");
    $pesan_ok = "Produk dihapus.";
}

// TOGGLE STATUS
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $koneksi->query("UPDATE produk SET status = IF(status='aktif','nonaktif','aktif') WHERE id=$id");
    header("Location: /admin/produk"); exit;
}

// ======================== AMBIL DATA ========================
$produk_list = $koneksi->query("SELECT * FROM produk ORDER BY dibuat_at DESC")->fetch_all(MYSQLI_ASSOC);

// Ambil data edit jika ada ?edit=id
$edit_data = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $r   = $koneksi->query("SELECT * FROM produk WHERE id=$eid LIMIT 1");
    if ($r) $edit_data = $r->fetch_assoc();
}

$total_aktif    = count(array_filter($produk_list, fn($p) => $p['status']==='aktif'));
$total_nonaktif = count($produk_list) - $total_aktif;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Produk - Petani GenZ Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root { --hijau:#1a6b3c; --hijau-muda:#e8f5ee; --aksen:#f4a322; }
  body { font-family:'DM Sans',sans-serif; background:#f4f6f4; }
  .navbar { background:var(--hijau)!important; }
  .sidebar { width:220px; min-height:100vh; background:#fff; border-right:1px solid #e5e5e5; padding:24px 16px; }
  .sidebar a { display:flex;align-items:center;gap:10px;color:#444;text-decoration:none;padding:10px 14px;border-radius:10px;font-size:.9rem;margin-bottom:4px; }
  .sidebar a:hover, .sidebar a.active { background:var(--hijau-muda);color:var(--hijau);font-weight:600; }
  .sidebar .brand { font-family:'Playfair Display',serif;font-size:1.3rem;color:var(--hijau);margin-bottom:24px;padding:0 6px; }

  .main { flex:1; padding:28px; }
  .stat-card { background:#fff;border-radius:14px;padding:18px 20px;box-shadow:0 2px 10px rgba(0,0,0,.05); }

  /* TABLE */
  .table-wrap { background:#fff;border-radius:16px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,.05); }
  .table th { font-size:.82rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#666;border-top:none; }
  .prod-thumb { width:52px;height:52px;border-radius:10px;object-fit:cover; }
  .prod-thumb-placeholder { width:52px;height:52px;border-radius:10px;background:var(--hijau-muda);display:flex;align-items:center;justify-content:center;font-size:1.4rem; }

  /* MODAL FORM */
  .modal-content { border-radius:20px;border:none; }
  .modal-header { border-bottom:none;padding:24px 28px 0; }
  .modal-body   { padding:20px 28px; }
  .modal-footer { border-top:none;padding:0 28px 24px; }
  .form-control, .form-select { border-radius:10px;font-size:.9rem; }
  .foto-preview { width:100%;height:140px;object-fit:cover;border-radius:10px;margin-top:8px;display:none; }
</style>
</head>
<body>

<nav class="navbar navbar-dark px-3 py-2">
  <span class="navbar-brand fw-bold text-white" style="font-family:'Playfair Display',serif">
    Petani<span class="text-warning">GenZ</span> <small class="opacity-50 fs-6">Admin</small>
  </span>
  <div class="ms-auto d-flex align-items-center gap-3">
    <span class="text-white opacity-75 d-none d-md-inline"><?= htmlspecialchars($_SESSION['nama']) ?></span>
    <a href="/logout" class="btn btn-outline-light btn-sm rounded-pill">Keluar</a>
  </div>
</nav>

<div class="d-flex">
  <!-- SIDEBAR -->
  <div class="sidebar d-none d-md-block">
    <div class="brand">🌿 Admin</div>
    <a href="/admin/dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="/admin/produk" class="active"><i class="bi bi-box-seam"></i> Kelola Produk</a>
    <a href="/admin/transaksi"><i class="bi bi-receipt-cutoff"></i> Semua Transaksi</a>
    <a href="/katalog" target="_blank"><i class="bi bi-shop"></i> Lihat Katalog</a>
  </div>

  <!-- MAIN -->
  <div class="main flex-grow-1">
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h4 class="fw-bold mb-0">Kelola Produk</h4>
        <p class="text-muted small mb-0">Tambah, ubah, atau hapus produk yang dijual</p>
      </div>
      <button class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalProduk" onclick="resetForm()">
        <i class="bi bi-plus-circle me-1"></i>Tambah Produk
      </button>
    </div>

    <!-- ALERTS -->
    <?php if ($pesan_ok):  ?><div class="alert alert-success rounded-pill"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($pesan_ok)  ?></div><?php endif; ?>
    <?php if ($pesan_err): ?><div class="alert alert-danger  rounded-pill"><i class="bi bi-x-circle me-2"></i><?= htmlspecialchars($pesan_err) ?></div><?php endif; ?>

    <!-- STATS -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card text-center">
          <div class="fs-3 fw-bold text-success"><?= count($produk_list) ?></div>
          <div class="text-muted small">Total Produk</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card text-center">
          <div class="fs-3 fw-bold text-success"><?= $total_aktif ?></div>
          <div class="text-muted small">Aktif</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card text-center">
          <div class="fs-3 fw-bold text-secondary"><?= $total_nonaktif ?></div>
          <div class="text-muted small">Nonaktif</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card text-center">
          <div class="fs-3 fw-bold text-warning">
            <?= count(array_filter($produk_list, fn($p) => $p['stok'] <= 5)) ?>
          </div>
          <div class="text-muted small">Stok Rendah</div>
        </div>
      </div>
    </div>

    <!-- TABEL PRODUK -->
    <div class="table-wrap">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Produk</th>
              <th>Kategori</th>
              <th>Harga</th>
              <th>Stok</th>
              <th>Status</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($produk_list)): ?>
            <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada produk. Tambahkan yang pertama!</td></tr>
          <?php else: ?>
            <?php foreach ($produk_list as $p): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-3">
                  <?php if ($p['foto_url']): ?>
                    <img src="<?= htmlspecialchars($p['foto_url']) ?>" class="prod-thumb" alt="">
                  <?php else: ?>
                    <div class="prod-thumb-placeholder">🌿</div>
                  <?php endif; ?>
                  <div>
                    <div class="fw-semibold"><?= htmlspecialchars($p['nama']) ?></div>
                    <div class="text-muted small"><?= htmlspecialchars(mb_substr($p['deskripsi'],0,45)) ?>...</div>
                  </div>
                </div>
              </td>
              <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['kategori']) ?></span></td>
              <td class="fw-semibold">Rp <?= number_format($p['harga'],0,',','.') ?><small class="text-muted fw-normal">/<?= htmlspecialchars($p['satuan']) ?></small></td>
              <td>
                <span class="fw-semibold <?= $p['stok'] <= 5 ? 'text-danger' : 'text-success' ?>">
                  <?= (int)$p['stok'] ?>
                </span>
                <?= $p['stok'] <= 5 ? '<span class="badge bg-danger ms-1">Rendah</span>' : '' ?>
              </td>
              <td>
                <a href="/admin/produk?toggle=<?= $p['id'] ?>" class="text-decoration-none">
                  <?php if ($p['status'] === 'aktif'): ?>
                    <span class="badge bg-success">Aktif</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Nonaktif</span>
                  <?php endif; ?>
                </a>
              </td>
              <td class="text-center">
                <button class="btn btn-sm btn-outline-primary rounded-pill me-1"
                        onclick='editProduk(<?= json_encode($p) ?>)'>
                  <i class="bi bi-pencil"></i>
                </button>
                <a href="/admin/produk?hapus=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill"
                   onclick="return confirm('Hapus produk ini?')">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- /main -->
</div><!-- /flex -->

<!-- MODAL TAMBAH / EDIT PRODUK -->
<div class="modal fade" id="modalProduk" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="modalJudul">Tambah Produk Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/admin/produk">
        <input type="hidden" name="aksi" id="inputAksi" value="tambah">
        <input type="hidden" name="id"   id="inputId"   value="">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label fw-semibold">Nama Produk *</label>
              <input type="text" name="nama" id="fNama" class="form-control" placeholder="contoh: Cabai Merah Segar" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Kategori *</label>
              <input type="text" name="kategori" id="fKategori" class="form-control" placeholder="Sayuran / Bumbu / Buah" required>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Deskripsi</label>
              <textarea name="deskripsi" id="fDeskripsi" class="form-control" rows="2" placeholder="Jelaskan produk secara singkat..."></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Harga (Rp) *</label>
              <input type="number" name="harga" id="fHarga" class="form-control" placeholder="18000" required min="0">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Stok *</label>
              <input type="number" name="stok" id="fStok" class="form-control" placeholder="50" required min="0">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Satuan *</label>
              <select name="satuan" id="fSatuan" class="form-select">
                <option value="kg">kg</option>
                <option value="gram">gram</option>
                <option value="ikat">ikat</option>
                <option value="buah">buah</option>
                <option value="liter">liter</option>
                <option value="pack">pack</option>
              </select>
            </div>
            <div class="col-md-8">
              <label class="form-label fw-semibold">URL Foto</label>
              <input type="url" name="foto_url" id="fFoto" class="form-control" placeholder="https://..." oninput="previewFoto(this.value)">
              <img id="fotoPreview" class="foto-preview" src="" alt="Preview foto">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Status</label>
              <select name="status" id="fStatus" class="form-select">
                <option value="aktif">Aktif (tampil di katalog)</option>
                <option value="nonaktif">Nonaktif (disembunyikan)</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer gap-2">
          <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold">Simpan Produk</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
// Auto-buka modal jika ada data edit dari GET
if ($edit_data): ?>
<script>
window.addEventListener('DOMContentLoaded', () => editProduk(<?= json_encode($edit_data) ?>));
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function resetForm() {
  document.getElementById('modalJudul').textContent = 'Tambah Produk Baru';
  document.getElementById('inputAksi').value = 'tambah';
  document.getElementById('inputId').value   = '';
  ['fNama','fKategori','fDeskripsi','fHarga','fStok','fFoto'].forEach(id => {
    document.getElementById(id).value = '';
  });
  document.getElementById('fSatuan').value = 'kg';
  document.getElementById('fStatus').value = 'aktif';
  document.getElementById('fotoPreview').style.display = 'none';
}

function editProduk(p) {
  document.getElementById('modalJudul').textContent   = 'Edit Produk';
  document.getElementById('inputAksi').value          = 'edit';
  document.getElementById('inputId').value            = p.id;
  document.getElementById('fNama').value              = p.nama;
  document.getElementById('fKategori').value          = p.kategori;
  document.getElementById('fDeskripsi').value         = p.deskripsi || '';
  document.getElementById('fHarga').value             = p.harga;
  document.getElementById('fStok').value              = p.stok;
  document.getElementById('fSatuan').value            = p.satuan;
  document.getElementById('fFoto').value              = p.foto_url || '';
  document.getElementById('fStatus').value            = p.status;
  previewFoto(p.foto_url);
  new bootstrap.Modal(document.getElementById('modalProduk')).show();
}

function previewFoto(url) {
  const img = document.getElementById('fotoPreview');
  if (url && url.startsWith('http')) {
    img.src = url;
    img.style.display = 'block';
  } else {
    img.style.display = 'none';
  }
}
</script>
</body>
</html>