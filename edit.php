<?php
session_start();

// Cek apakah user sudah login dan adalah admin
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['level'] != "admin") {
    header("Location: login.php?pesan=belum_login");
    exit;
}

include 'koneksi.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = intval($_GET['id']);
$query_penjualan = $koneksi->query("SELECT * FROM penjualan WHERE id_penjualan = $id LIMIT 1");
$data_penjualan = $query_penjualan->fetch_assoc();

if (!$data_penjualan) {
    echo "<script>alert('Data penjualan tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

// Fetch inventory list for dropdown selection
$query_barang = $koneksi->query("SELECT id_barang, kode_barang, nama_barang, jumlah_stok, harga_jual FROM tb_barang ORDER BY nama_barang ASC");
$barang_options = [];
while ($row = $query_barang->fetch_assoc()) {
    $barang_options[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Penjualan - Sweet Bakery</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            font-size: 0.9rem;
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

        .page-header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #ffffff;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 32px;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.15);
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .page-header p {
            opacity: 0.9;
            margin-bottom: 0;
            font-size: 0.95rem;
        }

        .form-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            padding: 32px;
            border: 1px solid rgba(15, 23, 42, 0.04);
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        .form-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
        }

        .form-label {
            font-weight: 500;
            color: #334155;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 0.2rem rgba(245, 158, 11, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn-group-custom {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }

        .btn-custom {
            padding: 12px 32px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-submit {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            flex: 1;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(245, 158, 11, 0.3);
        }

        .btn-cancel {
            background: #f1f5f9;
            color: #334155;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
        }

        .file-input-wrapper {
            position: relative;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .file-input-label:hover {
            border-color: #f59e0b;
            background: #fffbeb;
        }

        .file-input-label i {
            font-size: 2rem;
            color: #d97706;
            margin-right: 12px;
        }

        .file-input-label span {
            color: #334155;
            font-weight: 500;
        }

        #gambar_produk {
            display: none;
        }

        .helper-text {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 8px;
        }

        .stock-indicator {
            font-size: 0.9rem;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 8px;
            display: inline-block;
            margin-top: 5px;
        }
        .stock-safe {
            background-color: #d1fae5;
            color: #065f46;
        }
        .stock-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .current-image-preview {
            max-width: 150px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .btn-group-custom {
                flex-direction: column;
            }

            .page-header {
                padding: 20px;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="brand"><span>Sweet</span> Bakery</div>
            <p>Kelola data penjualan roti dan kue favorit Anda.</p>

            <nav>
                <a href="dashboard.php" class="nav-link">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="index.php" class="nav-link active">
                    <i class="bi bi-table"></i> Kelola Data
                </a>
                <a href="inventory.php" class="nav-link">
                    <i class="bi bi-boxes"></i> Inventory
                </a>
                <a href="logout.php" onclick="return confirm('Logout?')" class="nav-link">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="bi bi-pencil-square"></i> Edit Data Penjualan</h1>
                <p>Ubah detail transaksi penjualan produk di bawah ini</p>
            </div>

            <!-- Form Card -->
            <div class="form-card">
                <form method="POST" action="proses_edit.php" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $data_penjualan['id_penjualan']; ?>">
                    
                    <!-- Section: Informasi Dasar -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-info-circle"></i> Informasi Dasar
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="tanggal">Tanggal Penjualan</label>
                                <input class="form-control" type="date" id="tanggal" name="tanggal" value="<?php echo htmlspecialchars($data_penjualan['tanggal']); ?>" required>
                                <small class="helper-text">Pilih tanggal penjualan produk</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="nama_produk">Produk dari Inventory</label>
                                <select class="form-select" id="nama_produk" name="nama_produk" required>
                                    <option value="" disabled>-- Pilih Produk --</option>
                                    <?php 
                                    $produk_ditemukan = false;
                                    foreach ($barang_options as $barang): 
                                        $selected = "";
                                        if ($barang['nama_barang'] == $data_penjualan['nama_produk']) {
                                            $selected = "selected";
                                            $produk_ditemukan = true;
                                        }
                                    ?>
                                        <option value="<?php echo htmlspecialchars($barang['nama_barang']); ?>" 
                                                data-price="<?php echo $barang['harga_jual']; ?>" 
                                                data-stock="<?php echo $barang['jumlah_stok']; ?>"
                                                <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($barang['nama_barang']); ?> (Stok: <?php echo $barang['jumlah_stok']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                    
                                    <?php if (!$produk_ditemukan && !empty($data_penjualan['nama_produk'])): ?>
                                        <option value="<?php echo htmlspecialchars($data_penjualan['nama_produk']); ?>" 
                                                data-price="<?php echo $data_penjualan['harga_satuan']; ?>" 
                                                data-stock="0"
                                                selected>
                                            <?php echo htmlspecialchars($data_penjualan['nama_produk']); ?> (Tidak ada di Inventory)
                                        </option>
                                    <?php endif; ?>
                                </select>
                                <small class="helper-text">Pilih produk yang terjual dari persediaan inventory</small>
                                <div id="stock-info" class="mt-2">
                                    <span id="stock-badge" class="stock-indicator stock-safe"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Detail Penjualan -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-cash-coin"></i> Detail Penjualan
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="jumlah_terjual">Jumlah Terjual</label>
                                <input class="form-control" type="number" id="jumlah_terjual" name="jumlah_terjual" min="1" value="<?php echo intval($data_penjualan['jumlah_terjual']); ?>" required>
                                <small class="helper-text">Jumlah unit produk yang terjual</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="harga_satuan">Harga Satuan (Rp)</label>
                                <input class="form-control" type="number" id="harga_satuan" name="harga_satuan" min="1" value="<?php echo intval($data_penjualan['harga_satuan']); ?>" readonly required>
                                <small class="helper-text">Harga per unit (diisi otomatis berdasarkan inventory)</small>
                            </div>
                        </div>
                        <div class="form-row mt-3">
                            <div class="form-group col-12">
                                <label class="form-label" for="total_penjualan_display">Total Penjualan</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light fw-bold">Rp</span>
                                    <input class="form-control fw-bold text-success fs-5" type="text" id="total_penjualan_display" readonly value="<?php echo number_format($data_penjualan['total_penjualan'], 0, ',', '.'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Upload Foto -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-image"></i> Foto Produk
                        </div>
                        <div class="row align-items-center">
                            <div class="col-md-3 text-center mb-3 mb-md-0">
                                <span class="d-block text-muted mb-2 small fw-semibold">Foto Saat Ini</span>
                                <?php if (!empty($data_penjualan['gambar_produk']) && file_exists("uploads/" . $data_penjualan['gambar_produk'])): ?>
                                    <img src="uploads/<?php echo $data_penjualan['gambar_produk']; ?>" class="current-image-preview" alt="Foto produk">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center mx-auto" style="width: 120px; height: 120px; border-radius: 14px; background: #e2e8f0; color: #94a3b8;">
                                        <i class="bi bi-image fs-2"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-9">
                                <div class="file-input-wrapper">
                                    <label class="file-input-label" for="gambar_produk" id="upload-label">
                                        <i class="bi bi-cloud-arrow-up"></i>
                                        <span id="upload-text">Klik atau drag gambar baru ke sini (Opsional)</span>
                                    </label>
                                    <input class="form-control" type="file" id="gambar_produk" name="gambar_produk" accept="image/*">
                                    <small class="helper-text">Format: JPG, JPEG, PNG, GIF (Max: 5MB). Biarkan kosong jika tidak ingin mengganti gambar.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="btn-group-custom">
                        <button class="btn btn-custom btn-submit" type="submit" name="update">
                            <i class="bi bi-check-circle"></i> Simpan Perubahan
                        </button>
                        <a class="btn btn-custom btn-cancel" href="index.php">
                            <i class="bi bi-x-circle"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const namaProdukSelect = document.getElementById('nama_produk');
        const hargaSatuanInput = document.getElementById('harga_satuan');
        const jumlahTerjualInput = document.getElementById('jumlah_terjual');
        const totalDisplay = document.getElementById('total_penjualan_display');
        const stockInfoDiv = document.getElementById('stock-info');
        const stockBadge = document.getElementById('stock-badge');
        const fileInput = document.getElementById('gambar_produk');
        const uploadText = document.getElementById('upload-text');

        const originalQty = <?php echo intval($data_penjualan['jumlah_terjual']); ?>;
        let availableStock = 0;

        function updateStockLimit() {
            const selectedOption = namaProdukSelect.options[namaProdukSelect.selectedIndex];
            if (selectedOption) {
                const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
                const dbStock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
                
                // If it is the same product as original, the available stock is current inventory stock + original quantity sold
                const isOriginalProduct = selectedOption.value === "<?php echo htmlspecialchars($data_penjualan['nama_produk']); ?>";
                availableStock = isOriginalProduct ? (dbStock + originalQty) : dbStock;
                
                hargaSatuanInput.value = price;
                
                stockBadge.textContent = 'Stok tersedia untuk penjualan: ' + availableStock + ' (Termasuk ' + (isOriginalProduct ? originalQty : 0) + ' dari penjualan ini)';
                stockBadge.className = 'stock-indicator stock-safe';
                
                jumlahTerjualInput.max = availableStock;
                hitungTotal();
            }
        }

        // Initialize on page load
        updateStockLimit();

        namaProdukSelect.addEventListener('change', updateStockLimit);

        jumlahTerjualInput.addEventListener('input', function() {
            const qty = parseInt(this.value) || 0;
            if (qty > availableStock) {
                alert('Jumlah terjual melebihi total stok yang tersedia (' + availableStock + ')!');
                this.value = availableStock;
            }
            hitungTotal();
        });

        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                uploadText.textContent = this.files[0].name;
            } else {
                uploadText.textContent = 'Klik atau drag gambar baru ke sini (Opsional)';
            }
        });

        function hitungTotal() {
            const qty = parseInt(jumlahTerjualInput.value) || 0;
            const price = parseFloat(hargaSatuanInput.value) || 0;
            const total = qty * price;
            totalDisplay.value = new Intl.NumberFormat('id-ID').format(total);
        }
    </script>
</body>
</html>
