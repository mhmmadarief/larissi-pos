<?php
session_start();
if(!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}
include 'koneksi.php';

// Filter tanggal (opsional)
$filter_tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$filter_outlet = isset($_GET['outlet_id']) ? $_GET['outlet_id'] : '';

// Query ambil laporan
$query = "SELECT p.*, o.nama as nama_outlet 
          FROM penjualan p 
          JOIN outlet o ON p.outlet_id = o.id 
          WHERE 1=1";

if($filter_tanggal) {
    $query .= " AND p.tanggal = '$filter_tanggal'";
}
if($filter_outlet) {
    $query .= " AND p.outlet_id = '$filter_outlet'";
}

$query .= " ORDER BY p.tanggal DESC, p.id DESC";
$penjualan = mysqli_query($conn, $query);

// Ambil data outlet untuk filter
$outlet_query = "SELECT * FROM outlet";
$outlets = mysqli_query($conn, $outlet_query);

// Hitung total
$query_total = "SELECT SUM(p.total_bayar) as total 
                FROM penjualan p 
                WHERE 1=1";
if($filter_tanggal) {
    $query_total .= " AND p.tanggal = '$filter_tanggal'";
}
if($filter_outlet) {
    $query_total .= " AND p.outlet_id = '$filter_outlet'";
}
$total_result = mysqli_query($conn, $query_total);
$total_penjualan = mysqli_fetch_assoc($total_result)['total'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penjualan - Larissi POS</title>
    <style>
        body { font-family: Arial; margin: 0; padding: 20px; background: #f5f5f5; }
        .menu-nav { background: #333; padding: 10px; margin-bottom: 20px; }
        .menu-nav a { color: white; padding: 10px 20px; text-decoration: none; }
        .container { background: white; padding: 20px; border-radius: 10px; max-width: 1200px; margin: 0 auto; }
        .filter-box { background: #f9f9f9; padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
        .filter-group { display: flex; flex-direction: column; }
        .filter-group label { font-size: 12px; margin-bottom: 5px; color: #666; }
        .filter-group input, .filter-group select { padding: 8px; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #ff6b00; color: white; padding: 8px 20px; border: none; border-radius: 5px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #ff6b00; color: white; }
        tr:hover { background: #f5f5f5; }
        .total-box { background: #ff6b00; color: white; padding: 15px; border-radius: 10px; margin-top: 20px; text-align: right; }
        .total-box h3 { margin: 0; }
        .total-box .total { font-size: 28px; }
        .detail-btn { background: #333; color: white; padding: 5px 10px; border-radius: 3px; text-decoration: none; font-size: 12px; }
        .no-data { text-align: center; padding: 40px; color: #999; }
    </style>
</head>
<body>
    <div class="menu-nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="penjualan.php">Tambah Penjualan</a>
        <a href="stok.php">Manajemen Stok</a>
        <a href="laporan.php">Laporan</a>
        <a href="logout.php">Logout</a>
    </div>
    
    <div class="container">
        <h2>Laporan Penjualan</h2>
        
        <!-- Filter Form -->
        <div class="filter-box">
            <div class="filter-group">
                <label>Filter Tanggal</label>
                <input type="date" id="filter_tanggal" value="<?php echo $filter_tanggal; ?>">
            </div>
            <div class="filter-group">
                <label>Filter Outlet</label>
                <select id="filter_outlet">
                    <option value="">-- Semua Outlet --</option>
                    <?php while($outlet = mysqli_fetch_assoc($outlets)): ?>
                        <option value="<?php echo $outlet['id']; ?>" <?php echo ($filter_outlet == $outlet['id']) ? 'selected' : ''; ?>>
                            <?php echo $outlet['nama']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button onclick="applyFilter()">Tampilkan</button>
            <button onclick="resetFilter()" style="background: #666;">Reset</button>
            <button onclick="printLaporan()" style="background: green;">🖨️ Cetak</button>
        </div>
        
        <!-- Tabel Laporan -->
        <table id="laporan-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Outlet</th>
                    <th>Total Bayar</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($penjualan) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($penjualan)): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                            <td><?php echo $row['nama_outlet']; ?></td>
                            <td>Rp <?php echo number_format($row['total_bayar'], 0, ',', '.'); ?></td>
                            <td>
                                <a href="detail_penjualan.php?id=<?php echo $row['id']; ?>" class="detail-btn">Lihat Detail</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="no-data">Belum ada data penjualan</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Total Box -->
        <div class="total-box">
            <h3>Total Pendapatan</h3>
            <div class="total">Rp <?php echo number_format($total_penjualan, 0, ',', '.'); ?></div>
        </div>
    </div>
    
    <script>
        function applyFilter() {
            const tanggal = document.getElementById('filter_tanggal').value;
            const outlet = document.getElementById('filter_outlet').value;
            let url = 'laporan.php?';
            if(tanggal) url += 'tanggal=' + tanggal + '&';
            if(outlet) url += 'outlet_id=' + outlet;
            window.location.href = url;
        }
        
        function resetFilter() {
            window.location.href = 'laporan.php';
        }
        
        function printLaporan() {
            window.print();
        }
    </script>
    <style>
        @media print {
            .menu-nav, .filter-box, .detail-btn, button, .total-box:last-child {
                display: none;
            }
            .total-box {
                background: #ff6b00;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .container {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</body>
</html>