<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: login.php?pesan=belum_login");
    exit;
}

include 'koneksi.php';

$nama = $_SESSION['nama'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - Sweet Bakery</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --color-cream: #FBF8F3;
            --color-vanilla: #F5EFE7;
            --color-coffee: #8B6F47;
            --color-brown: #6B5344;
            --color-gold: #D4A574;
            --color-dark-brown: #4A3728;
            --color-peach: #F4E4D7;
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

        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--color-dark-brown);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-brand i {
            color: var(--color-gold);
            font-size: 2rem;
        }

        .nav-link {
            font-weight: 500;
            color: var(--color-coffee) !important;
            margin: 0 0.8rem;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--color-gold) !important;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* ==================== PAGE HEADER ==================== */
        .page-header {
            background: linear-gradient(135deg, var(--color-vanilla) 0%, var(--color-peach) 100%);
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(107, 83, 68, 0.1);
        }

        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--color-dark-brown);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header p {
            color: var(--color-coffee);
            font-size: 1rem;
            margin: 0;
        }

        /* ==================== EMPTY STATE ==================== */
        .empty-state {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(107, 83, 68, 0.1);
            padding: 60px 30px;
            text-align: center;
            border: 2px dashed rgba(212, 165, 116, 0.3);
        }

        .empty-state-icon {
            font-size: 4rem;
            color: var(--color-gold);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: var(--color-dark-brown);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .empty-state p {
            color: var(--color-coffee);
            margin-bottom: 30px;
            font-size: 1rem;
        }

        /* ==================== BUTTONS ==================== */
        .btn-shop {
            background: linear-gradient(135deg, var(--color-coffee) 0%, var(--color-brown) 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(139, 111, 71, 0.3);
        }

        .btn-shop:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(139, 111, 71, 0.4);
            color: white;
        }

        /* ==================== FOOTER ==================== */
        footer {
            background: var(--color-dark-brown);
            color: var(--color-vanilla);
            padding: 30px 20px 20px;
            text-align: center;
            margin-top: 60px;
        }

        footer p {
            margin: 0;
            font-size: 0.95rem;
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 768px) {
            .page-header {
                padding: 25px;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .empty-state {
                padding: 40px 20px;
            }
        }
    </style>
</head>
<body>

    <!-- ==================== NAVBAR ==================== -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="home.php">
                <i class="bi bi-cup-hot-fill"></i> Sweet Bakery
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="home.php">
                            <i class="bi bi-house"></i> Toko
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="riwayat_pesanan.php">
                            <i class="bi bi-list-check"></i> Pesanan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profil.php">
                            <i class="bi bi-person-circle"></i> Profil
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ==================== PAGE CONTENT ==================== -->
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <i class="bi bi-list-check"></i> Riwayat Pesanan
            </h1>
            <p>Lihat semua pesanan dan status pembelian Anda</p>
        </div>

        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-inbox"></i>
            </div>
            <h3>Belum Ada Pesanan</h3>
            <p>Anda belum membuat pesanan apapun. Mulai berbelanja sekarang dan nikmati roti segar dan kue lezat kami!</p>
            <a href="home.php" class="btn-shop">
                <i class="bi bi-shop"></i> Belanja Sekarang
            </a>
        </div>
    </div>

    <!-- ==================== FOOTER ==================== -->
    <footer>
        <p>&copy; 2024 Sweet Bakery. Semua hak dilindungi.</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
