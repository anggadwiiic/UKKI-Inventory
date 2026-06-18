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
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/img/logo-ukki.png">
    
    <style>
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
        ul { list-style: none; padding: 0; margin: 0; }

        /* NAVBAR */
        header { 
            background: var(--white); 
            border-bottom: 1px solid var(--border);
        }
        .logo-img { width: 45px; height: auto; filter: drop-shadow(0px 4px 4px rgba(0, 0, 0, 0.25)); }
        .logo-title { font-family: 'Aclonica', sans-serif; font-size: 1.4rem; font-weight: 400; color: var(--text-dark); line-height: 1.1; }
        .logo-subtitle { font-family: 'Inter', sans-serif; font-size: 0.75rem; font-weight: 500; color: var(--text-gray); line-height: 1.2; }

        .nav-link-custom { 
            color: var(--text-gray); 
            font-weight: 500; 
            position: relative; 
            padding: 0.5rem 1.2rem; 
            border-radius: 50px; 
            transition: all 0.3s ease;
        }
        .nav-link-custom:not(.active)::after {
            content: ''; position: absolute; width: 0; height: 2px; bottom: 4px; left: 50%; transform: translateX(-50%);
            background-color: var(--teal-dark); transition: width 0.3s ease;
        }
        .nav-link-custom:not(.active):hover::after { width: 50%; }
        .nav-link-custom:not(.active):hover { color: var(--teal-dark); }
        .nav-link-custom.active { background: var(--teal-dark); color: var(--white); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .nav-link-custom.active:hover { background: #0F172A; transform: translateY(-2px); color: var(--white);}

        /* BUTTONS */
        .btn-teal { background: var(--teal-dark); color: var(--white); padding: 0.6rem 1.5rem; border-radius: 50px; font-weight: 500; border: none; transition: 0.3s; display: inline-block;}
        .btn-teal:hover { background: #0F172A; transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); color: var(--white);}
        .btn-outline-custom { border: 1.5px solid var(--text-dark); color: var(--text-dark); background: transparent; padding: 0.6rem 1.5rem; border-radius: 50px; font-weight: 500; transition: 0.3s; display: inline-block; }
        .btn-outline-custom:hover { background: var(--text-dark); color: var(--white); transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);}

        /* HERO SECTION */
        .hero-text h1 { font-size: 3.5rem; line-height: 1.2; }
        .hero-stats h3 { font-size: 1.8rem; margin-bottom: 0.2rem; }
        .hero-stats p { font-size: 0.9rem; margin: 0;}
        .hero-image { background: var(--teal-light); border-radius: 24px; height: 400px; display: flex; align-items: center; justify-content: center; }
        .hero-image i { font-size: 5rem; color: var(--teal-dark); opacity: 0.5; }

        /* STEPS SECTION */
        .step-card { padding: 1rem; transition: all 0.3s ease; border-radius: 12px;}
        .step-card:hover { transform: translateY(-8px); background: var(--teal-soft); }
        .step-icon { width: 70px; height: 70px; margin: 0 auto 1.5rem; background: var(--white); border: 2px solid var(--border); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--text-dark); position: relative; }
        .step-number { position: absolute; top: -5px; right: -5px; background: #F59E0B; color: white; width: 24px; height: 24px; border-radius: 50%; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; font-weight: bold;}

        /* POPULAR ITEMS */
        .popular { background: #F8FAFC; }
        .product-card { background: var(--white); border-radius: 16px; padding: 1.5rem; border: 1px solid var(--border); transition: all 0.3s ease-in-out; height: 100%; display: flex; flex-direction: column;}
        .product-card:hover { transform: translateY(-10px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); border-color: var(--teal-light);}
        .product-img { background: var(--teal-light); height: 200px; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: center; font-size: 4rem; color: var(--teal-dark); opacity: 0.7;}
        .specs-list { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1.5rem; font-size: 0.85rem; }
        .specs-list li { display: flex; align-items: center; gap: 0.5rem; }
        .specs-list i { color: var(--teal-dark); font-size: 0.5rem; }
        .product-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 1rem; margin-top: auto;}
        .price { font-size: 1.25rem; font-weight: 700; color: var(--text-dark); margin: 0;}
        .price span { font-size: 0.85rem; color: var(--text-gray); font-weight: 400; }
        .btn-sewa { background: var(--teal-dark); color: var(--white); padding: 0.5rem 1.5rem; border-radius: 50px; font-size: 0.9rem; transition: 0.3s;}
        .btn-sewa:hover { background: #0F172A; color: var(--white);}

        /* WHY US */
        .why-us { background: var(--teal-soft); }
        .why-card { background: var(--white); padding: 2rem; border-radius: 16px; transition: all 0.3s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.05); height: 100%;}
        .why-card:hover { transform: translateY(-8px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .why-card h2 { color: var(--teal-dark); font-size: 2rem; margin-bottom: 0.5rem; }

        /* FOOTER */
        footer { background: #0F172A; color: #94A3B8; }
        .footer-logo { color: var(--white); font-size: 1.5rem; font-weight: 700; }
        .footer-col h4 { color: var(--white); margin-bottom: 1.5rem; }
        .footer-col a { color: #94A3B8; transition: 0.3s;}
        .footer-col a:hover { color: var(--teal-light); }
        .map-box { background: #1E293B; height: 150px; border-radius: 8px; overflow: hidden; position: relative;}
        .map-box iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
        .footer-bottom { border-top: 1px solid #1E293B; }
    </style>
</head>
<body>

    <header class="sticky-top py-3 px-md-5">
        <div class="container-fluid d-flex align-items-center justify-content-between">
            <a href="index.php" class="logo text-decoration-none d-flex align-items-center gap-2">
                <img src="assets/img/logo-ukki.png" alt="Logo UKKI" class="logo-img">
                <div class="logo-text d-flex flex-column justify-content-center">
                    <span class="logo-title">UKKI Inventory</span>
                    <span class="logo-subtitle">UPN "Veteran" Jawa Timur</span>
                </div>
            </a>

            <nav class="d-none d-md-flex gap-2 align-items-center">
                <a href="index.php" class="nav-link-custom active">Home</a>
                <a href="produk.php" class="nav-link-custom">Katalog</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="customer/dashboard.php" class="nav-link-custom">Dashboard</a>
                <?php else: ?>
                    <a href="auth/login.php" class="nav-link-custom">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <section class="py-5 px-md-5">
        <div class="container-fluid">
            <div class="row align-items-center gy-5">
                <div class="col-lg-6 hero-text pe-lg-5">
                    <span class="badge text-white px-3 py-2 rounded-pill mb-3" style="background: #B81449; font-weight: 600;">Unit Kegiatan Kerohanian Islam</span>
                    <h1 class="mb-4">Sewa Inventaris UKKI dengan Mudah</h1>
                    <p class="fs-5 mb-4 pe-lg-5">Sistem penyewaan inventaris organisasi mahasiswa yang aman, cepat, dan terpercaya.</p>
                    <div class="d-flex flex-wrap gap-3 mb-5">
                        <a href="produk.php" class="btn-teal">Mulai Sewa</a>
                        <a href="produk.php" class="btn-outline-custom">Lihat Katalog</a>
                    </div>
                    <div class="d-flex flex-wrap gap-4 gap-md-5 hero-stats">
                        <div>
                            <h3>20+</h3><p>Item Tersedia</p>
                        </div>
                        <div>
                            <h3>100+</h3><p>Penyewa Sukses</p>
                        </div>
                        <div>
                            <h3>100%</h3><p>Aman & Terawat</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="hero-image p-0 overflow-hidden shadow-lg">
                        <img src="assets/img/background.png" alt="Kegiatan UKKI" class="w-100 h-100 object-fit-cover">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 px-md-5">
        <div class="container-fluid">
            <h2 class="text-center fw-bold mb-2">Sewa dalam 4 Langkah Mudah</h2>
            <p class="text-center text-muted mb-5">Proses penyewaan yang praktis, aman, dan sepenuhnya otomatis.</p>
            
            <div class="row g-4 text-center">
                <div class="col-sm-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-icon">
                            <i class="fas fa-search"></i>
                            <span class="step-number">1</span>
                        </div>
                        <h4 class="fs-5 fw-semibold mb-2">Pilih Barang</h4>
                        <p class="mb-0">Temukan alat yang kamu butuhkan di katalog kami.</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-icon">
                            <i class="far fa-calendar-alt"></i>
                            <span class="step-number">2</span>
                        </div>
                        <h4 class="fs-5 fw-semibold mb-2">Tentukan Jadwal</h4>
                        <p class="mb-0">Pilih tanggal ambil dan kembali sesuai keperluan.</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-icon">
                            <i class="fa fa-id-card"></i>
                            <span class="step-number">3</span>
                        </div>
                        <h4 class="fs-5 fw-semibold mb-2">Validasi KTM</h4>
                        <p class="mb-0">Upload KTM aktif untuk verifikasi identitas.</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-icon">
                            <i class="fa fa-whatsapp"></i>
                            <span class="step-number">4</span>
                        </div>
                        <h4 class="fs-5 fw-semibold mb-2">Hubungi Admin</h4>
                        <p class="mb-0">Hubungi Admin melalui WhatsApp untuk Konfirmasi.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="popular py-5 px-md-5">
        <div class="container-fluid">
            <h2 class="text-center fw-bold mb-2">Peralatan yang Dapat Disewa</h2>
            <p class="text-center text-muted mb-5">Peralatan berkualitas tinggi siap mendukung kegiatanmu.</p>
            
            <div class="row g-4">
                <?php foreach ($inventaris_data as $inv) : ?>
                <div class="col-md-6 col-lg-4">
                    <div class="product-card">
                        <div class="product-img overflow-hidden">
                            <?php if(!empty($inv['gambar']) && file_exists("assets/img/inventaris/" . $inv['gambar'])): ?>
                                <img src="assets/img/inventaris/<?= $inv['gambar'] ?>" alt="<?= htmlspecialchars($inv['nama_barang']) ?>" class="w-100 h-100 object-fit-cover">
                            <?php else: ?>
                                <i class="fas fa-cube"></i>
                            <?php endif; ?>
                        </div>
                        <div class="product-info d-flex flex-column flex-grow-1">
                            <span class="text-uppercase text-muted" style="font-size: 0.8rem;"><?= htmlspecialchars($inv['nama_kategori']); ?></span>
                            <h3 class="fs-5 mb-3"><?= htmlspecialchars($inv['nama_barang']); ?></h3>
                            
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
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-5">
                <a href="produk.php" class="btn-outline-custom">Lihat Semua Item <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </section>

    <section class="why-us py-5 px-md-5">
        <div class="container-fluid text-center">
            <h2 class="fw-bold mb-5">Mengapa Memilih Sistem Inventory UKKI?</h2>
            <div class="row g-4 text-start">
                <div class="col-md-6 col-lg-3">
                    <div class="why-card">
                        <h2>01</h2>
                        <h4 class="fs-5 fw-semibold mb-2">Proses Cepat</h4>
                        <p class="text-muted mb-0">Penyewaan otomatis tanpa perlu antre.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="why-card">
                        <h2>02</h2>
                        <h4 class="fs-5 fw-semibold mb-2">Transparan</h4>
                        <p class="text-muted mb-0">Sistem perhitungan biaya dipastikan jelas.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="why-card">
                        <h2>03</h2>
                        <h4 class="fs-5 fw-semibold mb-2">Lengkap</h4>
                        <p class="text-muted mb-0">Peralatan event dan rapat yang memadai.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="why-card">
                        <h2>04</h2>
                        <h4 class="fs-5 fw-semibold mb-2">Terawat & Bersih</h4>
                        <p class="text-muted mb-0">Setiap barang dicek berkala secara ketat.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="pt-5 pb-3 px-md-5">
        <div class="container-fluid">
            <div class="row g-5 mb-5">
                <div class="col-lg-4">
                    <div class="footer-logo d-flex align-items-center gap-2 mb-3">UKKI Inventory</div>
                    <p class="mb-3 text-muted" style="color: #94A3B8 !important;">Sistem penyewaan inventaris organisasi mahasiswa yang aman, cepat, dan terpercaya.</p>
                    <ul class="d-flex flex-column gap-2 text-muted" style="color: #94A3B8 !important;">
                        <li><i class="fas fa-map-marker-alt text-center" style="width: 20px;"></i> Masjid Al Istiqomah UPN "Veteran" Jawa Timur</li>
                        <li><i class="fas fa-envelope text-center" style="width: 20px;"></i> event.ukki@gmail.com</li>
                        <li><i class="fas fa-phone text-center" style="width: 20px;"></i> +6289677778190</li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h4 class="text-white fs-5 fw-semibold mb-4">Quick Links</h4>
                    <ul class="d-flex flex-column gap-3">
                        <li><a href="index.php" class="text-decoration-none" style="color: #94A3B8;">Home</a></li>
                        <li><a href="produk.php" class="text-decoration-none" style="color: #94A3B8;">Katalog</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h4 class="text-white fs-5 fw-semibold mb-4">Social Media</h4>
                    <div class="social-icons d-flex gap-3">
                        <a href="https://wa.me/6289677778190" target="_blank" class="text-decoration-none fs-4" style="color: #94A3B8;"><i class="fab fa-whatsapp"></i></a>
                        <a href="https://www.instagram.com/ukki_upn/" target="_blank" class="text-decoration-none fs-4" style="color: #94A3B8;"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.linkedin.com/company/ukkiupnvjt/" target="_blank" class="text-decoration-none fs-4" style="color: #94A3B8;"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="map-box">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3957.387140801831!2d112.78762741477488!3d-7.333100694708173!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd7fab87f4c5111%3A0x6fbcebf5a32eb4a7!2sUPN%20%22Veteran%22%20Jawa%20Timur!5e0!3m2!1sen!2sid!4v1679412345678!5m2!1sen!2sid" allowfullscreen=""></iframe>
                    </div>
                </div>
            </div>
            <div class="footer-bottom text-center pt-4 border-top" style="border-color: #1E293B !important;">
                <p class="mb-0" style="color: #94A3B8;">© 2026 UKKI UPN "Veteran" Jawa Timur. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>