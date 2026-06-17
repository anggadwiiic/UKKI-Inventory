<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    exit('Akses Ditolak.');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi_status'])) {
    $id_pem = (int)$_POST['id_peminjaman'];
    $aksi = $_POST['aksi_status']; 

    if ($aksi === 'setujui') {
        $query = "UPDATE peminjaman SET status = 'disetujui' WHERE id_peminjaman = $id_pem";
        mysqli_query($conn, $query);
        header("Location: penyewaan.php");
        exit;
    } elseif ($aksi === 'selesai') {
        $query = "UPDATE peminjaman SET status = 'selesai' WHERE id_peminjaman = $id_pem";
        mysqli_query($conn, $query);
        header("Location: penyewaan.php");
        exit;
    }
}

$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'all';

$query_counts = "SELECT status, COUNT(*) as count FROM peminjaman GROUP BY status";
$res_counts = query($query_counts);
$counts = ['request'=>0, 'disetujui'=>0, 'selesai'=>0];
$count_all = 0;
if ($res_counts) {
    foreach($res_counts as $row) {
        if(isset($counts[$row['status']])) {
            $counts[$row['status']] = $row['count'];
            $count_all += $row['count'];
        }
    }
}

$where_clauses = [];
if ($status_filter !== 'all') {
    $where_clauses[] = "p.status = '$status_filter'";
} else {
    $where_clauses[] = "p.status IN ('request', 'disetujui', 'selesai')";
}

