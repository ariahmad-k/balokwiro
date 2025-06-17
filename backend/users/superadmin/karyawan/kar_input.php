<?php
session_start();
include('../../../koneksi.php');

// 1. KEAMANAN: Cek hak akses. Hanya owner yang boleh menambah karyawan.
if (!isset($_SESSION['user']) || $_SESSION['user']['jabatan'] !== 'owner') {
    header('Location: ../../../login.php');
    exit;
}

// 2. LOGIKA PROSES FORM SAAT DISUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $nama = $_POST['nama'];
    $username = $_POST['username'];
    $password = $_POST['password']; // Ambil password mentah
    $jabatan = $_POST['jabatan'];
    $no_tlp = $_POST['no_tlp'];
    $email = $_POST['email'];

    // 3. VALIDASI: Cek apakah username sudah ada
    $stmt_check = mysqli_prepare($koneksi, "SELECT id_karyawan FROM karyawan WHERE username = ?");
    mysqli_stmt_bind_param($stmt_check, "s", $username);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($result_check) > 0) {
        // Jika username sudah ada, buat notifikasi error
        $_SESSION['notif'] = ['pesan' => 'Gagal! Username "' . htmlspecialchars($username) . '" sudah digunakan.', 'tipe' => 'danger'];
        header('Location: kar_input.php'); // Kembali ke form input
        exit;
    }

    // 4. KEAMANAN: Hash password sebelum disimpan ke database
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 5. KEAMANAN: Gunakan Prepared Statement untuk INSERT data
    $query = "INSERT INTO karyawan (nama, username, password, jabatan, no_telepon, email) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ssssss", $nama, $username, $hashed_password, $jabatan, $no_tlp, $email);

    if (mysqli_stmt_execute($stmt)) {
        // Jika berhasil, buat notifikasi sukses dan arahkan ke halaman daftar
        $_SESSION['notif'] = ['pesan' => 'Karyawan baru berhasil ditambahkan.', 'tipe' => 'success'];
        header('Location: data_karyawan.php');
    } else {
        // Jika gagal, buat notifikasi error dan arahkan kembali ke form input
        $_SESSION['notif'] = ['pesan' => 'Gagal menambahkan karyawan. Terjadi error.', 'tipe' => 'danger'];
        header('Location: kar_input.php');
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title>Tambah Karyawan - Owner</title>
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
                    <h1 class="mt-4">Tambah Data Karyawan</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="data_karyawan.php">Data Karyawan</a></li>
                        <li class="breadcrumb-item active">Tambah Karyawan</li>
                    </ol>

                     <?php
                    // Menampilkan notifikasi jika ada error dari proses sebelumnya (misal: username duplikat)
                    if (isset($_SESSION['notif'])) {
                        $notif = $_SESSION['notif'];
                        echo '<div class="alert alert-' . htmlspecialchars($notif['tipe']) . ' alert-dismissible fade show" role="alert">';
                        echo htmlspecialchars($notif['pesan']);
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        unset($_SESSION['notif']);
                    }
                    ?>

                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-user-plus me-1"></i>Formulir Karyawan Baru</div>
                        <div class="card-body">
                            <form action="" method="post">
                                <div class="mb-3">
                                    <label for="nama" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" name="nama" id="nama" required />
                                </div>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" id="username" required />
                                </div>
                                <div class="mb-3">
                                    <label for="jabatan" class="form-label">Jabatan</label>
                                    <select class="form-select" name="jabatan" id="jabatan" required>
                                        <option value="" disabled selected>-- Pilih Jabatan --</option>
                                        <option value="kasir">Kasir</option>
                                        <option value="admin">Admin</option>
                                        <option value="owner">Owner</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="no_tlp" class="form-label">No. Telepon</label>
                                    <input type="text" class="form-control" name="no_tlp" id="no_tlp" required />
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" id="email" required />
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password" id="password" required />
                                </div>
                                <button type="submit" class="btn btn-primary">Simpan Karyawan</button>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../../../js/scripts.js"></script>
</body>
</html>