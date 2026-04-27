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

    $query = "INSERT INTO pesanan (nama_pembeli, nama_produk, harga, status) 
              VALUES ('$nama_user', '$nama_produk', '$harga', '$status')";

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