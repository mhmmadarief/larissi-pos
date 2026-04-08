<?php
session_start();
if(!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}
include 'koneksi.php';

// Proses update stok
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_stok'])) {
    $id = $_POST['id'];
    $jumlah_baru = $_POST['jumlah'];
    
    $query = "UPDATE stok SET jumlah = '$jumlah_baru' WHERE id = '$id'";
    if(mysqli_query($conn, $query)) {
        $success = "Stok berhasil diupdate!";
    } else {
        $error = "Gagal mengupdate stok!";
    }
}

// Ambil data stok
$query = "SELECT * FROM stok ORDER BY nama_bahan";
$stok = mysqli_query($conn, $query);

// Tambahkan setelah query ambil stok (sebelum HTML)
// Hitung stok minimum yang dibutuhkan
$query_min_stok = "SELECT s.id, s.nama_bahan, s.jumlah, 
                   COALESCE(SUM(r.jumlah_dibutuhkan), 0) as total_dibutuhkan_per_menu
                   FROM stok s
                   LEFT JOIN resep r ON s.id = r.bahan_id
                   GROUP BY s.id";
$min_stok_result = mysqli_query($conn, $query_min_stok);
$stok_warning = [];
while($row = mysqli_fetch_assoc($min_stok_result)) {
    if($row['jumlah'] < $row['total_dibutuhkan_per_menu'] && $row['total_dibutuhkan_per_menu'] > 0) {
        $stok_warning[$row['id']] = "⚠️ Stok tidak cukup untuk 1 porsi menu!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manajemen Stok - Larissi POS</title>
    <style>
        body { font-family: Arial; margin: 0; padding: 20px; background: #f5f5f5; }
        .menu-nav { background: #333; padding: 10px; margin-bottom: 20px; }
        .menu-nav a { color: white; padding: 10px 20px; text-decoration: none; }
        .container { background: white; padding: 20px; border-radius: 10px; max-width: 800px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #ff6b00; color: white; }
        input { padding: 8px; width: 100px; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #ff6b00; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; }
        .success { background: green; color: white; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
        .error { background: red; color: white; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
        .stok-menipis { color: red; font-weight: bold; }
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
        <h2>Manajemen Stok Bahan</h2>
        
        <?php if(isset($success)) echo "<div class='success'>$success</div>"; ?>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        
        <table>
            <thead>
                <tr>
                    <th>Nama Bahan</th>
                    <th>Satuan</th>
                    <th>Jumlah Saat Ini</th>
                    <th>Status</th>
                    <th>Update Stok</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($stok)): ?>
                <tr>
                    <td><?php echo $row['nama_bahan']; ?></td>
                    <td><?php echo $row['satuan']; ?></td>
                    <td class="<?php echo ($row['jumlah'] < 10) ? 'stok-menipis' : ''; ?>">
                        <?php echo $row['jumlah']; ?> <?php echo $row['satuan']; ?>
                        <?php if($row['jumlah'] < 10): ?>
                            <span style="font-size: 12px;">⚠️ Stok menipis!</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                        if(isset($stok_warning[$row['id']])) {
                            echo "<span style='color: red; font-size: 12px;'>{$stok_warning[$row['id']]}</span>";
                        } else {
                            echo "<span style='color: green; font-size: 12px;'>✅ Stok cukup</span>";
                     }
                     ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <p style="margin-top: 20px; color: #666; font-size: 14px;">
            <strong>Catatan:</strong> Stok akan otomatis berkurang setiap ada penjualan (fitur ini akan kita tambahkan nanti).
        </p>
    </div>
</body>
</html>