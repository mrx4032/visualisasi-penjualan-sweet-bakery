<?php
// Memulai session
session_start();

// Jika user sudah login, arahkan sesuai role
if (isset($_SESSION['status']) && $_SESSION['status'] == "login") {
    if ($_SESSION['level'] == "admin") {
        header("Location: dashboard.php");
    } else {
        header("Location: home.php");
    }
    exit;
}

// Menghubungkan ke database
include 'koneksi.php';

// Menangkap pesan error jika ada (misal: gagal login atau belum login)
$error = "";
if (isset($_GET['pesan'])) {
    if ($_GET['pesan'] == "gagal") {
        $error = "Login gagal! Username atau Password salah.";
    } else if ($_GET['pesan'] == "logout") {
        $error = "Anda telah berhasil logout.";
    } else if ($_GET['pesan'] == "belum_login") {
        $error = "Anda harus login untuk mengakses halaman tersebut.";
    }
}

// Proses form saat disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengamankan input dari SQL Injection
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    // Query untuk memeriksa kecocokan username dan password di tabel_users
    // Catatan: Jika password di database di-hash (misal pakai md5/bcrypt), sesuaikan query-nya.
    // Contoh ini berasumsi password disimpan dalam bentuk teks biasa (plain text).
    $query = mysqli_query($koneksi, "SELECT * FROM tabel_users WHERE username='$username' AND password='$password'");
    $cek = mysqli_num_rows($query);

    if ($cek > 0) {
        $data = mysqli_fetch_assoc($query);
        
        // Menyimpan data ke dalam session
        $_SESSION['username'] = $username;
        $_SESSION['nama'] = $data['nama_lengkap']; // Sesuaikan dengan kolom tabel_users
        $_SESSION['level'] = $data['level']; // Simpan level/role
        $_SESSION['status'] = "login";
        
        // Alihkan sesuai dengan level user
        if ($data['level'] == "admin") {
            header("Location: dashboard.php");
        } else {
            header("Location: home.php");
        }
        exit;
    } else {
        // Jika gagal, alihkan kembali ke login dengan pesan error
        header("Location: login.php?pesan=gagal");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Sweet Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            background: linear-gradient(135deg, var(--color-vanilla) 0%, var(--color-cream) 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(107, 83, 68, 0.15);
            padding: 40px;
            width: 100%;
            max-width: 450px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--color-dark-brown);
            margin-bottom: 10px;
        }

        .login-header p {
            color: var(--color-coffee);
            font-size: 0.95rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--color-dark-brown);
            margin-bottom: 8px;
        }

        .form-control {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            color: var(--color-dark-brown);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--color-gold);
            box-shadow: 0 0 0 0.2rem rgba(212, 165, 116, 0.25);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--color-coffee) 0%, var(--color-brown) 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 10px;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(139, 111, 71, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(139, 111, 71, 0.4);
        }

        .alert {
            border: none;
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 12px 15px;
        }

        .alert-danger {
            background-color: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }

        .alert-success {
            background-color: #efe;
            color: #3c3;
            border-left: 4px solid #3c3;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            color: var(--color-coffee);
        }

        .register-link a {
            color: var(--color-gold);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: var(--color-coffee);
        }

        .back-home {
            text-align: center;
            margin-bottom: 20px;
        }

        .back-home a {
            color: var(--color-gold);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s ease;
        }

        .back-home a:hover {
            color: var(--color-coffee);
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="back-home">
        <a href="home.php"><i class="bi bi-arrow-left"></i> Kembali ke Toko</a>
    </div>

    <div class="login-header">
        <h1><i class="bi bi-cup-hot-fill" style="color: var(--color-gold);"></i> Sweet Bakery</h1>
        <p>Masuk ke akun Anda</p>
    </div>
    
    <?php if ($error != ""): ?>
        <div class="alert <?php echo (isset($_GET['pesan']) && $_GET['pesan'] == 'logout') ? 'alert-success' : 'alert-danger'; ?>">
            <i class="bi <?php echo (isset($_GET['pesan']) && $_GET['pesan'] == 'logout') ? 'bi-check-circle' : 'bi-exclamation-circle'; ?>"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required>
        </div>
        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
        </div>
        <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right"></i> Login
        </button>
    </form>

    <div class="register-link">
        Belum memiliki akun? <a href="register.php">Daftar di sini</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>