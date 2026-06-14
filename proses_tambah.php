<?php
session_start();

// Cek apakah user sudah login dan adalah admin
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['level'] != "admin") {
    header("Location: login.php?pesan=belum_login");
    exit;
}

include 'koneksi.php';

if (isset($_POST['simpan'])) {
    $tanggal        = $koneksi->real_escape_string($_POST['tanggal']);
    $nama_produk    = $koneksi->real_escape_string($_POST['nama_produk']);
    $jumlah_terjual = intval($_POST['jumlah_terjual']);
    $harga_satuan   = intval($_POST['harga_satuan']);
    
    // Kalkulasi Total Penjualan
    $total_penjualan = $jumlah_terjual * $harga_satuan;

    // Proses Upload Gambar dengan validasi
    if (!isset($_FILES['gambar_produk']) || !is_uploaded_file($_FILES['gambar_produk']['tmp_name'])) {
        echo "<script>alert('Tidak ada file gambar yang diupload.'); window.location='tambah.php';</script>";
        exit;
    }

    if ($_FILES['gambar_produk']['error'] !== UPLOAD_ERR_OK) {
        $errCode = $_FILES['gambar_produk']['error'];
        echo "<script>alert('Gagal mengupload gambar. Error code: $errCode'); window.location='tambah.php';</script>";
        exit;
    }

    $tmp_file = $_FILES['gambar_produk']['tmp_name'];
    $nama_file = basename($_FILES['gambar_produk']['name']);
    $file_size = $_FILES['gambar_produk']['size'];
    $max_size = 5 * 1024 * 1024; // 5MB

    // Validasi tipe file
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $detected_type = mime_content_type($tmp_file);
    if (!in_array($detected_type, $allowed_types)) {
        echo "<script>alert('Tipe file tidak didukung. Hanya JPG/JPEG/PNG/GIF yang diperbolehkan.'); window.location='tambah.php';</script>";
        exit;
    }

    // Validasi ukuran
    if ($file_size > $max_size) {
        echo "<script>alert('Ukuran file terlalu besar. Maksimum 5MB.'); window.location='tambah.php';</script>";
        exit;
    }

    // Rename file agar unik
    $nama_file_baru = time() . "_" . preg_replace('/[^A-Za-z0-9._-]/', '_', $nama_file);
    $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $path_full = $upload_dir . $nama_file_baru;

    // Pindahkan file dari temporary ke folder uploads
    if (move_uploaded_file($tmp_file, $path_full)) {
        
        // Cek apakah produk ada di inventory
        $inventory_query = $koneksi->query("SELECT id_barang, jumlah_stok FROM tb_barang WHERE nama_barang = '$nama_produk' LIMIT 1");
        if ($inventory_query && $inventory_query->num_rows > 0) {
            $inventory_row = $inventory_query->fetch_assoc();
            $id_barang = intval($inventory_row['id_barang']);
            $stok_saat_ini = intval($inventory_row['jumlah_stok']);

            if ($stok_saat_ini < $jumlah_terjual) {
                // Delete uploaded image since process failed
                if (file_exists($path_full)) {
                    unlink($path_full);
                }
                echo "<script>alert('Stok untuk produk $nama_produk tidak mencukupi. Stok saat ini: $stok_saat_ini.'); window.location='tambah.php';</script>";
                exit;
            }

            // Jalankan transaksi database
            $koneksi->begin_transaction();
            
            $update_stock_query = "UPDATE tb_barang SET jumlah_stok = jumlah_stok - $jumlah_terjual WHERE id_barang = $id_barang";
            $insert_transaksi_query = "INSERT INTO tb_transaksi_stok (id_barang, jenis_transaksi, jumlah_perubahan, tanggal_transaksi, catatan) VALUES ($id_barang, 'KELUAR', $jumlah_terjual, NOW(), 'Penjualan admin (CRUD)')";
            $insert_penjualan_query = "INSERT INTO penjualan (tanggal, nama_produk, gambar_produk, jumlah_terjual, harga_satuan, total_penjualan) 
                  VALUES ('$tanggal', '$nama_produk', '$nama_file_baru', '$jumlah_terjual', '$harga_satuan', '$total_penjualan')";

            if ($koneksi->query($update_stock_query) && $koneksi->query($insert_transaksi_query) && $koneksi->query($insert_penjualan_query)) {
                $koneksi->commit();
                echo "<script>alert('Data penjualan berhasil ditambahkan!'); window.location='index.php';</script>";
                exit;
            } else {
                $koneksi->rollback();
                // Delete uploaded image since process failed
                if (file_exists($path_full)) {
                    unlink($path_full);
                }
                echo "Error: " . $koneksi->error;
                exit;
            }
        } else {
            // Jika produk tidak terdaftar di inventory
            $query = "INSERT INTO penjualan (tanggal, nama_produk, gambar_produk, jumlah_terjual, harga_satuan, total_penjualan) 
                      VALUES ('$tanggal', '$nama_produk', '$nama_file_baru', '$jumlah_terjual', '$harga_satuan', '$total_penjualan')";
                      
            if ($koneksi->query($query) === TRUE) {
                echo "<script>alert('Data berhasil ditambahkan!'); window.location='index.php';</script>";
            } else {
                if (file_exists($path_full)) {
                    unlink($path_full);
                }
                echo "Error: " . $koneksi->error;
            }
        }
    } else {
        echo "<script>alert('Gagal memindahkan file gambar!'); window.location='tambah.php';</script>";
    }
} else {
    header('Location: tambah.php');
}
?>
