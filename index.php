<?php
// Memulai session
session_start();

// Cek apakah user sudah login dan adalah admin
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['level'] != "admin") {
    header("Location: login.php?pesan=belum_login");
    exit;
}

// Memanggil file koneksi
include 'koneksi.php';

// Ringkasan metrik untuk halaman index
$summaryQuery = $koneksi->query("SELECT COUNT(id_penjualan) AS total_data, SUM(jumlah_terjual) AS total_qty, SUM(total_penjualan) AS total_income, COUNT(DISTINCT nama_produk) AS produk_unik FROM penjualan");
$summaryData = $summaryQuery->fetch_assoc();
$total_data = $summaryData['total_data'] ?? 0;
$total_qty = $summaryData['total_qty'] ?? 0;
$total_income = $summaryData['total_income'] ?? 0;
$produk_unik = $summaryData['produk_unik'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penjualan - Sweet Bakery</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f6fb;
            color: #334155;
        }

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: #0f172a;
            color: #e2e8f0;
            padding: 32px 24px;
            position: fixed;
            inset: 0 auto 0 0;
            box-shadow: 8px 0 32px rgba(15, 23, 42, 0.15);
        }

        .sidebar .brand {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .sidebar .brand span {
            color: #fbbf24;
        }

        .sidebar p {
            color: #94a3b8;
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .sidebar .nav-link {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            color: #cbd5e1;
            padding: 12px 16px;
            border-radius: 14px;
            margin-bottom: 0.6rem;
            text-decoration: none;
            transition: background 0.25s ease, color 0.25s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(248, 250, 252, 0.08);
            color: #ffffff;
        }

        .main-content {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 32px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .topbar .page-title {
            font-size: 1.85rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .topbar .page-desc {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 0;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.8rem;
        }

        .summary-card {
            background: #ffffff;
            border-radius: 22px;
            padding: 24px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
            border: 1px solid rgba(15, 23, 42, 0.05);
        }

        .summary-card .label {
            font-size: 0.85rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.8rem;
        }

        .summary-card .value {
            font-size: 1.95rem;
            font-weight: 700;
            color: #0f172a;
        }

        .summary-card .note {
            margin-top: 0.7rem;
            color: #94a3b8;
            font-size: 0.92rem;
        }

        .card-modern {
            border: none;
            border-radius: 24px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .card-modern .card-header {
            background: #ffffff;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            padding: 24px;
        }

        .card-modern .card-header h3 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
        }

        .card-modern .card-body {
            padding: 0;
        }

        .table thead th {
            background-color: #334155;
            color: #f8fafc;
            border: none;
            padding: 16px 18px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .table tbody tr {
            transition: background 0.18s ease;
        }

        .table tbody tr:hover {
            background-color: #f8fafc;
        }

        .table td {
            padding: 16px 18px;
            vertical-align: middle;
            color: #475569;
        }

        .img-thumbnail {
            border-radius: 12px;
            width: 60px;
            height: 60px;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }

        .btn-sm {
            padding: 6px 14px;
            border-radius: 18px;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .btn-edit {
            background-color: #f59e0b;
            border: none;
            color: #0f172a;
        }
        .btn-edit:hover { background-color: #d97706; color: #ffffff; }

        .btn-delete {
            background-color: #ef4444;
            border: none;
            color: #ffffff;
        }
        .btn-delete:hover { background-color: #dc2626; }

        .action-bar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .action-bar .badge {
            font-size: 0.85rem;
            padding: 0.7rem 0.95rem;
            border-radius: 999px;
        }

        @media (max-width: 1199px) {
            .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767px) {
            .dashboard-layout {
                display: block;
            }
            .sidebar {
                position: relative;
                width: 100%;
                box-shadow: none;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 24px;
            }
            .summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="brand"><span>Sweet</span> Bakery</div>
        <p>Kelola transaksi roti dengan tampilan profesional dan data yang mudah diakses.</p>

        <a href="dashboard.php" class="nav-link">
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>
        <a href="index.php" class="nav-link active">
            <i class="bi bi-table"></i>
            Kelola Data
        </a>
        <a href="inventory.php" class="nav-link">
            <i class="bi bi-boxes"></i>
            Inventory
        </a>
        <!-- 'Tambah Penjualan' menu removed -->
        <a href="logout.php" class="nav-link" onclick="return confirm('Yakin logout?')">
            <i class="bi bi-box-arrow-right"></i>
            Logout
        </a>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <div>
                <h1 class="page-title">Data Penjualan</h1>
                <p class="page-desc">Kelola segala transaksi dan pantau performa penjualan roti Anda.</p>
            </div>
            <div>
                <a href="tambah.php" class="btn btn-primary px-4 py-2" style="border-radius: 14px; font-weight: 600;"><i class="bi bi-plus-lg"></i> Tambah Penjualan</a>
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-card">
                <div class="label">Total Transaksi</div>
                <div class="value"><?php echo $total_data; ?></div>
                <div class="note">Semua entri penjualan yang tersimpan.</div>
            </div>
            <div class="summary-card">
                <div class="label">Jumlah Produk Terjual</div>
                <div class="value"><?php echo $total_qty; ?></div>
                <div class="note">Volume produk yang telah keluar.</div>
            </div>
            <div class="summary-card">
                <div class="label">Total Pendapatan</div>
                <div class="value">Rp <?php echo number_format($total_income, 0, ',', '.'); ?></div>
                <div class="note">Nilai penjualan kotor semua transaksi.</div>
            </div>
            <div class="summary-card">
                <div class="label">Produk Unik</div>
                <div class="value"><?php echo $produk_unik; ?></div>
                <div class="note">Jenis roti dan kue yang terjual.</div>
            </div>
        </div>

        <div class="card card-modern">
            <div class="card-header">
                <h3>Daftar Penjualan Terbaru</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">No</th>
                                <th style="width: 130px;">Tanggal</th>
                                <th>Produk</th>
                                <th style="width: 100px;">Gambar</th>
                                <th class="text-center" style="width: 90px;">Jml</th>
                                <th style="width: 140px;">Harga</th>
                                <th style="width: 140px;">Total</th>
                                <th class="text-center" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $query = $koneksi->query("SELECT * FROM penjualan ORDER BY tanggal DESC");
                            
                            if ($query->num_rows == 0) {
                                echo '<tr><td colspan="8" class="text-center py-5 text-muted">Belum ada data penjualan.</td></tr>';
                            } else {
                                while ($data = $query->fetch_assoc()) {
                            ?>
                            <tr>
                                <td class="text-center text-muted"><?php echo $no++; ?></td>
                                <td>
                                    <div class="fw-semibold"><?php echo date('d M Y', strtotime($data['tanggal'])); ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?php echo $data['nama_produk']; ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($data['gambar_produk'])): ?>
                                    <img src="uploads/<?php echo $data['gambar_produk']; ?>" alt="<?php echo $data['nama_produk']; ?>" class="img-thumbnail">
                                    <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; border-radius: 14px; background: #f1f5f9; color: #94a3b8;">
                                        <i class="bi bi-image fs-5"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill"><?php echo $data['jumlah_terjual']; ?></span>
                                </td>
                                <td>Rp <?php echo number_format($data['harga_satuan'], 0, ',', '.'); ?></td>
                                <td class="fw-bold text-success">Rp <?php echo number_format($data['total_penjualan'], 0, ',', '.'); ?></td>
                                <td class="text-center">
                                    <a href="edit.php?id=<?php echo $data['id_penjualan']; ?>" class="btn btn-sm btn-edit" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                    <a href="hapus.php?id=<?php echo $data['id_penjualan']; ?>" class="btn btn-sm btn-delete" title="Hapus" onclick="return confirm('Yakin hapus data ini?')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php 
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
