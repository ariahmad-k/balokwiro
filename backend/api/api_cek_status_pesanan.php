<?php
header('Content-Type: application/json');
include '../koneksi.php';

// Ambil daftar ID pesanan dari request POST
$ids_to_check = json_decode(file_get_contents('php://input'), true)['ids'] ?? [];

$response = [];

if (!empty($ids_to_check)) {
    // Buat placeholder untuk query IN (...) yang aman
    $placeholders = implode(',', array_fill(0, count($ids_to_check), '?'));
    $types = str_repeat('s', count($ids_to_check));

    // Query untuk mengambil status terbaru
    $sql = "SELECT id_pesanan, status_pesanan FROM pesanan WHERE id_pesanan IN ($placeholders)";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$ids_to_check);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        // Buat response dalam format: { "ID_PESANAN": "STATUSNYA" }
        $response[$row['id_pesanan']] = $row['status_pesanan'];
    }
}

echo json_encode($response);
exit;
?>