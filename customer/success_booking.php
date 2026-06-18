<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: ../index.php");
    exit;
}

$id_pem = (int)$_GET['id'];
$id_user = $_SESSION['user_id'];

$q = "SELECT p.*, i.nama_barang, u.nama_lengkap, u.username 
      FROM peminjaman p 
      JOIN detail_peminjaman d ON p.id_peminjaman = d.id_peminjaman 
      JOIN inventaris i ON d.id_inventaris = i.id_inventaris 
      JOIN users u ON p.id_user = u.id_user 
      WHERE p.id_peminjaman = $id_pem AND p.id_user = $id_user";
$res = mysqli_query($conn, $q);
$data = mysqli_fetch_assoc($res);

if (!$data) {
    header("Location: ../index.php");
    exit;
}

$date1 = new DateTime($data['tanggal_pinjam']);
$date2 = new DateTime($data['tanggal_kembali_rencana']);
$durasi = max(1, $date1->diff($date2)->days);

$status_text = 'Menunggu Konfirmasi';
$status_color = '#d97706'; 

switch($data['status']) {
    case 'request': 
        $status_text = 'Menunggu Konfirmasi'; 
        $status_color = '#d97706'; 
        break;
    case 'disetujui': 
        $status_text = 'Disetujui / Silakan Diambil'; 
        $status_color = '#059669'; 
        break;
    case 'selesai': 
        $status_text = 'Transaksi Selesai'; 
        $status_color = '#2563EB'; 
        break;
}

$wa_text = "Halo Admin, saya " . $data['nama_lengkap'] . " dengan NPM " . $data['username'] . " baru saja menyewa " . $data['nama_barang'] . " dengan durasi " . $durasi . " hari mulai tanggal " . date('d/m/Y', strtotime($data['tanggal_pinjam'])) . " hingga " . date('d/m/Y', strtotime($data['tanggal_kembali_rencana'])) . ". Mohon di-acc ya!";
$wa_link = "https://wa.me/6289677778190?text=" . urlencode($wa_text);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sukses Booking</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Aclonica&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- BOOTSTRAP 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/logo-ukki.png">
    
    <style>
        :root {
            --teal-dark: #0F766E;   
            --teal-light: #CCFBF1;  
            --text-dark: #1E293B;   
            --text-gray: #64748B;   
            --bg: #F8FAFC;
            --white: #FFFFFF;
            --border: #E2E8F0;
        }
        body { font-family: 'Outfit', sans-serif; background-color: var(--bg); color: var(--text-gray); line-height: 1.6; display: flex; flex-direction: column; min-height: 100vh;}
        .text-poppins { font-family: 'Poppins', sans-serif; }
        
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

        /* CARD CUSTOM STYLING */
        .success-card { background: var(--white); border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
        .receipt-box { background: var(--bg); border: 1px solid var(--border); border-radius: 12px; }
        
        .btn-wa { background-color: #22C55E; color: #FFFFFF; transition: 0.3s; box-shadow: 0 4px 6px -1px rgba(34, 197, 94, 0.2); border: none; font-family: 'Outfit', sans-serif;}
        .btn-wa:hover { background-color: #16A34A; color: #FFFFFF; transform: translateY(-2px); box-shadow: 0 6px 8px -1px rgba(34, 197, 94, 0.3);}
        
        .btn-outline-custom { background-color: transparent; color: var(--teal-dark); border: 1px solid var(--teal-dark); transition: 0.3s; font-family: 'Outfit', sans-serif;}
        .btn-outline-custom:hover { background: var(--teal-dark); color: var(--white); transform: translateY(-2px); }
    </style>
</head>
<body>

    <!-- NAVBAR KONSISTEN -->
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
                <a href="../produk.php" class="nav-link-custom">Katalog</a>
                <a href="dashboard.php" class="nav-link-custom">Dashboard</a>
            </nav> 
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="container py-5 d-flex align-items-center justify-content-center flex-grow-1">
        <div class="success-card w-100 p-4 p-md-5 text-center" style="max-width: 500px;">
            
            <h1 class="fs-1 fw-bold mb-2" style="color: var(--teal-dark);">Berhasil!</h1>
            <p class="small text-muted mb-4 pb-2 px-md-3 text-poppins">Data peminjaman dan foto KTM telah diterima. Segera hubungi admin untuk verifikasi.</p>

            <div class="receipt-box p-4 mb-4 text-start text-poppins">
                <div class="d-flex align-items-center gap-2 fw-semibold text-dark mb-3 pb-3 border-bottom">
                    Detail Pesanan
                </div>
                <div class="d-flex justify-content-between align-items-center small py-1 mb-2">
                    <span class="text-muted">Barang</span>
                    <span class="fw-medium text-dark text-end"><?= htmlspecialchars($data['nama_barang']); ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center small py-1 mb-2">
                    <span class="text-muted">Durasi</span>
                    <span class="fw-medium text-dark text-end"><?= $durasi; ?> hari</span>
                </div>
                <div class="d-flex justify-content-between align-items-center small mt-3 pt-3 border-top">
                    <span class="text-muted">Status</span>
                    <span class="fw-medium px-3 py-1 rounded-pill bg-white border" style="color: <?= $status_color; ?>;"><?= $status_text; ?></span>
                </div>
            </div>

            <div class="d-flex flex-column gap-3">
                <a href="<?= $wa_link; ?>" target="_blank" class="btn btn-wa w-100 py-3 fs-5 fw-bold d-flex align-items-center justify-content-center gap-2 rounded-3 text-decoration-none">
                    <i class="fab fa-whatsapp fs-5"></i> Kabari Admin via WA
                </a>
                <a href="dashboard.php" class="btn btn-outline-custom w-100 py-3 fs-5 fw-bold rounded-3 text-decoration-none">
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </main>

    <!-- FOOTER KONSISTEN -->
    <footer class="pt-5 pb-3 px-md-5 mt-auto text-poppins" style="background: #0F172A;">
        <div class="container-fluid" style="max-width: 1200px;">
            <div class="row gx-4 gy-5 mb-4">
                <div class="col-lg-5 pe-lg-5">
                    <div class="d-flex align-items-center gap-2 mb-3 text-white fs-4 fw-bold" style="font-family: 'Outfit', sans-serif;">UKKI Inventory</div>
                    <p class="mb-3 text-muted" style="color: #94A3B8 !important; font-size: 0.95rem;">Sistem penyewaan inventaris organisasi mahasiswa yang aman, cepat, dan terpercaya.</p>
                    <ul class="d-flex flex-column gap-2 text-muted list-unstyled" style="color: #94A3B8 !important; font-size: 0.95rem;">
                        <li><i class="fas fa-map-marker-alt" style="width: 25px;"></i> Masjid Al Istiqomah UPN "Veteran" Jawa Timur</li>
                        <li><i class="fas fa-envelope" style="width: 25px;"></i> event.ukki@gmail.com</li>
                        <li><i class="fas fa-phone" style="width: 25px;"></i> +6289677778190</li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h4 class="text-white fs-5 fw-bold mb-3" style="font-family: 'Outfit', sans-serif;">Quick Links</h4>
                    <ul class="d-flex flex-column gap-2 list-unstyled" style="font-size: 0.95rem;">
                        <li><a href="../index.php" class="text-decoration-none" style="color: #94A3B8;">Home</a></li>
                        <li><a href="../produk.php" class="text-decoration-none" style="color: #94A3B8;">Katalog</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h4 class="text-white fs-5 fw-bold mb-3" style="font-family: 'Outfit', sans-serif;">Social Media UKKI</h4>
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

    <!-- BOOTSTRAP 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>