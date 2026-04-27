<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php'; 

if (!isset($_SESSION['login'])) {
    header("Location: /login");
    exit;
}

if (isset($_POST['proses_beli'])) {
    $nama_user = $_SESSION['nama'] ?? 'Pelanggan';
    
    $nama_produk = mysqli_real_escape_string($conn, $_POST['nama_produk']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    $status = "Proses";

    // PERBAIKAN: Membuat ID Pesanan berupa angka acak 6 digit karena database tidak Auto Increment
    $id_pesanan_baru = rand(100000, 999999);

    // PERBAIKAN: Memasukkan variabel $id_pesanan_baru ke dalam query SQL
    $query = "INSERT INTO pesanan (id_pesanan, nama_pembeli, nama_produk, harga, status) 
              VALUES ('$id_pesanan_baru', '$nama_user', '$nama_produk', '$harga', '$status')";

    if (mysqli_query($conn, $query)) {
        echo "<script>
                alert('Pesanan $nama_produk berhasil dibuat!');
                window.location='/user/dashboard';
              </script>";
    } else {
        echo "Gagal memesan: " . mysqli_error($conn);
    }
} else {
    header("Location: /user/dashboard");
    exit;
}
?>