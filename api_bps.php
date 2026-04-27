<?php
require_once __DIR__ . '/auth_check.php';

if (!isset($_SESSION['login'])) { 
    header("Location: login.php"); 
    exit; 
}

include '../api/ambil_bps.php';
$res = getDataBPSFull();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Tabel BPS - Petani GenZ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <span class="navbar-text text-white">Data Indeks Statistik BPS</span>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white pt-4 px-4">
                <h5 class="fw-bold text-success">
                    <i class="bi bi-table"></i> Tabel Statistik Pertanian (BPS)
                </h5>
                <p class="text-muted small">Menampilkan data resmi sektor pertanian, kehutanan, dan perikanan.</p>
            </div>
            
            <div class="card-body p-4">
                <?php if ($res && is_array($res)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 15%;">ID Variabel</th>
                                    <th>Nama Variabel / Komoditas</th>
                                    <th style="width: 20%;" class="text-center">Status Data</th>
                                </tr>
                            </thead>
                            <tbody class="table-group-divider">
    <?php 
    if ($res && is_array($res)):
        $hasData = false;
        foreach ($res as $row): 
            // Ambil nama dari sub_nm atau title (fallback jika key berbeda)
            $nama = $row['sub_nm'] ?? $row['title'] ?? $row['name'] ?? null;
            $id = $row['sub_id'] ?? $row['id'] ?? '-';

            if ($nama):
                $hasData = true;
    ?>
    <tr>
        <td class="text-center fw-bold text-secondary"><?php echo htmlspecialchars($id); ?></td>
        <td>
            <div class="d-flex align-items-center">
                <i class="bi bi-file-earmark-bar-graph text-success me-2"></i>
                <?php echo htmlspecialchars($nama); ?>
            </div>
        </td>
        <td class="text-center">
            <span class="badge bg-primary-subtle text-primary px-3 py-2">Tersedia</span>
        </td>
    </tr>
    <?php 
            endif;
        endforeach; 

        if (!$hasData): ?>
            <tr><td colspan="3" class="text-center py-4">Data ditemukan, tetapi format kolom tidak sesuai.</td></tr>
        <?php endif;
    else: ?>
        <tr><td colspan="3" class="text-center py-5 text-danger">Gagal memuat data dari BPS.</td></tr>
    <?php endif; ?>
</tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="alert alert-danger d-inline-block px-5 shadow-sm">
                            <i class="bi bi-exclamation-triangle-fill fs-4 d-block mb-2"></i>
                            <strong>Gagal Terhubung!</strong><br>
                            Tidak dapat mengambil data dari server BPS. Periksa API Key atau koneksi internet Anda.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>