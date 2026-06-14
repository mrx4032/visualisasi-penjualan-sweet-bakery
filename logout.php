<?php
// Memulai session
session_start();

// Menghapus semua session
session_unset();
session_destroy();

// Mengalihkan halaman kembali ke halaman login dengan pesan logout
header("Location: login.php?pesan=logout");
exit;
?>