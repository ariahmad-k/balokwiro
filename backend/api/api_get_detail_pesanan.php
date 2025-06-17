<?php
// Set header agar output berupa JSON
header('Content-Type: application/json');
include '../koneksi.php'; // Sesuaikan path ke koneksi.php

// Siapkan array untuk response
$response = [
    'error' => true,
    'message' => 'ID Pesanan tidak valid.',
    'data' => null
];

if (isset($_GET['id'])) {
    $id_pesanan = $_GET['id'];

    // Ambil data header pesanan
    $sql_header = "SELECT pk.*, k.nama AS nama_kasir FROM pesanan pk LEFT JOIN karyawan k ON pk.id_karyawan = k.id_karyawan WHERE pk.id_pesanan = ?";
    $stmt_header = mysqli_prepare($koneksi, $sql_header);
    mysqli_stmt_bind_param($stmt_header, "s", $id_pesanan);
    mysqli_stmt_execute($stmt_header);
    $header = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_header));

    if ($header) {
        // Ambil data item pesanan
        $sql_items = "SELECT dp.jumlah, dp.harga_saat_transaksi, dp.sub_total, p.nama_produk FROM detail_pesanan dp JOIN produk p ON dp.id_produk = p.id_produk WHERE dp.id_pesanan = ?";
        $stmt_items = mysqli_prepare($koneksi, $sql_items);
        mysqli_stmt_bind_param($stmt_items, "s", $id_pesanan);
        mysqli_stmt_execute($stmt_items);
        $items = mysqli_fetch_all(mysqli_stmt_get_result($stmt_items), MYSQLI_ASSOC);

        // Jika berhasil, siapkan response sukses
        $response = [
            'error' => false,
            'message' => 'Data ditemukan.',
            'data' => [
                'header' => $header,
                'items' => $items
            ]
        ];
    }
}

// Kembalikan response dalam format JSON
echo json_encode($response);
exit;
?>