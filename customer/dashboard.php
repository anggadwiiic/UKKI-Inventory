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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Aclonica&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../assets/img/logo-ukki.png">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --teal-dark: #0F766E;   
            --teal-light: #CCFBF1;  
            --teal-soft: #EEF2F0;   
            --text-dark: #1E293B;   
            --text-gray: #64748B;   
            --white: #FFFFFF;
            --border: #E2E8F0;
        }
        body { font-family: 'Poppins', sans-serif; color: var(--text-gray); background-color: #F8FAFC; line-height: 1.6; }
        h1, h2, h3, h4 { font-family: 'Outfit', sans-serif; color: var(--text-dark); font-weight: 700; }
        a { text-decoration: none; transition: all 0.3s ease; }
        ul { list-style: none; }

        header { 
            padding: 1rem 5%; 
            background: var(--white); 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
            position: sticky; 
            top: 0; 
            z-index: 1000; 
            border-bottom: 1px solid var(--border);
        }
        .logo { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .logo-img { width: 45px; height: auto; filter: drop-shadow(0px 4px 4px rgba(0, 0, 0, 0.25)); }
        .logo-text { display: flex; flex-direction: column; justify-content: center; }
        .logo-title { font-family: 'Aclonica', sans-serif; font-size: 1.4rem; font-weight: 400; color: var(--text-dark); line-height: 1.1; }
        .logo-subtitle { font-family: 'Inter', sans-serif; font-size: 0.75rem; font-weight: 500; color: var(--text-gray); line-height: 1.2; }
        
        .nav-links { display: flex; gap: 0.5rem; align-items: center; }
        .nav-link { 
            color: var(--text-gray); 
            font-weight: 500; 
            position: relative; 
            padding: 0.5rem 1.2rem; 
            border-radius: 50px; 
            transition: all 0.3s ease;
        }
        .nav-link:not(.active)::after {
            content: ''; position: absolute; width: 0; height: 2px; bottom: 4px; left: 50%; transform: translateX(-50%);
            background-color: var(--teal-dark); transition: width 0.3s ease;
        }
        .nav-link:not(.active):hover::after { width: 50%; }
        .nav-link:not(.active):hover { color: var(--teal-dark); }

        .nav-link.active { 
            background: var(--teal-dark); 
            color: var(--white); 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); 
        }
        .nav-link.active:hover { background: #0F172A; transform: translateY(-2px); }

        .container { max-width: 1200px; margin: 3rem auto 6rem; padding: 0 5%; }
        
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 2.5rem; align-items: start; }
        
        .dashboard-card { 
            background: var(--white); 
            border-radius: 12px; 
            border: 1px solid var(--border); 
            padding: 2rem; 
        }
        
        .section-heading { font-size: 1.15rem; margin-bottom: 1.5rem; color: var(--text-dark); font-weight: 600;}

        .profile-header { display: flex; align-items: center; gap: 15px; margin-bottom: 1.5rem; }
        .profile-avatar { width: 50px; height: 50px; background: var(--teal-light); color: var(--teal-dark); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 700; font-family: 'Outfit'; }
        .profile-name { font-size: 1.1rem; font-weight: 600; color: var(--text-dark); font-family: 'Outfit', sans-serif; line-height: 1.2;}
        .profile-meta { font-size: 0.85rem; color: var(--text-gray); }

        .btn-logout {
            background: var(--teal-dark); 
            color: var(--white); 
            padding: 0.6rem 1.2rem; 
            border-radius: 50px; 
            font-weight: 500; 
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.95rem;
            width: 100%;
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
        }
        .btn-logout:hover { 
            background: #0F172A; 
            transform: translateY(-2px); 
        }

        .scrollable { max-height: 380px; overflow-y: auto; padding-right: 5px; }
        .scrollable::-webkit-scrollbar { width: 6px; }
        .scrollable::-webkit-scrollbar-track { background: #F1F5F9; border-radius: 10px; }
        .scrollable::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
        .scrollable::-webkit-scrollbar-thumb:hover { background: #94A3B8; }

        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 650px; }
        th { text-align: left; padding-bottom: 1rem; border-bottom: 1px solid var(--border); color: var(--text-gray); font-weight: 500; font-size: 0.8rem; text-transform: uppercase; position: sticky; top: 0; background: var(--white); z-index: 1;}
        td { padding: 1.2rem 0; border-bottom: 1px solid var(--border); font-size: 0.95rem; color: var(--text-dark); vertical-align: middle;}
        tr:last-child td { border-bottom: none; }

        .order-id { font-family: 'Outfit', sans-serif; font-weight: 600; color: var(--text-dark); }
        .item-name { font-weight: 500; }
        .text-muted { color: var(--text-gray); font-size: 0.85rem; }

        .badge { padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.8rem; font-weight: 500; display: inline-block; text-align: center; }
        .badge-request { background: #FEF3C7; color: #92400E; }
        .badge-disetujui { background: #DBEAFE; color: #1E40AF; }
        .badge-dipinjam { background: #FFEDD5; color: #9A3412; }
        .badge-kembali { background: #DCFCE7; color: #166534; }
        .badge-ditolak { background: #FEE2E2; color: #991B1B; }

        .fav-list { display: flex; flex-direction: column; }
        .fav-item { display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-bottom: 1px solid var(--border); }
        .fav-item:last-child { border-bottom: none; padding-bottom: 0;}
        .fav-name { font-weight: 500; color: var(--text-dark); font-size: 0.95rem; }
        .fav-count { font-size: 0.85rem; color: var(--text-gray); }

        footer { background: #0F172A; color: #94A3B8; padding: 4rem 5% 2rem; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 2fr; gap: 4rem; margin-bottom: 3rem; }
        .footer-logo { color: var(--white); font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: 10px; font-family: 'Outfit', sans-serif;}
        .footer-col h4 { color: var(--white); margin-bottom: 1.5rem; font-family: 'Outfit', sans-serif;}
        .footer-col ul li { margin-bottom: 0.8rem; }
        .footer-col a { color: #94A3B8; }
        .footer-col a:hover { color: var(--teal-light); }
        .social-icons { display: flex; gap: 1rem; font-size: 1.2rem; }
        .map-box { background: #1E293B; height: 150px; border-radius: 8px; overflow: hidden; position: relative;}
        .map-box iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
        .footer-bottom { text-align: center; border-top: 1px solid #1E293B; padding-top: 2rem; font-size: 0.9rem; }

        @media (max-width: 992px) { 
            .dashboard-grid { grid-template-columns: 1fr; } 
            .footer-grid { grid-template-columns: 1fr; gap: 2rem; } 
        }
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
            <a href="../produk.php" class="nav-link">Katalog</a>
            <a href="dashboard.php" class="nav-link active">Dashboard</a>
        </nav> 
    </header>

    <div class="container">
        <div class="dashboard-grid">
            
            <div class="dashboard-card">
                <h3 class="section-heading">Transaksi Terbaru</h3>
                <div class="table-responsive scrollable">
                    <table>
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
                                    <td class="item-name"><?= htmlspecialchars($row['nama_barang']); ?></td>
                                    <td class="text-muted"><?= $row['jumlah']; ?> Unit</td>
                                    <td class="text-muted"><?= date('d F Y', strtotime($row['tanggal_pinjam'])); ?></td>
                                    <td style="font-weight: 500;">Rp <?= number_format($row['total_biaya'], 0, ',', '.'); ?></td>
                                    <td><span class="badge <?= $badge_class; ?>"><?= $status_label; ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-gray);">Belum ada riwayat transaksi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <div class="dashboard-card" style="margin-bottom: 2rem;">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?= strtoupper(substr($user_data['nama_lengkap'], 0, 1)); ?>
                        </div>
                        <div>
                            <div class="profile-name"><?= htmlspecialchars($user_data['nama_lengkap']); ?></div>
                            <div class="profile-meta"><?= htmlspecialchars($user_data['username']); ?></div>
                        </div>
                    </div>
                    <a href="../auth/logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>

                <div class="dashboard-card">
                    <h3 class="section-heading">Item Paling Sering Disewa</h3>
                    <div class="fav-list scrollable">
                        <?php if (mysqli_num_rows($q_fav) > 0): ?>
                            <?php while ($fav = mysqli_fetch_assoc($q_fav)): ?>
                            <div class="fav-item">
                                <div class="fav-name"><?= htmlspecialchars($fav['nama_barang']); ?></div>
                                <div class="fav-count"><?= $fav['kali_disewa']; ?> kali disewa</div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="color: var(--text-gray); font-size: 0.9rem;">Belum ada data sewa.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <div class="footer-logo">UKKI Inventory</div>
                <p style="margin-bottom: 1rem;">Sistem penyewaan inventaris organisasi mahasiswa yang aman, cepat, dan terpercaya.</p>
                <p><i class="fas fa-map-marker-alt" style="width: 20px;"></i> Masjid Al Istiqomah UPN "Veteran" Jawa Timur</p>
                <p><i class="fas fa-envelope" style="width: 20px;"></i> event.ukki@gmail.com</p>
                <p><i class="fas fa-phone" style="width: 20px;"></i> +6289677778190</p>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="produk.php">Katalog</a></li>
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

</body>
</html>