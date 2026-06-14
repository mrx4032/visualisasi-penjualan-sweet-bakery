<?php
session_start();

// Jika sudah login, arahkan ke halaman sesuai role
if (isset($_SESSION['status']) && $_SESSION['status'] == "login") {
    if ($_SESSION['level'] == "admin") {
        header("Location: dashboard.php");
    } else {
        header("Location: home.php");
    }
    exit;
}

include 'koneksi.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);
    $password_confirm = mysqli_real_escape_string($koneksi, $_POST['password_confirm']);

    // Validasi
    if (empty($nama_lengkap) || empty($username) || empty($password) || empty($password_confirm)) {
        $error = "Semua field harus diisi!";
    } else if ($password != $password_confirm) {
        $error = "Password tidak cocok!";
    } else if (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        // Cek apakah username sudah ada
        $cek_username = $koneksi->query("SELECT * FROM tabel_users WHERE username='$username'");
        if ($cek_username->num_rows > 0) {
            $error = "Username sudah digunakan!";
        } else {
            // Insert user baru dengan level 'user'
            $insert = $koneksi->query("INSERT INTO tabel_users (username, password, nama_lengkap, level) 
                                      VALUES ('$username', '$password', '$nama_lengkap', 'user')");
            if ($insert) {
                $success = "Registrasi berhasil! Silakan login.";
                // Redirect ke login setelah 2 detik
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 2000);
                </script>";
            } else {
                $error = "Terjadi kesalahan saat registrasi: " . $koneksi->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Sweet Bakery</title>
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

        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(107, 83, 68, 0.15);
            padding: 40px;
            width: 100%;
            max-width: 450px;
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--color-dark-brown);
            margin-bottom: 10px;
        }

        .register-header p {
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

        .btn-register {
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

        .btn-register:hover {
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

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: var(--color-coffee);
        }

        .login-link a {
            color: var(--color-gold);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
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

    <div class="register-container">
        <div class="back-home">
            <a href="home.php"><i class="bi bi-arrow-left"></i> Kembali ke Toko</a>
        </div>

        <div class="register-header">
            <h1><i class="bi bi-cup-hot-fill" style="color: var(--color-gold);"></i> Sweet Bakery</h1>
            <p>Buat akun untuk memulai berbelanja</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
            </div>

            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="password_confirm" class="form-label">Konfirmasi Password</label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
            </div>

            <button type="submit" class="btn-register">
                <i class="bi bi-person-plus"></i> Daftar Sekarang
            </button>
        </form>

        <div class="login-link">
            Sudah memiliki akun? <a href="login.php">Login di sini</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
