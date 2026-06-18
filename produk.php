<?php
session_start();
require_once './config/koneksi.php';

$where_clause = "inventaris.status_barang = 'Tersedia'";
$keyword = "";
$id_kategori_aktif = '';

if (isset($_GET['kategori']) && !empty($_GET['kategori'])) {
    $id_kategori = (int)$_GET['kategori'];
    $id_kategori_aktif = $id_kategori;
    $where_clause .= " AND inventaris.id_kategori = $id_kategori";
}

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $keyword = mysqli_real_escape_string($conn, $_GET['q']);
    $where_clause .= " AND inventaris.nama_barang LIKE '%$keyword%'";
}

$kategori_data = query("SELECT * FROM kategori");

$batas_data = 6;

$halaman_aktif = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;

$offset = ($halaman_aktif - 1) * $batas_data;

$query_count = "SELECT COUNT(*) AS total FROM inventaris WHERE $where_clause";
$total_data = query($query_count)[0]['total'];
$total_halaman = ceil($total_data / $batas_data); 

$query_inventaris = "SELECT inventaris.*, kategori.nama_kategori 
                     FROM inventaris 
                     JOIN kategori ON inventaris.id_kategori = kategori.id_kategori 
                     WHERE $where_clause 
                     ORDER BY inventaris.id_inventaris DESC
                     LIMIT $offset, $batas_data";