if ($search !== '') {
    $search_id = (int)preg_replace('/[^0-9]/', '', $search);
    $id_clause = $search_id > 0 ? "OR p.id_peminjaman = $search_id" : "";
    $where_clauses[] = "(u.nama_lengkap LIKE '%$search%' OR u.username LIKE '%$search%' OR i.nama_barang LIKE '%$search%' $id_clause)";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

$query_trx = "SELECT p.*, u.nama_lengkap, u.username as npm, u.email, u.no_telp, 
              GROUP_CONCAT(i.nama_barang SEPARATOR ', ') as items
              FROM peminjaman p 
              JOIN users u ON p.id_user = u.id_user 
              LEFT JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman
              LEFT JOIN inventaris i ON dp.id_inventaris = i.id_inventaris
              $where_sql
              GROUP BY p.id_peminjaman
              ORDER BY p.tanggal_pengajuan DESC";
$transaksi = query($query_trx);

function getBadgeTrx($status) {
    $map = [
        'request' => ['class' => 'badge-warning', 'label' => 'Pending'],
        'disetujui' => ['class' => 'badge-info', 'label' => 'Disetujui'],
        'selesai' => ['class' => 'badge-success', 'label' => 'Selesai']
    ];
    $c = $map[$status]['class'] ?? 'badge-warning';
    $l = $map[$status]['label'] ?? 'Pending';
    return "<span class='badge $c'>$l</span>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Penyewaan - UKKI</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../assets/img/logo-ukki.png">
    <style>
        :root { 
            --primary: #0F766E; --bg: #F8FAFC; --text: #1E293B; --muted: #64748B; 
            --white: #FFFFFF; --border: #E2E8F0; --sidebar-bg: #155050;
            --sidebar-active-text: #155050; --sidebar-idle-text: #94A3B8;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--bg); color: var(--text); overflow-x: hidden; }
        a { text-decoration: none; color: inherit; }
        
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
        .nav-menu a.active { color: var(--sidebar-active-text); background: var(--white); font-weight: 500; }
        .nav-menu i { font-size: 1.1rem; width: 24px; text-align: center; font-style: normal;}
        
        .logout-item { margin-top: auto; }
        .logout-item a { transition: all 0.2s ease; }
        .logout-item a:hover { background: #DC2626; color: #FFFFFF !important; }
        .logout-item a:hover i { color: #FFFFFF !important; }
        
        .main-content { margin-left: 260px; padding: 1.5rem 2.5rem; transition: margin-left 0.3s ease; }
        .mobile-header { display: none; justify-content: space-between; align-items: center; background: var(--white); padding: 1rem 1.5rem; border-bottom: 1px solid var(--border); margin: -1.5rem -1.5rem 1.5rem -1.5rem; }
        .mobile-toggle { background: none; border: none; font-size: 1.5rem; color: var(--text); cursor: pointer; }
        
        .top-navbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .top-nav-titles h1 { font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 700; color: var(--text);}
        .top-nav-titles p { font-size: 0.9rem; color: var(--text); margin-top: 0.2rem;}
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-text { text-align: right; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--sidebar-bg); color: white; display: flex; align-items: center; justify-content: center; font-weight: 500; font-family: 'Outfit'; font-size: 1.1rem;}
        
        .toolbar-container { background: var(--white); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .search-form { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
        
        .search-box { position: relative; flex-grow: 1; }
        .search-box i.fa-search { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 0.9rem;}
        .btn-clear { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--muted); cursor: pointer; font-size: 1rem; display: none;}
        .btn-clear:hover { color: var(--text); }

        .search-input { width: 100%; padding: 0.75rem 2.5rem 0.75rem 2.5rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; color: var(--text); font-family: 'Poppins', sans-serif; outline: none; background: #F8FAFC; transition: 0.2s;}
        .search-input:focus { border-color: #1B6060; background: var(--white); }
        .btn-search { background: #1B6060; color: white; border: none; padding: 0 1.5rem; border-radius: 8px; font-weight: 500; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s;}
        .btn-search:hover { background: #124040; }
        
        .filter-tabs { display: flex; gap: 0.8rem; flex-wrap: wrap; }
        .tab-btn { background: #F3F4F6; color: #4A5565; border: 1px solid #F3F4F6; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.85rem; font-weight: 500; text-decoration: none; transition: 0.2s; display: inline-block;}
        .tab-btn.active { background: #1B6060; color: var(--white); border-color: #1B6060; }
        .tab-btn:hover:not(.active) { background: #E2E8F0; border-color: #E2E8F0; }
        
        .trx-list { display: flex; flex-direction: column; gap: 1.2rem; }
        .trx-card { background: var(--white); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; position: relative;}
        
        .trx-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; border-bottom: 1px solid #F1F5F9; padding-bottom: 1rem;}
        .trx-title-group { display: flex; flex-direction: column; gap: 0.3rem;}
        
        .trx-id-badge { display: flex; align-items: center; gap: 10px; }
        .trx-id { font-family: 'Outfit', sans-serif; font-weight: 600; font-size: 1.1rem; color: var(--text);}
        .trx-date { font-size: 0.8rem; color: var(--muted); }
        
        /* Tombol Detail Diperbarui Sesuai Revisi */
        .btn-detail { background: #1B6060; border: 1px solid #1B6060; color: #FFFFFF; padding: 0.5rem 1rem; border-radius: 33554400px; font-weight: 600; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: 0.2s; font-family: 'Poppins', sans-serif;}
        .btn-detail i { font-size: 0.9rem; color: #FFFFFF; }
        .btn-detail:hover { background: #0F172A; border-color: #0F172A; color: #FFFFFF; }

        .trx-body { display: grid; grid-template-columns: 2fr 1.5fr 1.5fr 1.5fr; gap: 1.5rem; align-items: start;}
        .info-col p { font-size: 0.75rem; color: var(--muted); margin-bottom: 0.3rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;}
        .info-col h5 { font-size: 0.95rem; font-weight: 500; color: var(--text); line-height: 1.4;}
        .info-col h5 span { font-size: 0.85rem; font-weight: 400; color: var(--muted); display: block; margin-top: 0.2rem;}
        .total-price { font-size: 1.1rem; font-weight: 600; color: var(--text); }
        
        .badge { padding: 4px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 500; display: inline-block;}
        .badge-warning { background: #FEF3C7; color: #D97706; }
        .badge-info { background: #E0F2FE; color: #0284C7; }
        .badge-success { background: #D1FAE5; color: #059669; }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); z-index: 2000; align-items: center; justify-content: center;}
        .modal-box { background: var(--white); width: 95%; max-width: 900px; border-radius: 16px; padding: 2rem; position: relative; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);}
        .modal-header { margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid #F1F5F9; }
        .modal-header h3 { font-family: 'Outfit', sans-serif; font-size: 1.4rem; color: var(--text); font-weight: 600; margin-bottom: 0.3rem;}
        .modal-header p { font-size: 0.85rem; color: var(--muted); }
        .modal-close { position: absolute; top: 1.5rem; right: 1.5rem; background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--muted); transition: 0.2s;}
        .modal-close:hover { color: var(--text); }
        
        .modal-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .section-box { border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .section-title { font-size: 1rem; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; color: var(--text); font-family: 'Outfit', sans-serif;}
        
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.8rem; color: var(--text); margin-bottom: 0.5rem; font-weight: 500;}
        .form-control-ro { width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: 8px; background: #F8FAFC; color: var(--muted); font-size: 0.9rem; }
        
        .item-card { border: 1px solid #BAE6FD; background: #F0F9FF; border-radius: 8px; padding: 1rem; margin-bottom: 0.8rem; }
        .item-card h5 { font-size: 0.95rem; color: #0284C7; margin-bottom: 0.3rem;}
        .item-card p { font-size: 0.8rem; color: var(--muted); line-height: 1.5;}
        
        .ktm-box { width: 100%; height: 180px; border: 1px dashed var(--border); border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #F1F5F9;}
        .ktm-box img { width: 100%; height: 100%; object-fit: cover; }
        
        .btn-wa { background: #22C55E; color: white; border: none; padding: 1rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; font-size: 0.95rem; margin-top: 1rem; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;}
        .btn-wa:hover { background: #16A34A; }
        
        .btn-done { background: #3B82F6; color: white; border: none; padding: 1rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; font-size: 0.95rem; margin-top: 1rem; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;}
        .btn-done:hover { background: #2563EB; }

        .empty-state { text-align: center; padding: 4rem 1rem; }
        .empty-state p { color: var(--muted); font-size: 1rem; font-weight: 500; }

        @media (max-width: 900px) {
            .trx-body { grid-template-columns: 1fr 1fr; }
            .modal-layout { grid-template-columns: 1fr; gap: 0; }
            .trx-header { flex-direction: column; gap: 1rem;}
            .btn-detail { align-self: flex-start; }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1.5rem; }
            .mobile-header { display: flex; }
            .top-navbar { display: none; }
            .search-form { flex-direction: column; gap: 0.5rem; }
            .btn-search { padding: 0.8rem; justify-content: center; }
            .trx-body { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php if(isset($_SESSION['toast_msg'])): ?>
    <div class="toast <?= $_SESSION['toast_type'] ?>" id="toastBox">
        <i class="fas <?= $_SESSION['toast_type'] == 'success' ? 'fa-check-circle success' : 'fa-exclamation-circle error' ?>"></i>
        <div class="toast-msg"><?= $_SESSION['toast_msg'] ?></div>
    </div>
    <script>
        setTimeout(() => { document.getElementById('toastBox').classList.add('show'); }, 100);
        setTimeout(() => { document.getElementById('toastBox').classList.remove('show'); }, 3000);
    </script>
    <?php unset($_SESSION['toast_msg']); unset($_SESSION['toast_type']); endif; ?>

    <div class="overlay" id="sidebarOverlay" style="z-index: 999;" onclick="toggleMenu()"></div>

    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="brand-text"><h2>Inventory</h2><p>UKKI UPN "Veteran" Jatim</p></div>
        </div>
        <p class="menu-label">MENU</p>
        <ul class="nav-menu">
            <li><a href="index.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="inventaris.php"><i class="fas fa-cube"></i> Inventaris</a></li>
            <li><a href="penyewaan.php" class="active"><i class="fas fa-file-alt"></i> Transaksi</a></li>
            <li class="logout-item"><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="mobile-header">
            <div class="user-profile" style="background: none; border: none; padding: 0;">
                <div class="avatar"><?= substr($_SESSION['user_nama'], 0, 1) ?></div>
            </div>
            <button class="mobile-toggle" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>
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

        <div class="toolbar-container">
            <form method="GET" action="" class="search-form">
                <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" id="searchInput" class="search-input" placeholder="Cari berdasarkan Order ID, Nama, Barang, atau NPM..." value="<?= htmlspecialchars($search) ?>" oninput="toggleClearBtn()">
                    <button type="button" class="btn-clear" id="clearBtn" onclick="clearSearch()"><i class="fas fa-times" style="position:static; transform:none;"></i></button>
                </div>
                <button type="submit" class="btn-search"><i class="fas fa-search"></i> Cari</button>
            </form>

            <div class="filter-tabs">
                <a href="?status=all&q=<?= urlencode($search) ?>" class="tab-btn <?= $status_filter == 'all' ? 'active' : '' ?>">Semua (<?= $count_all ?>)</a>
                <a href="?status=request&q=<?= urlencode($search) ?>" class="tab-btn <?= $status_filter == 'request' ? 'active' : '' ?>">Pending (<?= $counts['request'] ?>)</a>
                <a href="?status=disetujui&q=<?= urlencode($search) ?>" class="tab-btn <?= $status_filter == 'disetujui' ? 'active' : '' ?>">Disetujui (<?= $counts['disetujui'] ?>)</a>
                <a href="?status=selesai&q=<?= urlencode($search) ?>" class="tab-btn <?= $status_filter == 'selesai' ? 'active' : '' ?>">Selesai (<?= $counts['selesai'] ?>)</a>
            </div>
        </div>

        <div class="trx-list">
            <?php if (empty($transaksi)): ?>
                <div class="empty-state">
                    <p>Hasil tidak ditemukan</p>
                </div>
            <?php else: ?>
                <?php foreach ($transaksi as $t): 
                    $id_pem = $t['id_peminjaman'];
                    $detail_barang = query("SELECT dp.jumlah, i.nama_barang, i.harga_sewa_per_hari 
                                            FROM detail_peminjaman dp 
                                            JOIN inventaris i ON dp.id_inventaris = i.id_inventaris 
                                            WHERE dp.id_peminjaman = $id_pem");
                    $order_id_str = "TRX-" . date('Y', strtotime($t['tanggal_pengajuan'])) . "-" . str_pad($id_pem, 3, '0', STR_PAD_LEFT);
                ?>
                <div class="trx-card">
                    <div class="trx-header">
                        <div class="trx-title-group">
                            <div class="trx-id-badge">
                                <span class="trx-id"><?= $order_id_str ?></span>
                                <?= getBadgeTrx($t['status']) ?>
                            </div>
                            <span class="trx-date">Dibuat: <?= date('d M Y, H:i', strtotime($t['tanggal_pengajuan'])) ?></span>
                        </div>
                        <button class="btn-detail" onclick="openModal('modal_<?= $id_pem ?>')">
                            <i class="far fa-eye"></i> Lihat Detail
                        </button>
                    </div>
                    
                    <div class="trx-body">
                        <div class="info-col">
                            <p>Item</p>
                            <h5><?= htmlspecialchars(substr($t['items'] ?? '-', 0, 40)) ?><?= strlen($t['items'] ?? '') > 40 ? '...' : '' ?></h5>
                        </div>
                        <div class="info-col">
                            <p>Peminjam</p>
                            <h5><?= htmlspecialchars($t['nama_lengkap']) ?><span><?= htmlspecialchars($t['npm']) ?></span></h5>
                        </div>
                        <div class="info-col">
                            <p>Periode Sewa</p>
                            <h5><?= date('d M Y', strtotime($t['tanggal_pinjam'])) ?><span>s/d <?= date('d M Y', strtotime($t['tanggal_kembali_rencana'])) ?></span></h5>
                        </div>
                        <div class="info-col">
                            <p>Total Pembayaran</p>
                            <div class="total-price">Rp <?= number_format($t['total_biaya'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <div class="modal-overlay" id="modal_<?= $id_pem ?>">
                    <div class="modal-box">
                        <button class="modal-close" onclick="closeModal('modal_<?= $id_pem ?>')"><i class="fas fa-times"></i></button>
                        <div class="modal-header">
                            <h3>Data Penyewaan</h3>
                            <p>Perbarui status dan data penyewaan secara manual melalui form di bawah ini.</p>
                        </div>
                        
                        <div class="modal-layout">
                            <div>
                                <div class="section-box">
                                    <div class="section-title"><i class="far fa-user"></i> Data Peminjam</div>
                                    <div class="form-group">
                                        <label>Nama Lengkap</label>
                                        <div class="form-control-ro"><?= htmlspecialchars($t['nama_lengkap']) ?></div>
                                    </div>
                                    <div class="form-group">
                                        <label>NPM</label>
                                        <div class="form-control-ro"><?= htmlspecialchars($t['npm']) ?></div>
                                    </div>
                                    <div class="form-group">
                                        <label>Email</label>
                                        <div class="form-control-ro"><?= htmlspecialchars($t['email'] ?? '-') ?></div>
                                    </div>
                                    <div class="form-group">
                                        <label>No. HP (WhatsApp)</label>
                                        <div class="form-control-ro"><?= htmlspecialchars($t['no_telp']) ?></div>
                                    </div>
                                </div>

                                <div class="section-box">
                                    <div class="section-title"><i class="far fa-id-card"></i> Foto KTM</div>
                                    <div class="ktm-box">
                                        <?php if($t['foto_ktm']): ?>
                                            <img src="../assets/img/ktm/<?= htmlspecialchars($t['foto_ktm']) ?>" alt="KTM">
                                        <?php else: ?>
                                            <p style="color:var(--muted); font-size:0.85rem;">Tidak ada foto KTM</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="section-box">
                                    <div class="section-title"><i class="fas fa-box"></i> Barang yang Dipinjam</div>
                                    <?php foreach($detail_barang as $brg): ?>
                                    <div class="item-card">
                                        <h5> <?= htmlspecialchars($brg['nama_barang']) ?></h5>
                                        <p>Tarif: Rp <?= number_format($brg['harga_sewa_per_hari'], 0, ',', '.') ?>/hari</p>
                                        <p style="margin-top:0.3rem; font-weight:500; color:var(--text);">Jumlah: <?= $brg['jumlah'] ?> unit</p>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="section-box">
                                    <div class="section-title"><i class="far fa-calendar-alt"></i> Periode Sewa</div>
                                    <div style="display:flex; gap:1rem;">
                                        <div class="form-group" style="flex:1;">
                                            <label>Tanggal Mulai</label>
                                            <div class="form-control-ro"><?= date('d M Y', strtotime($t['tanggal_pinjam'])) ?></div>
                                        </div>
                                        <div class="form-group" style="flex:1;">
                                            <label>Tanggal Selesai</label>
                                            <div class="form-control-ro"><?= date('d M Y', strtotime($t['tanggal_kembali_rencana'])) ?></div>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($t['status'] === 'request'): ?>
                                    <form method="POST" action="" id="form_setujui_<?= $id_pem ?>">
                                        <input type="hidden" name="id_peminjaman" value="<?= $id_pem ?>">
                                        <input type="hidden" name="aksi_status" value="setujui">
                                        
                                        <div class="section-box" style="border-color: #22C55E; background:#F0FDF4; padding: 1.5rem;">
                                            <h4 style="font-size: 1rem; color: #166534; margin-bottom: 0.5rem;"><i class="fas fa-check-circle"></i> Menunggu Persetujuan</h4>
                                            <p style="font-size: 0.85rem; color: #15803D; margin-bottom: 1rem;">Klik tombol di bawah untuk menyetujui pengajuan ini dan mengirimkan instruksi via WhatsApp.</p>
                                            <button type="button" onclick="kirimWA('<?= $id_pem ?>', '<?= htmlspecialchars($t['no_telp']) ?>', '<?= htmlspecialchars(addslashes($t['nama_lengkap'])) ?>', '<?= htmlspecialchars(addslashes($t['items'] ?? 'Barang')) ?>')" class="btn-wa"><i class="fab fa-whatsapp" style="font-size: 1.1rem;"></i> Setujui Pengajuan</button>
                                        </div>
                                    </form>
                                <?php elseif ($t['status'] === 'disetujui'): ?>
                                    <form method="POST" action="">
                                        <input type="hidden" name="id_peminjaman" value="<?= $id_pem ?>">
                                        <input type="hidden" name="aksi_status" value="selesai">
                                        
                                        <div class="section-box" style="border-color: #3B82F6; background:#EFF6FF; padding: 1.5rem;">
                                            <h4 style="font-size: 1rem; color: #1E40AF; margin-bottom: 0.5rem;">Barang Sedang Disewa</h4>
                                            <p style="font-size: 0.85rem; color: #1D4ED8; margin-bottom: 1rem;">Jika barang sudah dikembalikan oleh peminjam, klik tombol di bawah untuk menyelesaikan transaksi.</p>
                                            <button type="submit" class="btn-done">Tandai Telah Selesai</button>
                                        </div>
                                    </form>
                                <?php elseif ($t['status'] === 'selesai'): ?>
                                    <div class="section-box" style="border-color: #E2E8F0; background:#F8FAFC; text-align: center; padding: 2rem;">
                                        <i class="fas fa-check-circle" style="font-size: 2.5rem; color: #10B981; margin-bottom: 1rem;"></i>
                                        <h4 style="font-size: 1rem; color: var(--text); margin-bottom: 0.5rem;">Transaksi Selesai</h4>
                                        <p style="font-size: 0.85rem; color: var(--muted);">Penyewaan ini telah diselesaikan dan barang telah dikembalikan.</p>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleMenu() {
            sidebar.classList.toggle('active');
            overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
        }

        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        function toggleClearBtn() {
            var input = document.getElementById('searchInput');
            var btn = document.getElementById('clearBtn');
            btn.style.display = input.value.length > 0 ? 'block' : 'none';
        }
        
        function clearSearch() {
            var input = document.getElementById('searchInput');
            input.value = '';
            toggleClearBtn();
            input.form.submit();
        }

        function kirimWA(idPem, noWa, namaPeminjam, namaBarang) {
            if(noWa.startsWith('08')) {
                noWa = '628' + noWa.substring(2);
            }
            var pesan = "Halo *" + namaPeminjam + "*,\n\n*" + namaBarang + "* yang ingin disewa sudah bisa diambil di Sekretariat Ikhwan UKKI sekarang.\n\nUntuk Pembayaran bisa melalui:\n💳 [SHOPEEPAY] 085607733892 a.n Arina Rochmatika (rinkarinar08)\n💳 [DANA] 085600284801 a.n. Arina Rochmatika\n💳 [SEABANK] 901080831103 a.n. Arina Rochmatika.\n\nAtau bisa juga bayar melalui cash di Sekretariat Ikhwan UKKI. Jangan lupa membawa KTM saat pengambilan barang. Terima kasih!";
            
            var urlWa = "https://api.whatsapp.com/send?phone=" + noWa + "&text=" + encodeURIComponent(pesan);
            
            window.open(urlWa, '_blank');
            
            document.getElementById('form_setujui_' + idPem).submit();
        }

        window.onload = function() {
            toggleClearBtn();
        };
    </script>
</body>
</html>