<?php
$host = 'sql108.infinityfree.com'; // Ganti dengan host database
$dbname = 'if0_38007014_finance_manager'; // Ganti dengan nama database Anda
$username = 'if0_38007014'; // Ganti dengan username database
$password = 'NorThgHwNmMu'; // Ganti dengan password database

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi ke database gagal: " . $e->getMessage());
}
?>
