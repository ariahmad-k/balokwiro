<?php
session_start();
include 'backend/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_data'])) {
    // --- Validasi Input Form (Tidak ada perubahan) ---
    $nama_pemesan = trim($_POST['nama_pemesan']);
    $no_telepon = trim($_POST['no_telepon']);
    $catatan = trim($_POST['catatan'] ?? '');
    $jenis_pesanan = $_POST['jenis_pesanan'] ?? 'take_away';
    $metode_pembayaran = $_POST['metode_pembayaran'] ?? ''; // Asumsi pembayaran online
    $cart_data = json_decode($_POST['cart_data'], true);

    if (!ctype_digit($no_telepon) || strlen($no_telepon) < 12 || strlen($no_telepon) > 13) {
        $_SESSION['notif_cart'] = ['pesan' => 'Format nomor telepon tidak valid.', 'tipe' => 'danger'];
        header('Location: keranjang.php');
        exit;
    }
    if (empty($nama_pemesan) || empty($cart_data) || json_last_error() !== JSON_ERROR_NONE) {
        $_SESSION['notif_cart'] = ['pesan' => 'Data tidak lengkap.', 'tipe' => 'danger'];
        header('Location: keranjang.php');
        exit;
    }

    // --- Mulai Transaksi ---
    mysqli_begin_transaction($koneksi);

    try {
        // =========================================================================
        // ==         BAGIAN 1: VALIDASI STOK DENGAN LOGIKA HISTORI BARU          ==
        // =========================================================================
        $total_harga_server = 0;
        $produk_info_map = []; // Untuk menyimpan info produk

        // Siapkan statement untuk cek produk dan kueri stok
        $stmt_produk_info = mysqli_prepare($koneksi, "SELECT id_produk, nama_produk, harga, status_produk, id_kategori_stok FROM produk WHERE id_produk = ?");
        $stmt_cek_stok_kategori = mysqli_prepare($koneksi, "SELECT SUM(jumlah_perubahan) as total FROM log_stok WHERE id_kategori_stok = ?");
        $stmt_cek_stok_individu = mysqli_prepare($koneksi, "SELECT SUM(jumlah_perubahan) as total FROM log_stok WHERE id_produk = ?");

        foreach ($cart_data as $id => $item) {
            // Ambil info dasar produk
            mysqli_stmt_bind_param($stmt_produk_info, "s", $id);
            mysqli_stmt_execute($stmt_produk_info);
            $produk_db = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_produk_info));

            if (!$produk_db || $produk_db['status_produk'] !== 'aktif') {
                throw new Exception("Produk '{$item['nama']}' tidak tersedia.");
            }

            // Cek stok dari tabel log_stok
            $stok_saat_ini = 0;
            if ($produk_db['id_kategori_stok'] !== null) {
                mysqli_stmt_bind_param($stmt_cek_stok_kategori, "s", $produk_db['id_kategori_stok']);
                mysqli_stmt_execute($stmt_cek_stok_kategori);
                $stok_saat_ini = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek_stok_kategori))['total'] ?? 0;
            } else {
                mysqli_stmt_bind_param($stmt_cek_stok_individu, "s", $id);
                mysqli_stmt_execute($stmt_cek_stok_individu);
                $stok_saat_ini = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek_stok_individu))['total'] ?? 0;
            }

            if ($stok_saat_ini < $item['jumlah']) {
                throw new Exception("Stok untuk produk '{$item['nama']}' tidak mencukupi (sisa: {$stok_saat_ini}).");
            }

            $total_harga_server += $produk_db['harga'] * $item['jumlah'];
            $produk_info_map[$id] = $produk_db; // Simpan info produk untuk nanti
        }

        // =========================================================================
        // ==                 BAGIAN PEMBUATAN PESANAN (TETAP SAMA)             ==
        // =========================================================================
        $id_pesanan_baru = "ONLINE-" . time();
        $tgl_pesanan = date("Y-m-d H:i:s");
        $tipe_pesanan = 'online';
        $status_pesanan = 'menunggu_pembayaran';
        

        $stmt_pesanan = mysqli_prepare($koneksi, "INSERT INTO pesanan (id_pesanan, tipe_pesanan, jenis_pesanan, nama_pemesan, no_telepon, catatan, tgl_pesanan, total_harga, metode_pembayaran, status_pesanan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_pesanan, "sssssssdss", $id_pesanan_baru, $tipe_pesanan, $jenis_pesanan, $nama_pemesan, $no_telepon, $catatan, $tgl_pesanan, $total_harga_server, $metode_pembayaran, $status_pesanan);
        mysqli_stmt_execute($stmt_pesanan);

        // =========================================================================
        // ==       BAGIAN PENGURANGAN STOK (HANYA INSERT KE LOG_STOK)          ==
        // =========================================================================
        $stmt_detail = mysqli_prepare($koneksi, "INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga_saat_transaksi, sub_total) VALUES (?, ?, ?, ?, ?)");
        $stmt_log = mysqli_prepare($koneksi, "INSERT INTO log_stok (id_produk, id_kategori_stok, jumlah_perubahan, jenis_aksi, id_pesanan, keterangan) VALUES (?, ?, ?, 'penjualan', ?, ?)");

        foreach ($cart_data as $id => $item) {
            // 1. Insert ke detail pesanan
            $harga_saat_ini = $produk_info_map[$id]['harga'];
            $sub_total = $harga_saat_ini * $item['jumlah'];
            mysqli_stmt_bind_param($stmt_detail, "ssidd", $id_pesanan_baru, $id, $item['jumlah'], $harga_saat_ini, $sub_total);
            mysqli_stmt_execute($stmt_detail);

            // 2. Catat pengurangan stok di log_stok
            $id_produk_log = null;
            $id_kategori_log = null;
            $jumlah_pengurangan = -1 * abs($item['jumlah']); // Pastikan nilainya negatif
            $keterangan_log = "Penjualan online";

            if ($produk_info_map[$id]['id_kategori_stok'] !== null) {
                $id_kategori_log = $produk_info_map[$id]['id_kategori_stok'];
            } else {
                $id_produk_log = $id;
            }

            mysqli_stmt_bind_param($stmt_log, "ssiss", $id_produk_log, $id_kategori_log, $jumlah_pengurangan, $id_pesanan_baru, $keterangan_log);
            mysqli_stmt_execute($stmt_log);
        }

        // Jika semua berhasil, simpan perubahan
        mysqli_commit($koneksi);

        // Arahkan ke halaman konfirmasi
        header("Location: konfirmasi.php?id=" . $id_pesanan_baru);
        exit;
    } catch (Exception $e) {
        // Jika ada error, batalkan semua query
        mysqli_rollback($koneksi);
        $_SESSION['notif_cart'] = ['pesan' => 'Gagal membuat pesanan: ' . $e->getMessage(), 'tipe' => 'danger'];
        header('Location: keranjang.php');
        exit;
    }
} else {
    header('Location: menu.php');
    exit;
}
