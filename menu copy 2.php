<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$page_title = "Menu Lengkap";
include __DIR__ . '/backend/cek_kadaluarsa.php';
include 'backend/koneksi.php';
// include 'includes/header.php'; // Di sini sudah ada session_start() dan koneksi.php

// =========================================================================
// ==           LOGIKA PENGAMBILAN DATA BARU (BERBASIS LOG_STOK)          ==
// =========================================================================

// 1. Ambil semua data stok terkini dari log_stok dalam satu kali query
$stok_terkini = [];
$query_stok = "
    SELECT id_produk, NULL as id_kategori_stok, SUM(jumlah_perubahan) as total FROM log_stok WHERE id_produk IS NOT NULL GROUP BY id_produk
    UNION ALL
    SELECT NULL as id_produk, id_kategori_stok, SUM(jumlah_perubahan) as total FROM log_stok WHERE id_kategori_stok IS NOT NULL GROUP BY id_kategori_stok
";
$result_stok = mysqli_query($koneksi, $query_stok);
while ($row_stok = mysqli_fetch_assoc($result_stok)) {
    if ($row_stok['id_produk']) {
        $stok_terkini['produk'][$row_stok['id_produk']] = $row_stok['total'];
    } else {
        $stok_terkini['kategori'][$row_stok['id_kategori_stok']] = $row_stok['total'];
    }
}

// 2. Ambil semua produk yang aktif
$sql_produk = "SELECT id_produk, nama_produk, harga, poto_produk, kategori, id_kategori_stok
               FROM produk 
               WHERE status_produk = 'aktif' 
               ORDER BY kategori ASC, nama_produk ASC";
