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
    <link rel="icon" type="image/png" href="assets/img/logo-ukki.png">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --teal-dark: #0F766E;   
            --teal-light: #CCFBF1;  
            --text-dark: #1E293B;   
            --text-gray: #64748B;   
            --bg: #F8FAFC;
            --white: #FFFFFF;
            --border: #E2E8F0;
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg); color: var(--text-gray); line-height: 1.6; }
        h1, h2, h3, h4 { font-family: 'Outfit', sans-serif; color: var(--text-dark); font-weight: 700; }
        a { text-decoration: none; transition: all 0.3s ease; }

        /* NAVBAR MODERN UX */
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
        .logo { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            text-decoration: none;
        }

        .logo-img {
            width: 45px;
            height: auto;
            filter: drop-shadow(0px 4px 4px rgba(0, 0, 0, 0.25)); 
        }

        .logo-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo-title {
            font-family: 'Aclonica', sans-serif;
            font-size: 1.4rem;
            font-weight: 400;
            color: var(--text-dark);
            line-height: 1.1;
        }

        .logo-subtitle {
            font-family: 'Inter', sans-serif;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-gray);
            line-height: 1.2;
        }
        
        .nav-links { 
            display: flex; 
            gap: 0.5rem; 
            align-items: center; 
        }
        
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
        .nav-link.active:hover { 
            background: #0F172A; 
            transform: translateY(-2px); 
        }

        /* HERO CATALOG */
        .catalog-hero { background: #0b3d39; padding: 4rem 5% 6rem; text-align: center; color: var(--white); }
        .catalog-hero h1 { color: var(--white); font-size: 2.5rem; margin-bottom: 0.5rem; }
        .catalog-hero p { color: #94A3B8; font-size: 0.95rem; }

        /* SEARCH BAR */
        .search-container { 
            max-width: 1000px; margin: -35px auto 0; background: var(--white); 
            border-radius: 12px; padding: 0.8rem; display: flex; gap: 1rem; 
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); position: relative; z-index: 10;
            border: 1px solid var(--border);
        }
        .search-box { flex-grow: 1; display: flex; align-items: center; gap: 10px; padding: 0 1rem; }
        .search-box i { color: var(--text-gray); font-size: 1.2rem;}
        .search-box input { border: none; outline: none; width: 100%; font-family: 'Poppins'; font-size: 1rem; color: var(--text-dark); }
        .btn-search { background: var(--teal-dark); color: var(--white); border: none; padding: 0.8rem 2.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px;}
        .btn-search:hover { background: #134E4A; }

        /* CATEGORY PILLS */
        .category-pills { max-width: 1200px; margin: 3rem auto 2rem; padding: 0 5%; display: flex; gap: 1rem; flex-wrap: wrap; }
        .pill { padding: 0.5rem 1.5rem; border-radius: 50px; font-size: 0.9rem; font-weight: 500; color: var(--text-dark); background: var(--white); border: 1px solid var(--border); transition: 0.3s; }
        .pill.active { background: var(--teal-dark); color: var(--white); border-color: var(--teal-dark); }
        .pill:not(.active):hover { border-color: var(--teal-dark); color: var(--teal-dark); }

        /* CATALOG CONTENT */
        .catalog-content { max-width: 1200px; margin: 0 auto 5rem; padding: 0 5%; }
        .results-text { margin-bottom: 2rem; font-size: 0.9rem; color: var(--text-gray); }
        
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 2rem; }
        .product-card { background: var(--white); border-radius: 16px; padding: 1.5rem; border: 1px solid var(--border); transition: all 0.3s ease; display: flex; flex-direction: column;}
        .product-card:hover { transform: translateY(-6px); box-shadow: 0 12px 20px -8px rgba(0,0,0,0.15); border-color: var(--teal-light);}
        
        .card-img-box { background: var(--teal-light); height: 200px; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: center; font-size: 4rem; color: var(--teal-dark); opacity: 0.8; position: relative;}
        
        .badge-status { position: absolute; top: 1rem; left: 1rem; font-size: 0.8rem; font-weight: 600; color: var(--teal-dark); }
        .badge-stock { position: absolute; top: 1rem; right: 1rem; background: var(--white); padding: 0.3rem 0.8rem; border-radius: 50px; font-size: 0.8rem; font-weight: 600; color: var(--text-dark); box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid var(--border);}
        
        .card-category { font-size: 0.8rem; color: var(--text-gray); margin-bottom: 0.2rem; }
        .card-title { font-size: 1.25rem; margin-bottom: 1rem; color: var(--text-dark); }
        
        .card-specs { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1.5rem; font-size: 0.85rem; list-style: none; }
        .card-specs li { display: flex; align-items: center; gap: 6px; }
        .card-specs i { color: var(--text-dark); font-size: 0.3rem; }

        .card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 1rem; border-top: 1px solid var(--border); margin-top: auto; }
        .price { font-size: 1.2rem; font-weight: 700; color: var(--text-dark); }
        .price span { font-size: 0.85rem; font-weight: 400; color: var(--text-gray); }
        
        .btn-sewa { background: var(--teal-dark); color: var(--white); padding: 0.5rem 1.5rem; border-radius: 50px; font-size: 0.9rem; font-weight: 500; transition: all 0.3s ease; border: none; cursor: pointer; text-align: center;}
        .btn-sewa:hover:not(:disabled) { background: #134E4A; }
        .btn-sewa:disabled { background: var(--border); color: var(--text-gray); cursor: not-allowed; }

        /* PAGINATION */
        .pagination { display: flex; justify-content: center; gap: 0.5rem; margin-top: 3rem; }
        .page-link { padding: 0.5rem 1rem; border: 1px solid var(--border); border-radius: 8px; color: var(--text-dark); background: var(--white); font-weight: 500; transition: 0.2s; }
        .page-link:hover { background: var(--border); }
        .page-link.active { background: var(--teal-dark); color: var(--white); border-color: var(--teal-dark); }
        .page-link.disabled { color: var(--text-gray); background: #F1F5F9; cursor: not-allowed; border-color: var(--border); }

        /* FOOTER */
        footer { background: #0F172A; color: #94A3B8; padding: 4rem 5% 2rem; margin-top: auto;}
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
        
        @media (max-width: 768px) {
            .search-container { flex-direction: column; padding: 1rem; margin-top: -20px;}
            .search-box { padding: 0.5rem 0; width: 100%;}
            .btn-search { width: 100%; justify-content: center; }
            .footer-grid { grid-template-columns: 1fr; gap: 2rem;}
        }
    </style>
</head>
<body>

    <header>
        <a href="index.php" class="logo">
            <img src="assets/img/logo-ukki.png" alt="Logo UKKI" class="logo-img"> <div class="logo-text">
                <span class="logo-title">UKKI Inventory</span>
                <span class="logo-subtitle">UPN "Veteran" Jawa Timur</span>
            </div>
        </a>
        <nav class="nav-links">
            <a href="index.php" class="nav-link">Home</a>
            <a href="produk.php" class="nav-link active">Katalog</a> <?php if (isset($_SESSION['user_id'])): ?>
                <a href="customer/dashboard.php" class="nav-link">Dashboard</a>
            <?php else: ?>
                <a href="auth/login.php" class="nav-link">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="catalog-hero">
        <h1>Katalog Inventaris</h1>
        <p>Temukan berbagai inventaris untuk kebutuhan acara dan kegiatan</p>
    </div>

    <form action="" method="GET" class="search-container">
        <?php if (isset($_GET['kategori'])): ?>
            <input type="hidden" name="kategori" value="<?= $_GET['kategori']; ?>">
        <?php endif; ?>
        
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="Cari proyektor, kamera, sound system..." value="<?= htmlspecialchars($keyword); ?>">
        </div>
        
        <button type="submit" class="btn-search"><i class="fas fa-search"></i> Cari</button>
    </form>

    <div class="category-pills">
        <a href="produk.php" class="pill <?= ($id_kategori_aktif == '') ? 'active' : ''; ?>">Semua</a>
        <?php foreach ($kategori_data as $kat): ?>
            <a href="produk.php?kategori=<?= $kat['id_kategori']; ?>" 
               class="pill <?= ($id_kategori_aktif == $kat['id_kategori']) ? 'active' : ''; ?>">
               <?= htmlspecialchars($kat['nama_kategori']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="catalog-content">
        <div class="results-text">
            Menampilkan <?= count($inventaris_data); ?> item <?= !empty($keyword) ? "untuk pencarian '$keyword'" : ""; ?>
        </div>
            
        <div class="product-grid">
            <?php if(empty($inventaris_data)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 4rem; color: var(--text-gray);">
                    <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>Tidak ada alat yang ditemukan.</p>
                </div>
            <?php else: ?>
                <?php foreach ($inventaris_data as $inv): ?>
                    <div class="product-card">
                        <div class="card-img-box" style="overflow: hidden;">
                        <span class="badge-status" style="z-index: 1;">Tersedia</span>
                        <span class="badge-stock" style="z-index: 1;">Stok: <?= $inv['stok']; ?></span>
                        <?php if(!empty($inv['gambar']) && file_exists("assets/img/inventaris/" . $inv['gambar'])): ?>
                            <img src="assets/img/inventaris/<?= $inv['gambar'] ?>" alt="<?= htmlspecialchars($inv['nama_barang']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-cube"></i>
                        <?php endif; ?>
                    </div>
                        
                        <div class="card-category"><?= htmlspecialchars($inv['nama_kategori']); ?></div>
                        <h3 class="card-title"><?= htmlspecialchars($inv['nama_barang']); ?></h3>
                        
                        <ul class="card-specs">
                            <li><i class="fas fa-circle"></i> Kondisi: <?= htmlspecialchars($inv['kondisi_barang']); ?></li>
                        </ul>

                        <div class="card-footer">
                            <div class="price">Rp <?= number_format($inv['harga_sewa_per_hari'], 0, ',', '.'); ?> <span>/ Hari</span></div>
                            <?php if($inv['stok'] > 0 && $inv['kondisi_barang'] != 'Rusak Berat'): ?>
                                <a href="customer/detail_produk.php?id=<?= $inv['id_inventaris']; ?>" class="btn-sewa">Sewa</a>
                            <?php else: ?>
                                <button class="btn-sewa" disabled>Habis</button>
                            <?php endif; ?>
                        </div>

                        <?php if ($total_halaman > 1): ?>
                    <?php endif; ?>
                    </div>
                    
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if ($total_halaman > 1): ?>
        <div class="pagination">
            <?php 
            // Menyimpan parameter search & kategori agar tidak hilang saat pindah halaman
            $url_params = "";
            if (isset($_GET['kategori'])) $url_params .= "&kategori=" . urlencode($_GET['kategori']);
            if (isset($_GET['q'])) $url_params .= "&q=" . urlencode($_GET['q']);
            ?>

            <?php if ($halaman_aktif > 1): ?>
                <a href="?halaman=<?= $halaman_aktif - 1 ?><?= $url_params ?>" class="page-link">&laquo; Prev</a>
            <?php else: ?>
                <span class="page-link disabled">&laquo; Prev</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                <a href="?halaman=<?= $i ?><?= $url_params ?>" class="page-link <?= ($i == $halaman_aktif) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($halaman_aktif < $total_halaman): ?>
                <a href="?halaman=<?= $halaman_aktif + 1 ?><?= $url_params ?>" class="page-link">Next &raquo;</a>
            <?php else: ?>
                <span class="page-link disabled">Next &raquo;</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
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