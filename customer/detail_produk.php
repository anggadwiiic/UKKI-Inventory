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
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/logo-ukki.png">
    
    <style>
        :root { 
            --teal-dark: #0F766E; --teal-light: #ECFDF5; --teal-soft: #F0FDF4;
            --text-dark: #1E293B; --text-gray: #64748B; --bg: #F8FAFC; 
            --white: #FFFFFF; --border: #E2E8F0;
            --cal-tersedia: #FEF3C7; --cal-terbatas: #FFEDD5; --cal-habis: #F1F5F9; --cal-pilih: #0F766E;
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg); color: var(--text-gray); line-height: 1.6; display: flex; flex-direction: column; min-height: 100vh;}
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

        /* LEFT COL (IMG & TEXT) */
        .img-showcase { background: var(--white); border: 1px solid var(--border); border-radius: 16px; height: 350px; font-size: 8rem; color: var(--text-gray); opacity: 0.7;}
        .status-badge { color: #059669; background: var(--teal-light); padding: 0.3rem 1rem; border-radius: 50px; font-weight: 500;}
        .item-category { font-size: 0.8rem; color: var(--white); background: var(--teal-dark); padding: 0.2rem 0.8rem; border-radius: 50px; margin-bottom: 0.5rem; display: inline-block;}
        .specs-item { background: var(--bg); padding: 0.8rem 1rem; border-radius: 8px; border: 1px solid var(--border); color: var(--text-dark); font-weight: 500;}

        /* RIGHT COL (CARDS & CALENDAR) */
        .right-card { background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem; margin-bottom: 1.5rem; }
        
        /* CALENDAR SPECIFIC (DO NOT CONVERT TO BS UTILITIES) */
        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; text-align: center; font-size: 0.85rem;}
        .cal-day-name { font-weight: 500; color: var(--text-gray); margin-bottom: 0.5rem; font-size: 0.75rem;}
        .cal-date { padding: 0.4rem 0; border-radius: 4px; cursor: pointer; font-weight: 500; border: 1px solid transparent;}
        .cal-empty { cursor: default; }
        .cal-tersedia { background: var(--cal-tersedia); color: #B45309; }
        .cal-terbatas { background: var(--cal-terbatas); color: #C2410C; }
        .cal-habis { background: var(--cal-habis); color: #94A3B8; text-decoration: line-through; cursor: not-allowed; }
        .cal-pilih { background: var(--cal-pilih) !important; color: var(--white) !important; font-weight: 700;}
        .cal-in-range { background: var(--teal-light) !important; color: var(--teal-dark) !important; }
        .legend-box { width: 10px; height: 10px; border-radius: 2px; }

        /* QTY & PRICE */
        .qty-input:focus { outline: none; }
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
        <a href="../produk.php" class="text-decoration-none text-muted mb-4 d-inline-block fw-medium"><i class="fas fa-chevron-left"></i> Kembali ke katalog</a>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2" role="alert"><i class="fas fa-exclamation-circle"></i> <?= $error; ?></div>
        <?php endif; ?>

        <div class="row gy-5 gx-lg-5">
            <div class="col-lg-7">
                <div class="img-showcase d-flex align-items-center justify-content-center mb-4">
                    <?php if(!empty($inv['gambar']) && file_exists("../assets/img/inventaris/" . $inv['gambar'])): ?>
                        <img src="../assets/img/inventaris/<?= $inv['gambar'] ?>" alt="<?= htmlspecialchars($inv['nama_barang']) ?>" class="img-fluid object-fit-contain p-3 h-100">
                    <?php else: ?>
                        <i class="fas fa-cube"></i>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom small">
                    <span class="status-badge">Tersedia</span>
                    <span class="fw-medium text-muted">Stok Tersedia: <span id="max_stock"><?= $inv['stok']; ?></span> unit</span>
                </div>

                <div class="item-category"><?= htmlspecialchars($inv['nama_kategori']); ?></div>
                <h1 class="fs-2 fw-bold text-dark mb-4 lh-sm" style="font-family: 'Outfit';"><?= htmlspecialchars($inv['nama_barang']); ?></h1>

                <div class="fs-6 fw-semibold mb-2" style="color: var(--teal-dark);">Deskripsi Produk</div>
                <div class="text-muted mb-5 lh-lg" style="font-size: 0.95rem;">
                    <?= nl2br(htmlspecialchars($inv['deskripsi']) ?: 'Tidak ada deskripsi.'); ?>
                </div>

                <div class="fs-6 fw-semibold mb-3" style="color: var(--teal-dark);">Spesifikasi Utama</div>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="specs-item d-flex align-items-center gap-2"><i class="far fa-check-circle" style="color: var(--teal-dark);"></i> Kondisi: <?= htmlspecialchars($inv['kondisi_barang']); ?></div>
                    </div>
                    <?php if(!empty($specs_array)): ?>
                        <?php foreach($specs_array as $item): ?>
                        <div class="col-sm-6">
                            <div class="specs-item d-flex align-items-center gap-2"><i class="far fa-check-circle" style="color: var(--teal-dark);"></i> <?= htmlspecialchars($item); ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="right-card">
                    <div class="d-flex align-items-center gap-2 fs-5 fw-semibold text-dark mb-1"><i class="far fa-calendar-alt"></i> Pilih Tanggal</div>
                    <p class="text-muted small mb-4">Klik tanggal mulai dan akhir penyewaan</p>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3 fw-semibold text-dark small">
                        <button type="button" class="btn border-0 text-muted p-0" id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                        <span id="monthYearDisplay">Januari 2026</span>
                        <button type="button" class="btn border-0 text-muted p-0" id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    
                    <div class="cal-grid">
                        <div class="cal-day-name">Min</div><div class="cal-day-name">Sen</div><div class="cal-day-name">Sel</div>
                        <div class="cal-day-name">Rab</div><div class="cal-day-name">Kam</div><div class="cal-day-name">Jum</div><div class="cal-day-name">Sab</div>
                    </div>
                    <div class="cal-grid mb-3" id="calendarDays"></div>

                    <div class="d-flex flex-wrap justify-content-center gap-2 align-items-center" style="font-size: 0.75rem;">
                        <div class="d-flex align-items-center gap-1"><div class="legend-box cal-tersedia"></div> Tersedia</div>
                        <div class="d-flex align-items-center gap-1"><div class="legend-box cal-terbatas"></div> Terbatas</div>
                        <div class="d-flex align-items-center gap-1"><div class="legend-box cal-habis"></div> Tidak Tersedia</div>
                        <div class="d-flex align-items-center gap-1"><div class="legend-box cal-pilih"></div> Dipilih</div>
                    </div>

                    <div class="mt-4 p-3 rounded-3 text-center" style="background: var(--teal-light); color: var(--teal-dark); font-size: 0.85rem;" id="selectedText">
                        Pilih rentang tanggal di kalender.
                    </div>
                </div>

                <div class="right-card">
                    <div class="d-flex align-items-center gap-2 fs-5 fw-semibold text-dark mb-1"><i class="fas fa-layer-group"></i> Jumlah Barang</div>
                    <p class="text-muted small mb-3">Sesuaikan jumlah yang ingin disewa</p>
                    
                    <div class="d-flex align-items-center justify-content-between border rounded-3 px-3 py-2">
                        <button type="button" class="btn border-0 p-0 fs-5 text-dark" id="btnMinus">-</button>
                        <input type="number" id="inputQty" class="qty-input bg-transparent border-0 text-center fw-semibold text-dark" style="width: 50px;" value="1" min="1" max="<?= $inv['stok']; ?>" readonly>
                        <button type="button" class="btn border-0 p-0 fs-5 text-dark" id="btnPlus">+</button>
                    </div>
                    
                    <div class="mt-2 text-start small d-none" style="color: #D97706;" id="warningText"><i class="fas fa-exclamation-circle"></i> Stok terbatas! Segera pesan sebelum kehabisan.</div>
                </div>

                <div class="right-card">
                    <div class="fs-2 fw-bold text-dark lh-sm" style="font-family: 'Outfit'; mb-1">Rp <?= number_format($inv['harga_sewa_per_hari'], 0, ',', '.'); ?> <span class="fs-6 fw-normal text-muted">/Hari</span></div>
                    <p class="text-muted small mb-4">Harga belum termasuk biaya admin</p>

                    <div class="border rounded-3 p-3 mb-4" style="background: var(--bg);">
                        <div class="d-flex justify-content-between small text-muted mb-2"><span>Durasi</span> <span id="dispDurasi">0 Hari</span></div>
                        <div class="d-flex justify-content-between small text-muted mb-3"><span>Jumlah</span> <span id="dispJumlah">1 Unit</span></div>
                        <div class="d-flex justify-content-between pt-3 border-top fw-bold fs-5" style="color: var(--teal-dark);"><span>Total Estimasi</span> <span id="total_tampil">Rp 0</span></div>
                    </div>

                    <ul class="list-unstyled text-muted small d-flex flex-column gap-2 mb-4">
                        <li class="d-flex align-items-center gap-2"><i class="far fa-check-circle" style="color: var(--teal-dark);"></i> Validasi KTM</li>
                        <li class="d-flex align-items-center gap-2"><i class="far fa-clock" style="color: var(--teal-dark);"></i> Konfirmasi melalui WhatsApp</li>
                        <li class="d-flex align-items-center gap-2"><i class="fas fa-map-marker-alt" style="color: var(--teal-dark);"></i> Lokasi: Sekretariat Ikhwan UKKI</li>
                    </ul>

                    <form action="form_peminjaman.php?id=<?= $id_inventaris; ?>" method="POST" id="bookingForm">
                        <input type="hidden" name="tanggal_pinjam" id="formPinjam">
                        <input type="hidden" name="tanggal_kembali" id="formKembali">
                        <input type="hidden" name="jumlah" id="formJumlah" value="1">
                        
                        <?php if($inv['stok'] > 0 && $inv['kondisi_barang'] != 'Rusak Berat'): ?>
                            <button type="submit" name="booking" class="w-100 py-3 rounded-3 fw-semibold d-flex align-items-center justify-content-center gap-2 btn-submit" id="btnSubmit" disabled><i class="far fa-calendar-check"></i> Booking Sekarang</button>
                        <?php else: ?>
                            <button type="button" class="w-100 py-3 rounded-3 fw-semibold btn-submit" disabled>Barang Tidak Tersedia</button>
                        <?php endif; ?>
                    </form>
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
                warningEl.classList.remove('d-none');
            } else {
                warningEl.classList.add('d-none');
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