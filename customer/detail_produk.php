<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../produk.php");
    exit;
}

$id_inventaris = (int)$_GET['id'];

$query = "SELECT i.*, k.nama_kategori 
          FROM inventaris i 
          JOIN kategori k ON i.id_kategori = k.id_kategori 
          WHERE i.id_inventaris = $id_inventaris";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    echo "<script>alert('Barang tidak ditemukan!'); window.location='../produk.php';</script>";
    exit;
}
$inv = mysqli_fetch_assoc($result);

$q_booking = "SELECT p.tanggal_pinjam, p.tanggal_kembali_rencana, d.jumlah 
              FROM peminjaman p 
              JOIN detail_peminjaman d ON p.id_peminjaman = d.id_peminjaman 
              WHERE d.id_inventaris = $id_inventaris 
              AND p.status IN ('request', 'disetujui')";
$res_booking = mysqli_query($conn, $q_booking);
$bookings = [];
while ($row = mysqli_fetch_assoc($res_booking)) {
    $bookings[] = $row;
}
$bookings_json = json_encode($bookings);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking'])) {
    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('Silakan login terlebih dahulu!'); window.location='../auth/login.php';</script>";
        exit;
    }

    $id_user = $_SESSION['user_id'];
    $tgl_pinjam = $_POST['tanggal_pinjam'];
    $tgl_kembali = $_POST['tanggal_kembali'];
    $jumlah = (int)$_POST['jumlah'];
    
    $date1 = new DateTime($tgl_pinjam);
    $date2 = new DateTime($tgl_kembali);
    $durasi = max(1, $date1->diff($date2)->days);

    if ($date1 > $date2 || empty($tgl_pinjam) || empty($tgl_kembali)) {
        $error = "Pilih rentang tanggal yang valid.";
    } elseif ($jumlah < 1) {
        $error = "Jumlah minimal sewa 1 unit.";
    } else {
        $total_biaya = $durasi * $inv['harga_sewa_per_hari'] * $jumlah;
        mysqli_begin_transaction($conn);
        try {
            $q_peminjaman = "INSERT INTO peminjaman (id_user, tanggal_pinjam, tanggal_kembali_rencana, total_biaya, status) 
                             VALUES ($id_user, '$tgl_pinjam', '$tgl_kembali', $total_biaya, 'request')";
            mysqli_query($conn, $q_peminjaman);
            $id_peminjaman = mysqli_insert_id($conn);

            $q_detail = "INSERT INTO detail_peminjaman (id_peminjaman, id_inventaris, jumlah) 
                         VALUES ($id_peminjaman, $id_inventaris, $jumlah)";
            mysqli_query($conn, $q_detail);

            mysqli_commit($conn);
            header("Location: form_peminjaman.php?id=$id_inventaris");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}

$specs_array = [];
if (!empty($inv['spesifikasi']) && $inv['spesifikasi'] !== 'null') {
    $specs_array = json_decode($inv['spesifikasi'], true);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($inv['nama_barang']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Aclonica&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../assets/img/logo-ukki.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { 
            --teal-dark: #0F766E; --teal-light: #ECFDF5; --teal-soft: #F0FDF4;
            --text-dark: #1E293B; --text-gray: #64748B; --bg: #F8FAFC; 
            --white: #FFFFFF; --border: #E2E8F0;
            --cal-tersedia: #FEF3C7; --cal-terbatas: #FFEDD5; --cal-habis: #F1F5F9; --cal-pilih: #0F766E;
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg); color: var(--text-gray); line-height: 1.6;}
        h1, h2, h3, h4 { font-family: 'Outfit', sans-serif; color: var(--text-dark); font-weight: 700; }
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

        .container { max-width: 1200px; margin: 2rem auto; padding: 0 5%; }
        .back-link { display: inline-block; color: var(--text-gray); margin-bottom: 2rem; font-weight: 500; font-size: 0.95rem; }
        .back-link:hover { color: var(--teal-dark); }
        .main-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 3rem; align-items: start; }

        /* Left Col */
        .img-showcase { background: var(--white); border: 1px solid var(--border); border-radius: 16px; height: 350px; display: flex; align-items: center; justify-content: center; font-size: 8rem; color: var(--text-gray); opacity: 0.7; margin-bottom: 1.5rem;}
        
        .status-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; font-size: 0.9rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);}
        .status-badge { color: #059669; background: var(--teal-light); padding: 0.3rem 1rem; border-radius: 50px; font-weight: 500;}
        
        .item-category { display: inline-block; font-size: 0.8rem; color: var(--white); background: var(--teal-dark); padding: 0.2rem 0.8rem; border-radius: 50px; margin-bottom: 0.5rem;}
        .item-title { font-size: 2.2rem; margin-bottom: 2rem; color: var(--text-dark); line-height: 1.2;}
        
        .content-heading { font-size: 1.1rem; color: var(--teal-dark); margin-bottom: 0.5rem; font-family: 'Poppins', sans-serif; font-weight: 600;}
        .content-text { font-size: 0.95rem; color: var(--text-gray); margin-bottom: 2.5rem; line-height: 1.7;}
        
        .specs-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.9rem;}
        .specs-item { display: flex; align-items: center; gap: 10px; background: var(--bg); padding: 0.8rem 1rem; border-radius: 8px; border: 1px solid var(--border); color: var(--text-dark); font-weight: 500;}

        /* Right Col Cards */
        .right-card { background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .card-title { display: flex; align-items: center; gap: 10px; font-size: 1.05rem; font-weight: 600; color: var(--text-dark); margin-bottom: 0.5rem;}
        .card-subtitle { font-size: 0.8rem; color: var(--text-gray); margin-bottom: 1.5rem;}
        
        .cal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; font-weight: 600; font-size: 0.9rem; color: var(--text-dark);}
        .cal-btn { background: none; border: none; cursor: pointer; color: var(--text-gray); font-size: 1rem;}
        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; text-align: center; font-size: 0.85rem;}
        .cal-day-name { font-weight: 500; color: var(--text-gray); margin-bottom: 0.5rem; font-size: 0.75rem;}
        .cal-date { padding: 0.4rem 0; border-radius: 4px; cursor: pointer; font-weight: 500; border: 1px solid transparent;}
        .cal-empty { cursor: default; }
        
        .cal-tersedia { background: var(--cal-tersedia); color: #B45309; }
        .cal-terbatas { background: var(--cal-terbatas); color: #C2410C; }
        .cal-habis { background: var(--cal-habis); color: #94A3B8; text-decoration: line-through; cursor: not-allowed; }
        .cal-pilih { background: var(--cal-pilih) !important; color: var(--white) !important; font-weight: 700;}
        .cal-in-range { background: var(--teal-light) !important; color: var(--teal-dark) !important; }

        .cal-legend { display: flex; flex-wrap: wrap; gap: 10px; font-size: 0.75rem; margin-top: 1rem; align-items: center; justify-content: center;}
        .legend-item { display: flex; align-items: center; gap: 5px; }
        .legend-box { width: 10px; height: 10px; border-radius: 2px; }
        .selected-dates-text { background: var(--teal-light); padding: 0.8rem; border-radius: 8px; font-size: 0.85rem; color: var(--teal-dark); margin-top: 1.5rem; text-align: center;}

        .qty-wrapper { display: flex; align-items: center; justify-content: space-between; border: 1px solid var(--border); border-radius: 8px; padding: 0.5rem 1rem; width: 100%;}
        .qty-btn { background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-dark); }
        .qty-input { width: 40px; text-align: center; border: none; outline: none; font-weight: 600; font-family: 'Poppins'; font-size: 1rem; background: transparent;}
        .warning-text { color: #D97706; font-size: 0.8rem; margin-top: 0.8rem; display: none; text-align: left;}
        .warning-text.active { display: block; }
        
        .price-main { font-size: 1.8rem; color: var(--text-dark); font-weight: 700; font-family: 'Outfit'; margin-bottom: 0.2rem;}
        .price-main span { font-size: 0.9rem; color: var(--text-gray); font-weight: 400;}
        
        .estimasi-box { background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 1.2rem; margin-top: 1.5rem; }
        .est-row { display: flex; justify-content: space-between; font-size: 0.9rem; color: var(--text-gray); margin-bottom: 0.5rem;}
        .est-total { display: flex; justify-content: space-between; font-weight: 700; color: var(--teal-dark); font-size: 1.1rem; padding-top: 0.8rem; border-top: 1px solid var(--border); margin-top: 0.5rem;}

        .checklist { list-style: none; font-size: 0.85rem; color: var(--text-gray); margin-top: 1.5rem; }
        .checklist li { margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px; }
        
        .btn-submit { width: 100%; padding: 1rem; background: var(--teal-dark); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; margin-top: 1.5rem; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px;}
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

        @media (max-width: 992px) { 
            .main-grid { grid-template-columns: 1fr; } 
            .footer-grid{ grid-template-columns: 1fr; gap: 2rem; } 
            .nav-links { gap: 0; }
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
            <a href="../produk.php" class="nav-link active">Katalog</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="nav-link">Dashboard</a>
            <?php else: ?>
                <a href="../auth/login.php" class="nav-link">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="container">
        <a href="../produk.php" class="back-link"><i class="fas fa-chevron-left"></i> Kembali ke katalog</a>

        <?php if(isset($error)): ?>
            <div class="alert"><i class="fas fa-exclamation-circle"></i> <?= $error; ?></div>
        <?php endif; ?>

        <div class="main-grid">
            <div class="left-col">
                <div class="img-showcase">
                <?php if(!empty($inv['gambar']) && file_exists("../assets/img/inventaris/" . $inv['gambar'])): ?>
                    <img src="../assets/img/inventaris/<?= $inv['gambar'] ?>" alt="<?= htmlspecialchars($inv['nama_barang']) ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                <?php else: ?>
                    <i class="fas fa-cube"></i>
                <?php endif; ?>
            </div>
                
                <div class="status-bar">
                    <span class="status-badge">Tersedia</span>
                    <span style="color: var(--text-gray); font-weight: 500;">Stok Tersedia: <span id="max_stock"><?= $inv['stok']; ?></span> unit</span>
                </div>

                <div class="item-category"><?= htmlspecialchars($inv['nama_kategori']); ?></div>
                <h1 class="item-title"><?= htmlspecialchars($inv['nama_barang']); ?></h1>

                <div class="content-heading">Deskripsi Produk</div>
                <div class="content-text">
                    <?= nl2br(htmlspecialchars($inv['deskripsi']) ?: 'Tidak ada deskripsi.'); ?>
                </div>

                <div class="content-heading">Spesifikasi Utama</div>
                <div class="specs-grid">
                    <div class="specs-item"><i class="far fa-check-circle" style="color: var(--teal-dark);"></i> Kondisi: <?= htmlspecialchars($inv['kondisi_barang']); ?></div>
                    
                    <?php if(!empty($specs_array)): ?>
                        <?php foreach($specs_array as $item): ?>
                            <div class="specs-item"><i class="far fa-check-circle" style="color: var(--teal-dark);"></i> <?= htmlspecialchars($item); ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="right-col">
                <div class="right-card">
                    <div class="card-title"><i class="far fa-calendar-alt"></i> Pilih Tanggal</div>
                    <p class="card-subtitle">Klik tanggal mulai dan akhir penyewaan</p>
                    
                    <div class="cal-header">
                        <button type="button" class="cal-btn" id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                        <span id="monthYearDisplay">Januari 2026</span>
                        <button type="button" class="cal-btn" id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    <div class="cal-grid">
                        <div class="cal-day-name">Min</div><div class="cal-day-name">Sen</div><div class="cal-day-name">Sel</div>
                        <div class="cal-day-name">Rab</div><div class="cal-day-name">Kam</div><div class="cal-day-name">Jum</div><div class="cal-day-name">Sab</div>
                    </div>
                    <div class="cal-grid" id="calendarDays"></div>

                    <div class="cal-legend">
                        <div class="legend-item"><div class="legend-box cal-tersedia"></div> Tersedia</div>
                        <div class="legend-item"><div class="legend-box cal-terbatas"></div> Terbatas</div>
                        <div class="legend-item"><div class="legend-box cal-habis"></div> Tidak Tersedia</div>
                        <div class="legend-item"><div class="legend-box cal-pilih"></div> Dipilih</div>
                    </div>

                    <div class="selected-dates-text" id="selectedText">
                        Pilih rentang tanggal di kalender.
                    </div>
                </div>

                <div class="right-card">
                    <div class="card-title"><i class="fas fa-layer-group"></i> Jumlah Barang</div>
                    <p class="card-subtitle">Sesuaikan jumlah yang ingin disewa</p>
                    
                    <div class="qty-wrapper">
                        <button type="button" class="qty-btn" id="btnMinus">-</button>
                        <input type="number" id="inputQty" class="qty-input" value="1" min="1" max="<?= $inv['stok']; ?>" readonly>
                        <button type="button" class="qty-btn" id="btnPlus">+</button>
                    </div>
                    
                    <div class="warning-text" id="warningText"><i class="fas fa-exclamation-circle"></i> Stok terbatas! Segera pesan sebelum kehabisan.</div>
                </div>

                <div class="right-card">
                    <div class="price-main">Rp <?= number_format($inv['harga_sewa_per_hari'], 0, ',', '.'); ?> <span>/Hari</span></div>
                    <p style="font-size: 0.8rem; color: var(--text-gray);">Harga belum termasuk biaya admin</p>

                    <div class="estimasi-box">
                        <div class="est-row"><span>Durasi</span> <span id="dispDurasi">0 Hari</span></div>
                        <div class="est-row"><span>Jumlah</span> <span id="dispJumlah">1 Unit</span></div>
                        <div class="est-total"><span>Total Estimasi</span> <span id="total_tampil">Rp 0</span></div>
                    </div>

                    <ul class="checklist">
                        <li><i class="far fa-check-circle" style="color: var(--teal-dark);"></i> Validasi KTM</li>
                        <li><i class="far fa-clock" style="color: var(--teal-dark);"></i> Konfirmasi melalui WhatsApp</li>
                        <li><i class="fas fa-map-marker-alt" style="color: var(--teal-dark);"></i> Lokasi: Sekretariat Ikhwan UKKI</li>
                    </ul>

                    <form action="form_peminjaman.php?id=<?= $id_inventaris; ?>" method="POST" id="bookingForm">
                        <input type="hidden" name="tanggal_pinjam" id="formPinjam">
                        <input type="hidden" name="tanggal_kembali" id="formKembali">
                        <input type="hidden" name="jumlah" id="formJumlah" value="1">
                        
                        <?php if($inv['stok'] > 0 && $inv['kondisi_barang'] != 'Rusak Berat'): ?>
                            <button type="submit" name="booking" class="btn-submit" id="btnSubmit" disabled><i class="far fa-calendar-check"></i> Booking Sekarang</button>
                        <?php else: ?>
                            <button type="button" class="btn-submit" disabled>Barang Tidak Tersedia</button>
                        <?php endif; ?>
                    </form>
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

    <script>
        const hargaPerHari = <?= $inv['harga_sewa_per_hari']; ?>;
        const maxStok = <?= $inv['stok']; ?>;
        const bookingsData = <?= $bookings_json; ?>;
        
        let currentDate = new Date();
        let currentMonth = currentDate.getMonth();
        let currentYear = currentDate.getFullYear();
        let selectedStart = null;
        let selectedEnd = null;
        let qty = 1;

        const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
        
        function getStockOnDate(dateStr) {
            let usedStock = 0;
            let targetDate = new Date(dateStr);
            bookingsData.forEach(b => {
                let start = new Date(b.tanggal_pinjam);
                let end = new Date(b.tanggal_kembali_rencana);
                start.setHours(0,0,0,0); end.setHours(0,0,0,0);
                if (targetDate >= start && targetDate <= end) {
                    usedStock += parseInt(b.jumlah);
                }
            });
            let sisa = maxStok - usedStock;
            return sisa > 0 ? sisa : 0;
        }

        function checkWarning() {
            const warningEl = document.getElementById('warningText');
            let isTerbatas = false;
            if (maxStok === 1) isTerbatas = true;
            if (selectedStart) {
                let temp = new Date(selectedStart);
                let endCheck = selectedEnd ? selectedEnd : selectedStart;
                while(temp <= endCheck) {
                    let dStr = `${temp.getFullYear()}-${String(temp.getMonth()+1).padStart(2,'0')}-${String(temp.getDate()).padStart(2,'0')}`;
                    if(getStockOnDate(dStr) <= 1) isTerbatas = true;
                    temp.setDate(temp.getDate() + 1);
                }
            }
            if(isTerbatas) {
                warningEl.classList.add('active');
            } else {
                warningEl.classList.remove('active');
            }
        }

        function renderCalendar() {
            const calDays = document.getElementById('calendarDays');
            document.getElementById('monthYearDisplay').innerText = `${monthNames[currentMonth]} ${currentYear}`;
            calDays.innerHTML = '';

            let firstDay = new Date(currentYear, currentMonth, 1).getDay();
            let daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

            for (let i = 0; i < firstDay; i++) {
                calDays.innerHTML += `<div class="cal-date cal-empty"></div>`;
            }

            let today = new Date();
            today.setHours(0,0,0,0);

            for (let i = 1; i <= daysInMonth; i++) {
                let cellDate = new Date(currentYear, currentMonth, i);
                cellDate.setHours(0,0,0,0);
                let dateStr = `${currentYear}-${String(currentMonth+1).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
                
                let sisaStok = getStockOnDate(dateStr);
                let classStatus = "";
                
                if (cellDate < today || sisaStok === 0) {
                    classStatus = "cal-habis";
                } else if (sisaStok === 1) {
                    classStatus = "cal-terbatas";
                } else {
                    classStatus = "cal-tersedia";
                }

                if (selectedStart && cellDate.getTime() === selectedStart.getTime()) {
                    classStatus += " cal-pilih";
                } else if (selectedEnd && cellDate.getTime() === selectedEnd.getTime()) {
                    classStatus += " cal-pilih";
                } else if (selectedStart && selectedEnd && cellDate > selectedStart && cellDate < selectedEnd) {
                    classStatus += " cal-in-range";
                }

                let div = document.createElement('div');
                div.className = `cal-date ${classStatus}`;
                div.innerText = i;
                div.onclick = () => selectDate(cellDate, sisaStok);
                calDays.appendChild(div);
            }
            updateSummary();
            checkWarning();
        }

        function selectDate(dateObj, sisaStok) {
            let today = new Date();
            today.setHours(0,0,0,0);
            if (dateObj < today || sisaStok === 0) return; 

            if (!selectedStart || (selectedStart && selectedEnd)) {
                selectedStart = dateObj;
                selectedEnd = null;
            } else if (dateObj < selectedStart) {
                selectedStart = dateObj;
            } else {
                let valid = true;
                let temp = new Date(selectedStart);
                while(temp <= dateObj) {
                    let dStr = `${temp.getFullYear()}-${String(temp.getMonth()+1).padStart(2,'0')}-${String(temp.getDate()).padStart(2,'0')}`;
                    if(getStockOnDate(dStr) < qty) valid = false;
                    temp.setDate(temp.getDate() + 1);
                }
                
                if(valid) selectedEnd = dateObj;
                else alert("Stok tidak mencukupi untuk rentang tanggal tersebut.");
            }
            renderCalendar();
        }

        function formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID').format(angka);
        }

        function updateSummary() {
            let durasi = 0;
            if (selectedStart && selectedEnd) {
                let diff = selectedEnd.getTime() - selectedStart.getTime();
                durasi = Math.ceil(diff / (1000 * 3600 * 24));
                if(durasi === 0) durasi = 1; 
                
                let opts = { day: 'numeric', month: 'long', year: 'numeric' };
                document.getElementById('selectedText').innerHTML = `Tanggal Sewa: <strong>${selectedStart.toLocaleDateString('id-ID', opts)}</strong> sampai <strong>${selectedEnd.toLocaleDateString('id-ID', opts)}</strong>`;
                
                let sStart = `${selectedStart.getFullYear()}-${String(selectedStart.getMonth()+1).padStart(2,'0')}-${String(selectedStart.getDate()).padStart(2,'0')}`;
                let sEnd = `${selectedEnd.getFullYear()}-${String(selectedEnd.getMonth()+1).padStart(2,'0')}-${String(selectedEnd.getDate()).padStart(2,'0')}`;
                document.getElementById('formPinjam').value = sStart;
                document.getElementById('formKembali').value = sEnd;
                document.getElementById('btnSubmit').disabled = false;
            } else {
                document.getElementById('selectedText').innerHTML = "Pilih rentang tanggal di kalender.";
                document.getElementById('btnSubmit').disabled = true;
            }

            document.getElementById('dispDurasi').innerText = durasi + " Hari";
            document.getElementById('dispJumlah').innerText = qty + " Unit";
            document.getElementById('total_tampil').innerText = "Rp " + formatRupiah(durasi * hargaPerHari * qty);
            document.getElementById('formJumlah').value = qty;
        }

        document.getElementById('btnMinus').onclick = () => { if(qty > 1) { qty--; document.getElementById('inputQty').value = qty; renderCalendar(); } };
        document.getElementById('btnPlus').onclick = () => { if(qty < maxStok) { qty++; document.getElementById('inputQty').value = qty; renderCalendar(); } };

        document.getElementById('prevMonth').onclick = () => { currentMonth--; if(currentMonth < 0){ currentMonth = 11; currentYear--; } renderCalendar(); };
        document.getElementById('nextMonth').onclick = () => { currentMonth++; if(currentMonth > 11){ currentMonth = 0; currentYear++; } renderCalendar(); };

        renderCalendar();
    </script>
</body>
</html>