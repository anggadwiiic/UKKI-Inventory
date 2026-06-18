<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$id_user = $_SESSION['user_id'];

$q_user = mysqli_query($conn, "SELECT nama_lengkap, username, email FROM users WHERE id_user = $id_user");
$user_data = mysqli_fetch_assoc($q_user);

$q_riwayat = mysqli_query($conn, "SELECT p.*, i.nama_barang, d.jumlah 
                                  FROM peminjaman p 
                                  JOIN detail_peminjaman d ON p.id_peminjaman = d.id_peminjaman 
                                  JOIN inventaris i ON d.id_inventaris = i.id_inventaris 
                                  WHERE p.id_user = $id_user 
                                  ORDER BY p.tanggal_pengajuan DESC LIMIT 15");

$q_fav = mysqli_query($conn, "SELECT i.nama_barang, COUNT(d.id_inventaris) as kali_disewa 
                              FROM detail_peminjaman d 
                              JOIN peminjaman p ON d.id_peminjaman = p.id_peminjaman 
                              JOIN inventaris i ON d.id_inventaris = i.id_inventaris 
                              WHERE p.id_user = $id_user AND p.status != 'ditolak'
                              GROUP BY d.id_inventaris 
                              ORDER BY kali_disewa DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Aclonica&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/logo-ukki.png">
    
    <style>
        :root {
            --teal-dark: #0F766E;   
            --teal-light: #CCFBF1;  
            --teal-soft: #EEF2F0;   
            --text-dark: #1E293B;   
            --text-gray: #64748B;   
            --bg: #F8FAFC;
            --white: #FFFFFF;
            --border: #E2E8F0;
        }
        body { font-family: 'Poppins', sans-serif; color: var(--text-gray); background-color: var(--bg); display: flex; flex-direction: column; min-height: 100vh;}
        h1, h2, h3, h4 { font-family: 'Outfit', sans-serif; color: var(--text-dark); font-weight: 700; }
        
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

        /* CARD & PROFILE */
        .dashboard-card { background: var(--white); border-radius: 12px; border: 1px solid var(--border); }
        .profile-avatar { width: 50px; height: 50px; background: var(--teal-light); color: var(--teal-dark); border-radius: 50%; font-size: 1.2rem; font-weight: 700; font-family: 'Outfit'; }
        
        .btn-logout { background: var(--teal-dark); color: var(--white); border-radius: 50px; transition: all 0.3s ease; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .btn-logout:hover { background: #0F172A; transform: translateY(-2px); color: var(--white);}

        /* SCROLLBAR & TABLE */
        .scrollable { max-height: 380px; overflow-y: auto; padding-right: 5px; }
        .scrollable::-webkit-scrollbar { width: 6px; }
        .scrollable::-webkit-scrollbar-track { background: #F1F5F9; border-radius: 10px; }
        .scrollable::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
        .scrollable::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
        
        .table th { font-size: 0.8rem; color: var(--text-gray); text-transform: uppercase; position: sticky; top: 0; background: var(--white); z-index: 1; border-bottom: 1px solid var(--border) !important;}
        .table td { padding: 1.2rem 0; font-size: 0.95rem; vertical-align: middle; border-bottom: 1px solid var(--border);}
        .table tr:last-child td { border-bottom: none; }
        .order-id { font-family: 'Outfit', sans-serif; font-weight: 600; color: var(--text-dark); }

        /* BADGES */
        .badge-custom { padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.8rem; font-weight: 500; }
        .badge-request { background: #FEF3C7; color: #92400E; }
        .badge-disetujui { background: #DBEAFE; color: #1E40AF; }
        .badge-dipinjam { background: #FFEDD5; color: #9A3412; }
        .badge-kembali { background: #DCFCE7; color: #166534; }
        .badge-ditolak { background: #FEE2E2; color: #991B1B; }
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
                <a href="../produk.php" class="nav-link-custom">Katalog</a>
                <a href="dashboard.php" class="nav-link-custom active">Dashboard</a>
            </nav> 
        </div>
    </header>

    <main class="container py-5">
        <div class="row gy-4 gx-lg-4">
            
            <div class="col-lg-8">
                <div class="dashboard-card h-100 p-4 p-md-5">
                    <h3 class="fs-5 fw-semibold mb-4 text-dark">Transaksi Terbaru</h3>
                    <div class="table-responsive scrollable">
                        <table class="table table-borderless align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Item</th>
                                    <th>Jumlah</th>
                                    <th>Tanggal Pinjam</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($q_riwayat) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($q_riwayat)): 
                                        $badge_class = 'badge-request';
                                        $status_label = 'Pending';
                                        switch($row['status']) {
                                        case 'request': $badge_class = 'badge-request'; $status_label = 'Pending'; break;
                                        case 'disetujui': $badge_class = 'badge-disetujui'; $status_label = 'Disetujui / Diambil'; break;
                                        case 'selesai': $badge_class = 'badge-kembali'; $status_label = 'Selesai'; break;
                                    }
                                    ?>
                                    <tr>
                                        <td class="order-id">ORD-<?= str_pad($row['id_peminjaman'], 3, '0', STR_PAD_LEFT); ?></td>
                                        <td class="fw-medium text-dark"><?= htmlspecialchars($row['nama_barang']); ?></td>
                                        <td class="text-muted small"><?= $row['jumlah']; ?> Unit</td>
                                        <td class="text-muted small"><?= date('d F Y', strtotime($row['tanggal_pinjam'])); ?></td>
                                        <td class="fw-medium text-dark">Rp <?= number_format($row['total_biaya'], 0, ',', '.'); ?></td>
                                        <td><span class="badge-custom <?= $badge_class; ?>"><?= $status_label; ?></span></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center p-5 text-muted">Belum ada riwayat transaksi.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="dashboard-card mb-4 p-4">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="profile-avatar d-flex align-items-center justify-content-center">
                            <?= strtoupper(substr($user_data['nama_lengkap'], 0, 1)); ?>
                        </div>
                        <div>
                            <div class="fs-6 fw-semibold text-dark" style="font-family: 'Outfit';"><?= htmlspecialchars($user_data['nama_lengkap']); ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($user_data['username']); ?></div>
                        </div>
                    </div>
                    <a href="../auth/logout.php" class="btn-logout w-100 py-2 text-center text-decoration-none d-flex align-items-center justify-content-center gap-2 fw-medium">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>

                <div class="dashboard-card p-4">
                    <h3 class="fs-5 fw-semibold mb-4 text-dark">Item Paling Sering Disewa</h3>
                    <div class="scrollable pe-2">
                        <?php if (mysqli_num_rows($q_fav) > 0): ?>
                            <?php while ($fav = mysqli_fetch_assoc($q_fav)): ?>
                            <div class="d-flex justify-content-between align-items-center py-3 border-bottom border-light">
                                <div class="fw-medium text-dark" style="font-size: 0.95rem;"><?= htmlspecialchars($fav['nama_barang']); ?></div>
                                <div class="small text-muted"><?= $fav['kali_disewa']; ?> kali</div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-muted small">Belum ada data sewa.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
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
                    <div class="social-icons d-flex gap-3 mt-1">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>