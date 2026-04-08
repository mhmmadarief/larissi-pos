<?php
session_start();
if(!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}
include 'koneksi.php';

$id = $_GET['id'];
$query = "SELECT p.*, o.nama as nama_outlet 
          FROM penjualan p 
          JOIN outlet o ON p.outlet_id = o.id 
          WHERE p.id = '$id'";
$result = mysqli_query($conn, $query);
$penjualan = mysqli_fetch_assoc($result);

if(!$penjualan) {
    header('Location: laporan.php');
    exit();
}

$query_detail = "SELECT d.*, m.nama as nama_menu 
                 FROM detail_penjualan d 
                 JOIN menu m ON d.menu_id = m.id 
                 WHERE d.penjualan_id = '$id'";
$details = mysqli_query($conn, $query_detail);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Detail Penjualan - Larissi POS</title>
    <style>
        body { font-family: Arial; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 10px; max-width: 600px; margin: 0 auto; }
        .back-btn { background: #333; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #ff6b00; color: white; }
        .total { font-size: 18px; font-weight: bold; text-align: right; margin-top: 20px; padding-top: 10px; border-top: 2px solid #ff6b00; }
        .info { background: #f9f9f9; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        @media print {
    .menu-nav, .back-btn, button, .total-box:last-child {
        display: none;
    }
}
@media print {
    body {
        padding: 0;
        margin: 0;
    }
    .container {
        box-shadow: none;
        padding: 10px;
    }
}
.btn-cetak {
    cursor: pointer;
}
    </style>
</head>
<body>
    <div class="container">
        <a href="laporan.php" class="back-btn">← Kembali ke Laporan</a>
        
        <h2>Detail Penjualan</h2>
        
        <div class="info">
            <strong>Tanggal:</strong> <?php echo date('d/m/Y', strtotime($penjualan['tanggal'])); ?><br>
            <strong>Outlet:</strong> <?php echo $penjualan['nama_outlet']; ?>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Menu</th>
                    <th>Jumlah</th>
                    <th>Harga</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php while($detail = mysqli_fetch_assoc($details)): ?>
                    <tr>
                        <td><?php echo $detail['nama_menu']; ?></td>
                        <td><?php echo $detail['jumlah']; ?></td>
                        <td>Rp <?php echo number_format($detail['subtotal'] / $detail['jumlah'], 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($detail['subtotal'], 0, ',', '.'); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <div class="total">
            Total: Rp <?php echo number_format($penjualan['total_bayar'], 0, ',', '.'); ?>
            <button onclick="printLaporan()" class="btn-cetak" style="background: green;">🖨️ Cetak</button>
        </div>
    </div>
<script>
function printLaporan() {
    window.print();
}
</script>
</body>
</html>