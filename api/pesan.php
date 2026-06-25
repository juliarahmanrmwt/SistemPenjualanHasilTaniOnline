<?php
// ✅ __DIR__ memastikan path selalu benar di Vercel, tidak peduli dari folder mana diakses
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../koneksi.php';

// Tolak akses selain POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['proses_beli'])) {
    header("Location: /dashboard");
    exit;
}

// Data session
$user_id   = (int)($_SESSION['user_id'] ?? 0);
$nama_user = $_SESSION['nama'] ?? 'Pelanggan';

if ($user_id === 0) {
    header("Location: /login");
    exit;
}

// Sanitasi input
$nama_produk = trim($_POST['nama_produk'] ?? '');
$harga       = (float)($_POST['harga']    ?? 0);
$jumlah      = max(1, (int)($_POST['jumlah'] ?? 1));
$alamat      = trim($_POST['alamat']      ?? '');
$catatan     = trim($_POST['catatan']     ?? '');
$produk_id   = (int)($_POST['produk_id'] ?? 0);
$total       = $harga * $jumlah;
$status      = 'Proses';

// Validasi wajib
if (empty($nama_produk) || $harga <= 0 || empty($alamat)) {
    header("Location: /dashboard?error=data_tidak_lengkap");
    exit;
}

// Cek stok produk (jika dari DB)
if ($produk_id > 0) {
    $cekStok = $conn->prepare("SELECT stok FROM produk WHERE id = ? AND status = 'aktif' LIMIT 1");
    $cekStok->bind_param("i", $produk_id);
    $cekStok->execute();
    $stokRow = $cekStok->get_result()->fetch_assoc();
    $cekStok->close();

    if (!$stokRow || $stokRow['stok'] < $jumlah) {
        header("Location: /dashboard?error=stok_habis");
        exit;
    }
}

// Generate id_pesanan INT unik
do {
    $id_pesanan = rand(100000, 999999);
    $cekId = $conn->prepare("SELECT id_pesanan FROM pesanan WHERE id_pesanan = ? LIMIT 1");
    $cekId->bind_param("i", $id_pesanan);
    $cekId->execute();
    $cekId->store_result();
    $ada = $cekId->num_rows > 0;
    $cekId->close();
} while ($ada);

// ✅ INSERT — bind_param: i=int, s=string, d=double
// urutan: id_pesanan(i), user_id(i), nama_pembeli(s), produk_id(i),
//         nama_produk(s), harga(d), jumlah(i), total(d),
//         alamat(s), catatan(s), status(s)
$types = "iisiididss" . "s"; // 11 karakter = 11 kolom
$stmt  = $conn->prepare("
    INSERT INTO pesanan
        (id_pesanan, user_id, nama_pembeli, produk_id, nama_produk,
         harga, jumlah, total, alamat_pengiriman, catatan, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    $types,
    $id_pesanan,
    $user_id,
    $nama_user,
    $produk_id,
    $nama_produk,
    $harga,
    $jumlah,
    $total,
    $alamat,
    $catatan,
    $status
);

if ($stmt->execute()) {
    $stmt->close();

    // Kurangi stok
    if ($produk_id > 0) {
        $upd = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id = ? AND stok >= ?");
        $upd->bind_param("iii", $jumlah, $produk_id, $jumlah);
        $upd->execute();
        $upd->close();
    }

    // Tambah tracking awal (jika tabel ada)
    $cekTrk = $conn->query("SHOW TABLES LIKE 'pesanan_tracking'");
    if ($cekTrk && $cekTrk->num_rows > 0) {
        $ket = 'Pesanan berhasil dibuat dan menunggu konfirmasi penjual.';
        $st  = 'Proses';
        $trk = $conn->prepare("INSERT INTO pesanan_tracking (id_pesanan, status, keterangan) VALUES (?, ?, ?)");
        $trk->bind_param("iss", $id_pesanan, $st, $ket);
        $trk->execute();
        $trk->close();
    }

    // Buat transaksi pembayaran (jika tabel ada)
    $cekTrx = $conn->query("SHOW TABLES LIKE 'transaksi'");
    if ($cekTrx && $cekTrx->num_rows > 0) {
        $kode   = 'PGZ' . strtoupper(substr(md5($id_pesanan . time()), 0, 6));
        $metode = 'Transfer Bank';
        $stByr  = 'Menunggu';
        $trx = $conn->prepare("
            INSERT INTO transaksi (id_pesanan, user_id, jumlah_bayar, metode_bayar, status_bayar, kode_bayar)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $trx->bind_param("iidsss", $id_pesanan, $user_id, $total, $metode, $stByr, $kode);
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