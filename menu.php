<?php
// Langkah 1: Definisikan judul halaman dan panggil header.
// Header akan secara otomatis memuat session_start() dan koneksi.php.
$page_title = "Menu Lengkap";
// include 'includes/header.php';
include 'backend/koneksi.php';

// =========================================================================
// == LOGIKA PENGAMBILAN DATA STOK & PRODUK
// =========================================================================

// Ambil semua data stok terkini dari log_stok dalam satu kali query yang efisien
$stok_terkini = [];
$query_stok = "
    SELECT id_produk, NULL as id_kategori_stok, SUM(jumlah_perubahan) as total FROM log_stok WHERE id_produk IS NOT NULL GROUP BY id_produk
    UNION ALL
    SELECT NULL as id_produk, id_kategori_stok, SUM(jumlah_perubahan) as total FROM log_stok WHERE id_kategori_stok IS NOT NULL GROUP BY id_kategori_stok
";
$result_stok = mysqli_query($koneksi, $query_stok);

// Cek jika query stok berhasil sebelum melanjutkan
if ($result_stok) {
    while ($row_stok = mysqli_fetch_assoc($result_stok)) {
        if ($row_stok['id_produk']) {
            $stok_terkini['produk'][$row_stok['id_produk']] = $row_stok['total'];
        } elseif ($row_stok['id_kategori_stok']) {
            $stok_terkini['kategori'][$row_stok['id_kategori_stok']] = $row_stok['total'];
        }
    }
}

// Ambil semua produk yang aktif
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
        .kategori-title {
            width: 100%;
            text-align: center;
            margin-top: 2.5rem;
            margin-bottom: 1rem;
            font-size: 2.2rem;
            color: var(--primary);
            border-bottom: 2px solid #eee;
            padding-bottom: 1rem;
        }

        .menu-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1.5rem;
            /* Jarak antar kartu */
        }

        /* ============================================= */
        /* == CSS PERBAIKAN UNTUK HALAMAN MENU & CARD == */
        /* ============================================= */

        /* --- 1. Perbaikan Posisi Konten Utama --- */
        /* Memberi jarak atas agar konten tidak tertutup navbar yang fixed */
        .main-content-page {
            padding-top: 6rem;
            /* Sesuaikan angka ini jika navbar Anda lebih tinggi/pendek */
        }

        /* --- 2. Perbaikan Tampilan Kartu Menu (.menu-card) --- */
        .menu-card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            /* Memberi sudut melengkung */
            text-align: center;
            padding: 1.5rem 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            /* Memberi sedikit bayangan */
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;

            /* Gunakan flexbox untuk menata isi kartu secara vertikal */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            /* Mendorong tombol ke bawah */
        }

        .menu-card:hover {
            transform: translateY(-5px);
            /* Efek sedikit terangkat saat disentuh mouse */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .menu-card .menu-card-img {
            width: 100%;
            height: 180px;
            /* Beri tinggi yang seragam untuk semua gambar */
            object-fit: cover;
            /* Memastikan gambar terpotong rapi, tidak gepeng */
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .menu-card .menu-card-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0.5rem 0;
            flex-grow: 1;
            /* Membuat judul mengisi ruang agar tombol rata bawah */
        }

        .menu-card .menu-card-price {
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--primary);
            /* Menggunakan warna utama tema Anda */
            margin: 0.5rem 0;
        }

        .menu-card .menu-card-stock {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
        }

        /* --- 3. Perbaikan Tombol di Dalam Kartu --- */
        .add-to-cart-btn {
            margin-top: auto;
            /* Mendorong tombol ini ke bagian paling bawah kartu */
        }

        .add-to-cart-btn .btn {
            width: 100%;
            background-color: var(--bg);
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 0.75rem;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.2s, color 0.2s;
        }

        .add-to-cart-btn .btn:hover {
            background-color: var(--primary);
            color: #fff;
        }

        .add-to-cart-btn .btn:disabled {
            background-color: #e9ecef;
            border-color: #dee2e6;
            color: #adb5bd;
            cursor: not-allowed;
        }

        .add-to-cart-btn .btn svg {
            margin-right: 0.5rem;
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
    <div class="main-content-page">


        <div class="card">

            <div class="card-body">

                <?php if ($result_produk && mysqli_num_rows($result_produk) > 0): ?>
                    <?php
                    $current_kategori = '';
                    // Loop untuk setiap kategori
                    while ($row = mysqli_fetch_assoc($result_produk)):
                        // Jika kategori berubah, cetak judul kategori baru
                        if ($row['kategori'] != $current_kategori) {
                            if ($current_kategori != '') echo '</div>'; // Tutup .menu-grid sebelumnya
                            $current_kategori = $row['kategori'];
                            echo '<h3 class="kategori-title">' . htmlspecialchars(strtoupper($current_kategori)) . '</h3>';
                            echo '<div class="menu-grid">'; // Buka .menu-grid baru
                        }

                        // Tentukan stok yang akan ditampilkan
                        $stok_tampil = 0;
                        if (!empty($row['id_kategori_stok'])) {
                            $stok_tampil = $stok_terkini['kategori'][$row['id_kategori_stok']] ?? 0;
                        } else {
                            $stok_tampil = $stok_terkini['produk'][$row['id_produk']] ?? 0;
                        }
                    ?>
                        <div class="menu-card"
                            data-id="<?= htmlspecialchars($row['id_produk']) ?>"
                            data-nama="<?= htmlspecialchars($row['nama_produk']) ?>"
                            data-harga="<?= htmlspecialchars($row['harga']) ?>"
                            data-stok="<?= htmlspecialchars($stok_tampil) ?>">

                            <img src="backend/assets/img/produk/<?= htmlspecialchars($row['poto_produk'] ?: 'default.jpg') ?>" alt="<?= htmlspecialchars($row['nama_produk']) ?>" class="menu-card-img">

                            <h3 class="menu-card-title">- <?= htmlspecialchars($row['nama_produk']) ?> -</h3>
                            <p class="menu-card-price">Rp <?= number_format($row['harga'], 0, ',', '.') ?></p>
                            <p class="menu-card-stock">Stok: <strong><?= $stok_tampil ?></strong></p>
                            <div class="add-to-cart-btn">
                                <button class="btn"
                                    data-id="<?= htmlspecialchars($row['id_produk'] ?? '') ?>"
                                    data-nama="<?= htmlspecialchars($row['nama_produk'] ?? 'Produk') ?>"
                                    data-harga="<?= htmlspecialchars($row['harga'] ?? 0) ?>">

                                    <i data-feather="shopping-cart"></i> Tambah
                                </button>
                            </div>
                        </div>

                    <?php
                    endwhile;
                    echo '</div>'; // Tutup .menu-grid terakhir
                    ?>
                <?php else: ?>
                    <p class="text-center">Maaf, belum ada menu yang tersedia saat ini.</p>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>