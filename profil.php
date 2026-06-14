<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: login.php?pesan=belum_login");
    exit;
}

include 'koneksi.php';

$username = $_SESSION['username'];
$nama = $_SESSION['nama'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Sweet Bakery</title>
    
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
            --color-light-gold: #E8D4B8;
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

        /* ==================== PROFILE CARD ==================== */
        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(107, 83, 68, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
            border: 1px solid rgba(107, 83, 68, 0.05);
        }

        .profile-card-header {
            background: linear-gradient(135deg, var(--color-vanilla) 0%, var(--color-light-gold) 100%);
            padding: 30px;
            border-bottom: 2px solid rgba(107, 83, 68, 0.1);
        }

        .profile-card-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--color-dark-brown);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .profile-card-header i {
            color: var(--color-gold);
            font-size: 2rem;
        }

        .profile-card-body {
            padding: 40px 30px;
        }

        .info-group {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f1f5f9;
        }

        .info-group:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--color-coffee);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-label i {
            color: var(--color-gold);
            font-size: 1.1rem;
        }

        .info-value {
            font-size: 1.2rem;
            color: var(--color-dark-brown);
            font-weight: 500;
            word-break: break-word;
        }

        /* ==================== ACTION BUTTONS ==================== */
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #f1f5f9;
        }

        .btn-action {
            padding: 14px 24px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .btn-home {
            background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-light-gold) 100%);
            color: var(--color-dark-brown);
        }

        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(212, 165, 116, 0.3);
            color: var(--color-dark-brown);
        }

        .btn-logout {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
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

            .profile-card-body {
                padding: 25px;
            }

            .action-buttons {
                grid-template-columns: 1fr;
            }

            .info-value {
                font-size: 1rem;
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

    <!-- ==================== PAGE HEADER ==================== -->
    <div class="container">
        <div class="page-header">
            <h1>
                <i class="bi bi-person-circle"></i> Profil Saya
            </h1>
            <p>Kelola informasi akun dan profil pribadi Anda</p>
        </div>

        <!-- ==================== PROFILE CARD ==================== -->
        <div class="profile-card">
            <div class="profile-card-header">
                <h2>
                    <i class="bi bi-person-vcard"></i> Informasi Akun
                </h2>
            </div>

            <div class="profile-card-body">
                <!-- Nama Lengkap -->
                <div class="info-group">
                    <div class="info-label">
                        <i class="bi bi-person"></i> Nama Lengkap
                    </div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($nama); ?>
                    </div>
                </div>

                <!-- Username -->
                <div class="info-group">
                    <div class="info-label">
                        <i class="bi bi-at"></i> Username
                    </div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($username); ?>
                    </div>
                </div>

                <!-- Tipe Akun -->
                <div class="info-group">
                    <div class="info-label">
                        <i class="bi bi-shield-check"></i> Tipe Akun
                    </div>
                    <div class="info-value">
                        Pelanggan <span style="background: rgba(212, 165, 116, 0.2); color: var(--color-gold); padding: 4px 12px; border-radius: 20px; font-size: 0.85rem;">User</span>
                    </div>
                </div>

                <!-- Status Akun -->
                <div class="info-group">
                    <div class="info-label">
                        <i class="bi bi-check-circle"></i> Status Akun
                    </div>
                    <div class="info-value">
                        Aktif ✓ <span style="color: #10b981; font-weight: 600;"></span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="home.php" class="btn-action btn-home">
                        <i class="bi bi-shop"></i> Kembali ke Toko
                    </a>
                    <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?');" class="btn-action btn-logout">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
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
