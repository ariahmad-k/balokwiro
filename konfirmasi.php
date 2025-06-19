<?php
// Memastikan sesi dimulai dengan aman di setiap halaman
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Memuat file koneksi dan file pengecekan pesanan kadaluarsa
// Pastikan path ini sesuai dengan struktur folder Anda
require_once 'backend/koneksi.php';
require_once 'backend/cek_kadaluarsa.php';

// ================================================================
// BAGIAN 1: LOGIKA PEMROSESAN FORM UPLOAD (POST REQUEST)
// ================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bukti_pembayaran'])) {

    $id_pesanan_post = $_POST['id_pesanan'] ?? '';
    $file = $_FILES['bukti_pembayaran'];

    // Validasi dasar: pastikan ada ID pesanan dan tidak ada error upload awal
    if (empty($id_pesanan_post) || $file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['notif_konfirmasi'] = ['pesan' => 'Terjadi error saat mengupload file. Silakan coba lagi.', 'tipe' => 'danger'];
        header("Location: konfirmasi.php?id=" . urlencode($id_pesanan_post));
        exit;
    }

    // Validasi tipe file berdasarkan ekstensi (lebih andal) dan ukuran
    $nama_file = $file['name'];
    $ext = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png'];
    $max_size = 2 * 1024 * 1024; // Batas ukuran 2MB

    if (in_array($ext, $allowed_ext) && $file['size'] < $max_size) {
        // Buat nama file baru yang unik untuk menghindari tumpang tindih
        $nama_file_baru = $id_pesanan_post . '-' . time() . '.' . $ext;
        // Path disesuaikan dengan struktur folder Anda
        $lokasi_upload = 'backend/assets/img/bukti_bayar/' . $nama_file_baru;

        // Coba pindahkan file dari direktori temporary ke direktori permanen
        if (move_uploaded_file($file['tmp_name'], $lokasi_upload)) {

            // Jika file berhasil diupload, update entri di database
            $stmt_update = mysqli_prepare($koneksi, "UPDATE pesanan SET bukti_pembayaran = ?, status_pesanan = 'menunggu_konfirmasi' WHERE id_pesanan = ? AND status_pesanan = 'menunggu_pembayaran'");
            mysqli_stmt_bind_param($stmt_update, "ss", $nama_file_baru, $id_pesanan_post);

            if (mysqli_stmt_execute($stmt_update) && mysqli_stmt_affected_rows($stmt_update) > 0) {
                // Jika update database berhasil
                $_SESSION['notif_konfirmasi'] = ['pesan' => 'Terima kasih! Bukti pembayaran berhasil diupload dan akan segera kami periksa.', 'tipe' => 'success'];
            } else {
                // Jika update database gagal
                $_SESSION['notif_konfirmasi'] = ['pesan' => 'Gagal menyimpan data bukti pembayaran. Mungkin pesanan sudah diproses atau dibatalkan.', 'tipe' => 'danger'];
            }
        } else {
            // Jika gagal memindahkan file
            $_SESSION['notif_konfirmasi'] = ['pesan' => 'Gagal memindahkan file yang diupload.', 'tipe' => 'danger'];
        }
    } else {
        // Jika tipe file atau ukuran tidak sesuai
        $_SESSION['notif_konfirmasi'] = ['pesan' => 'File tidak valid. Pastikan formatnya JPG/PNG dan ukuran di bawah 2MB.', 'tipe' => 'warning'];
    }

    // Arahkan kembali ke halaman yang sama untuk menampilkan notifikasi
    header("Location: konfirmasi.php?id=" . urlencode($id_pesanan_post));
    exit;
}

// ================================================================
// BAGIAN 2: LOGIKA PENGAMBILAN DATA UNTUK MENAMPILKAN HALAMAN
// ================================================================

// Validasi ID Pesanan dari URL (GET request)
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$id_pesanan = $_GET['id'];

// Ambil data pesanan utama dari database
$stmt_get = mysqli_prepare($koneksi, "SELECT * FROM pesanan WHERE id_pesanan = ?");
mysqli_stmt_bind_param($stmt_get, "s", $id_pesanan);
mysqli_stmt_execute($stmt_get);
$result_get = mysqli_stmt_get_result($stmt_get);
$pesanan = mysqli_fetch_assoc($result_get);

// Jika ID pesanan tidak ditemukan di database, arahkan ke halaman lacak
if (!$pesanan) {
    header('Location: lacak.php?error=notfound');
    exit;
}

// Ambil daftar metode pembayaran yang aktif untuk ditampilkan
$sql_metode = "SELECT * FROM metode_pembayaran WHERE status = 'aktif'";
$result_metode = mysqli_query($koneksi, $sql_metode);

// Set judul halaman dan panggil file header
$page_title = "Konfirmasi Pesanan";
include 'includes/header.php';
?>

