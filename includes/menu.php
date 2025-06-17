<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Definisikan judul halaman ini
$page_title = "Menu Lengkap";

// 2. Panggil kerangka bagian atas (header)
// Di dalam header sudah ada session_start() dan navbar
include 'includes/header.php';

// 3. Panggil koneksi database
// include 'includes/koneksi.php';

// 4. Ambil semua data produk yang aktif dari database
// $sql_produk = "SELECT id_produk, nama_produk, harga, poto_produk, kategori
//                FROM produk 
//                WHERE status_produk = 'aktif' 
//                ORDER BY kategori ASC, nama_produk ASC"; // Diurutkan agar rapi per kategori
// Ubah kueri ini
$sql_produk = "SELECT id_produk, nama_produk, harga, poto_produk, kategori, stok -- < TAMBAHKAN 'stok'
               FROM produk 
               WHERE status_produk = 'aktif' 
               ORDER BY kategori ASC, nama_produk ASC";
$result_produk = mysqli_query($koneksi, $sql_produk);
?>

<style>
    .menu-card-stock, .menu-card-price {
        font-size: 1.3rem;
        color: #666;
        margin-top: -1rem;
        /* Atur jarak dari harga */
        margin-bottom: 1rem;
    }
</style>
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
                    <p class="menu-card-stock">Stok: <strong><?= $row['stok'] ?></strong></p>
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
// 6. Panggil kerangka bagian bawah (footer)
include 'includes/footer.php';
?>