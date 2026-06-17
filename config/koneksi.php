<?php
// Konfigurasi Database
$host     = "localhost";
$username = "root";
$password = "";
$database = "ukki_inventory";

// Membuat koneksi
$conn = mysqli_connect($host, $username, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}

date_default_timezone_set('Asia/Jakarta');

function query($query) {
    global $conn;
    $result = mysqli_query($conn, $query);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>