<style>
    .konfirmasi-page {
        padding: 8rem 7% 4rem;
    }

    .container-konfirmasi {
        max-width: 800px;
        margin: auto;
    }

    .card-konfirmasi {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 2.5rem;
        text-align: center;
        background-color: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .icon-status {
        font-size: 4rem;
        margin-bottom: 1rem;
    }

    .icon-sukses {
        color: #198754;
    }

    .icon-menunggu {
        color: #0dcaf0;
    }

    .order-id-box {
        background-color: #e9ecef;
        padding: 0.75rem;
        border-radius: 0.5rem;
        display: inline-flex;
        align-items: center;
        gap: 1rem;
        margin-top: 0.5rem;
    }

    .order-id-box strong {
        font-size: 1.8rem;
    }

    #copy-btn {
        background: #6c757d;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 0.25rem;
        cursor: pointer;
    }

    #copy-btn.copied {
        background-color: #198754;
    }

    #countdown-timer {
        font-weight: 500;
        font-size: 1.5rem;
    }

    .payment-details {
        display: none;
        margin-top: 1.5rem;
        padding: 1.5rem;
        background-color: #e7f3ff;
        border: 1px solid #b3d7ff;
        border-radius: 5px;
        text-align: left;
    }

    .payment-details.active {
        display: block;
    }

    .payment-details img {
        max-width: 200px;
        margin-top: 1rem;
        display: block;
    }

    #copy-nomor-btn {
        padding: 0.3rem 0.6rem;
        font-size: 0.8rem;
    }
</style>

