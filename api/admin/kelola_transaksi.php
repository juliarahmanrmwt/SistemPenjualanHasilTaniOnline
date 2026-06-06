<?php
// admin/kelola_transaksi.php — Manajemen semua transaksi/pesanan
// Route: /admin/kelola-transaksi

require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /user/dashboard"); exit;
}

$pesan_ok  = '';

// UPDATE STATUS
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id     = $_GET['id'];
    $status = $_GET['status'];
    $allowed = ['Proses','Dikemas','Dikirim','Selesai','Dibatalkan'];
    if (in_array($status, $allowed)) {
        $st = $koneksi->prepare("UPDATE pesanan SET status=? WHERE id_pesanan=?");
        $st->bind_param("ss", $status, $id);
        $st->execute();

        // Kembalikan stok jika Dibatalkan
        if ($status === 'Dibatalkan') {
            $r = $koneksi->query("SELECT id_produk, jumlah FROM pesanan WHERE id_pesanan='$id' LIMIT 1");
            if ($r && $row = $r->fetch_assoc()) {
                if ($row['id_produk'] && $row['jumlah']) {
                    $koneksi->query("UPDATE produk SET stok = stok + {$row['jumlah']} WHERE id = {$row['id_produk']}");
                }
            }
        }
        $pesan_ok = "Status pesanan #$id diperbarui menjadi <strong>$status</strong>.";
    }
}

// HAPUS PESANAN
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $koneksi->query("DELETE FROM pesanan WHERE id_pesanan='$id'");
    $pesan_ok = "Pesanan #$id dihapus.";
}

// Filter & ambil data
$filter_status = isset($_GET['filter']) ? $_GET['filter'] : '';
$search        = isset($_GET['cari'])   ? trim($_GET['cari']) : '';

$where  = "WHERE 1=1";
$params = [];
$types  = '';

if ($filter_status) {
    $where   .= " AND status = ?";
    $params[] = $filter_status;
    $types   .= 's';
}
if ($search) {
    $where   .= " AND (id_pesanan LIKE ? OR nama_pembeli LIKE ? OR nama_produk LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types   .= 'sss';
}

