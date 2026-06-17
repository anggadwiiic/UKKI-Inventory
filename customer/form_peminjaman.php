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
    <link rel="icon" type="image/png" href="../assets/img/logo-ukki.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --teal-dark: #0F766E; --teal-light: #CCFBF1; --text-dark: #1E293B; --text-gray: #64748B; --bg: #F8FAFC; --white: #FFFFFF; --border: #E2E8F0; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg); color: var(--text-gray); line-height: 1.6;}
        h1, h2, h3, h4 { font-family: 'Outfit', sans-serif; color: var(--text-dark); font-weight: 700;}
        a { text-decoration: none; transition: 0.3s; }

        header { padding: 1rem 5%; background: var(--white); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid var(--border); }
        
        .logo { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .logo-img { width: 45px; height: auto; filter: drop-shadow(0px 4px 4px rgba(0, 0, 0, 0.25)); }
        .logo-text { display: flex; flex-direction: column; justify-content: center; }
        .logo-title { font-family: 'Aclonica', sans-serif; font-size: 1.4rem; font-weight: 400; color: var(--text-dark); line-height: 1.1; }
        .logo-subtitle { font-family: 'Inter', sans-serif; font-size: 0.75rem; font-weight: 500; color: var(--text-gray); line-height: 1.2; }
        
        .nav-links { display: flex; gap: 0.5rem; align-items: center; }
        .nav-link { color: var(--text-gray); font-weight: 500; position: relative; padding: 0.5rem 1.2rem; border-radius: 50px; transition: all 0.3s ease; }
        .nav-link:not(.active)::after { content: ''; position: absolute; width: 0; height: 2px; bottom: 4px; left: 50%; transform: translateX(-50%); background-color: var(--teal-dark); transition: width 0.3s ease; }
        .nav-link:not(.active):hover::after { width: 50%; }
        .nav-link:not(.active):hover { color: var(--teal-dark); }
        .nav-link.active { background: var(--teal-dark); color: var(--white); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .nav-link.active:hover { background: #0F172A; transform: translateY(-2px); }

        .container { max-width: 1200px; margin: 2rem auto 5rem; padding: 0 5%; }
        .back-link { display: inline-block; color: var(--text-gray); margin-bottom: 2rem; font-weight: 500; font-size: 0.95rem; }
        .back-link:hover { color: var(--teal-dark); }
        
        .main-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem; align-items: start; }
        
        .card-box { background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 2rem; margin-bottom: 1.5rem; }
        .card-title { font-size: 1.2rem; margin-bottom: 0.5rem; color: var(--text-dark);}
        .card-subtitle { font-size: 0.85rem; color: var(--text-gray); margin-bottom: 1.5rem;}

        .upload-area { border: 2px dashed var(--teal-dark); border-radius: 12px; padding: 2rem; text-align: center; background: var(--teal-light); cursor: pointer; margin-bottom: 1.5rem; transition: 0.3s;}
        .upload-area:hover { background: #D1FAE5; }
        .upload-area i { font-size: 2.5rem; color: var(--teal-dark); margin-bottom: 1rem;}
        
        .file-preview { display: none; align-items: center; justify-content: space-between; background: var(--white); border: 1px solid var(--border); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;}
        .file-info { display: flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 500; color: var(--teal-dark);}
        .file-info a { color: var(--teal-dark); text-decoration: underline; cursor: pointer; }
        .file-info a:hover { color: #134E4A; }
        .btn-remove { background: #FEE2E2; color: #B91C1C; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s;}
        .btn-remove:hover { background: #FECACA; }
        
        .tips-box { background: #FFFBEB; border: 1px solid #FDE68A; padding: 1.5rem; border-radius: 12px; font-size: 0.85rem; color: #92400E;}
        .tips-box h4 { color: #92400E; margin-bottom: 0.8rem; font-size: 1rem;}
        .tips-box ul { padding-left: 1.2rem; }

        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-dark); font-weight: 500;}
        .form-control { width: 100%; padding: 0.8rem 1rem; border: 1px solid #CBD5E1; border-radius: 8px; font-family: inherit; font-size: 0.95rem; background: var(--white);}
        .form-control:focus { border-color: var(--teal-dark); outline: none; box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);}

        .summary-card { position: sticky; top: 100px; }
        .summary-header { margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem;}
        .summary-title { font-size: 1.3rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.2rem;}
        .summary-cat { font-size: 0.85rem; color: var(--text-gray); margin-bottom: 0.5rem;}
        .summary-price { font-size: 1rem; color: var(--teal-dark); font-weight: 600;}

        .summary-item { display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 0.8rem; padding-bottom: 0.8rem; border-bottom: 1px solid var(--border);}
        .summary-total { display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 700; color: var(--text-dark); margin-top: 1rem;}
        
        .btn-submit { width: 100%; padding: 1rem; background: var(--teal-dark); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; margin-top: 1.5rem; transition: 0.3s;}
        .btn-submit:hover:not(:disabled) { background: #134E4A; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); transform: translateY(-2px);}
        .btn-submit:disabled { background: var(--border); color: var(--text-gray); cursor: not-allowed; }

        .alert { background: #FEE2E2; color: #B91C1C; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid #FCA5A5;}

        footer { background: #0F172A; color: #94A3B8; padding: 4rem 5% 2rem; margin-top: 4rem;}
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 2fr; gap: 4rem; max-width: 1200px; margin: 0 auto 3rem; }
        .footer-logo { color: var(--white); font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: 10px;}
        .footer-col h4 { color: var(--white); margin-bottom: 1.5rem; }
        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 0.8rem; }
        .footer-col a { color: #94A3B8; }
        .footer-col a:hover { color: var(--teal-light); }
        .social-icons { display: flex; gap: 1rem; font-size: 1.2rem; }
        .map-box { background: #1E293B; height: 150px; border-radius: 8px; overflow: hidden; position: relative;}
        .map-box iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
        .footer-bottom { text-align: center; border-top: 1px solid #1E293B; padding-top: 2rem; font-size: 0.9rem; }

        @media (max-width: 992px) { .main-grid { grid-template-columns: 1fr; } .footer-grid{ grid-template-columns: 1fr; gap: 2rem; } }
    </style>
</head>
<body>

    <header>
        <a href="../index.php" class="logo">
            <img src="../assets/img/logo-ukki.png" alt="Logo UKKI" class="logo-img">
            <div class="logo-text">
                <span class="logo-title">UKKI Inventory</span>
                <span class="logo-subtitle">UPN "Veteran" Jawa Timur</span>
            </div>
        </a>
        <nav class="nav-links">
            <a href="../index.php" class="nav-link">Home</a>
            <a href="../produk.php" class="nav-link active">Katalog</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="nav-link">Dashboard</a>
            <?php else: ?>
                <a href="../auth/login.php" class="nav-link">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="container">
        <a href="javascript:history.back()" class="back-link"><i class="fas fa-chevron-left"></i> Kembali ke detail item</a>

        <?php if(isset($error)): ?>
            <div class="alert"><i class="fas fa-exclamation-circle"></i> <?= $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data" class="main-grid" id="mainForm">
            <input type="hidden" name="tanggal_pinjam" value="<?= $tgl_pinjam; ?>">
            <input type="hidden" name="tanggal_kembali" value="<?= $tgl_kembali; ?>">
            <input type="hidden" name="jumlah" value="<?= $jumlah; ?>">

            <div class="left-col">
                <div class="card-box">
                    <h2 class="card-title">Form Peminjaman</h2>
                    <p class="card-subtitle">Lengkapi data diri dan upload KTM untuk validasi admin.</p>
                    
                    <h4 style="margin-bottom: 1rem; font-size: 0.95rem; color: var(--text-dark);">Upload Foto KTM (Maks 5MB) *</h4>
                    
                    <div class="upload-area" id="uploadArea" onclick="document.getElementById('ktm_input').click()">
                        <i class="far fa-id-card"></i>
                        <p style="color: var(--teal-dark); font-weight: 500;">Klik untuk upload foto KTM</p>
                        <p style="font-size: 0.8rem; color: var(--text-gray); margin-top: 5px;">Format: JPG, PNG, JPEG</p>
                    </div>

                    <input type="file" name="foto_ktm" id="ktm_input" style="display: none;" accept="image/png, image/jpeg, image/jpg" required>

                    <div class="file-preview" id="previewArea">
                        <div class="file-info">
                            <i class="fas fa-file-image"></i>
                            <span id="fileName">namafile.jpg</span>
                        </div>
                        <button type="button" class="btn-remove" onclick="removeFile()" title="Hapus file"><i class="fas fa-times"></i></button>
                    </div>

                    <div class="tips-box">
                        <h4>Tips Foto KTM yang Baik</h4>
                        <ul>
                            <li>Foto jelas dan tidak blur</li>
                            <li>Pencahayaan cukup, hindari bayangan</li>
                            <li>Seluruh kartu terlihat penuh</li>
                            <li>Tidak ada pantulan cahaya atau refleksi</li>
                        </ul>
                    </div>
                </div>

                <div class="card-box">
                    <h2 class="card-title">Data Peminjam</h2>
                    <p class="card-subtitle">Silakan sesuaikan data peminjam di bawah ini jika meminjam untuk orang lain.</p>

                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_peminjam" class="form-control" value="<?= htmlspecialchars($user['nama_lengkap']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>NPM</label>
                        <input type="text" name="npm_peminjam" class="form-control" value="<?= htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email_peminjam" class="form-control" value="<?= htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>No. HP (WhatsApp) *</label>
                        <input type="tel" name="no_wa" id="wa_input" class="form-control" placeholder="Contoh: 08123456789" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, ''); validateForm();" required>
                        <small id="wa_error" style="color: #DC2626; font-size: 0.8rem; display: none; margin-top: 4px;">Nomor WA harus diawali 08 dan terdiri dari 10-13 digit angka.</small>
                    </div>
                    <div class="form-group">
                        <label>Keperluan Peminjaman (Opsional)</label>
                        <textarea name="keperluan" class="form-control" rows="3" placeholder="Jelaskan untuk acara/kegiatan apa barang ini akan digunakan..."></textarea>
                    </div>
                </div>
            </div>

            <div class="right-col">
                <div class="card-box summary-card">
                    <h2 class="card-title" style="margin-bottom: 1.5rem;"> Ringkasan Pesanan</h2>
                    
                    <div class="summary-header">
                        <div class="summary-title"><?= htmlspecialchars($inv['nama_barang']); ?></div>
                        <div class="summary-cat"><?= htmlspecialchars($inv['nama_kategori']); ?></div>
                        <div class="summary-price">Rp <?= number_format($inv['harga_sewa_per_hari'], 0, ',', '.'); ?> <span style="font-weight: 400; font-size: 0.85rem; color: var(--text-gray);">/ hari</span></div>
                    </div>

                    <div class="summary-item">
                        <span style="color: var(--text-gray);">Tanggal Mulai</span>
                        <span style="font-weight: 500; color: var(--text-dark);"><?= date('d M Y', strtotime($tgl_pinjam)); ?></span>
                    </div>
                    <div class="summary-item">
                        <span style="color: var(--text-gray);">Tanggal Selesai</span>
                        <span style="font-weight: 500; color: var(--text-dark);"><?= date('d M Y', strtotime($tgl_kembali)); ?></span>
                    </div>
                    <div class="summary-item">
                        <span style="color: var(--text-gray);">Durasi</span>
                        <span style="font-weight: 500; color: var(--text-dark);"><?= $durasi; ?> hari</span>
                    </div>
                    <div class="summary-item" style="border: none; padding-bottom: 0;">
                        <span style="color: var(--text-gray);">Jumlah</span>
                        <span style="font-weight: 500; color: var(--text-dark);"><?= $jumlah; ?> unit</span>
                    </div>

                    <div class="summary-total">
                        <span>Total</span>
                        <span style="color: var(--teal-dark);">Rp <?= number_format($total_biaya, 0, ',', '.'); ?></span>
                    </div>

                    <p style="font-size: 0.8rem; color: var(--text-gray); text-align: center; margin-top: 1rem;">Selesaikan pengajuan untuk konfirmasi booking</p>
                    <button type="submit" name="submit_booking" class="btn-submit" id="btnSubmit" disabled>Ajukan Peminjaman</button>
                </div>
            </div>
        </form>
    </div>

    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <div class="footer-logo"><i class="fas fa-box"></i> UKKI Inventory</div>
                <p style="margin-bottom: 1rem;">Sistem penyewaan inventaris organisasi mahasiswa yang aman, cepat, dan terpercaya.</p>
                <p><i class="fas fa-map-marker-alt" style="width: 20px;"></i> Masjid Al Istiqomah UPN "Veteran" Jawa Timur</p>
                <p><i class="fas fa-envelope" style="width: 20px;"></i> admin@ukkiupnjt.com</p>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="../produk.php">Katalog</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Social Media UKKI</h4>
                <div class="social-icons">
                    <a href="https://wa.me/6289677778190" target="_blank"><i class="fab fa-whatsapp"></i></a>
                    <a href="https://www.instagram.com/ukki_upn/" target="_blank"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.linkedin.com/company/ukkiupnvjt/" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <div class="map-box">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3957.387140801831!2d112.78762741477488!3d-7.333100694708173!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd7fab87f4c5111%3A0x6fbcebf5a32eb4a7!2sUPN%20%22Veteran%22%20Jawa%20Timur!5e0!3m2!1sen!2sid!4v1679412345678!5m2!1sen!2sid" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>
        <div class="footer-bottom"><p>© 2026 UKKI UPN "Veteran" Jawa Timur. All rights reserved.</p></div>
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
                waError.style.display = 'block';
            } else {
                waError.style.display = 'none';
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
                fileNameDisp.innerHTML = `<a href="${fileURL}" target="_blank" title="Klik untuk lihat foto">${file.name}</a>`;
                
                uploadArea.style.display = 'none';
                previewArea.style.display = 'flex';
                validateForm();
            }
        });

        function removeFile() {
            fileInput.value = "";
            uploadArea.style.display = 'block';
            previewArea.style.display = 'none';
            validateForm();
        }
        
        window.onload = validateForm;
    </script>
</body>
</html>