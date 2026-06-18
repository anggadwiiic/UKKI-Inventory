<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    exit('Akses Ditolak.');
}

// stat utama
$total_inv = query("SELECT COUNT(*) as total FROM inventaris")[0]['total'];
$transaksi_aktif = query("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'dipinjam'")[0]['total'];
$pending_payment = query("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'request'")[0]['total'];

// pendapatan
$bln_ini = date('Y-m');
$bln_lalu = date('Y-m', strtotime('-1 month'));

$rev_bln_ini = query("SELECT SUM(total_biaya) as total FROM peminjaman WHERE status = 'selesai' AND DATE_FORMAT(tanggal_pengajuan, '%Y-%m') = '$bln_ini'")[0]['total'] ?? 0;
$rev_bln_lalu = query("SELECT SUM(total_biaya) as total FROM peminjaman WHERE status = 'selesai' AND DATE_FORMAT(tanggal_pengajuan, '%Y-%m') = '$bln_lalu'")[0]['total'] ?? 0;

$pertumbuhan_persen = 0;
$trend_class = 'up';
$trend_sign = '+';

if ($rev_bln_lalu > 0) {
    $pertumbuhan_persen = (($rev_bln_ini - $rev_bln_lalu) / $rev_bln_lalu) * 100;
} elseif ($rev_bln_ini > 0) {
    $pertumbuhan_persen = 100;
}

if ($pertumbuhan_persen < 0) {
    $trend_class = 'down';
    $trend_sign = ''; 
}
$pertumbuhan_text = $trend_sign . number_format($pertumbuhan_persen, 1) . '%';

// graphic
$bulan_indo = ['01'=>'Jan', '02'=>'Feb', '03'=>'Mar', '04'=>'Apr', '05'=>'Mei', '06'=>'Jun', '07'=>'Jul', '08'=>'Ags', '09'=>'Sep', '10'=>'Okt', '11'=>'Nov', '12'=>'Des'];
$labels_grafik = [];
$data_grafik = [];

for ($i = 5; $i >= 0; $i--) {
    $target_date = date("Y-m", strtotime("-$i months"));
    $bulan_angka = date("m", strtotime("-$i months"));
    $labels_grafik[] = $bulan_indo[$bulan_angka];
    $rev = query("SELECT SUM(total_biaya) as total FROM peminjaman WHERE status = 'selesai' AND DATE_FORMAT(tanggal_pengajuan, '%Y-%m') = '$target_date'")[0]['total'] ?? 0;
    $data_grafik[] = (int)$rev;
}

// transaction tabel
$query_transaksi = "SELECT p.*, u.nama_lengkap, u.username as npm, u.no_telp, GROUP_CONCAT(i.nama_barang SEPARATOR ', ') as items
                    FROM peminjaman p 
                    JOIN users u ON p.id_user = u.id_user 
                    LEFT JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman
                    LEFT JOIN inventaris i ON dp.id_inventaris = i.id_inventaris
                    GROUP BY p.id_peminjaman ORDER BY p.tanggal_pengajuan DESC LIMIT 5";
$transaksi_terbaru = query($query_transaksi);

