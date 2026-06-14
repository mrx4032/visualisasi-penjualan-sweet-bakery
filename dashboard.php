<?php
// Memulai session
session_start();

// Cek apakah user sudah login dan adalah admin
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['level'] != "admin") {
    header("Location: login.php?pesan=belum_login");
    exit;
}

// Memanggil file koneksi database
include 'koneksi.php';

// 1. Query untuk Total Transaksi
$query_transaksi = $koneksi->query("SELECT COUNT(id_penjualan) AS total_trx FROM penjualan");
$data_transaksi  = $query_transaksi->fetch_assoc();
$total_transaksi = $data_transaksi['total_trx'];

// 2. Query untuk Total Produk Terjual
$query_terjual = $koneksi->query("SELECT SUM(jumlah_terjual) AS total_qty FROM penjualan");
$data_terjual  = $query_terjual->fetch_assoc();
$total_produk_terjual = $data_terjual['total_qty'] ?? 0;

// 3. Query untuk Total Pendapatan
$query_pendapatan = $koneksi->query("SELECT SUM(total_penjualan) AS total_income FROM penjualan");
$data_pendapatan  = $query_pendapatan->fetch_assoc();
$total_pendapatan = $data_pendapatan['total_income'] ?? 0;

// 3b. Query untuk Data Inventory
$query_inventory = $koneksi->query("SELECT COUNT(id_barang) AS total_items, COALESCE(SUM(jumlah_stok),0) AS total_stock, SUM(CASE WHEN jumlah_stok <= COALESCE(batas_minimum_stok,0) THEN 1 ELSE 0 END) AS low_stock_count FROM tb_barang");
$inventory_data = $query_inventory->fetch_assoc();
$total_inventory_items = $inventory_data['total_items'] ?? 0;
$total_stock = $inventory_data['total_stock'] ?? 0;
$low_stock_count = $inventory_data['low_stock_count'] ?? 0;

