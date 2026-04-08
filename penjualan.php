<?php
session_start();
if(!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}
include 'koneksi.php';

// Ambil data outlet
$outlet_query = "SELECT * FROM outlet";
$outlets = mysqli_query($conn, $outlet_query);

// Proses simpan penjualan (VERSION 2 - DENGAN AUTO KURANGI STOK)
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_penjualan'])) {
    $tanggal = date('Y-m-d');
    $outlet_id = $_POST['outlet_id'];
    $total_bayar = $_POST['total_bayar'];
    
    mysqli_begin_transaction($conn);
    
    try {
        // Simpan ke penjualan
        $query_penjualan = "INSERT INTO penjualan (tanggal, outlet_id, total_bayar) 
                            VALUES ('$tanggal', '$outlet_id', '$total_bayar')";
        mysqli_query($conn, $query_penjualan);
        $penjualan_id = mysqli_insert_id($conn);
        
        // Simpan detail dan kurangi stok
        $menu_ids = $_POST['menu_id'];
        $jumlahs = $_POST['jumlah'];
        
        for($i = 0; $i < count($menu_ids); $i++) {
            if($jumlahs[$i] > 0 && $menu_ids[$i] != '') {
                // Ambil harga menu
                $menu_query = "SELECT harga FROM menu WHERE id = " . $menu_ids[$i];
                $menu_result = mysqli_query($conn, $menu_query);
                $menu = mysqli_fetch_assoc($menu_result);
                $subtotal = $menu['harga'] * $jumlahs[$i];
                
                // Simpan detail penjualan
                $query_detail = "INSERT INTO detail_penjualan (penjualan_id, menu_id, jumlah, subtotal) 
                                 VALUES ('$penjualan_id', '{$menu_ids[$i]}', '{$jumlahs[$i]}', '$subtotal')";
                mysqli_query($conn, $query_detail);
                
                // *** FITUR BARU: Kurangi stok berdasarkan resep ***
                $resep_query = "SELECT bahan_id, jumlah_dibutuhkan FROM resep WHERE menu_id = {$menu_ids[$i]}";
                $resep_result = mysqli_query($conn, $resep_query);
                
                while($resep = mysqli_fetch_assoc($resep_result)) {
                    $bahan_id = $resep['bahan_id'];
                    $jumlah_terpakai = $resep['jumlah_dibutuhkan'] * $jumlahs[$i];
                    
                    $update_stok = "UPDATE stok SET jumlah = jumlah - $jumlah_terpakai WHERE id = $bahan_id";
                    mysqli_query($conn, $update_stok);
                    
                    // Cek apakah stok minus (habis)
                    $cek_stok = "SELECT jumlah FROM stok WHERE id = $bahan_id";
                    $hasil_cek = mysqli_query($conn, $cek_stok);
                    $stok_skrg = mysqli_fetch_assoc($hasil_cek);
                    
                    if($stok_skrg['jumlah'] < 0) {
                        throw new Exception("Stok bahan habis! Stok tidak boleh minus.");
                    }
                }
            }
        }
        
        mysqli_commit($conn);
        $success = "Penjualan berhasil disimpan! Stok otomatis berkurang.";
        
    } catch(Exception $e) {
        mysqli_rollback($conn);
        $error = "Gagal menyimpan: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Tambah Penjualan - Larissi POS</title>
    <style>
        body { font-family: Arial; margin: 0; padding: 20px; background: #f5f5f5; }
        .menu-nav { background: #333; padding: 10px; margin-bottom: 20px; }
        .menu-nav a { color: white; padding: 10px 20px; text-decoration: none; }
        .form-container { background: white; padding: 20px; border-radius: 10px; max-width: 700px; margin: 0 auto; }
        select, input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #ff6b00; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .item-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
        .item-row select { flex: 2; margin: 0; }
        .item-row input { flex: 1; margin: 0; }
        .remove-btn { background: red; color: white; border: none; border-radius: 5px; cursor: pointer; padding: 10px 15px; }
        .add-btn { background: green; margin-top: 10px; }
        .total-display { font-size: 24px; font-weight: bold; color: #ff6b00; margin: 20px 0; text-align: right; }
        .success { background: green; color: white; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
        .error { background: red; color: white; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
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
    
    <div class="form-container">
        <h2>Tambah Penjualan</h2>
        
        <?php if(isset($success)) echo "<div class='success'>$success</div>"; ?>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        
        <form method="POST" id="penjualanForm">
            <label>Pilih Outlet:</label>
            <select name="outlet_id" id="outlet_id" required>
                <option value="">-- Pilih Outlet --</option>
                <?php while($outlet = mysqli_fetch_assoc($outlets)): ?>
                    <option value="<?php echo $outlet['id']; ?>"><?php echo $outlet['nama']; ?></option>
                <?php endwhile; ?>
            </select>
            
            <div id="items-container">
                <div class="item-row">
                    <select name="menu_id[]" class="menu-select" required>
                        <option value="">-- Pilih Menu (pilih outlet dulu) --</option>
                    </select>
                    <input type="number" name="jumlah[]" class="jumlah" placeholder="Jumlah" min="1" value="1" required>
                    <button type="button" class="remove-btn" onclick="removeRow(this)">X</button>
                </div>
            </div>
            
            <button type="button" class="add-btn" onclick="addRow()">+ Tambah Item</button>
            
            <div class="total-display">
                Total: Rp <span id="total_display">0</span>
                <input type="hidden" name="total_bayar" id="total_bayar" value="0">
            </div>
            
            <button type="submit" name="simpan_penjualan">Simpan Penjualan</button>
        </form>
    </div>
    
    <script>
        let menusData = {};
        
        document.getElementById('outlet_id').addEventListener('change', function() {
            const outletId = this.value;
            if(outletId) {
                fetch(`get_menu.php?outlet_id=${outletId}`)
                    .then(response => response.json())
                    .then(data => {
                        menusData = data;
                        document.querySelectorAll('.menu-select').forEach(select => {
                            updateMenuOptions(select);
                        });
                        calculateTotal();
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
        
        function updateMenuOptions(selectElement) {
            const currentValue = selectElement.value;
            selectElement.innerHTML = '<option value="">-- Pilih Menu --</option>';
            for(const id in menusData) {
                const option = document.createElement('option');
                option.value = id;
                option.textContent = `${menusData[id].nama} - Rp ${formatNumber(menusData[id].harga)}`;
                if(currentValue == id) option.selected = true;
                selectElement.appendChild(option);
            }
        }
        
        function addRow() {
            const container = document.getElementById('items-container');
            const newRow = document.createElement('div');
            newRow.className = 'item-row';
            newRow.innerHTML = `
                <select name="menu_id[]" class="menu-select" required>
                    <option value="">-- Pilih Menu --</option>
                </select>
                <input type="number" name="jumlah[]" class="jumlah" placeholder="Jumlah" min="1" value="1" required>
                <button type="button" class="remove-btn" onclick="removeRow(this)">X</button>
            `;
            container.appendChild(newRow);
            
            if(document.getElementById('outlet_id').value) {
                updateMenuOptions(newRow.querySelector('.menu-select'));
            }
            
            newRow.querySelector('.jumlah').addEventListener('input', calculateTotal);
            newRow.querySelector('.menu-select').addEventListener('change', calculateTotal);
        }
        
        function removeRow(btn) {
            const container = document.getElementById('items-container');
            if(container.children.length > 1) {
                btn.closest('.item-row').remove();
                calculateTotal();
            } else {
                alert('Minimal 1 item!');
            }
        }
        
        function calculateTotal() {
            let total = 0;
            const rows = document.querySelectorAll('.item-row');
            
            rows.forEach(row => {
                const menuSelect = row.querySelector('.menu-select');
                const jumlahInput = row.querySelector('.jumlah');
                const menuId = menuSelect.value;
                const jumlah = parseInt(jumlahInput.value) || 0;
                
                if(menuId && menusData[menuId]) {
                    total += menusData[menuId].harga * jumlah;
                }
            });
            
            document.getElementById('total_display').innerText = formatNumber(total);
            document.getElementById('total_bayar').value = total;
        }
        
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
        
        document.addEventListener('input', function(e) {
            if(e.target.classList.contains('jumlah') || e.target.classList.contains('menu-select')) {
                calculateTotal();
            }
        });
    </script>
</body>
</html>