// popular items
$top_items = query("SELECT i.nama_barang, SUM(dp.jumlah) as sewa_count 
                    FROM detail_peminjaman dp 
                    JOIN inventaris i ON dp.id_inventaris = i.id_inventaris 
                    JOIN peminjaman p ON dp.id_peminjaman = p.id_peminjaman
                    WHERE p.status NOT IN ('request', 'ditolak')
                    GROUP BY dp.id_inventaris ORDER BY sewa_count DESC LIMIT 5");

function getBadge($status) {
    $map = [
        'request' => ['class' => 'bg-warning text-dark', 'label' => 'pending'],
        'disetujui' => ['class' => 'bg-info text-dark', 'label' => 'approved'],
        'dipinjam' => ['class' => 'bg-primary', 'label' => 'on-rent'],
        'selesai' => ['class' => 'bg-success', 'label' => 'completed'],
        'ditolak' => ['class' => 'bg-danger', 'label' => 'cancelled']
    ];
    $c = $map[$status]['class'] ?? '';
    $l = $map[$status]['label'] ?? $status;
    return "<span class='badge $c px-2 py-1 rounded-3 text-lowercase fw-medium text-poppins'>$l</span>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- BOOTSTRAP 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/logo-ukki.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { 
            --primary: #0F766E; 
            --bg: #F8FAFC; 
            --text: #1E293B; 
            --muted: #64748B; 
            --white: #FFFFFF; 
            --border: #E2E8F0; 
            --sidebar-bg: #155050;
            --sidebar-active-text: #0F766E;
            --sidebar-idle-text: #94A3B8;
        }
        
        body { background: var(--bg); color: var(--text); overflow-x: hidden; font-family: 'Outfit', sans-serif;}
        .text-poppins { font-family: 'Poppins', sans-serif; }
        
        /* SIDEBAR */
        .sidebar { background: var(--sidebar-bg); width: 260px; height: 100vh; position: fixed; padding: 1.5rem 0 0 0; top: 0; left: 0; z-index: 1000; transition: transform 0.3s ease;}
        .brand-icon { width: 40px; height: 40px; background: rgba(255,255,255,0.1); color: var(--white); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .nav-menu a { display: flex; align-items: center; gap: 12px; padding: 0.8rem 1.2rem; color: rgba(255,255,255,0.7); font-size: 0.95rem; font-weight: 400; transition: 0.3s; margin: 0 0.8rem; border-radius: 8px; text-decoration: none;}
        .nav-menu a:hover { background: rgba(255,255,255,0.1); color: var(--white);}
        .nav-menu a.active { color: var(--sidebar-bg); background: var(--white); font-weight: 500; }
        .nav-menu i { font-size: 1.1rem; width: 24px; text-align: center;}
        .logout-item a:hover { background: rgba(239, 68, 68, 0.1); color: #FCA5A5; }
        
        /* MAIN LAYOUT */
        .main-content { margin-left: 260px; padding: 1.5rem 2.5rem; transition: margin-left 0.3s ease; }
        .avatar { width: 35px; height: 35px; border-radius: 50%; background: var(--sidebar-bg); color: white; display: flex; align-items: center; justify-content: center; font-weight: 500; font-family: 'Outfit'; font-size: 1rem;}
        
        /* STATS */
        .stat-card { background: var(--white); border-radius: 12px; border: 1px solid var(--border); }
        .trend { font-size: 0.75rem; font-weight: 600; padding: 2px 8px; border-radius: 50px; font-family: 'Poppins', sans-serif;}
        .trend.up { background: #DCFCE7; color: #166534; }
        .trend.down { background: #FEE2E2; color: #991B1B; }
        
        /* CARDS & TABLES */
        .card-box { background: var(--white); border-radius: 12px; border: 1px solid var(--border); padding: 1.5rem; display: flex; flex-direction: column; min-width: 0; overflow: hidden;}
        .table th { color: var(--muted); font-weight: 500; border-bottom: 1px solid var(--border) !important;}
        .table td { border-bottom: 1px solid var(--border); font-size: 0.85rem; vertical-align: middle;}
        
        .top-item-list { list-style: none; overflow-y: auto; flex-grow: 1; padding: 0; margin: 0;}
        .top-item { display: flex; align-items: center; gap: 12px; padding: 1rem 0; border-bottom: 1px solid var(--border); }
        .top-item:last-child { border-bottom: none; padding-bottom: 0; }
        
        .overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1.5rem; }
        }
    </style>
</head>
<body>

    <div class="overlay" id="sidebarOverlay"></div>

    <aside class="sidebar d-flex flex-column" id="sidebar">
        <div class="d-flex align-items-center gap-2 pb-4 mb-4 px-4 border-bottom" style="border-color: rgba(255,255,255,0.05) !important;">
            <div class="text-white">
                <h2 class="fs-5 mb-0 fw-medium">Inventory</h2>
                <p class="mb-0" style="font-size: 0.75rem; color: rgba(255,255,255,0.7);">UKKI UPN "Veteran" Jatim</p>
            </div>
        </div>
        <p class="px-4 mb-2" style="font-size: 0.7rem; color: rgba(255,255,255,0.5); letter-spacing: 1px;">MENU</p>
        <ul class="nav-menu d-flex flex-column flex-grow-1 p-0 m-0 mb-4">
            <li><a href="index.php" class="active"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="inventaris.php"><i class="fas fa-cube"></i> Inventaris</a></li>
            <li><a href="penyewaan.php"><i class="fas fa-file-alt"></i> Transaksi</a></li>
            
            <li class="logout-item mt-auto"><a href="../auth/logout.php" style="color:#FCA5A5;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <!-- Mobile Header -->
        <div class="d-flex d-md-none justify-content-between align-items-center bg-white p-3 border-bottom mx-n3 mt-n3 mb-4">
            <div class="avatar"><?= substr($_SESSION['user_nama'], 0, 1) ?></div>
            <button class="btn border-0 text-dark fs-4 p-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        </div>

        <!-- Desktop Header -->
        <div class="d-none d-md-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="fs-4 fw-bold text-dark mb-1">Dashboard Inventaris UKKI</h1>
                <p class="small text-muted mb-0 text-poppins">Manajemen data inventaris UKKI</p>
            </div>
            <div class="d-flex align-items-center gap-3 bg-white px-3 py-2 rounded-pill border">
                <div class="text-end">
                    <h4 class="fs-6 mb-0 text-dark fw-medium"><?= htmlspecialchars($_SESSION['user_nama']) ?></h4>
                    <p class="mb-0" style="font-size: 0.75rem; color: var(--muted);">Administrator</p>
                </div>
                <div class="avatar"><?= substr($_SESSION['user_nama'], 0, 1) ?></div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card p-4 h-100">
                    <h3 class="fs-2 fw-bold text-dark mb-1"><?= $total_inv ?></h3>
                    <p class="small text-muted mb-0 text-poppins">Total Inventaris</p>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card p-4 h-100">
                    <h3 class="fs-2 fw-bold text-dark mb-1"><?= $transaksi_aktif ?></h3>
                    <p class="small text-muted mb-0 text-poppins">Transaksi Aktif</p>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card p-4 h-100">
                    <h3 class="fs-2 fw-bold text-dark mb-1"><?= $pending_payment ?></h3>
                    <p class="small text-muted mb-0 text-poppins">Pending Payment</p>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card p-4 h-100">
                    <div class="d-flex align-items-baseline gap-2 mb-1">
                        <h3 class="fs-4 fw-bold text-dark mb-0">Rp <?= number_format($rev_bln_ini, 0, ',', '.') ?></h3>
                        <span class="trend <?= $trend_class ?>"><?= $pertumbuhan_text ?></span>
                    </div>
                    <p class="small text-muted mb-0 text-poppins">Pendapatan Bulan Ini</p>
                </div>
            </div>
        </div>

        <!-- Middle Grid -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card-box h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fs-6 fw-semibold text-dark mb-0">Pendapatan Bulanan (6 Bulan Terakhir)</h4>
                    </div>
                    <div class="position-relative w-100" style="min-height: 250px; flex-grow: 1;">
                        <canvas id="revChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card-box h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fs-6 fw-semibold text-dark mb-0">Item Paling Sering Disewa</h4>
                    </div>
                    <ul class="top-item-list text-poppins">
                        <?php if (empty($top_items)): ?>
                            <p class="text-center text-muted small mt-3">Belum ada data sewa valid.</p>
                        <?php else: ?>
                            <?php foreach ($top_items as $top): ?>
                            <li class="top-item">
                                <div>
                                    <h5 class="fs-6 fw-medium text-dark mb-1"><?= htmlspecialchars($top['nama_barang']) ?></h5>
                                    <p class="text-muted mb-0" style="font-size:0.8rem;"><?= $top['sewa_count'] ?> kali disewa</p>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Bottom Grid -->
        <div class="row g-4">
            <div class="col-12">
                <div class="card-box mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fs-6 fw-semibold text-dark mb-0">Penyewaan Terbaru</h4>
                        <a href="penyewaan.php" class="text-decoration-none fw-medium text-poppins" style="font-size:0.85rem; color:var(--primary);">Lihat Semua ></a>
                    </div>
                    <div class="table-responsive text-poppins">
                        <table class="table table-borderless align-middle mb-0 w-100" style="min-width: 500px;">
                            <thead>
                                <tr>
                                    <th class="small text-uppercase text-muted">Order ID</th>
                                    <th class="small text-uppercase text-muted">Item</th>
                                    <th class="small text-uppercase text-muted">Peminjam</th>
                                    <th class="small text-uppercase text-muted">Status</th>
                                    <th class="small text-uppercase text-muted">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transaksi_terbaru)): ?>
                                    <tr><td colspan="5" class="text-center p-5 text-muted">Belum ada transaksi</td></tr>
                                <?php else: ?>
                                    <?php foreach ($transaksi_terbaru as $t): ?>
                                    <tr>
                                        <td class="fw-medium text-dark" style="font-family: 'Outfit', sans-serif;">ORD-<?= str_pad($t['id_peminjaman'], 3, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= htmlspecialchars($t['items'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($t['nama_lengkap']) ?></td>
                                        <td><?= getBadge($t['status']) ?></td>
                                        <td class="fw-medium text-dark">Rp <?= number_format($t['total_biaya'], 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');

        function toggleMenu() {
            sidebar.classList.toggle('active');
            overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
        }
        if(toggleBtn) toggleBtn.addEventListener('click', toggleMenu);
        if(overlay) overlay.addEventListener('click', toggleMenu);

        // Render Chart.js
        new Chart(document.getElementById('revChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($labels_grafik) ?>,
                datasets: [{
                    label: 'Pendapatan',
                    data: <?= json_encode($data_grafik) ?>,
                    borderColor: '#0F766E',
                    backgroundColor: 'rgba(15, 118, 110, 0.15)',
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#0F766E',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        min: 0,
                        suggestedMax: 100000, 
                        grid: { borderDash: [5, 5], color: '#E2E8F0' }, 
                        ticks: { display: false } 
                    }, 
                    x: { 
                        display: true,
                        grid: { display: false }, 
                        ticks: { color: '#64748B', font: { family: 'Poppins' } } 
                    } 
                }
            }
        });
    </script>
</body>
</html>