$result_produk = mysqli_query($koneksi, $sql_produk);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/png" href="assets/img/logo-kuebalok.png">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>Kue Balok Mang Wiro</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,300;0,400;0,700;1,700&display=swap"
        rel="stylesheet" />

    <script src="https://unpkg.com/feather-icons"></script>

    <link rel="stylesheet" href="assets/css/style1.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .menu-card-price {
            margin-bottom: 0.5rem;
        }

        .menu-card-stock {
            font-size: 1.3rem;
            font-weight: 500;
            color: #007bff;
            /* Biru untuk stok agar mudah terlihat */
            margin-bottom: 1.5rem;
        }

        .kategori-title {
            width: 100%;
            text-align: center;
            margin-top: 3rem;
            margin-bottom: 1rem;
            font-size: 2.2rem;
            color: var(--primary);
            border-bottom: 2px solid #eee;
            padding-bottom: 1rem;
        }


        .menu-page .row {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            /* Opsional: membuat kartu rata di tengah */

            /* INI KUNCI UTAMANYA: Memberi jarak antar kartu */
            gap: 2.5rem;
            /* Anda bisa sesuaikan nilainya, misal: 2rem atau 3rem */
        }

        /* Sedikit penyesuaian pada menu-card agar tidak ada margin yang berlebihan */
        .menu-page .menu-card {
            margin: 0;
        }

        /* ============================================= */
        /* == CSS BARU UNTUK KONSISTENSI HALAMAN == */
        /* ============================================= */

        /* Tata Letak Halaman Utama */
        /* Gunakan pada div pembungkus utama di setiap halaman (selain landing page) */
        .main-content-page {
            padding: 8rem 7% 4rem;
            background-color: #f0f2f5;
            /* Warna latar belakang abu-abu muda seperti di admin */
            min-height: 100vh;
        }

        /* Judul Halaman */
        .page-title {
            font-size: 2.4rem;
            margin-bottom: 0.5rem;
            color: #333;
        }

        /* Gaya Breadcrumb (navigasi jejak) */
        .breadcrumb {
            display: flex;
            flex-wrap: wrap;
            padding: 0.5rem 1rem;
            margin-bottom: 1.5rem;
            list-style: none;
            background-color: #e9ecef;
            border-radius: 0.25rem;
        }

        .breadcrumb-item {
            display: flex;
            align-items: center;
        }

        .breadcrumb-item a {
            color: #007bff;
            text-decoration: none;
        }

        .breadcrumb-item a:hover {
            text-decoration: underline;
        }

        /* Membuat separator ' / ' antar item */
        .breadcrumb-item+.breadcrumb-item::before {
            padding-right: .5rem;
            padding-left: .5rem;
            color: #6c757d;
            content: "/";
        }

        .breadcrumb-item.active {
            color: #6c757d;
        }


        /* Gaya Kartu (Card) */
        .card {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            background-color: #ffffff;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
        }

        .card-header {
            padding: 0.75rem 1.25rem;
            margin-bottom: 0;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .card-header .fa-utensils,
        .card-header .fas {
            /* Menargetkan ikon font awesome */
            margin-right: 0.5rem;
            color: #6c757d;
        }

        .card-body {
            padding: 1.25rem;
        }

        /* Sedikit penyesuaian untuk menu-grid di dalam card */
        .card-body .menu-grid {
            gap: 1.5rem;
            /* Mungkin perlu sedikit mengurangi jarak antar kartu */
        }

        .menu-card-stock,
        .menu-card-price {
            font-size: 1.3rem;
            color: #666;
            margin-top: -1rem;
            /* Atur jarak dari harga */
            margin-bottom: 1rem;
        }
    </style>

</head>

<body>
    <nav class="navbar">
        <a href="index.php" class="navbar-logo">
            <img src="assets/img/logo kecil 5.png" alt="LOGO KUE BALOK MANG WIRO" />
        </a>
        <div class="navbar-nav">
            <a href="index.php#home">Beranda</a>
            <a href="index.php#about">Tentang Kami</a>
            <a href="menu.php">Menu</a>
            <a href="lacak.php">Lacak Pesanan</a>
            <a href="index.php#faq">FAQ</a>
            <a href="index.php#contact">Kontak</a>
        </div>

        <div class="navbar-extra">
            <a href="keranjang.php" id="shopping-cart-button">
                <i data-feather="shopping-cart"></i>
                <span class="cart-item-count" style="display:none;">0</span>
            </a>
            <a href="#" id="hamburger-menu"><i data-feather="menu"></i></a>
        </div>

        <div class="search-form">
            <input type="search" id="search-box" placeholder="Cari menu...">
            <label for="search-box"><i data-feather="search"></i></label>
        </div>
    </nav>

    <section class="menu menu-page" id="menu">
        <h2><span>Menu Lengkap</span> Kami</h2>
        <p>Pilih menu favorit Anda dan tambahkan ke keranjang belanja.</p>
        <?php
        if ($result_produk && mysqli_num_rows($result_produk) > 0) {
            $current_kategori = '';
            while ($row = mysqli_fetch_assoc($result_produk)) {
                // Tampilkan header kategori jika kategorinya baru
                if ($row['kategori'] != $current_kategori) {
                    // Tutup div.row sebelumnya jika bukan iterasi pertama
                    if ($current_kategori != '') {
                        echo '</div>';
                    }
                    $current_kategori = $row['kategori'];
                    echo '<h3 class="kategori-title">' . htmlspecialchars(strtoupper($current_kategori)) . '</h3>';
                    echo '<div class="row">'; // Buka div.row baru untuk setiap kategori
                }
        ?>
                <div class="menu-container">
                    <div class="menu-card">
                        <img src="../backend/assets/img/produk/<?= htmlspecialchars($row['poto_produk'] ?? 'default.jpg') ?>"
                            alt="<?= htmlspecialchars($row['nama_produk'] ?? 'Gambar Produk') ?>"
                            class="menu-card-img">
                        <h3 class="menu-card-title">- <?= htmlspecialchars($row['nama_produk'] ?? 'Nama Produk') ?> -</h3>
                        <!-- <p class="menu-card-price">Rp <?= number_format($row['harga'] ?? 0, 0, ',', '.') ?></p> -->
                        <p class="menu-card-price">Rp <?= number_format($row['harga'] ?? 0, 0, ',', '.') ?></p>
                        <!-- <p class="menu-card-stock">Stok: <strong><?= $row['total'] ?></strong></p> -->
                        <div class="add-to-cart-btn">

                            <button class="btn"
                                data-id="<?= htmlspecialchars($row['id_produk'] ?? '') ?>"
                                data-nama="<?= htmlspecialchars($row['nama_produk'] ?? 'Produk') ?>"
                                data-harga="<?= htmlspecialchars($row['harga'] ?? 0) ?>">

                                <i data-feather="shopping-cart"></i> Tambah
                            </button>
                        </div>
                    </div>
                </div>
        <?php
            } // Akhir loop while
            echo '</div>'; // Tutup div.row terakhir
        } else {
            echo '<p class="text-center">Maaf, belum ada menu yang tersedia saat ini.</p>';
        }
        ?>
        </div>
    </section>
  
    <?php
    include 'includes/footer.php';
    ?>

