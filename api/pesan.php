<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../koneksi.php';

// Tolak akses selain POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['proses_beli'])) {
    header("Location: /dashboard");
    exit;
}

// Ambil data session
$user_id   = (int)($_SESSION['user_id'] ?? 0);  // ✅ cast INT
$nama_user = $_SESSION['nama'] ?? 'Pelanggan';

if ($user_id === 0) {
    header("Location: /login");
    exit;
}

// Ambil & sanitasi input
$nama_produk = trim($_POST['nama_produk'] ?? '');
$harga       = (float)($_POST['harga']    ?? 0);
$jumlah      = max(1, (int)($_POST['jumlah'] ?? 1));
$alamat      = trim($_POST['alamat']      ?? '');
$catatan     = trim($_POST['catatan']     ?? '');
$produk_id   = (int)($_POST['produk_id'] ?? 0);  // ✅ cast INT
$total       = $harga * $jumlah;
$status      = 'Proses';

// Validasi input wajib
if (empty($nama_produk) || $harga <= 0 || empty($alamat)) {
    header("Location: /dashboard?error=data_tidak_lengkap");
    exit;
}

// Cek stok jika dari produk DB
if ($produk_id > 0) {
    $cekStok = $conn->prepare("SELECT stok FROM produk WHERE id = ? AND status = 'aktif' LIMIT 1");
    $cekStok->bind_param("i", $produk_id); // ✅ "i" = integer
    $cekStok->execute();
    $stokRow = $cekStok->get_result()->fetch_assoc();
    $cekStok->close();

    if (!$stokRow || $stokRow['stok'] < $jumlah) {
        header("Location: /dashboard?error=stok_habis");
        exit;
    }
}

// Generate id_pesanan unik (tidak bentrok)
do {
    $id_pesanan = rand(100000, 999999); // ✅ INT murni, bukan string
    $cekId = $conn->prepare("SELECT id_pesanan FROM pesanan WHERE id_pesanan = ? LIMIT 1");
    $cekId->bind_param("i", $id_pesanan); // ✅ "i" = integer
    $cekId->execute();
    $cekId->store_result();
    $sudahAda = $cekId->num_rows > 0;
    $cekId->close();
} while ($sudahAda);

// ✅ INSERT dengan prepared statement yang benar
// Semua tipe harus sesuai: i=integer, s=string, d=double
$stmt = $conn->prepare("
    INSERT INTO pesanan 
        (id_pesanan, user_id, nama_pembeli, produk_id, nama_produk, harga, jumlah, total, alamat_pengiriman, catatan, status)
    VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

// ✅ Urutan tipe: i  i  s      i        s            d      i       d      s        s       s
$stmt->bind_param(
    "iisiididsss",
    $id_pesanan,   // i - INT
    $user_id,      // i - INT
    $nama_user,    // s - STRING
    $produk_id,    // i - INT
    $nama_produk,  // s - STRING
    $harga,        // d - DOUBLE
    $jumlah,       // i - INT
    $total,        // d - DOUBLE
    $alamat,       // s - STRING
    $catatan,      // s - STRING
    $status        // s - STRING
);

if ($stmt->execute()) {
    $stmt->close();

    // Kurangi stok
    if ($produk_id > 0) {
        $updStok = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id = ? AND stok >= ?");
        $updStok->bind_param("iii", $jumlah, $produk_id, $jumlah);
        $updStok->execute();
        $updStok->close();
    }

    // Tracking awal (cek dulu tabelnya ada)
    $cekTblTracking = $conn->query("SHOW TABLES LIKE 'pesanan_tracking'");
    if ($cekTblTracking && $cekTblTracking->num_rows > 0) {
        $keterangan = 'Pesanan berhasil dibuat dan menunggu konfirmasi penjual.';
        $trkStatus  = 'Proses';
        $trk = $conn->prepare("INSERT INTO pesanan_tracking (id_pesanan, status, keterangan) VALUES (?, ?, ?)");
        $trk->bind_param("iss", $id_pesanan, $trkStatus, $keterangan);
        $trk->execute();
        $trk->close();
    }

    // Buat transaksi pembayaran (cek dulu tabelnya ada)
    $cekTblTrx = $conn->query("SHOW TABLES LIKE 'transaksi'");
    if ($cekTblTrx && $cekTblTrx->num_rows > 0) {
        $kode_bayar   = 'PGZ' . strtoupper(substr(md5($id_pesanan . time()), 0, 6));
        $metode_bayar = 'Transfer Bank';
        $status_bayar = 'Menunggu';

        $trx = $conn->prepare("
            INSERT INTO transaksi (id_pesanan, user_id, jumlah_bayar, metode_bayar, status_bayar, kode_bayar)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $trx->bind_param("iidsss", $id_pesanan, $user_id, $total, $metode_bayar, $status_bayar, $kode_bayar);
        $trx->execute();
        $trx->close();

        header("Location: /transaksi?pesanan=$id_pesanan&baru=1");
    } else {
        header("Location: /dashboard?sukses=pesanan_dibuat&id=$id_pesanan");
    }
    exit;

} else {
    $err = urlencode($stmt->error);
    $stmt->close();
    header("Location: /dashboard?error=gagal_pesan&detail=$err");
    exit;
}
?>