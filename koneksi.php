<?php
// Konfigurasi Database
$host     = "localhost";
$username = "root";
$password = "";
$database = "db_sweet_bakery";

// Membuat koneksi ke database
$koneksi = new mysqli($host, $username, $password, $database);

// Memeriksa apakah koneksi berhasil atau gagal
if ($koneksi->connect_error) {
    die("Koneksi database gagal: " . $koneksi->connect_error);
}

// Jika berhasil, variabel $koneksi siap digunakan di file lain
?>