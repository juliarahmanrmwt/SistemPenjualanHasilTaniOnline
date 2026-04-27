<?php
session_start();

// 1. KOREKSI: Keluar Proses, Masuk Server untuk koneksi
include 'koneksi.php'; 

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') { 
    exit("Akses Ditolak"); 
}

// --- PROSES TAMBAH USER ---
if (isset($_POST['tambah_user'])) {
    // 2. KOREKSI: Pastikan kunci sesuai name="nama_lengkap" di form
    $nama  = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role  = $_POST['role'];
    $pass  = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (nama_lengkap, email, password, role) VALUES ('$nama', '$email', '$pass', '$role')";
    
    if (mysqli_query($conn, $sql)) {
        // 3. KOREKSI: Keluar Proses, Masuk Server untuk redirect
        header("Location: ../api/admin_dashboard.php?status=sukses_tambah");
        exit;
    } else {
        echo "Error: " . mysqli_error($conn);
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
        header("Location: ../api/admin_dashboard.php?status=sukses_edit");
        exit;
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

// --- PROSES HAPUS USER ---
if (isset($_GET['hapus_user'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus_user']);
    mysqli_query($conn, "DELETE FROM users WHERE id='$id'");
    header("Location: ../api/admin_dashboard.php?status=sukses_hapus");
    exit;
}
?>