<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_POST['tanggal_pinjam'])) {
    header("Location: ../produk.php");
    exit;
}

$id_user = $_SESSION['user_id'];
$id_inventaris = (int)$_GET['id'];
$tgl_pinjam = $_POST['tanggal_pinjam'];
$tgl_kembali = $_POST['tanggal_kembali'];
$jumlah = (int)$_POST['jumlah'];

$q_user = mysqli_query($conn, "SELECT * FROM users WHERE id_user = $id_user");
$user = mysqli_fetch_assoc($q_user);

$q_inv = mysqli_query($conn, "SELECT i.*, k.nama_kategori FROM inventaris i JOIN kategori k ON i.id_kategori = k.id_kategori WHERE i.id_inventaris = $id_inventaris");
$inv = mysqli_fetch_assoc($q_inv);

$date1 = new DateTime($tgl_pinjam);
$date2 = new DateTime($tgl_kembali);
$durasi = max(1, $date1->diff($date2)->days);
$total_biaya = $durasi * $inv['harga_sewa_per_hari'] * $jumlah;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_booking'])) {
    $no_wa = mysqli_real_escape_string($conn, $_POST['no_wa']);
    $keperluan = mysqli_real_escape_string($conn, $_POST['keperluan']);
    
    $panjang_wa = strlen($no_wa);
    if ($panjang_wa < 10 || $panjang_wa > 13 || !ctype_digit($no_wa) || strpos($no_wa, '08') !== 0) {
        $error = "Nomor WhatsApp tidak valid. Harus diawali '08' dan berupa angka 10-13 digit.";
    } else {
        mysqli_query($conn, "UPDATE users SET no_telp = '$no_wa' WHERE id_user = $id_user");

        $foto_ktm = '';
        if (isset($_FILES['foto_ktm']) && $_FILES['foto_ktm']['error'] == 0) {
            if ($_FILES['foto_ktm']['size'] > 5242880) {
                $error = "Ukuran file foto KTM maksimal 5MB!";
            } else {
                $ext = pathinfo($_FILES['foto_ktm']['name'], PATHINFO_EXTENSION);
                $foto_ktm = time() . '_' . $id_user . '.' . $ext;
                move_uploaded_file($_FILES['foto_ktm']['tmp_name'], '../assets/img/ktm/' . $foto_ktm);
            }
        } else {
            $error = "Foto KTM wajib diupload!";
        }
    }

    if (!isset($error)) {
        mysqli_begin_transaction($conn);
        try {
            $q_pem = "INSERT INTO peminjaman (id_user, tanggal_pinjam, tanggal_kembali_rencana, total_biaya, status, foto_ktm, keperluan) 
                      VALUES ($id_user, '$tgl_pinjam', '$tgl_kembali', $total_biaya, 'request', '$foto_ktm', '$keperluan')";
            mysqli_query($conn, $q_pem);
            $id_peminjaman = mysqli_insert_id($conn);

            mysqli_query($conn, "INSERT INTO detail_peminjaman (id_peminjaman, id_inventaris, jumlah) VALUES ($id_peminjaman, $id_inventaris, $jumlah)");
            
            mysqli_commit($conn);
            header("Location: success_booking.php?id=$id_peminjaman");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Gagal memproses booking.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Peminjaman - UKKI Inventory</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Aclonica&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/logo-ukki.png">
    
    <style>
        :root { --teal-dark: #0F766E; --teal-light: #CCFBF1; --text-dark: #1E293B; --text-gray: #64748B; --bg: #F8FAFC; --white: #FFFFFF; --border: #E2E8F0; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg); color: var(--text-gray); line-height: 1.6; display: flex; flex-direction: column; min-height: 100vh;}
        h1, h2, h3, h4 { font-family: 'Outfit', sans-serif; color: var(--text-dark); font-weight: 700;}
        
        /* HEADER KUSTOM */
        .logo-img { width: 45px; height: auto; filter: drop-shadow(0px 4px 4px rgba(0, 0, 0, 0.25)); }
        .logo-title { font-family: 'Aclonica', sans-serif; font-size: 1.4rem; font-weight: 400; color: var(--text-dark); line-height: 1.1; }
        .logo-subtitle { font-family: 'Inter', sans-serif; font-size: 0.75rem; font-weight: 500; color: var(--text-gray); line-height: 1.2; }
        
        .nav-link-custom { color: var(--text-gray); font-weight: 500; position: relative; padding: 0.5rem 1.2rem; border-radius: 50px; transition: all 0.3s ease; text-decoration: none;}
        .nav-link-custom:not(.active)::after { content: ''; position: absolute; width: 0; height: 2px; bottom: 4px; left: 50%; transform: translateX(-50%); background-color: var(--teal-dark); transition: width 0.3s ease; }
        .nav-link-custom:not(.active):hover::after { width: 50%; }
        .nav-link-custom:not(.active):hover { color: var(--teal-dark); }
        .nav-link-custom.active { background: var(--teal-dark); color: var(--white); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .nav-link-custom.active:hover { background: #0F172A; transform: translateY(-2px); color: var(--white);}

        /* UPLOAD AREA */
        .upload-area { border: 2px dashed var(--teal-dark); background: var(--teal-light); cursor: pointer; transition: 0.3s;}
        .upload-area:hover { background: #D1FAE5; }
        .btn-remove { background: #FEE2E2; color: #B91C1C; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s;}
        .btn-remove:hover { background: #FECACA; }
        
        /* FORM CONTROL KUSTOM */
        .form-control-custom { border: 1px solid #CBD5E1; border-radius: 8px; font-family: inherit; font-size: 0.95rem; }
        .form-control-custom:focus { border-color: var(--teal-dark); outline: none; box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);}

        /* SUMMARY & BUTTONS */
        .btn-submit { background: var(--teal-dark); color: white; transition: 0.3s; border: none; }
        .btn-submit:hover:not(:disabled) { background: #134E4A; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); transform: translateY(-2px);}
        .btn-submit:disabled { background: var(--border); color: var(--text-gray); cursor: not-allowed; border: none; }
    </style>
</head>
<body>

    <header class="sticky-top bg-white border-bottom py-3 px-md-5">
        <div class="container-fluid d-flex align-items-center justify-content-between">
            <a href="../index.php" class="text-decoration-none d-flex align-items-center gap-2">
                <img src="../assets/img/logo-ukki.png" alt="Logo UKKI" class="logo-img">
                <div class="logo-text d-flex flex-column justify-content-center">
                    <span class="logo-title">UKKI Inventory</span>
                    <span class="logo-subtitle">UPN "Veteran" Jawa Timur</span>
                </div>
            </a>
            <nav class="d-none d-md-flex align-items-center gap-2">
                <a href="../index.php" class="nav-link-custom">Home</a>
                <a href="../produk.php" class="nav-link-custom active">Katalog</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="nav-link-custom">Dashboard</a>
                <?php else: ?>
                    <a href="../auth/login.php" class="nav-link-custom">Login</a>
                <?php endif; ?>
            </nav> 
        </div>
    </header>

    <main class="container py-4 my-3" style="max-width: 1200px;">
        <a href="javascript:history.back()" class="text-decoration-none text-muted mb-4 d-inline-block fw-medium"><i class="fas fa-chevron-left"></i> Kembali ke detail item</a>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2" role="alert"><i class="fas fa-exclamation-circle"></i> <?= $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data" class="row gy-4 gx-lg-5" id="mainForm">
            <input type="hidden" name="tanggal_pinjam" value="<?= $tgl_pinjam; ?>">
            <input type="hidden" name="tanggal_kembali" value="<?= $tgl_kembali; ?>">
            <input type="hidden" name="jumlah" value="<?= $jumlah; ?>">

            <div class="col-lg-7">
                <div class="bg-white border rounded-4 p-4 mb-4">
                    <h2 class="fs-5 fw-semibold text-dark mb-2">Form Peminjaman</h2>
                    <p class="text-muted small mb-4">Lengkapi data diri dan upload KTM untuk validasi admin.</p>
                    
                    <h4 class="fs-6 fw-medium text-dark mb-3">Upload Foto KTM (Maks 5MB) *</h4>
                    
                    <div class="upload-area rounded-3 text-center p-4 mb-4" id="uploadArea" onclick="document.getElementById('ktm_input').click()">
                        <i class="far fa-id-card fs-1 mb-3" style="color: var(--teal-dark);"></i>
                        <p class="fw-medium mb-1" style="color: var(--teal-dark);">Klik untuk upload foto KTM</p>
                        <p class="small text-muted mb-0">Format: JPG, PNG, JPEG</p>
                    </div>

                    <input type="file" name="foto_ktm" id="ktm_input" class="d-none" accept="image/png, image/jpeg, image/jpg" required>

                    <div class="file-preview align-items-center justify-content-between bg-white border p-3 rounded-3 mb-4 d-none" id="previewArea">
                        <div class="d-flex align-items-center gap-2 small fw-medium" style="color: var(--teal-dark);">
                            <i class="fas fa-file-image"></i>
                            <span id="fileName">namafile.jpg</span>
                        </div>
                        <button type="button" class="btn-remove" onclick="removeFile()" title="Hapus file"><i class="fas fa-times"></i></button>
                    </div>

                    <div class="p-3 rounded-3 mb-2 border" style="background: #FFFBEB; border-color: #FDE68A; color: #92400E;">
                        <h4 class="fs-6 fw-medium mb-2">Tips Foto KTM yang Baik</h4>
                        <ul class="mb-0 small ps-3">
                            <li>Foto jelas dan tidak blur</li>
                            <li>Pencahayaan cukup, hindari bayangan</li>
                            <li>Seluruh kartu terlihat penuh</li>
                            <li>Tidak ada pantulan cahaya atau refleksi</li>
                        </ul>
                    </div>
                </div>

                <div class="bg-white border rounded-4 p-4">
                    <h2 class="fs-5 fw-semibold text-dark mb-2">Data Peminjam</h2>
                    <p class="text-muted small mb-4">Silakan sesuaikan data peminjam di bawah ini jika meminjam untuk orang lain.</p>

                    <div class="mb-3">
                        <label class="form-label small fw-medium text-dark">Nama Lengkap</label>
                        <input type="text" name="nama_peminjam" class="form-control form-control-custom py-2" value="<?= htmlspecialchars($user['nama_lengkap']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-dark">NPM</label>
                        <input type="text" name="npm_peminjam" class="form-control form-control-custom py-2" value="<?= htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-dark">Email</label>
                        <input type="email" name="email_peminjam" class="form-control form-control-custom py-2" value="<?= htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-dark">No. HP (WhatsApp) *</label>
                        <input type="tel" name="no_wa" id="wa_input" class="form-control form-control-custom py-2" placeholder="Contoh: 08123456789" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, ''); validateForm();" required>
                        <small id="wa_error" class="text-danger d-none mt-1" style="font-size: 0.8rem;">Nomor WA harus diawali 08 dan terdiri dari 10-13 digit angka.</small>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-medium text-dark">Keperluan Peminjaman (Opsional)</label>
                        <textarea name="keperluan" class="form-control form-control-custom py-2" rows="3" placeholder="Jelaskan untuk acara/kegiatan apa barang ini akan digunakan..."></textarea>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="bg-white border rounded-4 p-4 sticky-top" style="top: 100px;">
                    <h2 class="fs-5 fw-semibold text-dark mb-4">Ringkasan Pesanan</h2>
                    
                    <div class="pb-3 border-bottom mb-4">
                        <div class="fs-5 fw-bold text-dark lh-1 mb-1" style="font-family: 'Outfit';"><?= htmlspecialchars($inv['nama_barang']); ?></div>
                        <div class="small text-muted mb-2"><?= htmlspecialchars($inv['nama_kategori']); ?></div>
                        <div class="fw-semibold" style="color: var(--teal-dark);">Rp <?= number_format($inv['harga_sewa_per_hari'], 0, ',', '.'); ?> <span class="fw-normal small text-muted">/ hari</span></div>
                    </div>

                    <div class="d-flex justify-content-between pb-3 border-bottom mb-3 small">
                        <span class="text-muted">Tanggal Mulai</span>
                        <span class="fw-medium text-dark"><?= date('d M Y', strtotime($tgl_pinjam)); ?></span>
                    </div>
                    <div class="d-flex justify-content-between pb-3 border-bottom mb-3 small">
                        <span class="text-muted">Tanggal Selesai</span>
                        <span class="fw-medium text-dark"><?= date('d M Y', strtotime($tgl_kembali)); ?></span>
                    </div>
                    <div class="d-flex justify-content-between pb-3 border-bottom mb-3 small">
                        <span class="text-muted">Durasi</span>
                        <span class="fw-medium text-dark"><?= $durasi; ?> hari</span>
                    </div>
                    <div class="d-flex justify-content-between mb-4 small">
                        <span class="text-muted">Jumlah</span>
                        <span class="fw-medium text-dark"><?= $jumlah; ?> unit</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center fs-5 fw-bold text-dark mt-2">
                        <span>Total</span>
                        <span style="color: var(--teal-dark);">Rp <?= number_format($total_biaya, 0, ',', '.'); ?></span>
                    </div>

                    <p class="small text-muted text-center mt-4 mb-3">Selesaikan pengajuan untuk konfirmasi booking</p>
                    <button type="submit" name="submit_booking" class="w-100 py-3 rounded-3 fw-semibold btn-submit" id="btnSubmit" disabled>Ajukan Peminjaman</button>
                </div>
            </div>
        </form>
    </main>

    <footer class="pt-5 pb-3 px-md-5 mt-auto" style="background: #0F172A;">
        <div class="container-fluid" style="max-width: 1200px;">
            <div class="row gx-4 gy-5 mb-4">
                <div class="col-lg-5 pe-lg-5">
                    <div class="d-flex align-items-center gap-2 mb-3 text-white fs-4 fw-bold" style="font-family: 'Outfit';">UKKI Inventory</div>
                    <p class="mb-3 text-muted" style="color: #94A3B8 !important; font-size: 0.95rem;">Sistem penyewaan inventaris organisasi mahasiswa yang aman, cepat, dan terpercaya.</p>
                    <ul class="d-flex flex-column gap-2 text-muted list-unstyled" style="color: #94A3B8 !important; font-size: 0.95rem;">
                        <li><i class="fas fa-map-marker-alt" style="width: 25px;"></i> Masjid Al Istiqomah UPN "Veteran" Jawa Timur</li>
                        <li><i class="fas fa-envelope" style="width: 25px;"></i> event.ukki@gmail.com</li>
                        <li><i class="fas fa-phone" style="width: 25px;"></i> +6289677778190</li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h4 class="text-white fs-5 fw-bold mb-3">Quick Links</h4>
                    <ul class="d-flex flex-column gap-2 list-unstyled" style="font-size: 0.95rem;">
                        <li><a href="../index.php" class="text-decoration-none" style="color: #94A3B8;">Home</a></li>
                        <li><a href="../produk.php" class="text-decoration-none" style="color: #94A3B8;">Katalog</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h4 class="text-white fs-5 fw-bold mb-3">Social Media UKKI</h4>
                    <div class="d-flex gap-3 mt-1">
                        <a href="https://wa.me/6289677778190" target="_blank" class="fs-5 text-decoration-none" style="color: #94A3B8;"><i class="fab fa-whatsapp"></i></a>
                        <a href="https://www.instagram.com/ukki_upn/" target="_blank" class="fs-5 text-decoration-none" style="color: #94A3B8;"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.linkedin.com/company/ukkiupnvjt/" target="_blank" class="fs-5 text-decoration-none" style="color: #94A3B8;"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="w-100" style="height: 150px; border-radius: 8px; overflow: hidden; position: relative;">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3957.387140801831!2d112.78762741477488!3d-7.333100694708173!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd7fab87f4c5111%3A0x6fbcebf5a32eb4a7!2sUPN%20%22Veteran%22%20Jawa%20Timur!5e0!3m2!1sen!2sid!4v1679412345678!5m2!1sen!2sid" allowfullscreen="" loading="lazy" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;"></iframe>
                    </div>
                </div>
            </div>
            <div class="text-center pt-4 border-top mt-4" style="border-color: #1E293B !important; font-size: 0.9rem;">
                <p class="mb-0" style="color: #94A3B8;">© 2026 UKKI UPN "Veteran" Jawa Timur. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        const fileInput = document.getElementById('ktm_input');
        const uploadArea = document.getElementById('uploadArea');
        const previewArea = document.getElementById('previewArea');
        const fileNameDisp = document.getElementById('fileName');
        const waInput = document.getElementById('wa_input');
        const waError = document.getElementById('wa_error');
        const btnSubmit = document.getElementById('btnSubmit');

        function validateForm() {
            let isFileValid = fileInput.files && fileInput.files.length > 0;
            let waVal = waInput.value;
            let waLength = waVal.length;
            let isWaValid = (waLength >= 10 && waLength <= 13 && waVal.startsWith('08'));

            if (waLength > 0 && !isWaValid) {
                waError.classList.remove('d-none');
            } else {
                waError.classList.add('d-none');
            }

            if (isFileValid && isWaValid) {
                btnSubmit.disabled = false;
            } else {
                btnSubmit.disabled = true;
            }
        }

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const maxAllowedSize = 5 * 1024 * 1024; 
                
                if (file.size > maxAllowedSize) {
                    alert("Gagal: Ukuran file foto KTM maksimal 5MB!");
                    this.value = "";
                    validateForm();
                    return;
                }
                
                const fileURL = URL.createObjectURL(file);
                fileNameDisp.innerHTML = `<a href="${fileURL}" target="_blank" title="Klik untuk lihat foto" style="color: #0F766E; text-decoration: underline;">${file.name}</a>`;
                
                uploadArea.classList.add('d-none');
                previewArea.classList.remove('d-none');
                previewArea.classList.add('d-flex');
                validateForm();
            }
        });

        function removeFile() {
            fileInput.value = "";
            uploadArea.classList.remove('d-none');
            previewArea.classList.add('d-none');
            previewArea.classList.remove('d-flex');
            validateForm();
        }
        
        window.onload = validateForm;
    </script>
</body>
</html>