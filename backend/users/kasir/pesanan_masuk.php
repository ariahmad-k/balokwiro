<?php
session_start();
include '../../koneksi.php';

// 1. OTENTIKASI & OTORISASI KASIR
if (!isset($_SESSION['user']) || $_SESSION['user']['jabatan'] !== 'kasir') {
    header('Location: ../../login.php');
    exit;
}

// 2. LOGIKA PEMROSESAN SEMUA AKSI FORM (POST REQUEST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Aksi: Validasi Pesanan Online
    if (isset($_POST['validasi_pesanan'])) {
        $id_pesanan = $_POST['id_pesanan'];
        $id_kasir = $_SESSION['user']['id'];

        mysqli_begin_transaction($koneksi);
        try {
            $sql_beban = "SELECT SUM(dp.jumlah) AS total_item_aktif FROM detail_pesanan dp JOIN pesanan p ON dp.id_pesanan = p.id_pesanan WHERE p.status_pesanan IN ('pending', 'diproses') AND (p.id_pesanan != ?) AND (dp.id_produk LIKE 'KB%' OR dp.id_produk LIKE 'KS%')";
            $stmt_beban = mysqli_prepare($koneksi, $sql_beban);
            mysqli_stmt_bind_param($stmt_beban, "s", $id_pesanan);
            mysqli_stmt_execute($stmt_beban);
            $result_beban = mysqli_stmt_get_result($stmt_beban);
            $beban_dapur = mysqli_fetch_assoc($result_beban)['total_item_aktif'] ?? 0;

            $status_baru = ($beban_dapur < 20) ? 'diproses' : 'pending';

            $stmt_update = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pesanan = ?, id_karyawan = ? WHERE id_pesanan = ? AND status_pesanan = 'menunggu_konfirmasi'");
            mysqli_stmt_bind_param($stmt_update, "sis", $status_baru, $id_kasir, $id_pesanan);
            mysqli_stmt_execute($stmt_update);
            mysqli_commit($koneksi);
            $_SESSION['notif'] = ['pesan' => 'Pesanan berhasil divalidasi dan masuk antrean.', 'tipe' => 'success'];
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $_SESSION['notif'] = ['pesan' => 'Gagal memvalidasi pesanan: ' . $e->getMessage(), 'tipe' => 'danger'];
        }
    }

    // Aksi: Batalkan Pesanan (dengan pengembalian stok ke log_stok)
    if (isset($_POST['batalkan_pesanan'])) {
        $id_pesanan = $_POST['id_pesanan'];
        mysqli_begin_transaction($koneksi);
        try {
            $sql_items = "SELECT id_produk, jumlah FROM detail_pesanan WHERE id_pesanan = ?";
            $stmt_items = mysqli_prepare($koneksi, $sql_items);
            mysqli_stmt_bind_param($stmt_items, "s", $id_pesanan);
            mysqli_stmt_execute($stmt_items);
            $result_items = mysqli_stmt_get_result($stmt_items);

            $stmt_log = mysqli_prepare($koneksi, "INSERT INTO log_stok (id_produk, id_kategori_stok, jumlah_perubahan, jenis_aksi, id_pesanan, keterangan) VALUES (?, ?, ?, 'pembatalan', ?, ?)");
            $stmt_info = mysqli_prepare($koneksi, "SELECT id_kategori_stok FROM produk WHERE id_produk = ?");

            while ($item = mysqli_fetch_assoc($result_items)) {
                mysqli_stmt_bind_param($stmt_info, "s", $item['id_produk']);
                mysqli_stmt_execute($stmt_info);
                $info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_info));

                $id_produk_log = null;
                $id_kategori_log = null;
                $jumlah_penambahan = abs($item['jumlah']);
                $keterangan_log = "Dibatalkan oleh kasir";

                if ($info && $info['id_kategori_stok'] !== null) {
                    $id_kategori_log = $info['id_kategori_stok'];
                } else {
                    $id_produk_log = $item['id_produk'];
                }
                mysqli_stmt_bind_param($stmt_log, "ssiss", $id_produk_log, $id_kategori_log, $jumlah_penambahan, $id_pesanan, $keterangan_log);
                mysqli_stmt_execute($stmt_log);
            }

            $stmt_batal = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pesanan = 'dibatalkan' WHERE id_pesanan = ? AND status_pesanan = 'menunggu_konfirmasi'");
            mysqli_stmt_bind_param($stmt_batal, "s", $id_pesanan);
            mysqli_stmt_execute($stmt_batal);

            mysqli_commit($koneksi);
            $_SESSION['notif'] = ['pesan' => "Pesanan #$id_pesanan telah dibatalkan dan stok dikembalikan.", 'tipe' => 'warning'];
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $_SESSION['notif'] = ['pesan' => 'Gagal membatalkan pesanan. Error: ' . $e->getMessage(), 'tipe' => 'danger'];
        }
    }

    // Aksi: Tandai Siap Diambil
    if (isset($_POST['siap_diambil'])) {
        $id_pesanan = $_POST['id_pesanan'];
        $stmt_siap = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pesanan = 'siap_diambil' WHERE id_pesanan = ? AND status_pesanan IN ('pending', 'diproses')");
        mysqli_stmt_bind_param($stmt_siap, "s", $id_pesanan);
        mysqli_stmt_execute($stmt_siap);
        $_SESSION['notif'] = ['pesan' => "Pesanan #$id_pesanan telah ditandai Siap Diambil.", 'tipe' => 'info'];
    }

    // Aksi: Konfirmasi Pengambilan (Selesai)
    if (isset($_POST['konfirmasi_pengambilan'])) {
        $id_pesanan = $_POST['id_pesanan'];
        $stmt_selesai = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pesanan = 'selesai' WHERE id_pesanan = ? AND status_pesanan = 'siap_diambil'");
        mysqli_stmt_bind_param($stmt_selesai, "s", $id_pesanan);
        mysqli_stmt_execute($stmt_selesai);
        $_SESSION['notif'] = ['pesan' => "Pesanan #$id_pesanan telah diselesaikan.", 'tipe' => 'success'];
    }

    header('Location: pesanan_masuk.php');
    exit;
}

