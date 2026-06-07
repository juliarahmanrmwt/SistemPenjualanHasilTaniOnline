<?php
// api/penjual/pesanan.php
require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/koneksi.php';

if ($_SESSION['role'] !== 'penjual') {
    header("Location: /user/dashboard"); exit;
}

$id_penjual = $_SESSION['user_id'];

// Update status pesanan jika ada aksi
if (isset($_GET['update']) && isset($_GET['id'])) {
    $status_baru = $_GET['update'];
    $id_pesanan  = $_GET['id'];
    $allowed     = ['Dikemas','Dikirim','Selesai'];
    if (in_array($status_baru, $allowed)) {
        $upd = $koneksi->prepare("
            UPDATE pesanan p
            JOIN produk pr ON p.id_produk = pr.id
            SET p.status = ?
            WHERE p.id_pesanan = ? AND pr.id_penjual = ?
        ");
        $upd->bind_param("ssi", $status_baru, $id_pesanan, $id_penjual);
        $upd->execute();
    }
    header("Location: /penjual/pesanan"); exit;
}

// Filter status
$filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$where_status = $filter ? "AND p.status = ?" : "";

$sql = "
    SELECT p.*, pr.nama AS nama_produk_asli, pr.foto_url
    FROM pesanan p
    JOIN produk pr ON p.id_produk = pr.id
    WHERE pr.id_penjual = ? $where_status
    ORDER BY p.dibuat_at DESC
";
$stmt = $koneksi->prepare($sql);
if ($filter) {
    $stmt->bind_param("is", $id_penjual, $filter);
} else {
    $stmt->bind_param("i", $id_penjual);
}
$stmt->execute();
$pesanan_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan Saya - Petani GenZ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root { --hijau:#1a6b3c; --hijau-muda:#e8f5ee; --aksen:#f4a322; }
  body { font-family:'DM Sans',sans-serif; background:#fafaf8; }
  .navbar { background:var(--hijau)!important; }
  .navbar-brand { font-family:'Playfair Display',serif; font-size:1.5rem; }
  .page-title { font-family:'Playfair Display',serif; font-size:1.6rem; }

  /* FILTER TAB */
  .filter-tab { background:#fff; border-radius:14px; padding:6px; box-shadow:0 2px 10px rgba(0,0,0,.06); display:inline-flex; gap:4px; margin-bottom:24px; }
  .tab-btn { border:none; background:transparent; border-radius:10px; padding:8px 18px; font-size:.85rem; font-weight:600; color:#888; transition:.2s; text-decoration:none; }
  .tab-btn.active, .tab-btn:hover { background:var(--hijau); color:#fff; }

  /* PESANAN CARD */
  .pesanan-card { background:#fff; border-radius:16px; padding:20px; box-shadow:0 2px 12px rgba(0,0,0,.06); margin-bottom:16px; transition:.2s; }
  .pesanan-card:hover { box-shadow:0 6px 24px rgba(0,0,0,.1); }
  .pesanan-id { font-family:monospace; font-size:.85rem; color:var(--hijau); font-weight:700; }
  .produk-thumb { width:60px; height:60px; border-radius:10px; object-fit:cover; background:var(--hijau-muda); display:flex; align-items:center; justify-content:center; font-size:1.5rem; flex-shrink:0; }

  /* STATUS BADGE */
  .badge-proses   { background:#fff3cd; color:#856404; padding:5px 14px; border-radius:20px; font-size:.78rem; font-weight:700; }
  .badge-dikemas  { background:#cce5ff; color:#004085; padding:5px 14px; border-radius:20px; font-size:.78rem; font-weight:700; }
  .badge-dikirim  { background:#d4edda; color:#155724; padding:5px 14px; border-radius:20px; font-size:.78rem; font-weight:700; }
  .badge-selesai  { background:#e8f5ee; color:var(--hijau); padding:5px 14px; border-radius:20px; font-size:.78rem; font-weight:700; }

  /* UPDATE BTN */
  .btn-update { border:none; border-radius:20px; padding:6px 14px; font-size:.8rem; font-weight:600; text-decoration:none; transition:.2s; }
  .btn-dikemas  { background:#cce5ff; color:#004085; }
  .btn-dikemas:hover { background:#004085; color:#fff; }
  .btn-dikirim  { background:#d4edda; color:#155724; }
  .btn-dikirim:hover { background:#155724; color:#fff; }
  .btn-selesai  { background:var(--hijau-muda); color:var(--hijau); }
  .btn-selesai:hover { background:var(--hijau); color:#fff; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm">
  <div class="container">
    <a class="navbar-brand text-white" href="/penjual/dashboard"><i class="bi bi-arrow-left me-2"></i>Petani<span class="text-warning">GenZ</span></a>
    <span class="text-white opacity-75">Manajemen Pesanan</span>
  </div>
</nav>

<div class="container py-4">

  <h2 class="page-title mb-4">🛒 Semua Pesanan</h2>

  <!-- FILTER TAB -->
  <div class="filter-tab">
    <a href="/penjual/pesanan" class="tab-btn <?= $filter==='' ? 'active' : '' ?>">Semua</a>
    <a href="/penjual/pesanan?status=Proses"   class="tab-btn <?= $filter==='Proses'   ? 'active' : '' ?>">Proses</a>
    <a href="/penjual/pesanan?status=Dikemas"  class="tab-btn <?= $filter==='Dikemas'  ? 'active' : '' ?>">Dikemas</a>
    <a href="/penjual/pesanan?status=Dikirim"  class="tab-btn <?= $filter==='Dikirim'  ? 'active' : '' ?>">Dikirim</a>
    <a href="/penjual/pesanan?status=Selesai"  class="tab-btn <?= $filter==='Selesai'  ? 'active' : '' ?>">Selesai</a>
  </div>

  <?php if (empty($pesanan_list)): ?>
    <div class="text-center py-5 bg-white rounded-4 shadow-sm text-muted">
      <i class="bi bi-inbox fs-1 d-block mb-2"></i>
      <p class="fw-semibold">Tidak ada pesanan <?= $filter ? "dengan status <strong>$filter</strong>" : '' ?></p>
    </div>
  <?php else: ?>
    <p class="text-muted mb-3">Menampilkan <strong><?= count($pesanan_list) ?></strong> pesanan<?= $filter ? " — <strong>$filter</strong>" : '' ?></p>
    <?php foreach ($pesanan_list as $ps): ?>
    <div class="pesanan-card">
      <div class="d-flex align-items-start gap-3">
        <!-- Thumb -->
        <?php if ($ps['foto_url']): ?>
          <img src="<?= htmlspecialchars($ps['foto_url']) ?>" class="produk-thumb" alt="">
        <?php else: ?>
          <div class="produk-thumb">🌿</div>
        <?php endif; ?>

        <!-- Info -->
        <div class="flex-grow-1">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
              <span class="pesanan-id"><?= htmlspecialchars($ps['id_pesanan']) ?></span>
              <span class="text-muted small ms-2"><?= date('d M Y, H:i', strtotime($ps['dibuat_at'])) ?></span>
            </div>
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
          </div>

          <div class="mt-2">
            <strong><?= htmlspecialchars($ps['nama_produk']) ?></strong>
            <span class="text-muted small"> × <?= (int)$ps['jumlah'] ?></span>
          </div>
          <div class="text-muted small">Pembeli: <strong><?= htmlspecialchars($ps['nama_pembeli']) ?></strong></div>
          <?php if ($ps['catatan']): ?>
            <div class="text-muted small"><i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars($ps['catatan']) ?></div>
          <?php endif; ?>

          <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
            <span class="fw-bold" style="color:var(--hijau)">Rp <?= number_format($ps['total_harga'],0,',','.') ?></span>

            <!-- Tombol update status -->
            <div class="d-flex gap-2">
              <?php if ($ps['status'] === 'Proses'): ?>
                <a href="/penjual/pesanan?update=Dikemas&id=<?= urlencode($ps['id_pesanan']) ?>" class="btn-update btn-dikemas">
                  <i class="bi bi-box-seam me-1"></i>Kemas
                </a>
              <?php elseif ($ps['status'] === 'Dikemas'): ?>
                <a href="/penjual/pesanan?update=Dikirim&id=<?= urlencode($ps['id_pesanan']) ?>" class="btn-update btn-dikirim">
                  <i class="bi bi-truck me-1"></i>Kirim
                </a>
              <?php elseif ($ps['status'] === 'Dikirim'): ?>
                <a href="/penjual/pesanan?update=Selesai&id=<?= urlencode($ps['id_pesanan']) ?>" class="btn-update btn-selesai">
                  <i class="bi bi-check-circle me-1"></i>Selesai
                </a>
              <?php else: ?>
                <span class="text-muted small"><i class="bi bi-check-all me-1"></i>Pesanan selesai</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

<footer class="text-center py-4 text-muted border-top mt-2">
  <small>&copy; 2026 Petani GenZ - Platform Pertanian Modern</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>