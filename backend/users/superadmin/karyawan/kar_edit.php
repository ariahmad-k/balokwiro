<?php
session_start();
include('../../../koneksi.php');

// 1. KEAMANAN: Cek hak akses. Hanya owner yang boleh mengedit.
if (!isset($_SESSION['user']) || $_SESSION['user']['jabatan'] !== 'owner') {
    header('Location: ../../login.php');
    exit;
}

// 2. VALIDASI & AMBIL DATA KARYAWAN YANG AKAN DIEDIT
if (!isset($_GET['id_karyawan'])) {
    $_SESSION['notif'] = ['pesan' => 'Aksi tidak valid, ID karyawan tidak ditemukan.', 'tipe' => 'warning'];
    header('Location: data_karyawan.php');
    exit;
}
$id_karyawan_edit = $_GET['id_karyawan'];

// Ambil data lama menggunakan prepared statement untuk ditampilkan di form
$stmt_get = mysqli_prepare($koneksi, "SELECT * FROM karyawan WHERE id_karyawan = ?");
mysqli_stmt_bind_param($stmt_get, "i", $id_karyawan_edit);
mysqli_stmt_execute($stmt_get);
$result_get = mysqli_stmt_get_result($stmt_get);
$data = mysqli_fetch_assoc($result_get);

if (!$data) {
    $_SESSION['notif'] = ['pesan' => 'Data karyawan tidak ditemukan.', 'tipe' => 'warning'];
    header('Location: data_karyawan.php');
    exit;
}

// 3. LOGIKA PROSES FORM SAAT DISUBMIT (UPDATE DATA)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil semua data dari form
    $id_karyawan_post = $_POST['id_karyawan'];
    $nama = $_POST['nama'];
    $username = $_POST['username'];
    $jabatan = $_POST['jabatan'];
    $no_tlp = $_POST['no_telepon'];
    $email = $_POST['email'];
    $password = $_POST['password']; // Bisa kosong

    // 4. LOGIKA CERDAS UNTUK UPDATE PASSWORD
    // Cek apakah admin mengisi kolom password baru.
    if (!empty($password)) {
        // Jika diisi, hash password baru dan siapkan query UPDATE dengan kolom password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query_update = "UPDATE karyawan SET nama=?, username=?, jabatan=?, no_telepon=?, email=?, password=? WHERE id_karyawan=?";
        $stmt_update = mysqli_prepare($koneksi, $query_update);
        mysqli_stmt_bind_param($stmt_update, "ssssssi", $nama, $username, $jabatan, $no_tlp, $email, $hashed_password, $id_karyawan_post);
    } else {
        // Jika dikosongkan, siapkan query UPDATE TANPA mengubah kolom password
        $query_update = "UPDATE karyawan SET nama=?, username=?, jabatan=?, no_telepon=?, email=? WHERE id_karyawan=?";
        $stmt_update = mysqli_prepare($koneksi, $query_update);
        mysqli_stmt_bind_param($stmt_update, "sssssi", $nama, $username, $jabatan, $no_tlp, $email, $id_karyawan_post);
    }

    // Eksekusi query update
    if (mysqli_stmt_execute($stmt_update)) {
        $_SESSION['notif'] = ['pesan' => 'Data karyawan berhasil diperbarui.', 'tipe' => 'success'];
    } else {
        $_SESSION['notif'] = ['pesan' => 'Gagal memperbarui data. Mungkin username/email sudah digunakan.', 'tipe' => 'danger'];
    }
    header("Location: data_karyawan.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title>Edit Karyawan - Owner</title>
    <link href="../../../css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="icon" type="image/png" href="../../../assets/img/logo-kuebalok.png"> 

</head>
<body class="sb-nav-fixed">
    <?php include "../inc/navbar.php"; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include "../inc/sidebar.php"; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Edit Data Karyawan</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="data_karyawan.php">Data Karyawan</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>

                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-user-edit me-1"></i>Formulir Edit: <?= htmlspecialchars($data['nama']) ?></div>
                        <div class="card-body">
                            <form action="" method="post">
                                <input type="hidden" name="id_karyawan" value="<?= htmlspecialchars($data['id_karyawan']) ?>">

                                <div class="mb-3">
                                    <label for="nama" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" name="nama" id="nama" value="<?= htmlspecialchars($data['nama']) ?>" required />
                                </div>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" id="username" value="<?= htmlspecialchars($data['username']) ?>" required />
                                </div>
                                <div class="mb-3">
                                    <label for="jabatan" class="form-label">Jabatan</label>
                                    <select class="form-select" name="jabatan" id="jabatan" required>
                                        <option value="kasir" <?= ($data['jabatan'] === 'kasir') ? 'selected' : '' ?>>Kasir</option>
                                        <option value="admin" <?= ($data['jabatan'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                                        <option value="owner" <?= ($data['jabatan'] === 'owner') ? 'selected' : '' ?>>Owner</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="no_tlp" class="form-label">No. Telepon</label>
                                    <input type="text" class="form-control" name="no_tlp" id="no_tlp" value="<?= htmlspecialchars($data['no_telepon']) ?>" required />
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars($data['email']) ?>" required />
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password Baru</label>
                                    <input type="password" class="form-control" name="password" id="password" placeholder="Kosongkan jika tidak ingin mengubah password" />
                                    <small class="form-text text-muted">Hanya isi kolom ini jika Anda ingin mengubah password karyawan.</small>
                                </div>
                                <button type="submit" name="submit" class="btn btn-primary">Simpan Perubahan</button>
                                <a href="data_karyawan.php" class="btn btn-secondary">Batal</a>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
            <footer class="py-4 bg-light mt-auto">
                </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../js/scripts.js"></script>
</body>
</html>