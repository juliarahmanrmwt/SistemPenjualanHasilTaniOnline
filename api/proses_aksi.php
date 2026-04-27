<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/koneksi.php'; 

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') { 
    exit("Akses Ditolak"); 
}

// --- PROSES TAMBAH USER ---
if (isset($_POST['tambah_user'])) {
    $nama  = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role  = $_POST['role'];
    $pass  = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (nama_lengkap, email, password, role) VALUES ('$nama', '$email', '$pass', '$role')";
    if (mysqli_query($conn, $sql)) {
        header("Location: /admin/dashboard?status=sukses_tambah"); exit;
    }
}

// --- PROSES EDIT USER ---
if (isset($_POST['edit_user'])) {
    $id    = $_POST['id'];
    $nama  = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role  = $_POST['role'];

    $sql = "UPDATE users SET nama_lengkap='$nama', email='$email', role='$role' WHERE id='$id'";
    if (mysqli_query($conn, $sql)) {
        header("Location: /admin/dashboard?status=sukses_edit"); exit;
    }
}

// --- PROSES HAPUS USER ---
if (isset($_GET['hapus_user'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus_user']);
    mysqli_query($conn, "DELETE FROM users WHERE id='$id'");
    header("Location: /admin/dashboard?status=sukses_hapus"); exit;
}

// --- PROSES SELESAIKAN PESANAN (BARU) ---
if (isset($_GET['selesai_pesanan'])) {
    $id = mysqli_real_escape_string($conn, $_GET['selesai_pesanan']);
    mysqli_query($conn, "UPDATE pesanan SET status='Selesai' WHERE id_pesanan='$id'");
    header("Location: /admin/dashboard?status=pesanan_update"); exit;
}

// --- PROSES HAPUS PESANAN (BARU) ---
if (isset($_GET['hapus_pesanan'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus_pesanan']);
    mysqli_query($conn, "DELETE FROM pesanan WHERE id_pesanan='$id'");
    header("Location: /admin/dashboard?status=pesanan_hapus"); exit;
}
?>