// 4. Query untuk Produk Terlaris
$query_terlaris = $koneksi->query("SELECT nama_produk, SUM(jumlah_terjual) AS total_laris 
                                   FROM penjualan 
                                   GROUP BY nama_produk 
                                   ORDER BY total_laris DESC 
                                   LIMIT 1");
$data_terlaris  = $query_terlaris->fetch_assoc();
$produk_terlaris = $data_terlaris['nama_produk'] ?? "Belum ada data";
$qty_terlaris    = $data_terlaris['total_laris'] ?? 0;

// --- 5. Query untuk Grafik: Jumlah Penjualan per Produk (Qty) ---
$label_produk = [];
$data_penjualan_produk = [];
$query_grafik1 = $koneksi->query("SELECT nama_produk, SUM(jumlah_terjual) AS total_qty FROM penjualan GROUP BY nama_produk");
while ($row = $query_grafik1->fetch_assoc()) {
    $label_produk[] = $row['nama_produk'];
    $data_penjualan_produk[] = $row['total_qty'];
}

// --- 6. Query untuk Grafik: Pendapatan per Produk (Income) ---
$label_pendapatan_produk = [];
$data_pendapatan_produk = [];
$query_grafik2 = $koneksi->query("SELECT nama_produk, SUM(total_penjualan) AS total_uang FROM penjualan GROUP BY nama_produk");
while ($row = $query_grafik2->fetch_assoc()) {
    $label_pendapatan_produk[] = $row['nama_produk'];
    $data_pendapatan_produk[] = $row['total_uang'];
}

// --- 7. Query untuk Grafik: Tren Penjualan Harian ---
$label_tanggal = [];
$data_tren_harian = [];
$query_grafik3 = $koneksi->query("SELECT tanggal, SUM(total_penjualan) AS pendapatan_harian FROM penjualan GROUP BY tanggal ORDER BY tanggal ASC");
while ($row = $query_grafik3->fetch_assoc()) {
    $label_tanggal[] = $row['tanggal'];
    $data_tren_harian[] = $row['pendapatan_harian'];
}

// --- 8. Query untuk Grafik: Persentase Penjualan ---
// Kita bisa reuse data dari query 5 (jumlah terjual) karena来表示比例
$label_persentase = $label_produk;
$data_persentase = $data_penjualan_produk;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sweet Bakery Dashboard</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
            margin-bottom: 1.75rem;
        }

        .dashboard-sidebar .brand span {
            color: #fbbf24;
        }

        .dashboard-sidebar nav a {
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

        .dashboard-sidebar nav a:hover,
        .dashboard-sidebar nav a.active {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
        }

        .dashboard-sidebar .nav-section {
            margin-top: 2rem;
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

        .page-header small {
            opacity: 0.85;
        }

        .badge-soft {
            background: rgba(255, 255, 255, 0.18);
            color: #f8fafc;
            border-radius: 999px;
            padding: 0.55rem 0.95rem;
            font-size: 0.88rem;
        }

        .stat-card,
        .chart-box,
        .summary-card {
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

        .stat-card .icon-box {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(67, 97, 238, 0.12);
            font-size: 1.35rem;
            color: #4361ee;
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

        .chart-box {
            padding: 26px;
            min-height: 345px;
        }

        .chart-box canvas {
            width: 100% !important;
            height: 290px !important;
        }

        .chart-header {
            font-weight: 700;
            color: #111827;
            margin-bottom: 22px;
            font-size: 1.05rem;
        }

        .summary-card {
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .summary-card .summary-label {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .summary-card .summary-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
        }

        .summary-card .summary-note {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .footer-note {
            margin-top: 16px;
            color: #6b7280;
            font-size: 0.95rem;
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
            <p class="mb-4 text-sm">Dashboard profesional untuk memantau penjualan, pendapatan, dan produk unggulan.</p>

            <nav class="nav-section">
                <a href="dashboard.php" class="active"><span class="sidebar-icon"><i class="bi bi-speedometer2"></i></span>Dashboard</a>
                <a href="index.php"><span class="sidebar-icon"><i class="bi bi-table"></i></span>Kelola Data</a>
                <a href="inventory.php"><span class="sidebar-icon"><i class="bi bi-boxes"></i></span>Inventory</a>
                <!-- 'Tambah Penjualan' menu removed -->
                <a href="home.php" target="_blank"><span class="sidebar-icon"><i class="bi bi-shop"></i></span>Lihat Toko Online</a>
                <a href="logout.php" onclick="return confirm('Logout?')"><span class="sidebar-icon"><i class="bi bi-box-arrow-right"></i></span>Logout</a>
            </nav>
        </aside>

        <main class="dashboard-main">
            <div class="page-header mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                    <div>
                        <p class="mb-2 text-white opacity-80">Halo, selamat datang kembali!</p>
                        <h1 class="fw-bold">Ringkasan Kinerja Sweet Bakery</h1>
                        <p class="mb-0">Lihat metrik penjualan utama dan tren bisnis secara real time di dashboard ini.</p>
                    </div>
                    <div class="text-end">
                        <span class="badge-soft">Dashboard Bisnis</span>
                        <div class="mt-3 text-white opacity-80">Data terakhir diambil dari tabel penjualan.</div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-sm-6">
                    <div class="stat-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="label">Total Transaksi</div>
                                <div class="value"><?php echo $total_transaksi; ?></div>
                            </div>
                            <div class="icon-box bg-blue text-white"><i class="bi bi-receipt"></i></div>
                        </div>
                        <p class="mb-0 text-muted">Semua faktur penjualan yang tercatat.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="stat-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="label">Produk Terjual</div>
                                <div class="value"><?php echo $total_produk_terjual; ?></div>
                            </div>
                            <div class="icon-box bg-green text-white"><i class="bi bi-box-seam"></i></div>
                        </div>
                        <p class="mb-0 text-muted">Jumlah unit produk yang keluar.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="stat-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="label">Total Pendapatan</div>
                                <div class="value">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></div>
                            </div>
                            <div class="icon-box bg-yellow text-white"><i class="bi bi-cash-stack"></i></div>
                        </div>
                        <p class="mb-0 text-muted">Total pendapatan dari semua transaksi.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="stat-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="label">Produk Terlaris</div>
                                <div class="value"><?php echo $produk_terlaris; ?></div>
                            </div>
                            <div class="icon-box bg-purple text-white"><i class="bi bi-trophy"></i></div>
                        </div>
                        <p class="mb-0 text-muted">Produk dengan volume penjualan tertinggi.</p>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-4 col-md-6">
                    <div class="stat-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="label">Total Item Inventory</div>
                                <div class="value"><?php echo $total_inventory_items; ?></div>
                            </div>
                            <div class="icon-box bg-info text-white"><i class="bi bi-basket3"></i></div>
                        </div>
                        <p class="mb-0 text-muted">Jumlah produk di inventory.</p>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="stat-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="label">Stok Tersedia</div>
                                <div class="value"><?php echo number_format($total_stock, 0, ',', '.'); ?></div>
                            </div>
                            <div class="icon-box bg-success text-white"><i class="bi bi-stack"></i></div>
                        </div>
                        <p class="mb-0 text-muted">Total unit stok saat ini.</p>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="stat-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="label">Produk Stok Rendah</div>
                                <div class="value"><?php echo $low_stock_count; ?></div>
                            </div>
                            <div class="icon-box bg-red text-white"><i class="bi bi-exclamation-triangle"></i></div>
                        </div>
                        <p class="mb-0 text-muted">Produk yang perlu restock segera.</p>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-4 col-md-6">
                    <div class="summary-card">
                        <div>
                            <div class="summary-label">Rata-rata Penjualan / Produk</div>
                            <div class="summary-value"><?php echo $total_produk_terjual > 0 ? round($total_produk_terjual / max($total_transaksi, 1), 1) : 0; ?></div>
                        </div>
                        <div class="summary-note">Efisiensi stok dan permintaan.</div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="summary-card">
                        <div>
                            <div class="summary-label">Estimasi Margin</div>
                            <div class="summary-value">Rp <?php echo number_format($total_pendapatan * 0.65, 0, ',', '.'); ?></div>
                        </div>
                        <div class="summary-note">Perkiraan margin bersih 65%.</div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-12">
                    <div class="summary-card">
                        <div>
                            <div class="summary-label">Penjualan Terakhir</div>
                            <div class="summary-value"><?php echo !empty($label_tanggal) ? end($label_tanggal) : 'Tidak ada'; ?></div>
                        </div>
                        <div class="summary-note">Tanggal transaksi terbaru.</div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="chart-box">
                        <h4 class="chart-header">Jumlah Penjualan per Produk</h4>
                        <canvas id="chartTerjual"></canvas>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="chart-box">
                        <h4 class="chart-header">Pendapatan per Produk</h4>
                        <canvas id="chartPendapatan"></canvas>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-xl-6">
                    <div class="chart-box">
                        <h4 class="chart-header">Tren Pendapatan Harian</h4>
                        <canvas id="chartTren"></canvas>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="chart-box">
                        <h4 class="chart-header">Komposisi Penjualan Produk</h4>
                        <canvas id="chartPersentase"></canvas>
                    </div>
                </div>
            </div>

            <div class="footer-note">Tampilan dashboard telah diperbarui dengan desain profesional dan tata letak yang rapi.</div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const colors = ['#4361ee', '#2ec4b6', '#ff9f1c', '#833bfc', '#ff6b6b', '#4ecdc4'];

        const labels1 = <?php echo json_encode($label_produk); ?>;
        const data1 = <?php echo json_encode($data_penjualan_produk); ?>;
        const labels2 = <?php echo json_encode($label_pendapatan_produk); ?>;
        const data2 = <?php echo json_encode($data_pendapatan_produk); ?>;
        const labels3 = <?php echo json_encode($label_tanggal); ?>;
        const data3 = <?php echo json_encode($data_tren_harian); ?>;
        const labels4 = <?php echo json_encode($label_persentase); ?>;
        const data4 = <?php echo json_encode($data_persentase); ?>;

        const formatRupiah = value => new Intl.NumberFormat('id-ID', {
            style: 'currency', currency: 'IDR', minimumFractionDigits: 0
        }).format(value);

        new Chart(document.getElementById('chartTerjual'), {
            type: 'bar',
            data: {
                labels: labels1,
                datasets: [{
                    label: 'Jumlah Terjual',
                    data: data1,
                    backgroundColor: colors,
                    borderRadius: 16,
                    maxBarThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${ctx.formattedValue}` } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(15, 23, 42, 0.08)' } },
                    x: { grid: { display: false } }
                }
            }
        });

        new Chart(document.getElementById('chartPendapatan'), {
            type: 'bar',
            data: {
                labels: labels2,
                datasets: [{
                    label: 'Pendapatan',
                    data: data2,
                    backgroundColor: '#4f46e5',
                    borderRadius: 16,
                    maxBarThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => formatRupiah(ctx.raw) } }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: value => value.toLocaleString('id-ID') },
                        grid: { color: 'rgba(15, 23, 42, 0.08)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });

        new Chart(document.getElementById('chartTren'), {
            type: 'line',
            data: {
                labels: labels3,
                datasets: [{
                    label: 'Pendapatan Harian',
                    data: data3,
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22, 163, 74, 0.18)',
                    tension: 0.32,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#16a34a'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => formatRupiah(ctx.raw) } }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: value => value.toLocaleString('id-ID') },
                        grid: { color: 'rgba(15, 23, 42, 0.08)' }
                    }
                }
            }
        });

        new Chart(document.getElementById('chartPersentase'), {
            type: 'doughnut',
            data: {
                labels: labels4,
                datasets: [{
                    data: data4,
                    backgroundColor: colors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 12, padding: 16 } },
                    tooltip: { callbacks: { label: ctx => `${ctx.label}: ${ctx.formattedValue}` } }
                }
            }
        });
    </script>
</body>
</html>