<?php
session_start();

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['level'] != "admin") {
    header("Location: login.php?pesan=belum_login");
    exit;
}

include 'koneksi.php';

if (!isset($_GET['id'])) {
    header('Location: inventory.php');
    exit;
}

$id = intval($_GET['id']);

$query = $koneksi->query("SELECT * FROM tb_barang WHERE id_barang = $id LIMIT 1");
$item = $query->fetch_assoc();

if ($item) {
    $delete_query = "DELETE FROM tb_barang WHERE id_barang = $id";
    if ($koneksi->query($delete_query) === TRUE) {
        echo "<script>alert('Item inventory berhasil dihapus.'); window.location='inventory.php';</script>";
        exit;
    } else {
        echo "Error: " . $koneksi->error;
        exit;
    }
}

header('Location: inventory.php');
exit;
?>