$stmt = $koneksi->prepare("SELECT * FROM pesanan $where ORDER BY dibuat_at DESC");
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$semua_pesanan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Statistik
$all = $koneksi->query("SELECT status, COUNT(*) c, SUM(COALESCE(total_harga, harga)) s FROM pesanan GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$stat = [];
foreach ($all as $a) $stat[$a['status']] = $a;
$total_omzet = array_sum(array_column($all, 's'));

function badgeStatus($s) {
    return match($s) {
        'Proses'     => 'warning text-dark',
        'Dikemas'    => 'info text-dark',
        'Dikirim'    => 'primary',
        'Selesai'    => 'success',
        'Dibatalkan' => 'secondary',
        default      => 'light text-dark',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Transaksi - Petani GenZ Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root { --hijau:#1a6b3c; --hijau-muda:#e8f5ee; --aksen:#f4a322; }
  body { font-family:'DM Sans',sans-serif; background:#f4f6f4; }
  .navbar { background:var(--hijau)!important; }

  .sidebar { width:220px;min-height:100vh;background:#fff;border-right:1px solid #e5e5e5;padding:24px 16px; }
  .sidebar a { display:flex;align-items:center;gap:10px;color:#444;text-decoration:none;padding:10px 14px;border-radius:10px;font-size:.9rem;margin-bottom:4px; }
  .sidebar a:hover,.sidebar a.active { background:var(--hijau-muda);color:var(--hijau);font-weight:600; }
  .sidebar .brand { font-family:'Playfair Display',serif;font-size:1.3rem;color:var(--hijau);margin-bottom:24px;padding:0 6px; }
  .main { flex:1;padding:28px; }

  .stat-card { background:#fff;border-radius:14px;padding:18px 20px;box-shadow:0 2px 10px rgba(0,0,0,.05); }
  .stat-icon { width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;margin-bottom:8px; }

  .table-wrap { background:#fff;border-radius:16px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,.05); }
  .table th { font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#666; }
  .id-badge { font-family:monospace;font-size:.85rem;background:#f0f0f0;padding:3px 10px;border-radius:6px; }

  /* Status dropdown */
  .status-select { border:none;background:transparent;font-size:.85rem;font-weight:600;cursor:pointer;padding:0; }

  /* Search */
  .search-bar .form-control { border-radius:30px 0 0 30px;border-right:none; }
  .search-bar .btn { border-radius:0 30px 30px 0; }
</style>
</head>
<body>

<nav class="navbar navbar-dark px-3 py-2">
  <span class="navbar-brand fw-bold text-white" style="font-family:'Playfair Display',serif">
    Petani<span class="text-warning">GenZ</span> <small class="opacity-50 fs-6">Admin</small>
  </span>
  <div class="ms-auto d-flex gap-3 align-items-center">
    <span class="text-white opacity-75 d-none d-md-inline"><?= htmlspecialchars($_SESSION['nama']) ?></span>
    <a href="/logout" class="btn btn-outline-light btn-sm rounded-pill">Keluar</a>
  </div>
</nav>

<div class="d-flex">
  <div class="sidebar d-none d-md-block">
    <div class="brand">🌿 Admin</div>
    <a href="/admin/dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="/admin/produk"><i class="bi bi-box-seam"></i> Kelola Produk</a>
    <a href="/admin/kelola-transaksi" class="active"><i class="bi bi-receipt-cutoff"></i> Kelola Transaksi</a>
    <a href="/katalog" target="_blank"><i class="bi bi-shop"></i> Lihat Katalog</a>
  </div>

  <div class="main flex-grow-1">
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h4 class="fw-bold mb-0">Kelola Transaksi</h4>
        <p class="text-muted small mb-0">Monitor & kelola status pesanan pelanggan</p>
      </div>
      <!-- Export sederhana -->
      <a href="#" onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill">
        <i class="bi bi-printer me-1"></i>Cetak
      </a>
    </div>

    <?php if ($pesan_ok): ?>
      <div class="alert alert-success rounded-pill"><?= $pesan_ok ?></div>
    <?php endif; ?>

    <!-- STATISTIK -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:#fff3cd;color:#856404">⏳</div>
          <div class="fs-3 fw-bold"><?= ($stat['Proses']['c'] ?? 0) + ($stat['Dikemas']['c'] ?? 0) + ($stat['Dikirim']['c'] ?? 0) ?></div>
          <div class="text-muted small">Perlu Ditangani</div>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:#d1e7dd;color:#0f5132">✅</div>
          <div class="fs-3 fw-bold"><?= $stat['Selesai']['c'] ?? 0 ?></div>
          <div class="text-muted small">Selesai</div>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:#f8d7da;color:#842029">❌</div>
          <div class="fs-3 fw-bold"><?= $stat['Dibatalkan']['c'] ?? 0 ?></div>
          <div class="text-muted small">Dibatalkan</div>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon" style="background:var(--hijau-muda);color:var(--hijau)">💰</div>
          <div class="fw-bold" style="font-size:1.05rem">Rp <?= number_format($total_omzet,0,',','.') ?></div>
          <div class="text-muted small">Total Omzet</div>
        </div>
      </div>
    </div>

    <!-- FILTER & SEARCH -->
    <div class="table-wrap">
      <div class="d-flex flex-wrap gap-2 mb-3 align-items-center justify-content-between">
        <div class="d-flex gap-2 flex-wrap">
          <?php
          $filters = [''=>'Semua','Proses'=>'⏳ Proses','Dikemas'=>'📦 Dikemas','Dikirim'=>'🚚 Dikirim','Selesai'=>'✅ Selesai','Dibatalkan'=>'❌ Batal'];
          foreach ($filters as $val => $lbl):
          ?>
            <a href="?filter=<?= $val ?><?= $search ? '&cari='.urlencode($search) : '' ?>"
               class="btn btn-sm rounded-pill <?= $filter_status===$val ? 'btn-success' : 'btn-outline-secondary' ?>">
              <?= $lbl ?>
            </a>
          <?php endforeach; ?>
        </div>
        <form method="GET" class="d-flex search-bar" style="min-width:220px;">
          <?php if ($filter_status): ?><input type="hidden" name="filter" value="<?= htmlspecialchars($filter_status) ?>"><?php endif; ?>
          <input type="text" name="cari" value="<?= htmlspecialchars($search) ?>" class="form-control form-control-sm" placeholder="Cari ID / nama...">
          <button class="btn btn-success btn-sm">Cari</button>
        </form>
      </div>

      <!-- TABEL -->
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>ID Pesanan</th>
              <th>Pembeli</th>
              <th>Produk</th>
              <th>Total</th>
              <th>Tanggal</th>
              <th>Status</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($semua_pesanan)): ?>
            <tr><td colspan="7" class="text-center py-5 text-muted">Tidak ada pesanan ditemukan.</td></tr>
          <?php else: ?>
            <?php foreach ($semua_pesanan as $p):
              $total = $p['total_harga'] ?: ($p['harga'] * ($p['jumlah'] ?: 1));
              $tgl   = isset($p['dibuat_at']) ? date('d M Y H:i', strtotime($p['dibuat_at'])) : '-';
            ?>
            <tr>
              <td><span class="id-badge">#<?= htmlspecialchars($p['id_pesanan']) ?></span></td>
              <td>
                <div class="fw-semibold"><?= htmlspecialchars($p['nama_pembeli']) ?></div>
                <?php if (!empty($p['catatan'])): ?>
                  <div class="text-muted small"><i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars(mb_substr($p['catatan'],0,40)) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <div><?= htmlspecialchars($p['nama_produk']) ?></div>
                <div class="text-muted small"><?= (int)($p['jumlah'] ?: 1) ?> × Rp <?= number_format($p['harga'],0,',','.') ?></div>
              </td>
              <td class="fw-bold text-success">Rp <?= number_format($total,0,',','.') ?></td>
              <td class="text-muted small"><?= $tgl ?></td>
              <td>
                <!-- Dropdown ubah status langsung -->
                <form method="GET" style="display:inline">
                  <input type="hidden" name="id" value="<?= htmlspecialchars($p['id_pesanan']) ?>">
                  <?php if ($filter_status) echo '<input type="hidden" name="filter" value="'.htmlspecialchars($filter_status).'">'; ?>
                  <select name="status" class="status-select badge bg-<?= badgeStatus($p['status']) ?>"
                          onchange="this.form.submit()" <?= $p['status']==='Selesai' || $p['status']==='Dibatalkan' ? 'disabled' : '' ?>>
                    <?php foreach (['Proses','Dikemas','Dikirim','Selesai','Dibatalkan'] as $s): ?>
                      <option value="<?= $s ?>" <?= $p['status']===$s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td class="text-center">
                <a href="?hapus=<?= urlencode($p['id_pesanan']) ?><?= $filter_status ? '&filter='.urlencode($filter_status) : '' ?>"
                   class="btn btn-sm btn-outline-danger rounded-pill"
                   onclick="return confirm('Hapus pesanan ini?')">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="mt-3 text-muted small">
        Menampilkan <strong><?= count($semua_pesanan) ?></strong> pesanan
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>