<?php
session_start();

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['level'] != "admin") {
    header("Location: login.php?pesan=belum_login");
    exit;
}

include 'koneksi.php';

$message = '';

if (isset($_POST['simpan'])) {
    $kode_barang = $koneksi->real_escape_string($_POST['kode_barang']);
    $nama_barang = $koneksi->real_escape_string($_POST['nama_barang']);
    $kategori = $koneksi->real_escape_string($_POST['kategori']);
    $supplier = $koneksi->real_escape_string($_POST['supplier']);
    $jumlah_stok = intval($_POST['jumlah_stok']);
    $satuan = $koneksi->real_escape_string($_POST['satuan']);
    $harga_beli = floatval($_POST['harga_beli']);
    $harga_jual = floatval($_POST['harga_jual']);
    $batas_minimum_stok = intval($_POST['batas_minimum_stok']);
    $lokasi_gudang = $koneksi->real_escape_string($_POST['lokasi_gudang']);

    $koneksi->begin_transaction();

    $insert_query = "INSERT INTO tb_barang (kode_barang, nama_barang, kategori, supplier, jumlah_stok, satuan, harga_beli, harga_jual, batas_minimum_stok, lokasi_gudang, created_at, updated_at) VALUES ('$kode_barang', '$nama_barang', '$kategori', '$supplier', $jumlah_stok, '$satuan', $harga_beli, $harga_jual, $batas_minimum_stok, '$lokasi_gudang', NOW(), NOW())";

    if ($koneksi->query($insert_query) === TRUE) {
        $id_barang = $koneksi->insert_id;
        
        if ($jumlah_stok > 0) {
            $insert_transaksi_query = "INSERT INTO tb_transaksi_stok (id_barang, jenis_transaksi, jumlah_perubahan, tanggal_transaksi, catatan) VALUES ($id_barang, 'MASUK', $jumlah_stok, NOW(), 'Stok awal barang baru')";
            if (!$koneksi->query($insert_transaksi_query)) {
                $koneksi->rollback();
                $message = 'Error saat menyimpan log transaksi stok: ' . $koneksi->error;
            } else {
                $koneksi->commit();
                header('Location: inventory.php');
                exit;
            }
        } else {
            $koneksi->commit();
            header('Location: inventory.php');
            exit;
        }
    } else {
        $koneksi->rollback();
        $message = 'Error saat menyimpan data inventory: ' . $koneksi->error;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Inventory - Sweet Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #eff2f7;
            color: #374151;
        }
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }
        .dashboard-sidebar {
            width: 270px;
            background: #111827;
            color: #f8fafc;
            padding: 32px 20px;
            box-shadow: 6px 0 30px rgba(15, 23, 42, 0.18);
            position: fixed;
            inset: 0 auto 0 0;
            overflow-y: auto;
        }
        .dashboard-sidebar .brand {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            margin-bottom: 1.5rem;
        }
        .dashboard-sidebar .brand span {
            color: #fbbf24;
        }
        .dashboard-sidebar .nav-section a {
            display: flex;
            align-items: center;
            gap: 0.95rem;
            color: #d1d5db;
            text-decoration: none;
            padding: 14px 16px;
            border-radius: 16px;
            margin-bottom: 10px;
            transition: background 0.25s ease, color 0.25s ease;
        }
        .dashboard-sidebar .nav-section a:hover,
        .dashboard-sidebar .nav-section a.active {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
        }
        .dashboard-main {
            margin-left: 270px;
            width: calc(100% - 270px);
            padding: 32px 32px 48px;
        }
        .page-header {
            background: linear-gradient(135deg, #4f46e5 0%, #ec4899 100%);
            color: #ffffff;
            border-radius: 28px;
            padding: 30px;
            box-shadow: 0 24px 50px rgba(79, 70, 229, 0.16);
        }
        .form-card {
            border-radius: 24px;
            background: #ffffff;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
            padding: 28px;
        }
        .form-label {
            font-weight: 600;
            color: #374151;
        }
        .btn-group-custom {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        .btn-custom {
            min-width: 160px;
        }
        @media (max-width: 991px) {
            .dashboard-sidebar {
                width: 100%;
                position: relative;
                box-shadow: none;
            }
            .dashboard-main {
                margin-left: 0;
                width: 100%;
                padding-top: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="dashboard-sidebar">
            <div class="brand"><span>Sweet</span> Bakery</div>
            <p class="mb-4 text-sm">Kelola stock inventory dan tambahkan produk baru dengan mudah.</p>
            <nav class="nav-section">
                <a href="dashboard.php"><span class="sidebar-icon"><i class="bi bi-speedometer2"></i></span>Dashboard</a>
                <a href="index.php"><span class="sidebar-icon"><i class="bi bi-table"></i></span>Kelola Data</a>
                <a href="inventory.php"><span class="sidebar-icon"><i class="bi bi-boxes"></i></span>Inventory</a>
                <a href="inventory_add.php" class="active"><span class="sidebar-icon"><i class="bi bi-plus-square"></i></span>Tambah Inventory</a>
                <!-- 'Tambah Penjualan' menu removed -->
                <a href="home.php" target="_blank"><span class="sidebar-icon"><i class="bi bi-shop"></i></span>Lihat Toko Online</a>
                <a href="logout.php" onclick="return confirm('Logout?')"><span class="sidebar-icon"><i class="bi bi-box-arrow-right"></i></span>Logout</a>
            </nav>
        </aside>
        <main class="dashboard-main">
            <div class="page-header mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                    <div>
                        <p class="mb-2 text-white opacity-80">Tambah item baru ke inventory.</p>
                        <h1 class="fw-bold">Form Tambah Inventory</h1>
                        <p class="mb-0">Masukkan detail produk supaya stok bisa dilacak dengan benar.</p>
                    </div>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-danger"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="form-card">
                <form method="POST" action="" autocomplete="off">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Kode Barang</label>
                            <input type="text" name="kode_barang" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Barang</label>
                            <input type="text" name="nama_barang" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <input type="text" name="kategori" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Supplier</label>
                            <input type="text" name="supplier" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jumlah Stok</label>
                            <input type="number" name="jumlah_stok" class="form-control" min="0" value="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Satuan</label>
                            <input type="text" name="satuan" class="form-control" placeholder="pcs / kg" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Batas Minimum</label>
                            <input type="number" name="batas_minimum_stok" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Harga Beli</label>
                            <input type="number" step="0.01" name="harga_beli" class="form-control" min="0" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Harga Jual</label>
                            <input type="number" step="0.01" name="harga_jual" class="form-control" min="0" value="0.00" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Lokasi Gudang</label>
                            <input type="text" name="lokasi_gudang" class="form-control">
                        </div>
                    </div>
                    <div class="mt-4 btn-group-custom">
                        <button type="submit" name="simpan" class="btn btn-primary btn-custom">Simpan Inventory</button>
                        <a href="inventory.php" class="btn btn-outline-secondary btn-custom">Kembali ke Inventory</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
