<?php
// Memulai session
session_start();

// Memanggil file koneksi
include 'koneksi.php';

// Query untuk mendapatkan semua produk dari inventory yang memiliki stok > 0
$query_produk = $koneksi->query("
    SELECT 
        b.nama_barang AS nama_produk,
        b.harga_jual AS harga_satuan,
        b.jumlah_stok,
        COALESCE(
            (SELECT p.gambar_produk FROM penjualan p WHERE p.nama_produk = b.nama_barang AND p.gambar_produk != '' ORDER BY p.tanggal DESC LIMIT 1),
            ''
        ) AS gambar_produk,
        (SELECT COUNT(p2.id_penjualan) FROM penjualan p2 WHERE p2.nama_produk = b.nama_barang) AS total_terjual
    FROM tb_barang b
    WHERE b.jumlah_stok > 0
    ORDER BY total_terjual DESC
");

$produk_list = [];
while ($row = $query_produk->fetch_assoc()) {
    $produk_list[] = $row;
}

// Query untuk statistik keseluruhan
$stats_query = $koneksi->query("
    SELECT 
        (SELECT COUNT(id_barang) FROM tb_barang WHERE jumlah_stok > 0) AS total_produk,
        COALESCE(SUM(jumlah_terjual), 0) AS total_penjualan,
        COUNT(id_penjualan) AS total_transaksi
    FROM penjualan
");
$stats = $stats_query->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sweet Bakery - Roti Segar Setiap Hari</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --color-cream: #FFF0F5;
            --color-vanilla: #FFFFFF;
            --color-coffee: #8B6D74;
            --color-brown: #FFB7C5;
            --color-gold: #FF6B8B;
            --color-dark-brown: #4A2E35;
            --color-peach: #FFE4E1;
            --color-light-gold: #FFC0CB;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--color-cream);
            color: var(--color-dark-brown);
            line-height: 1.6;
        }

        /* ==================== NAVBAR ==================== */
        .navbar {
            background: linear-gradient(135deg, var(--color-vanilla) 0%, var(--color-cream) 100%);
            box-shadow: 0 4px 20px rgba(107, 83, 68, 0.08);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--color-dark-brown);
            margin-right: 3rem;
            letter-spacing: -0.5px;
        }

        .navbar .navbar-brand .logo-icon {
            color: var(--color-gold);
            margin-right: 0.5rem;
        }

        .nav-link {
            font-weight: 500;
            color: var(--color-coffee) !important;
            margin: 0 0.8rem;
            position: relative;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--color-gold) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 0;
            background-color: var(--color-gold);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .cart-icon {
            font-size: 1.5rem;
            color: var(--color-dark-brown);
            cursor: pointer;
            position: relative;
            transition: color 0.3s ease;
        }

        .cart-icon:hover {
            color: var(--color-gold);
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--color-gold);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .navbar-menu-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-divider {
            width: 1px;
            height: 30px;
            background-color: rgba(107, 83, 68, 0.2);
        }

        .btn-user {
            background: transparent;
            border: none;
            color: var(--color-coffee) !important;
            font-weight: 500;
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        .btn-user:hover {
            color: var(--color-gold) !important;
        }

        .btn-user i {
            font-size: 1.3rem;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 25px rgba(107, 83, 68, 0.15);
            border-radius: 12px;
            padding: 0.5rem 0;
            margin-top: 0.5rem;
        }

        .dropdown-menu .dropdown-item {
            padding: 0.8rem 1.2rem;
            color: var(--color-dark-brown);
            font-weight: 500;
            transition: all 0.2s ease;
            border-radius: 8px;
            margin: 0.3rem 0.3rem;
        }

        .dropdown-menu .dropdown-item:hover {
            background-color: var(--color-peach);
            color: var(--color-dark-brown);
            padding-left: 1.5rem;
        }

        .dropdown-menu .dropdown-item i {
            margin-right: 0.5rem;
            color: var(--color-gold);
        }

        .dropdown-item-text {
            padding: 0.8rem 1.2rem;
            color: var(--color-dark-brown);
        }

        /* ==================== HERO SECTION ==================== */
        .hero {
            background: linear-gradient(135deg, var(--color-vanilla) 0%, var(--color-peach) 100%);
            padding: 80px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            right: -100px;
            width: 300px;
            height: 300px;
            background: rgba(212, 165, 116, 0.1);
            border-radius: 50%;
        }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--color-dark-brown);
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.3rem;
            color: var(--color-coffee);
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-cta {
            background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-light-gold) 100%);
            color: var(--color-dark-brown);
            border: none;
            padding: 14px 40px;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(212, 165, 116, 0.3);
            cursor: pointer;
        }

        .btn-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(212, 165, 116, 0.4);
            background: linear-gradient(135deg, var(--color-light-gold) 0%, var(--color-gold) 100%);
            color: var(--color-dark-brown);
        }

        /* ==================== SECTION TITLE ==================== */
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            font-weight: 800;
            color: var(--color-dark-brown);
            text-align: center;
            margin: 60px 0 50px 0;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--color-gold), var(--color-light-gold));
            border-radius: 2px;
        }

        /* ==================== PRODUCT GRID ==================== */
        .products-section {
            padding: 60px 20px;
            background: var(--color-cream);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .product-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(107, 83, 68, 0.1);
            transition: all 0.4s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(107, 83, 68, 0.15);
        }

        .product-image {
            width: 100%;
            height: 240px;
            background: linear-gradient(135deg, var(--color-peach), var(--color-light-gold));
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.1);
        }

        .product-image-placeholder {
            font-size: 4rem;
            color: rgba(212, 165, 116, 0.3);
        }

        .product-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--color-gold);
            color: white;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .product-content {
            padding: 24px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .product-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-dark-brown);
            margin-bottom: 0.5rem;
        }

        .product-description {
            color: var(--color-coffee);
            font-size: 0.95rem;
            margin-bottom: 1rem;
            flex-grow: 1;
        }

        .product-price {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--color-gold);
            margin-bottom: 1.5rem;
        }

        .product-price .currency {
            font-size: 0.9rem;
        }

        .btn-add-cart {
            background: linear-gradient(135deg, var(--color-coffee) 0%, var(--color-brown) 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 111, 71, 0.3);
            background: linear-gradient(135deg, var(--color-brown), var(--color-dark-brown));
        }

        /* ==================== STATS SECTION ==================== */
        .stats-section {
            background: linear-gradient(135deg, var(--color-vanilla) 0%, var(--color-peach) 100%);
            padding: 60px 20px;
            margin: 60px 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            max-width: 1000px;
            margin: 0 auto;
            text-align: center;
        }

        .stat-card {
            padding: 30px;
        }

        .stat-number {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--color-gold);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: var(--color-coffee);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ==================== FOOTER ==================== */
        footer {
            background: var(--color-dark-brown);
            color: var(--color-vanilla);
            padding: 40px 20px 20px;
            text-align: center;
        }

        footer p {
            margin: 10px 0;
        }

        .footer-links {
            margin: 20px 0;
        }

        .footer-links a {
            color: var(--color-light-gold);
            text-decoration: none;
            margin: 0 15px;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--color-gold);
        }

        .footer-bottom {
            border-top: 1px solid rgba(212, 165, 116, 0.3);
            margin-top: 30px;
            padding-top: 20px;
            font-size: 0.9rem;
            color: rgba(245, 239, 231, 0.8);
        }

        /* ==================== MODAL CART ==================== */
        .modal-content {
            background: var(--color-cream);
            border: none;
            border-radius: 20px;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--color-gold), var(--color-light-gold));
            color: white;
            border-radius: 20px 20px 0 0;
            border: none;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        /* ==================== RESPONSIF ==================== */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.2rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .navbar .navbar-brand {
                font-size: 1.4rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
            }

            .product-image {
                height: 180px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .nav-divider {
                display: none;
            }

            .btn-user span {
                display: none;
            }

            .navbar-menu-right {
                gap: 0.5rem;
            }
        }

        @media (max-width: 576px) {
            .hero {
                padding: 50px 15px;
            }

            .hero h1 {
                font-size: 1.8rem;
            }

            .nav-link {
                margin: 0.5rem 0;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>

    <!-- ==================== NAVBAR ==================== -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#home">
                <i class="bi bi-cup-hot-fill logo-icon"></i> Sweet Bakery
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#beranda">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#menu">Menu Roti</a></li>
                    <li class="nav-item"><a class="nav-link" href="#promo">Promo Spesial</a></li>
                    <li class="nav-item"><a class="nav-link" href="#tentang">Tentang Kami</a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php" style="color: var(--color-gold) !important; font-weight: 600;"><i class="bi bi-shield-check"></i> Admin</a></li>
                </ul>
                <div class="navbar-menu-right">
                    <a href="#" class="cart-icon" data-bs-toggle="offcanvas" data-bs-target="#cartCanvas" title="Keranjang">
                        <i class="bi bi-bag"></i>
                        <span class="cart-badge" id="cartCount">0</span>
                    </a>
                    <div class="nav-divider"></div>
                    <div class="dropdown ms-3">
                        <button class="btn btn-user dropdown-toggle" type="button" data-bs-toggle="dropdown" title="Akun">
                            <i class="bi bi-person-circle"></i> <span id="userMenuLabel">Akun</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (isset($_SESSION['status']) && $_SESSION['status'] == "login"): ?>
                                <li><span class="dropdown-item-text"><strong><?php echo htmlspecialchars($_SESSION['nama']); ?></strong></span></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="profil.php"><i class="bi bi-person"></i> Profil Saya</a></li>
                                <li><a class="dropdown-item" href="riwayat_pesanan.php"><i class="bi bi-list-check"></i> Riwayat Pesanan</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a></li>
                                <li><a class="dropdown-item" href="register.php"><i class="bi bi-person-plus"></i> Daftar</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- ==================== HERO SECTION ==================== -->
    <section class="hero" id="beranda">
        <div class="container">
            <h1>🥖 Roti Segar Setiap Hari,<br>Langsung dari Oven</h1>
            <p>Nikmati kelezatan roti premium yang dibuat dengan bahan-bahan pilihan dan penuh cinta di setiap gigitan.</p>
            <button class="btn-cta" onclick="document.getElementById('menu').scrollIntoView({ behavior: 'smooth' })">
                <i class="bi bi-shop"></i> Pesan Sekarang
            </button>
        </div>
    </section>

    <!-- ==================== STATS SECTION ==================== -->
    <section class="stats-section">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_produk'] ?? 0; ?></div>
                <div class="stat-label">Jenis Roti</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_penjualan'] ?? 0); ?></div>
                <div class="stat-label">Produk Terjual</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_transaksi'] ?? 0; ?></div>
                <div class="stat-label">Pelanggan Puas</div>
            </div>
        </div>
    </section>

    <!-- ==================== PRODUCTS SECTION ==================== -->
    <section class="products-section" id="menu">
        <h2 class="section-title">Menu Roti Pilihan</h2>
        <div class="products-grid">
            <?php if (count($produk_list) > 0): ?>
                <?php foreach ($produk_list as $index => $produk): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if (!empty($produk['gambar_produk'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($produk['gambar_produk']); ?>" alt="<?php echo htmlspecialchars($produk['nama_produk']); ?>">
                            <?php else: ?>
                                <div class="product-image-placeholder">
                                    <i class="bi bi-image"></i>
                                </div>
                            <?php endif; ?>
                            <?php if ($index === 0): ?>
                                <span class="product-badge">Terlaris</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-content">
                            <h3 class="product-name"><?php echo htmlspecialchars($produk['nama_produk']); ?></h3>
                            <p class="product-description">Roti berkualitas premium dengan rasa yang lezat dan tekstur yang sempurna.</p>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-success text-white">Stok: <?php echo $produk['jumlah_stok']; ?></span>
                            </div>
                            <div class="product-price">
                                <span class="currency">Rp</span> <?php echo number_format($produk['harga_satuan'], 0, ',', '.'); ?>
                            </div>
                            <button class="btn-add-cart" onclick="addToCart('<?php echo htmlspecialchars($produk['nama_produk']); ?>', <?php echo $produk['harga_satuan']; ?>, <?php echo $produk['jumlah_stok']; ?>)">
                                <i class="bi bi-bag-plus"></i> Tambah ke Keranjang
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
                    <p style="color: var(--color-coffee);">Belum ada produk tersedia. Segera datang kembali untuk melihat menu terbaru kami!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ==================== FOOTER ==================== -->
    <footer id="tentang">
        <div class="container">
            <h3 style="font-family: 'Playfair Display', serif; margin-bottom: 20px;">Sweet Bakery</h3>
            <p><strong>Roti Segar, Dibuat dengan Cinta</strong></p>
            <p style="color: var(--color-light-gold);">Kami menyediakan roti berkualitas premium dengan bahan-bahan pilihan terbaik setiap harinya.</p>
            
            <div class="footer-links">
                <a href="https://maps.app.goo.gl/UvzFrATRAbY9EeHd6" target="_blank"><i class="bi bi-geo-alt"></i> Lokasi</a>
                <a href="https://wa.me/085280203106"><i class="bi bi-telephone"></i> Hubungi Kami</a>
                <a href="mailto:taufikaprianto23@gmail.com"><i class="bi bi-envelope"></i> Email</a>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2026 Sweet Bakery. Semua hak dilindungi oleh Taufik Apriyanto. | Dibuat dengan <span style="color: var(--color-gold);">❤</span> untuk Anda</p>
            </div>
        </div>
    </footer>

    <!-- ==================== SHOPPING CART OFFCANVAS ==================== -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartCanvas">
        <div class="offcanvas-header" style="background: linear-gradient(135deg, var(--color-gold), var(--color-light-gold));">
            <h5 class="offcanvas-title" style="color: white; font-weight: 700;">🛒 Keranjang Belanja</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <div id="cartItems">
                <p style="text-align: center; color: var(--color-coffee);">Keranjang Anda masih kosong</p>
            </div>
            <div id="cartSummary" style="display: none;">
                <hr>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <strong>Total:</strong>
                    <strong style="color: var(--color-gold); font-size: 1.3rem;" id="cartTotal">Rp 0</strong>
                </div>
                <button class="btn-cta" style="width: 100%; margin-top: 20px;" onclick="goToCheckout()">
                    <i class="bi bi-check-circle"></i> Lanjut Checkout
                </button>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Shopping Cart Logic
        let cart = JSON.parse(localStorage.getItem('sweetBakeryCart')) || [];

        function addToCart(nama, harga, maxStock) {
            const existingItem = cart.find(item => item.nama === nama);
            
            if (existingItem) {
                if (existingItem.qty >= maxStock) {
                    showNotification(`Stok untuk ${nama} terbatas (maksimum ${maxStock})!`);
                    return;
                }
                existingItem.qty += 1;
            } else {
                cart.push({ nama: nama, harga: harga, qty: 1, maxStock: maxStock });
            }
            
            localStorage.setItem('sweetBakeryCart', JSON.stringify(cart));
            updateCartUI();
            
            // Show toast notification
            showNotification(`${nama} ditambahkan ke keranjang!`);
        }

        function updateCartUI() {
            const cartCount = cart.reduce((total, item) => total + item.qty, 0);
            document.getElementById('cartCount').textContent = cartCount;

            const cartItemsDiv = document.getElementById('cartItems');
            const cartSummary = document.getElementById('cartSummary');

            if (cart.length === 0) {
                cartItemsDiv.innerHTML = '<p style="text-align: center; color: var(--color-coffee);">Keranjang Anda masih kosong</p>';
                cartSummary.style.display = 'none';
                return;
            }

            let cartHTML = '<div style="margin-bottom: 20px;">';
            let total = 0;

            cart.forEach((item, index) => {
                const subtotal = item.harga * item.qty;
                total += subtotal;
                cartHTML += `
                    <div style="background: var(--color-peach); padding: 12px; border-radius: 10px; margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="color: var(--color-dark-brown);">${item.nama}</strong><br>
                                <small style="color: var(--color-coffee);">Rp ${item.harga.toLocaleString('id-ID')}</small>
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <button class="btn btn-sm btn-outline-secondary" onclick="decreaseQty(${index})" style="width: 30px; height: 30px; padding: 0;">-</button>
                                <span style="width: 30px; text-align: center; font-weight: 600;">${item.qty}</span>
                                <button class="btn btn-sm btn-outline-secondary" onclick="increaseQty(${index})" style="width: 30px; height: 30px; padding: 0;">+</button>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${index})" style="width: 30px; height: 30px; padding: 0;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div style="text-align: right; margin-top: 8px; color: var(--color-gold); font-weight: 700;">
                            Rp ${subtotal.toLocaleString('id-ID')}
                        </div>
                    </div>
                `;
            });

            cartHTML += '</div>';
            cartItemsDiv.innerHTML = cartHTML;
            document.getElementById('cartTotal').textContent = `Rp ${total.toLocaleString('id-ID')}`;
            cartSummary.style.display = 'block';
        }

        function increaseQty(index) {
            const item = cart[index];
            if (item.maxStock && item.qty >= item.maxStock) {
                showNotification(`Stok untuk ${item.nama} terbatas (maksimum ${item.maxStock})!`);
                return;
            }
            item.qty += 1;
            localStorage.setItem('sweetBakeryCart', JSON.stringify(cart));
            updateCartUI();
        }

        function decreaseQty(index) {
            if (cart[index].qty > 1) {
                cart[index].qty -= 1;
            }
            localStorage.setItem('sweetBakeryCart', JSON.stringify(cart));
            updateCartUI();
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            localStorage.setItem('sweetBakeryCart', JSON.stringify(cart));
            updateCartUI();
        }

        function goToCheckout() {
            if (cart.length === 0) {
                showNotification('Keranjang Anda masih kosong!');
                return;
            }
            // Redirect to checkout page
            window.location.href = 'checkout.php';
        }

        function showNotification(message) {
            const alertDiv = document.createElement('div');
            alertDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, var(--color-gold), var(--color-light-gold));
                color: var(--color-dark-brown);
                padding: 15px 25px;
                border-radius: 50px;
                box-shadow: 0 8px 20px rgba(212, 165, 116, 0.3);
                font-weight: 600;
                z-index: 2000;
                animation: slideIn 0.3s ease;
            `;
            alertDiv.textContent = message;
            document.body.appendChild(alertDiv);

            setTimeout(() => {
                alertDiv.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => alertDiv.remove(), 300);
            }, 2500);
        }

        // Load cart on page load
        updateCartUI();

        // Animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(400px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(400px); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>

</body>
</html>
