<?php
// api/penjual/produk_hapus.php
require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__) . '/koneksi.php';

if ($_SESSION['role'] !== 'penjual') {
    header("Location: /user/dashboard"); exit;
}

$id_penjual = $_SESSION['user_id'];
$id_produk  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_produk > 0) {
    // Hapus hanya jika produk milik penjual ini
    $stmt = $koneksi->prepare("DELETE FROM produk WHERE id = ? AND id_penjual = ?");
    $stmt->bind_param("ii", $id_produk, $id_penjual);
    $stmt->execute();
}

header("Location: /penjual/dashboard?status=produk_dihapus");
exit;