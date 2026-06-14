<?php
// Memulai session
session_start();

// Cek apakah user sudah login dan adalah admin
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['level'] != "admin") {
    header("Location: login.php?pesan=belum_login");
    exit;
}

include 'koneksi.php';

// Query inventory
$barang_query = $koneksi->query("SELECT * FROM tb_barang ORDER BY nama_barang ASC");
$barang_list = [];
while ($row = $barang_query->fetch_assoc()) {
    $barang_list[] = $row;
}

$inventory_summary = $koneksi->query("SELECT COUNT(id_barang) AS total_items, COALESCE(SUM(jumlah_stok),0) AS total_stock, SUM(CASE WHEN jumlah_stok <= COALESCE(batas_minimum_stok,0) THEN 1 ELSE 0 END) AS low_stock_count FROM tb_barang");
$summary = $inventory_summary->fetch_assoc();

$transaksi_query = $koneksi->query("SELECT ts.*, b.kode_barang, b.nama_barang FROM tb_transaksi_stok ts LEFT JOIN tb_barang b ON ts.id_barang = b.id_barang ORDER BY ts.tanggal_transaksi DESC LIMIT 20");
$transaksi_list = [];
while ($row = $transaksi_query->fetch_assoc()) {
    $transaksi_list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Sweet Bakery</title>
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
        .stat-card,
        .card-modern {
            border: none;
            border-radius: 24px;
            background: #ffffff;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
        }
        .stat-card {
            padding: 22px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 22px 45px rgba(15, 23, 42, 0.1);
        }
        .stat-card .label {
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.75rem;
            font-weight: 700;
            color: #6b7280;
        }
        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            margin-top: 0.45rem;
        }
        .icon-box {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }
        .badge-soft {
            display: inline-block;
            padding: 0.55rem 0.95rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            color: #f8fafc;
        }
        .table thead th {
            background-color: #111827;
            color: #f8fafc;
            border: none;
            padding: 16px 18px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .table tbody tr:hover {
            background-color: #f8fafc;
        }
        .table td {
            padding: 14px 16px;
            vertical-align: middle;
            color: #475569;
        }
        .badge-status {
            font-size: 0.8rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
        }
        .badge-low {
            background: #fee2e2;
            color: #b91c1c;
        }
        @media (max-width: 991px) {
            .dashboard-sidebar {
                position: relative;
                width: 100%;
                height: auto;
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
            <p class="mb-4 text-sm">Kelola stock, supplier, dan transaksi inventory dengan mudah.</p>
            <nav class="nav-section">
                <a href="dashboard.php"><span class="sidebar-icon"><i class="bi bi-speedometer2"></i></span>Dashboard</a>
                <a href="index.php"><span class="sidebar-icon"><i class="bi bi-table"></i></span>Kelola Data</a>
                <a href="inventory.php" class="active"><span class="sidebar-icon"><i class="bi bi-boxes"></i></span>Inventory</a>
                <!-- 'Tambah Penjualan' menu removed -->
                <a href="home.php" target="_blank"><span class="sidebar-icon"><i class="bi bi-shop"></i></span>Lihat Toko Online</a>
                <a href="logout.php" onclick="return confirm('Logout?')"><span class="sidebar-icon"><i class="bi bi-box-arrow-right"></i></span>Logout</a>
            </nav>
        </aside>

        <main class="dashboard-main">
            <div class="page-header mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                    <div>
                        <p class="mb-2 text-white opacity-80">Fitur inventory sudah aktif.</p>
                        <h1 class="fw-bold">Manajemen Inventory</h1>
                        <p class="mb-0">Lihat persediaan barang, stok rendah, dan riwayat transaksi stok.</p>
                    </div>
                    <div class="text-end">
                        <a href="inventory_add.php" class="btn btn-light btn-sm mb-2"><i class="bi bi-plus-lg"></i> Tambah Item</a>
                        <span class="badge-soft">Inventory System</span>
                        <div class="mt-3 text-white opacity-80">Data diambil langsung dari tabel inventory.</div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-4 col-md-6">
                    <div class="stat-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="label">Total Produk</div>
                                <div class="value"><?php echo $summary['total_items'] ?? 0; ?></div>
                            </div>
                            <div class="icon-box bg-blue text-white"><i class="bi bi-basket3"></i></div>
                        </div>
                        <p class="mb-0 text-muted">Semua produk yang tercatat dalam inventory.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="stat-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="label">Total Stok</div>
                                <div class="value"><?php echo number_format($summary['total_stock'] ?? 0, 0, ',', '.'); ?></div>
                            </div>
                            <div class="icon-box bg-green text-white"><i class="bi bi-stack"></i></div>
                        </div>
                        <p class="mb-0 text-muted">Jumlah unit stok sekarang.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="stat-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="label">Stok Rendah</div>
                                <div class="value"><?php echo $summary['low_stock_count'] ?? 0; ?></div>
                            </div>
                            <div class="icon-box bg-red text-white"><i class="bi bi-exclamation-triangle"></i></div>
                        </div>
                        <p class="mb-0 text-muted">Produk yang perlu direstock segera.</p>
                    </div>
                </div>
            </div>

            <div class="card-modern mb-4">
                <div class="card-header">
                    <h3>Daftar Inventory</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Produk</th>
                                    <th>Kategori</th>
                                    <th>Supplier</th>
                                    <th>Stok</th>
                                    <th>Satuan</th>
                                    <th>Harga Jual</th>
                                    <th>Status</th>
                                    <th class="text-center" style="width: 120px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($barang_list) === 0): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">Belum ada data inventory.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($barang_list as $barang): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($barang['kode_barang']); ?></td>
                                            <td><?php echo htmlspecialchars($barang['nama_barang']); ?></td>
                                            <td><?php echo htmlspecialchars($barang['kategori']); ?></td>
                                            <td><?php echo htmlspecialchars($barang['supplier']); ?></td>
                                            <td><?php echo number_format($barang['jumlah_stok'] ?? 0, 0, ',', '.'); ?></td>
                                            <td><?php echo htmlspecialchars($barang['satuan']); ?></td>
                                            <td>Rp <?php echo number_format($barang['harga_jual'] ?? 0, 0, ',', '.'); ?></td>
                                            <td>
                                                <?php if ($barang['jumlah_stok'] !== null && $barang['jumlah_stok'] <= ($barang['batas_minimum_stok'] ?? 0)): ?>
                                                    <span class="badge-status badge-low">Rendah</span>
                                                <?php else: ?>
                                                    <span class="badge-status bg-success text-white">Aman</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="inventory_edit.php?id=<?php echo $barang['id_barang']; ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                                <a href="inventory_delete.php?id=<?php echo $barang['id_barang']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin menghapus item inventory ini?')"><i class="bi bi-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card-modern">
                <div class="card-header">
                    <h3>Riwayat Transaksi Stok</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Kode Produk</th>
                                    <th>Nama Produk</th>
                                    <th>Jenis</th>
                                    <th>Jumlah</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($transaksi_list) === 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Belum ada transaksi stok.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($transaksi_list as $tx): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($tx['tanggal_transaksi']); ?></td>
                                            <td><?php echo htmlspecialchars($tx['kode_barang']); ?></td>
                                            <td><?php echo htmlspecialchars($tx['nama_barang']); ?></td>
                                            <td><?php echo htmlspecialchars($tx['jenis_transaksi']); ?></td>
                                            <td><?php echo number_format($tx['jumlah_perubahan'], 0, ',', '.'); ?></td>
                                            <td><?php echo htmlspecialchars($tx['catatan']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
