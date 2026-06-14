<?php
session_start();

// Cek apakah user sudah login dan adalah admin
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['level'] != "admin") {
    header("Location: login.php?pesan=belum_login");
    exit;
}

include 'koneksi.php';

if (isset($_POST['update'])) {
    $id             = intval($_POST['id']);
    $tanggal        = $koneksi->real_escape_string($_POST['tanggal']);
    $nama_produk    = $koneksi->real_escape_string($_POST['nama_produk']);
    $jumlah_terjual = intval($_POST['jumlah_terjual']);
    $harga_satuan   = intval($_POST['harga_satuan']);
    
    // Kalkulasi Total Penjualan
    $total_penjualan = $jumlah_terjual * $harga_satuan;

    // Ambil data lama
    $query_lama = $koneksi->query("SELECT * FROM penjualan WHERE id_penjualan = $id LIMIT 1");
    $data_lama = $query_lama->fetch_assoc();

    if (!$data_lama) {
        echo "<script>alert('Data penjualan tidak ditemukan!'); window.location='index.php';</script>";
        exit;
    }

    $old_nama_produk = $data_lama['nama_produk'];
    $old_qty         = intval($data_lama['jumlah_terjual']);
    $gambar_lama     = $data_lama['gambar_produk'];

    // Handle File Upload jika ada gambar baru
    $nama_file_baru = $gambar_lama; // default gunakan gambar lama
    $upload_baru = false;

    if (isset($_FILES['gambar_produk']) && is_uploaded_file($_FILES['gambar_produk']['tmp_name'])) {
        if ($_FILES['gambar_produk']['error'] === UPLOAD_ERR_OK) {
            $tmp_file = $_FILES['gambar_produk']['tmp_name'];
            $nama_file = basename($_FILES['gambar_produk']['name']);
            $file_size = $_FILES['gambar_produk']['size'];
            $max_size = 5 * 1024 * 1024; // 5MB

            // Validasi tipe file
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $detected_type = mime_content_type($tmp_file);
            if (!in_array($detected_type, $allowed_types)) {
                echo "<script>alert('Tipe file gambar tidak didukung! Hanya JPG/JPEG/PNG/GIF.'); window.location='edit.php?id=$id';</script>";
                exit;
            }

            if ($file_size > $max_size) {
                echo "<script>alert('Ukuran file terlalu besar. Maksimum 5MB.'); window.location='edit.php?id=$id';</script>";
                exit;
            }

            // Rename file baru
            $nama_file_baru = time() . "_" . preg_replace('/[^A-Za-z0-9._-]/', '_', $nama_file);
            $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
            $path_full = $upload_dir . $nama_file_baru;

            if (move_uploaded_file($tmp_file, $path_full)) {
                $upload_baru = true;
            } else {
                echo "<script>alert('Gagal mengunggah gambar baru!'); window.location='edit.php?id=$id';</script>";
                exit;
            }
        }
    }

    // Mulai Transaksi
    $koneksi->begin_transaction();

    try {
        // Logika Update Stok Inventory
        if ($nama_produk == $old_nama_produk) {
            // Jika produk sama, hitung selisih qty
            $selisih = $jumlah_terjual - $old_qty;

            if ($selisih != 0) {
                $barang_query = $koneksi->query("SELECT id_barang, jumlah_stok FROM tb_barang WHERE nama_barang = '$nama_produk' LIMIT 1");
                if ($barang_query && $barang_query->num_rows > 0) {
                    $barang = $barang_query->fetch_assoc();
                    $id_barang = intval($barang['id_barang']);
                    $stok_sekarang = intval($barang['jumlah_stok']);

                    if ($selisih > 0) {
                        // Kebutuhan stok bertambah, kurangi dari inventory
                        if ($stok_sekarang < $selisih) {
                            throw new Exception("Stok untuk produk $nama_produk tidak mencukupi untuk penambahan ini. Sisa stok: $stok_sekarang.");
                        }
                        $koneksi->query("UPDATE tb_barang SET jumlah_stok = jumlah_stok - $selisih WHERE id_barang = $id_barang");
                        $koneksi->query("INSERT INTO tb_transaksi_stok (id_barang, jenis_transaksi, jumlah_perubahan, tanggal_transaksi, catatan) 
                                         VALUES ($id_barang, 'KELUAR', $selisih, NOW(), 'Penyesuaian penjualan (Edit Qty bertambah)')");
                    } else {
                        // Kebutuhan stok berkurang, kembalikan ke inventory
                        $kembali = abs($selisih);
                        $koneksi->query("UPDATE tb_barang SET jumlah_stok = jumlah_stok + $kembali WHERE id_barang = $id_barang");
                        $koneksi->query("INSERT INTO tb_transaksi_stok (id_barang, jenis_transaksi, jumlah_perubahan, tanggal_transaksi, catatan) 
                                         VALUES ($id_barang, 'MASUK', $kembali, NOW(), 'Penyesuaian penjualan (Edit Qty berkurang)')");
                    }
                }
            }
        } else {
            // Jika produk berubah:
            // 1. Kembalikan stok produk lama
            $old_barang_query = $koneksi->query("SELECT id_barang FROM tb_barang WHERE nama_barang = '$old_nama_produk' LIMIT 1");
            if ($old_barang_query && $old_barang_query->num_rows > 0) {
                $old_barang = $old_barang_query->fetch_assoc();
                $id_barang_lama = intval($old_barang['id_barang']);
                $koneksi->query("UPDATE tb_barang SET jumlah_stok = jumlah_stok + $old_qty WHERE id_barang = $id_barang_lama");
                $koneksi->query("INSERT INTO tb_transaksi_stok (id_barang, jenis_transaksi, jumlah_perubahan, tanggal_transaksi, catatan) 
                                 VALUES ($id_barang_lama, 'MASUK', $old_qty, NOW(), 'Kembalikan stok (Edit ganti produk)')");
            }

            // 2. Kurangi stok produk baru
            $new_barang_query = $koneksi->query("SELECT id_barang, jumlah_stok FROM tb_barang WHERE nama_barang = '$nama_produk' LIMIT 1");
            if ($new_barang_query && $new_barang_query->num_rows > 0) {
                $new_barang = $new_barang_query->fetch_assoc();
                $id_barang_baru = intval($new_barang['id_barang']);
                $stok_baru = intval($new_barang['jumlah_stok']);

                if ($stok_baru < $jumlah_terjual) {
                    throw new Exception("Stok untuk produk baru $nama_produk tidak mencukupi. Sisa stok: $stok_baru.");
                }

                $koneksi->query("UPDATE tb_barang SET jumlah_stok = jumlah_stok - $jumlah_terjual WHERE id_barang = $id_barang_baru");
                $koneksi->query("INSERT INTO tb_transaksi_stok (id_barang, jenis_transaksi, jumlah_perubahan, tanggal_transaksi, catatan) 
                                 VALUES ($id_barang_baru, 'KELUAR', $jumlah_terjual, NOW(), 'Potong stok (Edit ganti produk)')");
            }
        }

        // Jalankan Update Penjualan
        $update_query = "UPDATE penjualan SET 
                            tanggal = '$tanggal', 
                            nama_produk = '$nama_produk', 
                            gambar_produk = '$nama_file_baru', 
                            jumlah_terjual = $jumlah_terjual, 
                            harga_satuan = $harga_satuan, 
                            total_penjualan = $total_penjualan 
                         WHERE id_penjualan = $id";

        if (!$koneksi->query($update_query)) {
            throw new Exception("Gagal memperbarui database: " . $koneksi->error);
        }

        // Commit transaksi jika sukses semua
        $koneksi->commit();

        // Hapus file gambar lama jika berhasil upload gambar baru
        if ($upload_baru && !empty($gambar_lama)) {
            $path_lama = "uploads/" . $gambar_lama;
            if (file_exists($path_lama)) {
                unlink($path_lama);
            }
        }

        echo "<script>alert('Data penjualan berhasil diperbarui!'); window.location='index.php';</script>";
        exit;

    } catch (Exception $e) {
        $koneksi->rollback();
        // Hapus file baru yang gagal di-commit
        if ($upload_baru && file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $nama_file_baru)) {
            unlink(__DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $nama_file_baru);
        }
        echo "<script>alert('" . $e->getMessage() . "'); window.location='edit.php?id=$id';</script>";
        exit;
    }
} else {
    header('Location: index.php');
}
?>
