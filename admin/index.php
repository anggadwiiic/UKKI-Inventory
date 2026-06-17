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
        'request' => ['class' => 'badge-warning', 'label' => 'pending'],
        'disetujui' => ['class' => 'badge-info', 'label' => 'approved'],
        'dipinjam' => ['class' => 'badge-primary', 'label' => 'on-rent'],
        'selesai' => ['class' => 'badge-success', 'label' => 'completed'],
        'ditolak' => ['class' => 'badge-danger', 'label' => 'cancelled']
    ];
    $c = $map[$status]['class'] ?? '';
    $l = $map[$status]['label'] ?? $status;
    return "<span class='badge $c'>$l</span>";
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
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--bg); color: var(--text); overflow-x: hidden; }
        a { text-decoration: none; color: inherit; }
        
        /* sidebar */
        .sidebar { background: var(--sidebar-bg); width: 260px; height: 100vh; position: fixed; padding: 1.5rem 0 0 0; top: 0; left: 0; z-index: 1000; transition: transform 0.3s ease; display: flex; flex-direction: column; }
        .brand { padding: 0 1.5rem 2rem; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .brand-icon { width: 40px; height: 40px; background: rgba(255,255,255,0.1); color: var(--white); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .brand-text h2 { font-family: 'Outfit', sans-serif; font-size: 1.1rem; line-height: 1.2; color: var(--white); font-weight: 500;}
        .brand-text p { font-size: 0.75rem; color: rgba(255,255,255,0.7); }
        
        .menu-label { padding: 0 2rem; font-size: 0.7rem; color: rgba(255,255,255,0.5); margin-top: 1.5rem; margin-bottom: 0.5rem; letter-spacing: 1px; }
        .nav-menu { list-style: none; display: flex; flex-direction: column; flex-grow: 1; margin-bottom: 1.5rem;}
        .nav-menu li { width: 100%; }
        .nav-menu a { display: flex; align-items: center; gap: 12px; padding: 0.8rem 1.2rem; color: rgba(255,255,255,0.7); font-size: 0.95rem; font-weight: 400; transition: 0.3s; margin: 0 0.8rem; border-radius: 8px; font-style: normal; }
        .nav-menu a:hover { background: rgba(255,255,255,0.1); color: var(--white);}
        .nav-menu a.active { color: var(--sidebar-bg); background: var(--white); font-weight: 500; }
        .nav-menu i { font-size: 1.1rem; width: 24px; text-align: center; font-style: normal;}
        
        /* Class for scroll down*/
        .logout-item { margin-top: auto; }
        .logout-item a:hover { background: rgba(239, 68, 68, 0.1); color: #FCA5A5; }
        
        /* MAIN LAYOUT */
        .main-content { margin-left: 260px; padding: 1.5rem 2.5rem; transition: margin-left 0.3s ease; }
        .mobile-header { display: none; justify-content: space-between; align-items: center; background: var(--white); padding: 1rem 1.5rem; border-bottom: 1px solid var(--border); margin: -1.5rem -1.5rem 1.5rem -1.5rem; }
        .mobile-toggle { background: none; border: none; font-size: 1.5rem; color: var(--text); cursor: pointer; }
        
        .top-navbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .top-nav-titles h1 { font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 600; color: var(--text);}
        .top-nav-titles p { font-size: 0.85rem; color: var(--muted); }
        .user-profile { display: flex; align-items: center; gap: 12px; background: var(--white); padding: 0.5rem 1rem; border-radius: 50px; border: 1px solid var(--border);}
        .user-text { text-align: right; }
        .avatar { width: 35px; height: 35px; border-radius: 50%; background: var(--sidebar-bg); color: white; display: flex; align-items: center; justify-content: center; font-weight: 500; font-family: 'Outfit'; font-size: 1rem;}
        
        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 1.5rem; }
        .stat-card { background: var(--white); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border); position: relative; }
        .stat-card h3 { font-family: 'Outfit', sans-serif; font-size: 1.8rem; display: flex; align-items: baseline; gap: 8px; margin-bottom: 0.2rem; }
        .stat-card p { color: var(--muted); font-size: 0.85rem; font-weight: 400;}
        .trend { font-size: 0.75rem; font-weight: 600; padding: 2px 8px; border-radius: 50px; }
        .trend.up { background: #DCFCE7; color: #166534; }
        .trend.down { background: #FEE2E2; color: #991B1B; }
        
        /* Layout Grid */
        .middle-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; min-width: 0; }
        .bottom-grid { display: grid; grid-template-columns: 1fr; min-width: 0; }
        
        .card-box { background: var(--white); border-radius: 12px; border: 1px solid var(--border); padding: 1.5rem; display: flex; flex-direction: column; min-width: 0; overflow: hidden;}
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .card-header h4 { font-family: 'Outfit', sans-serif; font-size: 1.1rem; font-weight: 500; color: var(--text);}
        .chart-container { flex-grow: 1; position: relative; min-height: 250px; width: 100%; }
        
        /* Tabel & List */
        .table-responsive { overflow-x: auto; width: 100%; }
        table { width: 100%; border-collapse: collapse; min-width: 500px;}
        th, td { padding: 1rem 0.5rem; text-align: left; border-bottom: 1px solid var(--border); font-size: 0.85rem; }
        th { color: var(--muted); font-weight: 500; }
        
        .badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 500; text-transform: lowercase; }
        .badge-warning { background: #FEF3C7; color: #D97706; }
        .badge-info { background: #E0F2FE; color: #0284C7; }
        .badge-primary { background: #DBEAFE; color: #1D4ED8; }
        .badge-success { background: #D1FAE5; color: #059669; }
        .badge-danger { background: #FEE2E2; color: #DC2626; }
        
        .top-item-list { list-style: none; overflow-y: auto; flex-grow: 1;}
        .top-item { display: flex; align-items: center; gap: 12px; padding: 1rem 0; border-bottom: 1px solid var(--border); }
        .top-item:last-child { border-bottom: none; padding-bottom: 0; }
        .item-icon { width: 40px; height: 40px; background: #F8FAFC; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--primary); }
        
        .overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }

        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .middle-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1.5rem; }
            .mobile-header { display: flex; }
            .top-navbar { display: none; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="overlay" id="sidebarOverlay" style="z-index: 999;"></div>

    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="brand-text"><h2>Inventory</h2><p>UKKI UPN "Veteran" Jatim</p></div>
        </div>
        <p class="menu-label">MENU</p>
        <ul class="nav-menu">
            <li><a href="index.php" class="active"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="inventaris.php"><i class="fas fa-cube"></i> Inventaris</a></li>
            <li><a href="penyewaan.php"><i class="fas fa-file-alt"></i> Transaksi</a></li>
            
            <li class="logout-item"><a href="../auth/logout.php" style="color:#FCA5A5;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="mobile-header">
            <div class="user-profile" style="background: none; border: none; padding: 0;">
                <div class="avatar"><?= substr($_SESSION['user_nama'], 0, 1) ?></div>
            </div>
            <button class="mobile-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        </div>

        <div class="top-navbar">
            <div class="top-nav-titles">
                <h1>Dashboard Inventaris UKKI</h1>
                <p>Manajemen data inventaris UKKI</p>
            </div>
            <div class="user-profile">
                <div class="user-text">
                    <h4 style="font-size:0.9rem; color:var(--text); font-weight: 500;"><?= htmlspecialchars($_SESSION['user_nama']) ?></h4>
                    <p style="font-size:0.75rem; color:var(--muted);">Administrator</p>
                </div>
                <div class="avatar"><?= substr($_SESSION['user_nama'], 0, 1) ?></div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= $total_inv ?></h3>
                <p>Total Inventaris</p>
            </div>
            <div class="stat-card">
                <h3><?= $transaksi_aktif ?></h3>
                <p>Transaksi Aktif</p>
            </div>
            <div class="stat-card">
                <h3><?= $pending_payment ?></h3>
                <p>Pending Payment</p>
            </div>
            <div class="stat-card">
                <h3 style="font-size:1.5rem;">Rp <?= number_format($rev_bln_ini, 0, ',', '.') ?> 
                    <span class="trend <?= $trend_class ?>"><?= $pertumbuhan_text ?></span>
                </h3>
                <p>Pendapatan Bulan Ini</p>
            </div>
        </div>

        <div class="middle-grid">
            <div class="card-box">
                <div class="card-header"><h4>Pendapatan Bulanan (6 Bulan Terakhir)</h4></div>
                <div class="chart-container"><canvas id="revChart"></canvas></div>
            </div>
            <div class="card-box">
                <div class="card-header"><h4>Item Paling Sering Disewa</h4></div>
                <ul class="top-item-list">
                    <?php if (empty($top_items)): ?>
                        <p style="text-align:center; color:var(--muted); font-size:0.85rem; margin-top: 1rem;">Belum ada data sewa valid.</p>
                    <?php else: ?>
                        <?php foreach ($top_items as $top): ?>
                        <li class="top-item">
                            <div>
                                <h5 style="font-size:0.9rem; font-weight:500;"><?= htmlspecialchars($top['nama_barang']) ?></h5>
                                <p style="font-size:0.8rem; color:var(--muted);"><?= $top['sewa_count'] ?> kali disewa</p>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="bottom-grid">
            <div class="card-box" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h4>Penyewaan Terbaru</h4>
                    <a href="penyewaan.php" style="font-size:0.85rem; color:var(--primary); font-weight:500;">Lihat Semua ></a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Item</th>
                                <th>Peminjam</th>
                                <th>Status</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transaksi_terbaru)): ?>
                                <tr><td colspan="5" style="text-align:center; padding: 2rem;">Belum ada transaksi</td></tr>
                            <?php else: ?>
                                <?php foreach ($transaksi_terbaru as $t): ?>
                                <tr>
                                    <td style="font-weight:500;">ORD-<?= str_pad($t['id_peminjaman'], 3, '0', STR_PAD_LEFT) ?></td>
                                    <td><?= htmlspecialchars($t['items'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($t['nama_lengkap']) ?></td>
                                    <td><?= getBadge($t['status']) ?></td>
                                    <td style="font-weight:500;">Rp <?= number_format($t['total_biaya'], 0, ',', '.') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
        toggleBtn.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);

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
                        ticks: { color: '#64748B' } 
                    } 
                }
            }
        });
    </script>
</body>
</html>