$inventaris_data = query($query_inventaris);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Aclonica&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="icon" type="image/png" href="assets/img/logo-ukki.png">
    
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
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg); color: var(--text-gray); line-height: 1.6; display: flex; flex-direction: column; min-height: 100vh;}
        h1, h2, h3, h4 { font-family: 'Outfit', sans-serif; color: var(--text-dark); font-weight: 700; }
        
        /* NAVBAR KUSTOM */
        .logo-img { width: 45px; height: auto; filter: drop-shadow(0px 4px 4px rgba(0, 0, 0, 0.25)); }
        .logo-title { font-family: 'Aclonica', sans-serif; font-size: 1.4rem; font-weight: 400; color: var(--text-dark); line-height: 1.1; }
        .logo-subtitle { font-family: 'Inter', sans-serif; font-size: 0.75rem; font-weight: 500; color: var(--text-gray); line-height: 1.2; }
        
        .nav-link-custom { color: var(--text-gray); font-weight: 500; position: relative; padding: 0.5rem 1.2rem; border-radius: 50px; transition: all 0.3s ease; text-decoration: none;}
        .nav-link-custom:not(.active)::after { content: ''; position: absolute; width: 0; height: 2px; bottom: 4px; left: 50%; transform: translateX(-50%); background-color: var(--teal-dark); transition: width 0.3s ease; }
        .nav-link-custom:not(.active):hover::after { width: 50%; }
        .nav-link-custom:not(.active):hover { color: var(--teal-dark); }
        .nav-link-custom.active { background: var(--teal-dark); color: var(--white); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .nav-link-custom.active:hover { background: #0F172A; transform: translateY(-2px); color: var(--white);}

        /* COMPONENT CUSTOM STYLING */
        .search-container { max-width: 1000px; margin-top: -35px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); position: relative; z-index: 10; border: 1px solid var(--border); }
        .search-input:focus { outline: none; }
        
        .btn-teal { background: var(--teal-dark); color: var(--white); padding: 0.8rem 2.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; border: none; text-decoration: none;}
        .btn-teal:hover:not(:disabled) { background: #134E4A; color: var(--white); }
        .btn-teal:disabled { background: var(--border); color: var(--text-gray); cursor: not-allowed; }
        
        .btn-sewa { padding: 0.5rem 1.5rem; border-radius: 50px; font-size: 0.9rem; font-weight: 500; }

        .pill { padding: 0.5rem 1.5rem; border-radius: 50px; font-size: 0.9rem; font-weight: 500; color: var(--text-dark); background: var(--white); border: 1px solid var(--border); transition: 0.3s; text-decoration: none; white-space: nowrap;}
        .pill.active { background: var(--teal-dark); color: var(--white); border-color: var(--teal-dark); }
        .pill:not(.active):hover { border-color: var(--teal-dark); color: var(--teal-dark); }

        /* PRODUCT CARDS */
        .product-card { background: var(--white); border-radius: 16px; padding: 1.5rem; border: 1px solid var(--border); transition: all 0.3s ease; height: 100%; display: flex; flex-direction: column;}
        .product-card:hover { transform: translateY(-6px); box-shadow: 0 12px 20px -8px rgba(0,0,0,0.15); border-color: var(--teal-light);}
        .card-img-box { background: var(--teal-light); height: 200px; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: center; font-size: 4rem; color: var(--teal-dark); opacity: 0.8;}
        
        .badge-status { top: 1rem; left: 1rem; font-size: 0.8rem; font-weight: 600; color: var(--teal-dark); }
        .badge-stock { top: 1rem; right: 1rem; background: var(--white); padding: 0.3rem 0.8rem; border-radius: 50px; font-size: 0.8rem; font-weight: 600; color: var(--text-dark); box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid var(--border);}
        
        .card-specs i { font-size: 0.4rem; color: var(--text-dark);}
        
        /* PAGINATION */
        .page-link-custom { padding: 0.5rem 1rem; border: 1px solid var(--border); border-radius: 8px; color: var(--text-dark); background: var(--white); font-weight: 500; transition: 0.2s; text-decoration: none;}
        .page-link-custom:hover:not(.disabled) { background: var(--border); color: var(--text-dark); }
        .page-link-custom.active { background: var(--teal-dark); color: var(--white); border-color: var(--teal-dark); }
        .page-link-custom.disabled { color: var(--text-gray); background: #F1F5F9; cursor: not-allowed; border-color: var(--border); }

        /* FOOTER */
        footer { background: #0F172A; color: #94A3B8; margin-top: auto;}
        .footer-logo { color: var(--white); font-size: 1.5rem; font-weight: 700; }
        .footer-col a { color: #94A3B8; transition: 0.3s; text-decoration: none;}
        .footer-col a:hover { color: var(--teal-light); }
        .map-box { background: #1E293B; height: 150px; border-radius: 8px; overflow: hidden; position: relative;}
        .map-box iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
    </style>
</head>
<body>

    <header class="sticky-top bg-white border-bottom py-3 px-md-5">
        <div class="container-fluid d-flex align-items-center justify-content-between">
            <a href="index.php" class="text-decoration-none d-flex align-items-center gap-2">
                <img src="assets/img/logo-ukki.png" alt="Logo UKKI" class="logo-img"> 
                <div class="logo-text d-flex flex-column justify-content-center">
                    <span class="logo-title">UKKI Inventory</span>
                    <span class="logo-subtitle">UPN "Veteran" Jawa Timur</span>
                </div>
            </a>
            <nav class="d-none d-md-flex align-items-center gap-2">
                <a href="index.php" class="nav-link-custom">Home</a>
                <a href="produk.php" class="nav-link-custom active">Katalog</a> 
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="customer/dashboard.php" class="nav-link-custom">Dashboard</a>
                <?php else: ?>
                    <a href="auth/login.php" class="nav-link-custom">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="text-center text-white pt-5" style="background: #0b3d39; padding-bottom: 6rem;">
        <h1 class="text-white fs-1 mb-2">Katalog Inventaris</h1>
        <p style="color: #94A3B8;">Temukan berbagai inventaris untuk kebutuhan acara dan kegiatan</p>
    </div>

    <form action="" method="GET" class="container bg-white rounded-3 p-2 d-flex flex-column flex-md-row gap-2 search-container">
        <?php if (isset($_GET['kategori'])): ?>
            <input type="hidden" name="kategori" value="<?= $_GET['kategori']; ?>">
        <?php endif; ?>
        
        <div class="d-flex align-items-center flex-grow-1 px-3 py-2 gap-2">
            <i class="fas fa-search text-muted"></i>
            <input type="text" name="q" class="border-0 w-100 search-input" style="font-family: 'Poppins'; font-size: 1rem; color: var(--text-dark);" placeholder="Cari proyektor, kamera, sound system..." value="<?= htmlspecialchars($keyword); ?>">
        </div>
        
        <button type="submit" class="btn-teal w-8 w-md-auto d-flex align-items-center justify-content-center gap-2">
            <i class="fas fa-search"></i> Cari
        </button>
    </form>

    <main class="container py-5">
        <div class="d-flex flex-wrap justify-content-center gap-3 mb-5">
            <a href="produk.php" class="pill <?= ($id_kategori_aktif == '') ? 'active' : ''; ?>">Semua</a>
            <?php foreach ($kategori_data as $kat): ?>
                <a href="produk.php?kategori=<?= $kat['id_kategori']; ?>" 
                   class="pill <?= ($id_kategori_aktif == $kat['id_kategori']) ? 'active' : ''; ?>">
                   <?= htmlspecialchars($kat['nama_kategori']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="text-muted small mb-4">
            Menampilkan <?= count($inventaris_data); ?> item <?= !empty($keyword) ? "untuk pencarian '$keyword'" : ""; ?>
        </div>
            
        <div class="row g-4 mb-5">
            <?php if(empty($inventaris_data)): ?>
                <div class="col-12 text-center py-5 text-muted">
                    <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>Tidak ada alat yang ditemukan.</p>
                </div>
            <?php else: ?>
                <?php foreach ($inventaris_data as $inv): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="product-card">
                            <div class="card-img-box position-relative overflow-hidden">
                                <span class="badge-status position-absolute">Tersedia</span>
                                <span class="badge-stock position-absolute">Stok: <?= $inv['stok']; ?></span>
                                <?php if(!empty($inv['gambar']) && file_exists("assets/img/inventaris/" . $inv['gambar'])): ?>
                                    <img src="assets/img/inventaris/<?= $inv['gambar'] ?>" alt="<?= htmlspecialchars($inv['nama_barang']) ?>" class="w-100 h-100 object-fit-cover">
                                <?php else: ?>
                                    <i class="fas fa-cube"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div class="small text-muted mb-1"><?= htmlspecialchars($inv['nama_kategori']); ?></div>
                            <h3 class="fs-5 text-dark mb-3"><?= htmlspecialchars($inv['nama_barang']); ?></h3>
                            
                            <ul class="d-flex flex-column gap-2 small mb-4 list-unstyled card-specs">
                                <li class="d-flex align-items-center gap-2"><i class="fas fa-circle"></i> Kondisi: <?= htmlspecialchars($inv['kondisi_barang']); ?></li>
                            </ul>

                            <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                <div class="fw-bold fs-5 text-dark">Rp <?= number_format($inv['harga_sewa_per_hari'], 0, ',', '.'); ?> <span class="fs-6 fw-normal text-muted">/ Hari</span></div>
                                <?php if($inv['stok'] > 0 && $inv['kondisi_barang'] != 'Rusak Berat'): ?>
                                    <a href="customer/detail_produk.php?id=<?= $inv['id_inventaris']; ?>" class="btn-teal btn-sewa text-center">Sewa</a>
                                <?php else: ?>
                                    <button class="btn-teal btn-sewa" disabled>Habis</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($total_halaman > 1): ?>
        <div class="d-flex justify-content-center gap-2 mt-5">
            <?php 
            $url_params = "";
            if (isset($_GET['kategori'])) $url_params .= "&kategori=" . urlencode($_GET['kategori']);
            if (isset($_GET['q'])) $url_params .= "&q=" . urlencode($_GET['q']);
            ?>

            <?php if ($halaman_aktif > 1): ?>
                <a href="?halaman=<?= $halaman_aktif - 1 ?><?= $url_params ?>" class="page-link-custom">« Prev</a>
            <?php else: ?>
                <span class="page-link-custom disabled">« Prev</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                <a href="?halaman=<?= $i ?><?= $url_params ?>" class="page-link-custom <?= ($i == $halaman_aktif) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($halaman_aktif < $total_halaman): ?>
                <a href="?halaman=<?= $halaman_aktif + 1 ?><?= $url_params ?>" class="page-link-custom">Next »</a>
            <?php else: ?>
                <span class="page-link-custom disabled">Next »</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>

    <footer class="pt-5 pb-3 px-md-5">
        <div class="container-fluid" style="max-width: 1200px;">
            <div class="row gx-4 gy-5 mb-4">
                <div class="col-lg-5 pe-lg-5">
                    <div class="footer-logo d-flex align-items-center gap-2 mb-3">UKKI Inventory</div>
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
                        <li><a href="index.php" class="text-decoration-none" style="color: #94A3B8;">Home</a></li>
                        <li><a href="produk.php" class="text-decoration-none" style="color: #94A3B8;">Katalog</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h4 class="text-white fs-5 fw-bold mb-3">Social Media UKKI</h4>
                    <div class="social-icons d-flex gap-3 mt-1">
                        <a href="https://wa.me/6289677778190" target="_blank" class="fs-5" style="color: #94A3B8;"><i class="fab fa-whatsapp"></i></a>
                        <a href="https://www.instagram.com/ukki_upn/" target="_blank" class="fs-5" style="color: #94A3B8;"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.linkedin.com/company/ukkiupnvjt/" target="_blank" class="fs-5" style="color: #94A3B8;"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="map-box w-100" style="height: 150px; border-radius: 8px; overflow: hidden; position: relative;">
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