<section class="konfirmasi-page">
    <div class="container-konfirmasi">
        <h2 style="text-align: center; font-size: 2.6rem; margin-bottom: 2rem;">Konfirmasi <span>Pemesanan</span></h2>

        <?php
        // Tampilkan notifikasi dari proses upload jika ada
        if (isset($_SESSION['notif_konfirmasi'])) {
            $notif = $_SESSION['notif_konfirmasi'];
            echo '<div class="alert alert-' . htmlspecialchars($notif['tipe']) . '">' . htmlspecialchars($notif['pesan']) . '</div>';
            unset($_SESSION['notif_konfirmasi']);
        }
        ?>

        <div class="card-konfirmasi">
            <?php if ($pesanan['status_pesanan'] == 'menunggu_pembayaran'): ?>
                <div class="icon-status icon-sukses"><i class="fas fa-check-circle"></i></div>
                <h3>Pesanan Anda Berhasil Dibuat!</h3>
                <p>Harap simpan Nomor Pesanan Anda dan selesaikan pembayaran sebelum waktu habis.</p>
                <div id="countdown-timer" class="alert alert-warning"></div>

                <h4 class="mt-3">Nomor Pesanan Anda:</h4>
                <div class="order-id-box">
                    <strong id="order-id-text"><?= htmlspecialchars($pesanan['id_pesanan']) ?></strong>
                    <button id="copy-btn" title="Salin Nomor Pesanan"><i class="fas fa-clipboard"></i></button>
                </div>

                <hr style="margin: 2rem 0;">

                <h4>Cara Pembayaran</h4>
                <ol style="text-align: left; max-width: 450px; margin: 1rem auto; padding-left: 2rem;">
                    <li>Pilih salah satu metode pembayaran di bawah.</li>
                    <li>Lakukan transfer sejumlah <strong>Rp <?= number_format($pesanan['total_harga']) ?></strong> ke rekening/nomor yang muncul.</li>
                    <li>Upload bukti transfer Anda pada formulir di bagian paling bawah.</li>
                </ol>

                <div class="payment-options mt-4 text-start">
                    <h5>Pilih Metode Pembayaran:</h5>
                    <?php if ($result_metode && mysqli_num_rows($result_metode) > 0): ?>
                        <?php mysqli_data_seek($result_metode, 0); // Reset pointer hasil query 
                        ?>
                        <?php while ($metode = mysqli_fetch_assoc($result_metode)): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="pilihan_pembayaran" id="metode_<?= $metode['id_metode'] ?>"
                                    data-nama-metode="<?= htmlspecialchars($metode['nama_metode']) ?>"
                                    data-atas-nama="<?= htmlspecialchars($metode['atas_nama']) ?>"
                                    data-nomor="<?= htmlspecialchars($metode['nomor_tujuan']) ?>"
                                    data-gambar="<?= htmlspecialchars($metode['gambar_path'] ?? '') ?>">
                                <label class="form-check-label" for="metode_<?= $metode['id_metode'] ?>">
                                    <?= htmlspecialchars($metode['nama_metode']) ?>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>

                    <div id="detail-pembayaran" class="payment-details">
                        <p class="mb-1">Silakan lakukan pembayaran ke:</p>
                        <h5 id="info-atas-nama" class="mb-0"></h5>
                        <div id="nomor-container" class="d-flex align-items-center gap-2 mt-1">
                            <h4 id="info-nomor" class="fw-bold mb-0"></h4>
                            <button id="copy-nomor-btn" class="btn btn-secondary btn-sm" title="Salin Nomor"><i class="fas fa-clipboard"></i></button>
                        </div>
                        <div id="gambar-container">
                            <img src="" id="info-gambar" alt="QR Code Pembayaran">
                        </div>
                    </div>
                </div>

                <div class="upload-wrapper mt-4">
                    <h5>Upload Bukti Pembayaran Anda</h5>
                    <form action="konfirmasi.php?id=<?= htmlspecialchars($id_pesanan) ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id_pesanan" value="<?= htmlspecialchars($pesanan['id_pesanan']) ?>">
                        <div class="my-3">
                            <input type="file" name="bukti_pembayaran" class="form-control" accept="image/jpeg, image/png, image/jpg" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Kirim Bukti Pembayaran</button>
                    </form>
                </div>

            <?php else: ?>
                <div class="icon-status icon-menunggu"><i class="fas fa-hourglass-half"></i></div>
                <h3>Terima Kasih!</h3>
                <p>Status pesanan Anda <strong>#<?= htmlspecialchars($pesanan['id_pesanan']) ?></strong> saat ini adalah:</p>
                <h4 style="background: #eee; padding: 1rem; border-radius: 5px; display: inline-block; text-transform: capitalize;">
                    <?= str_replace('_', ' ', htmlspecialchars($pesanan['status_pesanan'])); ?>
                </h4>
                <p class="mt-4">Anda bisa memeriksa kembali status pesanan Anda secara berkala di halaman Lacak Pesanan.</p>
                <a href="lacak.php" class="btn btn-info">Lacak Pesanan Lain</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Jalankan skrip hanya jika elemen-elemen untuk halaman 'menunggu_pembayaran' ada
        if (document.getElementById('countdown-timer')) {

            // --- FITUR TOMBOL SALIN NOMOR PESANAN ---
            const copyButton = document.getElementById('copy-btn');
            if (copyButton) {
                const orderIdTextElement = document.getElementById('order-id-text');
                copyButton.addEventListener('click', function() {
                    navigator.clipboard.writeText(orderIdTextElement.textContent).then(() => {
                        const originalHTML = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-check"></i>';
                        this.classList.add('copied');
                        setTimeout(() => {
                            this.innerHTML = originalHTML;
                            this.classList.remove('copied');
                        }, 2000);
                    });
                });
            }

            // --- FITUR TIMER KADALUARSA 10 MENIT ---
            const countdownElement = document.getElementById('countdown-timer');
            const orderTime = new Date('<?= date("Y-m-d H:i:s", strtotime($pesanan['tgl_pesanan'])) ?>');
            const expiryTime = orderTime.getTime() + 10 * 60 * 1000;
            const timerInterval = setInterval(() => {
                const now = new Date().getTime();
                const distance = expiryTime - now;
                if (distance < 0) {
                    clearInterval(timerInterval);
                    countdownElement.innerHTML = "Waktu pembayaran telah habis. Pesanan otomatis dibatalkan.";
                    countdownElement.classList.remove('alert-warning');
                    countdownElement.classList.add('alert-danger');
                    setTimeout(() => window.location.reload(), 3000);
                    return;
                }
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                countdownElement.innerHTML = `Sisa waktu pembayaran: <strong>${minutes}m ${seconds}s</strong>`;
            }, 1000);

            // --- FITUR PILIHAN PEMBAYARAN DINAMIS ---
            const detailDiv = document.getElementById('detail-pembayaran');
            const infoAtasNama = document.getElementById('info-atas-nama');
            const infoNomor = document.getElementById('info-nomor');
            const copyNomorBtn = document.getElementById('copy-nomor-btn');
            const nomorContainer = document.getElementById('nomor-container');
            const gambarContainer = document.getElementById('gambar-container');
            const infoGambar = document.getElementById('info-gambar');

            document.querySelectorAll('input[name="pilihan_pembayaran"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    detailDiv.classList.add('active');
                    infoAtasNama.textContent = "Atas Nama: " + this.dataset.atasNama;

                    if (this.dataset.namaMetode.toUpperCase() === 'QRIS') {
                        nomorContainer.style.display = 'none';
                        gambarContainer.style.display = 'block';
                        if (this.dataset.gambar && this.dataset.gambar !== '') {
                            // Path disesuaikan agar konsisten
                            infoGambar.src = 'backend/assets/img/metode_bayar/QRIS.jpg';
                        }
                    } else {
                        nomorContainer.style.display = 'flex';
                        gambarContainer.style.display = 'none';
                        infoNomor.textContent = this.dataset.nomor;
                    }
                });
            });

            if (copyNomorBtn) {
                copyNomorBtn.addEventListener('click', function() {
                    navigator.clipboard.writeText(infoNomor.textContent).then(() => {
                        const originalIcon = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-check"></i>';
                        setTimeout(() => {
                            this.innerHTML = originalIcon;
                        }, 2000);
                    });
                });
            }
        }
    });
</script>

<?php
// Panggil kerangka bagian bawah (footer)
include 'includes/footer.php';
?>