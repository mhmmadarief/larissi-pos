<?php
session_start();
if(!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}
include 'koneksi.php';

$tanggal_hari_ini = date('Y-m-d');
$query_penjualan = "SELECT SUM(total_bayar) as total FROM penjualan WHERE tanggal = '$tanggal_hari_ini'";
$result = mysqli_query($conn, $query_penjualan);
$total_hari_ini = mysqli_fetch_assoc($result)['total'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Larissi POS</title>
    <style>
        body {
            font-family: Arial;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .menu {
            background: #333;
            padding: 10px;
            margin-bottom: 20px;
        }
        .menu a {
            color: white;
            padding: 10px 20px;
            text-decoration: none;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .total {
            font-size: 32px;
            color: #ff6b00;
            font-weight: bold;
        }
        button {
            background: #ff6b00;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="penjualan.php">Tambah Penjualan</a>
        <a href="stok.php">Manajemen Stok</a>
        <a href="laporan.php">Laporan</a>
        <a href="logout.php">Logout</a>
    </div>
    
    <div class="card">
        <h2>Selamat datang, <?php echo $_SESSION['admin']; ?>!</h2>
        <p>Larissi Ayam Crispy & Melek Tea - Sistem Manajemen</p>
    </div>
    
    <div class="card">
        <h3>Penjualan Hari Ini (<?php echo $tanggal_hari_ini; ?>)</h3>
        <div class="total">Rp <?php echo number_format($total_hari_ini, 0, ',', '.'); ?></div>
    </div>
    
    <div class="card">
        <h3>Aksi Cepat</h3>
        <a href="penjualan.php"><button>Tambah Penjualan Baru</button></a>
    </div>
</body>
</html>