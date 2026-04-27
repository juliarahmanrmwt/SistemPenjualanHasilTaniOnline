<?php
require_once __DIR__ . '/auth_check.php';
include 'koneksi.php'; // HARUS ADA agar $conn dikenali

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['proses_beli'])) {
    // Pastikan session 'id' dan 'nama' sudah diset saat login
    $user_id = $_SESSION['id'] ?? 0; 
    $nama_user = $_SESSION['nama'] ?? 'Pelanggan';
    
    // Mengamankan input dari user
    $nama_produk = mysqli_real_escape_string($conn, $_POST['nama_produk']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    $status = "Proses";

    // Simpan ke database
    $query = "INSERT INTO pesanan (nama_pembeli, nama_produk, harga, status) 
              VALUES ('$nama_user', '$nama_produk', '$harga', '$status')";

    if (mysqli_query($conn, $query)) {
        echo "<script>
                alert('Pesanan $nama_produk berhasil dibuat!');
                window.location='dashboard.php';
              </script>";
    } else {
        echo "Gagal memesan: " . mysqli_error($conn);
    }
} else {
    header("Location: dashboard.php");
    exit;
}
?>