// 3. LOGIKA PENGAMBILAN DATA UNTUK DITAMPILKAN
$sql_pesanan_baru = "SELECT * FROM pesanan WHERE status_pesanan IN ('menunggu_pembayaran', 'menunggu_konfirmasi') ORDER BY tgl_pesanan ASC";
$pesanan_masuk_online = mysqli_fetch_all(mysqli_query($koneksi, $sql_pesanan_baru), MYSQLI_ASSOC);
$antrean_pesanan = mysqli_fetch_all(mysqli_query($koneksi, "SELECT * FROM pesanan WHERE status_pesanan IN ('pending', 'diproses') ORDER BY FIELD(status_pesanan, 'diproses', 'pending'), tgl_pesanan ASC"), MYSQLI_ASSOC);
$pesanan_siap_diambil = mysqli_fetch_all(mysqli_query($koneksi, "SELECT * FROM pesanan WHERE status_pesanan = 'siap_diambil' ORDER BY tgl_pesanan ASC"), MYSQLI_ASSOC);

$detail_items = [];
$all_pesanan_ids = array_merge(array_column($pesanan_masuk_online, 'id_pesanan'), array_column($antrean_pesanan, 'id_pesanan'), array_column($pesanan_siap_diambil, 'id_pesanan'));
if (!empty($all_pesanan_ids)) {
    $ids_string = "'" . implode("','", $all_pesanan_ids) . "'";
    $sql_details = "SELECT dp.id_pesanan, dp.jumlah, p.nama_produk FROM detail_pesanan dp JOIN produk p ON dp.id_produk = p.id_produk WHERE dp.id_pesanan IN ($ids_string)";
    $result_details = mysqli_query($koneksi, $sql_details);
    while ($row = mysqli_fetch_assoc($result_details)) {
        $detail_items[$row['id_pesanan']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>Manajemen Pesanan - Kasir</title>
    <link href="../../css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="icon" type="image/png" href="../../assets/img/logo-kuebalok.png">
    <meta http-equiv="refresh" content="60">
    <style>
        .catatan-pesanan {
            background-color: #fff3cd;
            border-left: 4px solid #ffeeba;
            padding: 10px;
            border-radius: 4px;
            margin-top: 1rem;
            font-size: 1.3rem;
        }

        .catatan-pesanan strong {
            display: block;
            margin-bottom: 5px;
            color: #856404;
        }

        .catatan-pesanan p {
            color: #555;
            margin-bottom: 0;
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include 'inc/navbar.php'; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'inc/sidebar.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Manajemen Pesanan</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Pesanan Masuk</li>
                    </ol>

                    <?php if (isset($_SESSION['notif'])): $notif = $_SESSION['notif']; ?>
                        <div class="alert alert-<?= $notif['tipe'] ?> alert-dismissible fade show" role="alert"><?= htmlspecialchars($notif['pesan']) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
                    <?php unset($_SESSION['notif']);
                    endif; ?>

                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white"><i class="fas fa-inbox me-1"></i>Pesanan Online Masuk (<?= count($pesanan_masuk_online) ?>)</div>
                                <div class="card-body" style="max-height: 70vh; overflow-y: auto;">
                                    <?php if (empty($pesanan_masuk_online)): ?>
                                        <p class="text-center text-muted">Tidak ada pesanan online yang masuk.</p>
                                        <?php else: foreach ($pesanan_masuk_online as $pesanan): ?>
                                            <div class="card mb-3" id="pesanan-<?= htmlspecialchars($pesanan['id_pesanan']) ?>">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between">
                                                        <h5 class="card-title"><?= htmlspecialchars($pesanan['nama_pemesan']) ?></h5>
                                                        <span id="status-label-<?= htmlspecialchars($pesanan['id_pesanan']) ?>" class="badge bg-<?= ($pesanan['status_pesanan'] == 'menunggu_pembayaran') ? 'secondary' : 'info' ?>"><?= str_replace('_', ' ', $pesanan['status_pesanan']); ?></span>
                                                    </div>

                                                    <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($pesanan['id_pesanan']) ?></h6>
                                                    <h6 class="card-subtitle mb-2 text-muted fst-italic"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $pesanan['jenis_pesanan']))) ?></h6>

                                                    <ul class="list-unstyled mb-2 small">
                                                        <?php if (isset($detail_items[$pesanan['id_pesanan']])): foreach ($detail_items[$pesanan['id_pesanan']] as $item): ?>
                                                                <li><?= htmlspecialchars($item['jumlah']) ?>x <?= htmlspecialchars($item['nama_produk']) ?></li>
                                                        <?php endforeach;
                                                        endif; ?>
                                                    </ul>
                                                    <?php if (!empty($pesanan['catatan'])): ?><div class="catatan-pesanan"><strong><i class="fas fa-sticky-note"></i> Catatan:</strong>
                                                            <p><em><?= nl2br(htmlspecialchars($pesanan['catatan'])) ?></em></p>
                                                        </div><?php endif; ?>
                                                    <p class="card-text mt-2"><strong>Total:</strong> Rp <?= number_format($pesanan['total_harga']) ?></p>

                                                    <div class="action-buttons mt-2" id="actions-<?= htmlspecialchars($pesanan['id_pesanan']) ?>">
                                                        <?php if ($pesanan['status_pesanan'] == 'menunggu_konfirmasi'): ?>
                                                            <div class="d-flex gap-2">
                                                                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#buktiBayarModal" data-bukti-bayar="<?= htmlspecialchars($pesanan['bukti_pembayaran']) ?>">Bukti</button>
                                                                <form method="POST" class="d-inline" onsubmit="return confirm('Validasi pesanan ini?');"><input type="hidden" name="id_pesanan" value="<?= $pesanan['id_pesanan'] ?>"><button type="submit" name="validasi_pesanan" class="btn btn-success btn-sm">Validasi</button></form>
                                                                <form method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin MEMBATALKAN pesanan ini? Stok akan dikembalikan.');"><input type="hidden" name="id_pesanan" value="<?= $pesanan['id_pesanan'] ?>"><button type="submit" name="batalkan_pesanan" class="btn btn-danger btn-sm">Batalkan</button></form>
                                                            </div>
                                                        <?php else: ?>
                                                            <small class="text-muted">Menunggu pelanggan mengupload bukti pembayaran.</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card mb-4">
                                <div class="card-header bg-warning"><i class="fas fa-blender-phone me-1"></i>Antrean Dapur (<?= count($antrean_pesanan) ?>)</div>
                                <div class="card-body" style="max-height: 70vh; overflow-y: auto;">
                                    <?php if (empty($antrean_pesanan)): ?><p class="text-center text-muted">Antrean dapur kosong.</p>
                                        <?php else: foreach ($antrean_pesanan as $antrean): ?>
                                            <div class="card mb-3 border-<?= $antrean['status_pesanan'] === 'pending' ? 'danger' : 'primary' ?>">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between">
                                                        <h5 class="card-title"><?= htmlspecialchars($antrean['nama_pemesan']) ?></h5><span class="badge bg-<?= $antrean['status_pesanan'] === 'pending' ? 'danger' : 'primary' ?>"><?= ucfirst($antrean['status_pesanan']) ?></span>
                                                    </div>


                                                    <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($antrean['id_pesanan']) ?></h6>
                                                    <h6 class="card-subtitle mb-2 text-muted fst-italic"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $antrean['jenis_pesanan']))) ?></h6>

                                                    <ul class="list-unstyled mb-2 small">
                                                        <?php if (isset($detail_items[$antrean['id_pesanan']])): foreach ($detail_items[$antrean['id_pesanan']] as $item): ?>
                                                                <li><?= htmlspecialchars($item['jumlah']) ?>x <?= htmlspecialchars($item['nama_produk']) ?></li>
                                                        <?php endforeach;
                                                        endif; ?>
                                                    </ul>
                                                    <?php if (!empty($antrean['catatan'])): ?><div class="catatan-pesanan"><strong><i class="fas fa-sticky-note"></i> Catatan:</strong>
                                                            <p><em><?= nl2br(htmlspecialchars($antrean['catatan'])) ?></em></p>
                                                        </div><?php endif; ?>
                                                    <form method="POST" class="mt-2" onsubmit="return confirm('Tandai pesanan ini sudah SIAP DIAMBIL?');">
                                                        <input type="hidden" name="id_pesanan" value="<?= $antrean['id_pesanan'] ?>"><button type="submit" name="siap_diambil" class="btn btn-dark btn-sm w-100">Tandai Siap Diambil</button>
                                                    </form>
                                                </div>
                                            </div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white"><i class="fas fa-check-circle me-1"></i>Pesanan Siap Diambil (<?= count($pesanan_siap_diambil) ?>)</div>
                                <div class="card-body" style="max-height: 70vh; overflow-y: auto;">
                                    <?php if (empty($pesanan_siap_diambil)): ?><p class="text-center text-muted">Tidak ada pesanan yang siap diambil.</p>
                                        <?php else: foreach ($pesanan_siap_diambil as $siap): ?>
                                            <div class="card mb-3 border-success">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?= htmlspecialchars($siap['nama_pemesan']) ?></h5>
                                                    <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($siap['id_pesanan']) ?></h6>
                                                    <h6 class="card-subtitle mb-2 text-muted fst-italic"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $siap['jenis_pesanan']))) ?></h6>

                                                    <ul class="list-unstyled mb-2 small">
                                                        <?php if (isset($detail_items[$siap['id_pesanan']])): foreach ($detail_items[$siap['id_pesanan']] as $item): ?>
                                                                <li><?= htmlspecialchars($item['jumlah']) ?>x <?= htmlspecialchars($item['nama_produk']) ?></li>
                                                        <?php endforeach;
                                                        endif; ?>
                                                    </ul>
                                                    <?php if (!empty($siap['catatan'])): ?><div class="catatan-pesanan"><strong><i class="fas fa-sticky-note"></i> Catatan:</strong>
                                                            <p><em><?= nl2br(htmlspecialchars($siap['catatan'])) ?></em></p>
                                                        </div><?php endif; ?>
                                                    <form method="POST" class="mt-2" onsubmit="return confirm('Konfirmasi pesanan ini sudah diambil pelanggan?');">
                                                        <input type="hidden" name="id_pesanan" value="<?= $siap['id_pesanan'] ?>"><button type="submit" name="konfirmasi_pengambilan" class="btn btn-primary btn-sm w-100">Konfirmasi Pengambilan</button>
                                                    </form>
                                                </div>
                                            </div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="buktiBayarModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bukti Pembayaran</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center"><img id="gambarBuktiBayar" src="" class="img-fluid"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/scripts.js"></script>
    <script>
        // Skrip untuk Modal Bukti Bayar
        const buktiBayarModal = document.getElementById('buktiBayarModal');
        if (buktiBayarModal) {
            buktiBayarModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                const namaFileBukti = button.getAttribute('data-bukti-bayar');
                const gambarModal = buktiBayarModal.querySelector('#gambarBuktiBayar');
                if (namaFileBukti) {
                    gambarModal.src = '../../assets/img/bukti_bayar/' + namaFileBukti;
                } else {
                    gambarModal.src = ''; // Kosongkan jika tidak ada bukti
                }
            });
        }

        // Skrip untuk Sinkronisasi Real-time
        document.addEventListener('DOMContentLoaded', function() {
            const pesananDiHalaman = document.querySelectorAll('.card-body .action-buttons');
            let idsToWatch = Array.from(pesananDiHalaman).map(div => div.id.replace('actions-', ''));

            async function checkOrderStatus() {
                if (idsToWatch.length === 0) {
                    clearInterval(statusInterval);
                    return;
                }
                try {
                    const response = await fetch('../api/api_cek_status_pesanan.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            ids: idsToWatch
                        })
                    });
                    const statuses = await response.json();

                    for (const id in statuses) {
                        const newStatus = statuses[id];
                        const cardElement = document.getElementById('pesanan-' + id);
                        if (!cardElement) continue;

                        const currentStatusLabel = document.getElementById('status-label-' + id);
                        const currentStatusText = currentStatusLabel.textContent.trim().replace(/ /g, '_');

                        if (newStatus !== currentStatusText) {
                            // Cukup muat ulang halaman untuk mendapat data dan tampilan terbaru
                            // Ini adalah cara paling sederhana dan andal untuk memperbarui UI secara keseluruhan
                            // terutama untuk mengubah dari "menunggu pembayaran" menjadi "menunggu konfirmasi"
                            // yang membutuhkan data `bukti_pembayaran` baru.
                            const cardBody = cardElement.querySelector('.card-body');
                            let statusMessage = cardBody.querySelector('.status-update-message');
                            if (!statusMessage) {
                                statusMessage = document.createElement('div');
                                statusMessage.className = 'alert alert-info mt-2 p-2 status-update-message';
                                cardBody.appendChild(statusMessage);
                            }
                            statusMessage.innerHTML = `Status berubah menjadi <strong>${newStatus.replace('_', ' ')}</strong>. Halaman akan dimuat ulang...`;
                            setTimeout(() => window.location.reload(), 2000);
                        }
                    }
                } catch (error) {
                    console.error("Gagal memeriksa status:", error);
                }
            }

            if (idsToWatch.length > 0) {
                const statusInterval = setInterval(checkOrderStatus, 15000); // Cek setiap 15 detik
            }
        });
    </script>
</body>

</html>