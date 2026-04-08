<?php
include 'koneksi.php';

if(isset($_GET['outlet_id'])) {
    $outlet_id = $_GET['outlet_id'];
    $query = "SELECT id, nama, harga FROM menu WHERE outlet_id = '$outlet_id'";
    $result = mysqli_query($conn, $query);
    
    $menus = [];
    while($row = mysqli_fetch_assoc($result)) {
        $menus[$row['id']] = [
            'nama' => $row['nama'],
            'harga' => $row['harga']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($menus);
} else {
    echo json_encode([]);
}
?>