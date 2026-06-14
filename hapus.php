<?php
session_start();
// Cek apakah user sudah login dan adalah admin
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['level'] != "admin") {
    header("Location: login.php?pesan=belum_login");
    exit;
}
include 'koneksi.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Ambil data penjualan yang akan dihapus
    $query_penjualan = $koneksi->query("SELECT nama_produk, jumlah_terjual, gambar_produk FROM penjualan WHERE id_penjualan = $id LIMIT 1");
    $penjualan = $query_penjualan->fetch_assoc();

    if ($penjualan) {
        $nama_file = $penjualan['gambar_produk'];
        $jumlah_penjualan = intval($penjualan['jumlah_terjual']);
        $nama_produk = $penjualan['nama_produk'];

        $koneksi->begin_transaction();

        // Restore stok ke inventory jika produk terkait ada
        $inventory_query = $koneksi->query("SELECT id_barang, jumlah_stok FROM tb_barang WHERE nama_barang = '" . $koneksi->real_escape_string($nama_produk) . "' LIMIT 1");
        if ($inventory_query && $inventory_query->num_rows > 0) {
            $inventory_row = $inventory_query->fetch_assoc();
            $id_barang = intval($inventory_row['id_barang']);

            $restore_stock_query = "UPDATE tb_barang SET jumlah_stok = jumlah_stok + $jumlah_penjualan WHERE id_barang = $id_barang";
            $restore_transaksi_query = "INSERT INTO tb_transaksi_stok (id_barang, jenis_transaksi, jumlah_perubahan, tanggal_transaksi, catatan) VALUES ($id_barang, 'MASUK', $jumlah_penjualan, NOW(), 'Restok dari penghapusan penjualan')";

            if (!$koneksi->query($restore_stock_query) || !$koneksi->query($restore_transaksi_query)) {
                $koneksi->rollback();
                echo "Error: " . $koneksi->error;
                exit;
            }
        }

        // Hapus file gambar dari folder uploads jika file-nya ada
        $path_file = "uploads/" . $nama_file;
        if (!empty($nama_file) && file_exists($path_file)) {
            unlink($path_file);
        }

        // Hapus data penjualan
        $query_hapus = "DELETE FROM penjualan WHERE id_penjualan = $id";
        if ($koneksi->query($query_hapus) === TRUE) {
            $koneksi->commit();
            echo "<script>alert('Data berhasil dihapus!'); window.location='index.php';</script>";
        } else {
            $koneksi->rollback();
            echo "Error: " . $koneksi->error;
        }
    } else {
        echo "<script>alert('Data tidak ditemukan.'); window.location='index.php';</script>";
    }
}
?>