<?php
session_start();
require_once 'config/koneksi.php';

$inventaris_data = query("SELECT inventaris.*, kategori.nama_kategori 
                          FROM inventaris 
                          JOIN kategori ON inventaris.id_kategori = kategori.id_kategori 
                          WHERE inventaris.status_barang = 'Tersedia'
                          ORDER BY inventaris.id_inventaris ASC 
                          LIMIT 3");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UKKI Inventory</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Aclonica&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="assets/img/logo-ukki.png">
    
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
        body { font-family: 'Poppins', sans-serif; color: var(--text-gray); background-color: var(--white); line-height: 1.6; }
        h1, h2, h3, h4 { font-family: 'Outfit', sans-serif; color: var(--text-dark); font-weight: 700; }
        a { text-decoration: none; transition: all 0.3s ease; }
        ul { list-style: none; }

        /* NAVBAR */
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
            content: ''; 
            position: absolute; 
            width: 0; 
            height: 2px; 
            bottom: 4px; 
            left: 50%; 
            transform: translateX(-50%);
            background-color: var(--teal-dark); 
            transition: width 0.3s ease;
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

        .btn-teal { background: var(--teal-dark); color: var(--white); padding: 0.6rem 1.5rem; border-radius: 50px; font-weight: 500; border: none; cursor: pointer; display: inline-block;}
        .btn-teal:hover { background: #0F172A; transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .btn-outline { border: 1.5px solid var(--text-dark); color: var(--text-dark); background: transparent; padding: 0.6rem 1.5rem; border-radius: 50px; font-weight: 500; display: inline-block; }
        .btn-outline:hover { background: var(--text-dark); color: var(--white); transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);}

        /* HERO SECTION */
        .hero { display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; padding: 5rem 5%; align-items: center; }
        .hero-text h1 { font-size: 3.5rem; line-height: 1.2; margin-bottom: 1.5rem; }
        .hero-text p { font-size: 1.1rem; margin-bottom: 2rem; max-width: 90%; }
        .hero-buttons { display: flex; gap: 1rem; margin-bottom: 3rem; }
        .hero-stats { display: flex; gap: 3rem; }
        .stat-item h3 { font-size: 1.8rem; margin-bottom: 0.2rem; }
        .stat-item p { font-size: 0.9rem; }
        .hero-image { background: var(--teal-light); border-radius: 24px; height: 400px; display: flex; align-items: center; justify-content: center; }
        .hero-image i { font-size: 5rem; color: var(--teal-dark); opacity: 0.5; }

        /* STEPS SECTION */
        .section-title { text-align: center; font-size: 2rem; margin-bottom: 1rem; }
        .section-subtitle { text-align: center; color: var(--text-gray); margin-bottom: 3rem; }
        .steps { padding: 4rem 5%; }
        .steps-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem; text-align: center; margin-bottom: 3rem;}
        
        .step-card { padding: 1rem; transition: all 0.3s ease; border-radius: 12px;}
        .step-card:hover { transform: translateY(-8px); background: var(--teal-soft); }
        .step-icon { width: 70px; height: 70px; margin: 0 auto 1.5rem; background: var(--white); border: 2px solid var(--border); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--text-dark); position: relative; }
        .step-number { position: absolute; top: -5px; right: -5px; background: #F59E0B; color: white; width: 24px; height: 24px; border-radius: 50%; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; font-weight: bold;}
        .step-card h4 { margin-bottom: 0.5rem; font-size: 1.1rem; }
        .step-card p { font-size: 0.9rem; }

        /* POPULAR ITEMS */
        .popular { background: #F8FAFC; padding: 5rem 5%; }
        .product-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-top: 3rem;}
        
        .product-card { background: var(--white); border-radius: 16px; padding: 1.5rem; border: 1px solid var(--border); transition: all 0.3s ease-in-out; }
        .product-card:hover { transform: translateY(-10px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); border-color: var(--teal-light);}
        
        .product-img { background: var(--teal-light); height: 200px; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: center; font-size: 4rem; color: var(--teal-dark); opacity: 0.7;}
        .product-info h3 { font-size: 1.2rem; margin-bottom: 1rem; }
        .specs-list { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1.5rem; font-size: 0.85rem; }
        .specs-list li { display: flex; align-items: center; gap: 0.5rem; }
        .specs-list i { color: var(--teal-dark); font-size: 0.5rem; }
        .product-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 1rem; }
        .price { font-size: 1.25rem; font-weight: 700; color: var(--text-dark); }
        .price span { font-size: 0.85rem; color: var(--text-gray); font-weight: 400; }
        .btn-sewa { background: var(--teal-dark); color: var(--white); padding: 0.5rem 1.5rem; border-radius: 50px; font-size: 0.9rem; transition: 0.3s;}
        .btn-sewa:hover { background: #0F172A; }

        /* WHY US */
        .why-us { padding: 5rem 5%; background: var(--teal-soft); text-align: center;}
        .why-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem; margin-top: 3rem; }
        
        .why-card { background: var(--white); padding: 2rem; border-radius: 16px; text-align: left; transition: all 0.3s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.05);}
        .why-card:hover { transform: translateY(-8px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        
        .why-card h2 { color: var(--teal-dark); font-size: 2rem; margin-bottom: 0.5rem; }
        .why-card h4 { margin-bottom: 0.5rem; }
        .why-card p { font-size: 0.9rem; }

        /* INFO SECTION */
        .info-section { padding: 5rem 5%; text-align: center; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; max-width: 800px; margin: 3rem auto 0; }
        
        .info-card { padding: 2rem; border: 1px solid var(--border); border-radius: 16px; transition: all 0.3s ease; background: var(--white);}
        .info-card:hover { transform: translateY(-8px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        
        .info-card i { font-size: 2rem; color: var(--teal-dark); margin-bottom: 1rem; }
        .info-card h3 { margin-bottom: 0.5rem; }
        .info-card p { font-size: 0.9rem; margin-bottom: 1.5rem; }

        /* FOOTER */
        footer { background: #0F172A; color: #94A3B8; padding: 4rem 5% 2rem; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 2fr; gap: 4rem; margin-bottom: 3rem; }
        .footer-logo { color: var(--white); font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: 10px;}
        .footer-col h4 { color: var(--white); margin-bottom: 1.5rem; }
        .footer-col ul li { margin-bottom: 0.8rem; }
        .footer-col a { color: #94A3B8; }
        .footer-col a:hover { color: var(--teal-light); }
        .social-icons { display: flex; gap: 1rem; font-size: 1.2rem; }
        
        .map-box { background: #1E293B; height: 150px; border-radius: 8px; overflow: hidden; position: relative;}
        .map-box iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
        
        .footer-bottom { text-align: center; border-top: 1px solid #1E293B; padding-top: 2rem; font-size: 0.9rem; }

        @media (max-width: 992px) {
            .hero, .steps-grid, .product-grid, .why-grid, .footer-grid { grid-template-columns: 1fr; }
            .hero-image { display: none; }
            .info-grid { grid-template-columns: 1fr; }
            .nav-links { gap: 0; }
        }
    </style>
</head>
<body>

<header>
        <a href="index.php" class="logo">
            <img src="assets/img/logo-ukki.png" alt="Logo UKKI" class="logo-img">
            <div class="logo-text">
                <span class="logo-title">UKKI Inventory</span>
                <span class="logo-subtitle">UPN "Veteran" Jawa Timur</span>
            </div>
        </a>

        <nav class="nav-links">
            <a href="index.php" class="nav-link active">Home</a>
            <a href="produk.php" class="nav-link">Katalog</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="customer/dashboard.php" class="nav-link">Dashboard</a>
            <?php else: ?>
                <a href="auth/login.php" class="nav-link">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <section class="hero">
        <div class="hero-text">
            <span style="background: #B81449; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; margin-bottom: 1rem; display: inline-block;">Unit Kegiatan Kerohanian Islam</span>
            <h1>Sewa Inventaris UKKI dengan Mudah</h1>
            <p>Sistem penyewaan inventaris organisasi mahasiswa yang aman, cepat, dan terpercaya.</p>
            <div class="hero-buttons">
                <a href="produk.php" class="btn-teal">Mulai Sewa</a>
                <a href="produk.php" class="btn-outline">Lihat Katalog</a>
            </div>
            <div class="hero-stats">
                <div class="stat-item">
                    <h3>20+</h3><p>Item Tersedia</p>
                </div>
                <div class="stat-item">
                    <h3>100+</h3><p>Penyewa Sukses</p>
                </div>
                <div class="stat-item">
                    <h3>100%</h3><p>Aman & Terawat</p>
                </div>
            </div>
        </div>
        </div>
        <div class="hero-image" style="background: transparent; padding: 0; overflow: hidden;">
            <img src="assets/img/background.png" alt="Kegiatan UKKI" style="width: 100%; height: 100%; object-fit: cover; border-radius: 24px; box-shadow: 0 10px 25px rgba(15, 118, 110, 0.15);">
        </div>
    </section>
    </section>

    <section class="steps">
        <h2 class="section-title">Sewa dalam 4 Langkah Mudah</h2>
        <p class="section-subtitle">Proses penyewaan yang praktis, aman, dan sepenuhnya otomatis.</p>
        
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-icon">
                    <i class="fas fa-search"></i>
                    <span class="step-number">1</span>
                </div>
                <h4>Pilih Barang</h4>
                <p>Temukan alat yang kamu butuhkan di katalog kami.</p>
            </div>
            <div class="step-card">
                <div class="step-icon">
                    <i class="far fa-calendar-alt"></i>
                    <span class="step-number">2</span>
                </div>
                <h4>Tentukan Jadwal</h4>
                <p>Pilih tanggal ambil dan kembali sesuai keperluan.</p>
            </div>
            <div class="step-card">
                <div class="step-icon">
                    <i class="far fa-id-card"></i>
                    <span class="step-number">3</span>
                </div>
                <h4>Validasi KTM</h4>
                <p>Upload KTM aktif untuk verifikasi identitas.</p>
            </div>
            <div class="step-card">
                <div class="step-icon">
                    <i class="fas fa-whatsapp"></i>
                    <span class="step-number">4</span>
                </div>
                <h4>Booking dan Hubungi Admin</h4>
                <p>Hubungi Admin melalui WhatsApp untuk Konfirmasi.</p>
            </div>
        </div>
    </section>

    <section class="popular">
        <h2 class="section-title">Peralatan yang Dapat Disewa</h2>
        <p class="section-subtitle">Peralatan berkualitas tinggi siap mendukung kegiatanmu.</p>
        
        <div class="product-grid">
            <?php foreach ($inventaris_data as $inv) : ?>
            <div class="product-card">
                <div class="product-img" style="overflow: hidden;">
                    <?php if(!empty($inv['gambar']) && file_exists("assets/img/inventaris/" . $inv['gambar'])): ?>
                        <img src="assets/img/inventaris/<?= $inv['gambar'] ?>" alt="<?= htmlspecialchars($inv['nama_barang']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <i class="fas fa-cube"></i>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <span style="font-size: 0.8rem; color: var(--text-gray); text-transform: uppercase;"><?= htmlspecialchars($inv['nama_kategori']); ?></span>
                    <h3><?= htmlspecialchars($inv['nama_barang']); ?></h3>
                    
                    <ul class="specs-list">
                        <li><i class="fas fa-circle"></i> Stok: <?= $inv['stok']; ?> Unit</li>
                        <li><i class="fas fa-circle"></i> Kondisi: <?= $inv['kondisi_barang']; ?></li>
                    </ul>

                    <div class="product-footer">
                        <div class="price">
                            Rp <?= number_format($inv['harga_sewa_per_hari'], 0, ',', '.'); ?> <span>/ Hari</span>
                        </div>
                        <a href="customer/detail_produk.php?id=<?= $inv['id_inventaris']; ?>" class="btn-sewa">Sewa</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align: center; margin-top: 3rem;">
            <a href="produk.php" class="btn-outline">Lihat Semua Item <i class="fas fa-arrow-right"></i></a>
        </div>
    </section>

    <section class="why-us">
        <h2 class="section-title">Mengapa Memilih Sistem Inventory UKKI?</h2>
        <div class="why-grid">
            <div class="why-card">
                <h2>01</h2>
                <h4>Proses Cepat</h4>
                <p>Penyewaan otomatis tanpa perlu antre.</p>
            </div>
            <div class="why-card">
                <h2>02</h2>
                <h4>Transparan</h4>
                <p>Sistem perhitungan biaya dipastikan jelas.</p>
            </div>
            <div class="why-card">
                <h2>03</h2>
                <h4>Lengkap</h4>
                <p>Peralatan event dan rapat yang memadai.</p>
            </div>
            <div class="why-card">
                <h2>04</h2>
                <h4>Terawat & Bersih</h4>
                <p>Setiap barang dicek berkala secara ketat.</p>
            </div>
        </div>
    </section>

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