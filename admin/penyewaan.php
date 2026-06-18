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
        'request' => ['class' => 'badge-pending', 'label' => 'Pending'],
        'disetujui' => ['class' => 'badge-disetujui', 'label' => 'Disetujui'],
        'selesai' => ['class' => 'badge-selesai', 'label' => 'Selesai']
    ];
    $c = $map[$status]['class'] ?? 'badge-pending';
    $l = $map[$status]['label'] ?? 'Pending';
    return "<span class='badge $c px-3 py-1 rounded-pill fw-medium text-poppins' style='font-size: 0.85rem;'>$l</span>";
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
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/logo-ukki.png">
    <style>
        :root { 
            --primary: #0F766E; --bg: #F8FAFC; --text: #1E293B; --muted: #64748B; 
            --white: #FFFFFF; --border: #E2E8F0; --sidebar-bg: #155050;
            --sidebar-active-text: #155050; --sidebar-idle-text: #94A3B8;
        }
        body { background: var(--bg); color: var(--text); overflow-x: hidden; font-family: 'Outfit', sans-serif;}
        .text-poppins { font-family: 'Poppins', sans-serif; }
        
        /* SIDEBAR */
        .sidebar { background: var(--sidebar-bg); width: 260px; height: 100vh; position: fixed; padding: 1.5rem 0 0 0; top: 0; left: 0; z-index: 1000; transition: transform 0.3s ease;}
        .brand-icon { width: 40px; height: 40px; background: rgba(255,255,255,0.1); color: var(--white); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .nav-menu a { display: flex; align-items: center; gap: 12px; padding: 0.8rem 1.2rem; color: rgba(255,255,255,0.7); font-size: 0.95rem; font-weight: 400; transition: 0.3s; margin: 0 0.8rem; border-radius: 8px; text-decoration: none;}
        .nav-menu a:hover { background: rgba(255,255,255,0.1); color: var(--white);}
        .nav-menu a.active { color: var(--sidebar-active-text); background: var(--white); font-weight: 500; }
        .nav-menu i { font-size: 1.1rem; width: 24px; text-align: center;}
        .logout-item a:hover { background: rgba(239, 68, 68, 0.1); color: #FCA5A5; }
        
        /* MAIN LAYOUT */
        .main-content { margin-left: 260px; padding: 1.5rem 2.5rem; transition: margin-left 0.3s ease; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--sidebar-bg); color: white; display: flex; align-items: center; justify-content: center; font-weight: 500; font-family: 'Outfit'; font-size: 1.1rem;}
        
        /* TOOLBAR & SEARCH */
        .search-box { position: relative; flex-grow: 1; }
        .search-box i.fa-search { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 0.9rem;}
        .btn-clear { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--muted); cursor: pointer; font-size: 1rem; display: none;}
        .btn-clear:hover { color: var(--text); }
        .search-input { width: 100%; padding: 0.8rem 2.5rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; color: var(--text); font-family: 'Poppins', sans-serif; outline: none; background: var(--white); transition: 0.2s;}
        .search-input:focus { border-color: #1B6060; box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1); }
        .btn-search { background: #1B6060; color: white; border: none; padding: 0 1.5rem; border-radius: 8px; font-weight: 500; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s;}
        .btn-search:hover { background: #124040; }
        
        /* FILTER TABS */
        .tab-btn { background: #F8FAFC; color: #4A5565; border: 1px solid #F8FAFC; padding: 0.6rem 1.2rem; border-radius: 8px; font-size: 0.9rem; font-weight: 500; text-decoration: none; transition: 0.2s; display: inline-block; font-family: 'Poppins', sans-serif;}
        .tab-btn.active { background: #1B6060; color: var(--white); border-color: #1B6060; }
        .tab-btn:hover:not(.active) { background: #E2E8F0; border-color: #E2E8F0; }
        
        /* CUSTOM BADGES (Identical to Original) */
        .badge-pending { background: #FEF3C7; color: #D97706; }
        .badge-disetujui { background: #E0F2FE; color: #0284C7; }
        .badge-selesai { background: #DCFCE7; color: #16A34A; }

        /* TRX CARD */
        .trx-card { background: var(--white); border: 1px solid var(--border); border-radius: 12px; position: relative;}
        .btn-detail { background: #0F766E; border: none; color: #FFFFFF; padding: 0.5rem 1.2rem; border-radius: 50px; font-weight: 600; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: 0.2s; font-family: 'Poppins', sans-serif;}
        .btn-detail:hover { background: #0F172A; color: #FFFFFF; transform: translateY(-2px); }

        /* MODAL */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); z-index: 2000; align-items: center; justify-content: center;}
        .modal-box { background: var(--white); width: 95%; max-width: 900px; border-radius: 16px; position: relative; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);}
        .modal-close { position: absolute; top: 1.5rem; right: 1.5rem; background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--muted); transition: 0.2s;}
        .modal-close:hover { color: var(--text); }
        
        .form-control-ro { width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: 8px; background: #F8FAFC; color: var(--muted); font-size: 0.9rem; font-family: 'Poppins', sans-serif;}
        .ktm-box { width: 100%; height: 180px; border: 1px dashed var(--border); border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #F1F5F9;}
        .ktm-box img { width: 100%; height: 100%; object-fit: cover; }
        
        .btn-wa { background: #22C55E; color: white; border: none; padding: 1rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; font-size: 0.95rem; margin-top: 1rem; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;}
        .btn-wa:hover { background: #16A34A; }
        .btn-done { background: #3B82F6; color: white; border: none; padding: 1rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; font-size: 0.95rem; margin-top: 1rem; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;}
        .btn-done:hover { background: #2563EB; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1.5rem; }
        }
    </style>
</head>
<body>

    <div class="overlay" id="sidebarOverlay" style="z-index: 999;" onclick="toggleMenu()"></div>

    <aside class="sidebar d-flex flex-column" id="sidebar">
        <div class="d-flex align-items-center gap-2 pb-4 mb-4 px-4 border-bottom" style="border-color: rgba(255,255,255,0.05) !important;">
            <div class="text-white">
                <h2 class="fs-5 mb-0 fw-medium">Inventory</h2>
                <p class="mb-0" style="font-size: 0.75rem; color: rgba(255,255,255,0.7);">UKKI UPN "Veteran" Jatim</p>
            </div>
        </div>
        <p class="px-4 mb-2" style="font-size: 0.7rem; color: rgba(255,255,255,0.5); letter-spacing: 1px;">MENU</p>
        <ul class="nav-menu d-flex flex-column flex-grow-1 p-0 m-0 mb-4">
            <li><a href="index.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="inventaris.php"><i class="fas fa-cube"></i> Inventaris</a></li>
            <li><a href="penyewaan.php" class="active"><i class="fas fa-file-alt"></i> Transaksi</a></li>
            <li class="logout-item mt-auto"><a href="../auth/logout.php" style="color:#FCA5A5;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="d-flex d-md-none justify-content-between align-items-center bg-white p-3 border-bottom mx-n3 mt-n3 mb-4">
            <div class="avatar"><?= substr($_SESSION['user_nama'], 0, 1) ?></div>
            <button class="btn border-0 text-dark fs-4 p-0" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>
        </div>

        <div class="d-none d-md-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="fs-4 fw-bold text-dark mb-1">Dashboard Inventaris UKKI</h1>
                <p class="small text-muted mb-0 text-poppins">Manajemen data inventaris UKKI</p>
            </div>
            <div class="d-flex align-items-center gap-3 bg-white px-3 py-2 rounded-pill border">
                <div class="text-end">
                    <h4 class="fs-6 mb-0 text-dark fw-medium text-poppins"><?= htmlspecialchars($_SESSION['user_nama']) ?></h4>
                    <p class="mb-0" style="font-size: 0.75rem; color: var(--muted); font-family: 'Poppins', sans-serif;">Administrator</p>
                </div>
                <div class="avatar"><?= substr($_SESSION['user_nama'], 0, 1) ?></div>
            </div>
        </div>

        <div class="bg-white border rounded-3 p-4 mb-4">
            <form method="GET" action="" class="d-flex flex-column flex-md-row gap-3 mb-4">
                <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" id="searchInput" class="search-input" placeholder="Cari berdasarkan Order ID, Nama, Barang, atau NPM..." value="<?= htmlspecialchars($search) ?>" oninput="toggleClearBtn()">
                    <button type="button" class="btn-clear" id="clearBtn" onclick="clearSearch()"><i class="fas fa-times" style="position:static; transform:none;"></i></button>
                </div>
                <button type="submit" class="btn-search text-poppins w-10 w-md-auto justify-content-center px-4"><i class="fas fa-search"></i> Cari</button>
            </form>

            <div class="d-flex flex-wrap gap-2 text-poppins">
                <a href="?status=all&q=<?= urlencode($search) ?>" class="tab-btn <?= $status_filter == 'all' ? 'active' : '' ?>">Semua (<?= $count_all ?>)</a>
                <a href="?status=request&q=<?= urlencode($search) ?>" class="tab-btn <?= $status_filter == 'request' ? 'active' : '' ?>">Pending (<?= $counts['request'] ?>)</a>
                <a href="?status=disetujui&q=<?= urlencode($search) ?>" class="tab-btn <?= $status_filter == 'disetujui' ? 'active' : '' ?>">Disetujui (<?= $counts['disetujui'] ?>)</a>
                <a href="?status=selesai&q=<?= urlencode($search) ?>" class="tab-btn <?= $status_filter == 'selesai' ? 'active' : '' ?>">Selesai (<?= $counts['selesai'] ?>)</a>
            </div>
        </div>

        <div class="d-flex flex-column gap-4">
            <?php if (empty($transaksi)): ?>
                <div class="text-center py-5 text-muted text-poppins">
                    <p class="mb-0 fw-medium">Hasil tidak ditemukan</p>
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
                <div class="trx-card p-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-4 border-bottom gap-3">
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                                <h3 class="fs-5 fw-bold text-dark mb-0 lh-1" style="font-family: 'Outfit', sans-serif;"><?= $order_id_str ?></h3>
                                <?= getBadgeTrx($t['status']) ?>
                            </div>
                            <div class="small text-muted text-poppins">Dibuat: <?= date('d M Y, H:i', strtotime($t['tanggal_pengajuan'])) ?></div>
                        </div>
                        <button class="btn-detail text-nowrap" onclick="openModal('modal_<?= $id_pem ?>')">
                            <i class="far fa-eye"></i> Lihat Detail
                        </button>
                    </div>
                    
                    <div class="row g-4 text-poppins">
                        <div class="col-sm-6 col-lg-3">
                            <div class="small text-muted fw-semibold text-uppercase mb-2" style="letter-spacing: 0.5px; font-size: 0.75rem;">Item</div>
                            <div class="fs-6 fw-semibold text-dark mb-0 lh-base"><?= htmlspecialchars(substr($t['items'] ?? '-', 0, 40)) ?><?= strlen($t['items'] ?? '') > 40 ? '...' : '' ?></div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="small text-muted fw-semibold text-uppercase mb-2" style="letter-spacing: 0.5px; font-size: 0.75rem;">Peminjam</div>
                            <div class="fs-6 fw-semibold text-dark mb-1"><?= htmlspecialchars($t['nama_lengkap']) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($t['npm']) ?></div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="small text-muted fw-semibold text-uppercase mb-2" style="letter-spacing: 0.5px; font-size: 0.75rem;">Periode Sewa</div>
                            <div class="fs-6 fw-semibold text-dark mb-1"><?= date('d M Y', strtotime($t['tanggal_pinjam'])) ?></div>
                            <div class="small text-muted">s/d <?= date('d M Y', strtotime($t['tanggal_kembali_rencana'])) ?></div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="small text-muted fw-semibold text-uppercase mb-2" style="letter-spacing: 0.5px; font-size: 0.75rem;">Total Pembayaran</div>
                            <div class="fs-5 fw-bold text-dark" style="font-family: 'Outfit', sans-serif;">Rp <?= number_format($t['total_biaya'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <div class="modal-overlay" id="modal_<?= $id_pem ?>">
                    <div class="modal-box p-4 p-md-5">
                        <button class="modal-close" onclick="closeModal('modal_<?= $id_pem ?>')"><i class="fas fa-times"></i></button>
                        <div class="mb-4 pb-3 border-bottom">
                            <h3 class="fs-4 fw-bold text-dark mb-2">Data Penyewaan</h3>
                            <p class="small text-muted mb-0 text-poppins">Perbarui status dan data penyewaan secara manual melalui form di bawah ini.</p>
                        </div>
                        
                        <div class="row g-4 text-poppins">
                            <div class="col-lg-6">
                                <div class="border rounded-3 p-4 mb-4 bg-white">
                                    <div class="fs-6 fw-bold text-dark mb-3 d-flex align-items-center gap-2" style="font-family: 'Outfit', sans-serif;"><i class="far fa-user text-muted"></i> Data Peminjam</div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-medium text-dark mb-1">Nama Lengkap</label>
                                        <div class="form-control-ro py-2"><?= htmlspecialchars($t['nama_lengkap']) ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-medium text-dark mb-1">NPM</label>
                                        <div class="form-control-ro py-2"><?= htmlspecialchars($t['npm']) ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-medium text-dark mb-1">Email</label>
                                        <div class="form-control-ro py-2"><?= htmlspecialchars($t['email'] ?? '-') ?></div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label small fw-medium text-dark mb-1">No. HP (WhatsApp)</label>
                                        <div class="form-control-ro py-2"><?= htmlspecialchars($t['no_telp']) ?></div>
                                    </div>
                                </div>

                                <div class="border rounded-3 p-4 bg-white">
                                    <div class="fs-6 fw-bold text-dark mb-3 d-flex align-items-center gap-2" style="font-family: 'Outfit', sans-serif;"><i class="far fa-id-card text-muted"></i> Foto KTM</div>
                                    <div class="ktm-box">
                                        <?php if($t['foto_ktm']): ?>
                                            <img src="../assets/img/ktm/<?= htmlspecialchars($t['foto_ktm']) ?>" alt="KTM">
                                        <?php else: ?>
                                            <p class="small text-muted mb-0">Tidak ada foto KTM</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="border rounded-3 p-4 mb-4 bg-white">
                                    <div class="fs-6 fw-bold text-dark mb-3 d-flex align-items-center gap-2" style="font-family: 'Outfit', sans-serif;"> Barang yang Dipinjam</div>
                                    <?php foreach($detail_barang as $brg): ?>
                                    <div class="border rounded-3 p-3 mb-2" style="background: #F0F9FF; border-color: #BAE6FD;">
                                        <h5 class="fs-6 fw-semibold mb-1" style="color: #0284C7; font-family: 'Outfit', sans-serif;"><?= htmlspecialchars($brg['nama_barang']) ?></h5>
                                        <p class="small text-muted mb-1">Tarif: Rp <?= number_format($brg['harga_sewa_per_hari'], 0, ',', '.') ?>/hari</p>
                                        <p class="small fw-medium text-dark mb-0 mt-2 pt-2 border-top" style="border-color: #BAE6FD !important;">Jumlah: <?= $brg['jumlah'] ?> unit</p>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="border rounded-3 p-4 mb-4 bg-white">
                                    <div class="fs-6 fw-bold text-dark mb-3 d-flex align-items-center gap-2" style="font-family: 'Outfit', sans-serif;"><i class="far fa-calendar-alt text-muted"></i> Periode Sewa</div>
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-medium text-dark mb-1">Tanggal Mulai</label>
                                            <div class="form-control-ro py-2 text-center fw-medium"><?= date('d M Y', strtotime($t['tanggal_pinjam'])) ?></div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-medium text-dark mb-1">Tanggal Selesai</label>
                                            <div class="form-control-ro py-2 text-center fw-medium"><?= date('d M Y', strtotime($t['tanggal_kembali_rencana'])) ?></div>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($t['status'] === 'request'): ?>
                                    <form method="POST" action="" id="form_setujui_<?= $id_pem ?>">
                                        <input type="hidden" name="id_peminjaman" value="<?= $id_pem ?>">
                                        <input type="hidden" name="aksi_status" value="setujui">
                                        
                                        <div class="border rounded-3 p-4" style="border-color: #22C55E !important; background:#F0FDF4;">
                                            <h4 class="fs-6 fw-bold mb-2 d-flex align-items-center gap-2" style="color: #166534; font-family: 'Outfit', sans-serif;"><i class="fas fa-check-circle"></i> Menunggu Persetujuan</h4>
                                            <p class="small mb-3" style="color: #15803D;">Klik tombol di bawah untuk menyetujui pengajuan ini dan mengirimkan instruksi via WhatsApp.</p>
                                            <button type="button" onclick="kirimWA('<?= $id_pem ?>', '<?= htmlspecialchars($t['no_telp']) ?>', '<?= htmlspecialchars(addslashes($t['nama_lengkap'])) ?>', '<?= htmlspecialchars(addslashes($t['items'] ?? 'Barang')) ?>')" class="btn-wa text-poppins"><i class="fab fa-whatsapp fs-5"></i> Setujui Pengajuan</button>
                                        </div>
                                    </form>
                                <?php elseif ($t['status'] === 'disetujui'): ?>
                                    <form method="POST" action="">
                                        <input type="hidden" name="id_peminjaman" value="<?= $id_pem ?>">
                                        <input type="hidden" name="aksi_status" value="selesai">
                                        
                                        <div class="border rounded-3 p-4" style="border-color: #3B82F6 !important; background:#EFF6FF;">
                                            <h4 class="fs-6 fw-bold mb-2 d-flex align-items-center gap-2" style="color: #1E40AF; font-family: 'Outfit', sans-serif;"><i class="fas fa-info-circle"></i> Barang Sedang Disewa</h4>
                                            <p class="small mb-3" style="color: #1D4ED8;">Jika barang sudah dikembalikan oleh peminjam, klik tombol di bawah untuk menyelesaikan transaksi.</p>
                                            <button type="submit" class="btn-done text-poppins"><i class="fas fa-check"></i> Tandai Telah Selesai</button>
                                        </div>
                                    </form>
                                <?php elseif ($t['status'] === 'selesai'): ?>
                                    <div class="border rounded-3 p-4 text-center" style="border-color: #E2E8F0 !important; background:#F8FAFC;">
                                        <i class="fas fa-check-circle mb-3" style="font-size: 3rem; color: #10B981;"></i>
                                        <h4 class="fs-6 fw-bold text-dark mb-2" style="font-family: 'Outfit', sans-serif;">Transaksi Selesai</h4>
                                        <p class="small text-muted mb-0">Penyewaan ini telah diselesaikan dan barang telah dikembalikan.</p>
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