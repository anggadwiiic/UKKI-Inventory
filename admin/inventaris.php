<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    exit('Akses Ditolak.');
}

$kategori_data = query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
$map_kategori = [];
foreach($kategori_data as $kat) {
    $map_kategori[$kat['id_kategori']] = $kat['nama_kategori'];
}

if (isset($_POST['hapus_barang'])) {
    $id_hapus = (int)$_POST['id_inventaris'];
    $dt = query("SELECT gambar FROM inventaris WHERE id_inventaris = $id_hapus")[0];
    if ($dt['gambar'] && file_exists("../assets/img/inventaris/" . $dt['gambar'])) {
        unlink("../assets/img/inventaris/" . $dt['gambar']);
    }
    if (mysqli_query($conn, "DELETE FROM inventaris WHERE id_inventaris = $id_hapus")) {
        $_SESSION['toast_msg'] = "Barang berhasil dihapus!";
        $_SESSION['toast_type'] = "success";
    }
    header("Location: inventaris.php");
    exit;
}

if (isset($_POST['simpan_barang'])) {
    $id_inv = isset($_POST['id_inventaris']) ? (int)$_POST['id_inventaris'] : 0;
    $nama = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $kategori = (int)$_POST['id_kategori'];
    $kondisi = mysqli_real_escape_string($conn, $_POST['kondisi_barang']);
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $stok = (int)$_POST['stok'];
    $status = mysqli_real_escape_string($conn, $_POST['status_barang']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $harga = (int)$_POST['harga_sewa'];
    
    $specs = [];
    if(isset($_POST['spesifikasi'])) {
        foreach($_POST['spesifikasi'] as $sp) {
            if(trim($sp) !== '') $specs[] = mysqli_real_escape_string($conn, trim($sp));
        }
    }
    $spesifikasi_json = json_encode($specs);

    $gambar = $_POST['gambar_lama'] ?? '';
    $upload_error = false;

    // VALIDASI UPLOAD FILE DI SISI PHP (5MB = 5242880 bytes)
    if (isset($_FILES['foto']['name']) && $_FILES['foto']['name'] != '') {
        if ($_FILES['foto']['size'] > 5242880) {
            $_SESSION['toast_msg'] = "Gagal menyimpan: Ukuran foto maksimal 5MB!";
            $_SESSION['toast_type'] = "error";
            $upload_error = true;
        } else {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $gambar = time() . '_' . rand(100,999) . '.' . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], "../assets/img/inventaris/" . $gambar);
            if ($id_inv > 0 && $_POST['gambar_lama'] && file_exists("../assets/img/inventaris/" . $_POST['gambar_lama'])) {
                unlink("../assets/img/inventaris/" . $_POST['gambar_lama']);
            }
        }
    }

    if (!$upload_error) {
        if ($id_inv > 0) {
            $q = "UPDATE inventaris SET id_kategori=$kategori, nama_barang='$nama', stok=$stok, kondisi_barang='$kondisi', harga_sewa_per_hari=$harga, gambar='$gambar', deskripsi='$deskripsi', lokasi='$lokasi', status_barang='$status', spesifikasi='$spesifikasi_json' WHERE id_inventaris=$id_inv";
        } else {
            $q = "INSERT INTO inventaris (id_kategori, nama_barang, stok, kondisi_barang, harga_sewa_per_hari, gambar, deskripsi, lokasi, status_barang, spesifikasi) VALUES ($kategori, '$nama', $stok, '$kondisi', $harga, '$gambar', '$deskripsi', '$lokasi', '$status', '$spesifikasi_json')";
        }

        if (mysqli_query($conn, $q)) {
            $_SESSION['toast_msg'] = "Data barang berhasil disimpan!";
            $_SESSION['toast_type'] = "success";
        } else {
            $_SESSION['toast_msg'] = "Gagal menyimpan: " . mysqli_error($conn);
            $_SESSION['toast_type'] = "error";
        }
    }
    
    header("Location: inventaris.php");
    exit;
}

$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
$where_clauses = [];
if ($search !== '') {
    $search_id = (int)preg_replace('/[^0-9]/', '', $search);
    $id_clause = $search_id > 0 ? "OR i.id_inventaris = $search_id" : "";
    $where_clauses[] = "(i.nama_barang LIKE '%$search%' OR i.lokasi LIKE '%$search%' OR k.nama_kategori LIKE '%$search%' $id_clause)";
}
$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

$query_inv = "SELECT i.* FROM inventaris i LEFT JOIN kategori k ON i.id_kategori = k.id_kategori $where_sql ORDER BY i.id_inventaris DESC";
$inventaris = query($query_inv);

$stat_total = count($inventaris);
$stat_tersedia = 0;
$stat_perbaikan = 0;
$stat_stok = 0;

foreach($inventaris as $inv) {
    if($inv['status_barang'] == 'Tersedia') $stat_tersedia++;
    if(in_array($inv['kondisi_barang'], ['Rusak Ringan', 'Rusak Berat'])) $stat_perbaikan++;
    $stat_stok += (int)$inv['stok'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Inventaris</title>
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
        .search-input { width: 100%; padding: 0.75rem 2.5rem 0.75rem 2.5rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; color: var(--text); font-family: 'Poppins', sans-serif; outline: none; background: var(--white); transition: 0.2s;}
        .search-input:focus { border-color: #1B6060; box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1); }
        .btn-search { background: #1B6060; color: white; border: none; padding: 0 1.2rem; border-radius: 8px; font-weight: 500; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s;}
        .btn-search:hover { background: #124040; }
        .btn-add { background: #1B6060; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 500; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s; white-space: nowrap;}
        .btn-add:hover { background: #124040; }

        /* STATS (Identical Kalibrasi) */
        .stat-card { background: var(--white); border-radius: 12px; border: 1px solid var(--border); padding: 1.5rem; }
        .c-blue p { color: #2563EB; font-weight: 500; font-size: 0.85rem;} .c-blue h3 { color: #1E3A8A; font-size: 2rem;}
        .c-green { background: #F0FDF4; border-color: #DCFCE7;} .c-green p { color: #16A34A; font-weight: 500; font-size: 0.85rem;} .c-green h3 { color: #14532D; font-size: 2rem;}
        .c-yellow { background: #FEFCE8; border-color: #FEF08A;} .c-yellow p { color: #D97706; font-weight: 500; font-size: 0.85rem;} .c-yellow h3 { color: #78350F; font-size: 2rem;}
        .c-purple p { color: #9333EA; font-weight: 500; font-size: 0.85rem;} .c-purple h3 { color: #581C87; font-size: 2rem;}

        /* TABLE */
        .table-container { background: var(--white); border-radius: 12px; border: 1px solid var(--border); overflow-x: auto;}
        .table th { border-bottom: 1px solid var(--border) !important;}
        .table td { border-bottom: 1px solid #F1F5F9; vertical-align: middle;}
        
        .badge-cat { background: #E0F2FE; color: #0284C7; padding: 6px 14px; border-radius: 50px; font-size: 0.8rem; font-weight: 500; display: inline-block;}
        .badge-cond-baik { color: #16A34A; font-weight: 600; font-size: 0.85rem;}
        .badge-cond-ringan { color: #D97706; font-weight: 600; font-size: 0.85rem;}
        .badge-cond-berat { color: #DC2626; font-weight: 600; font-size: 0.85rem;}
        
        .sewa-harga { background: #F1F5F9; color: #475569; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; display: inline-block;}

        .btn-action { background: none; border: none; color: var(--muted); font-size: 1rem; cursor: pointer; transition: 0.2s; padding: 0.4rem; display: inline-flex; align-items: center; justify-content: center;}
        .btn-action:hover { color: var(--primary); }
        .btn-action.del:hover { color: #DC2626; }

        /* MODAL */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); z-index: 2000; align-items: center; justify-content: center;}
        .modal-box { background: var(--white); width: 95%; max-width: 900px; border-radius: 16px; position: relative; max-height: 90vh; overflow-y: auto;}
        .modal-close { position: absolute; top: 1.5rem; right: 1.5rem; background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--muted); transition: 0.2s;}
        .modal-close:hover { color: var(--text); }
        
        .form-control-custom { font-family: 'Poppins', sans-serif; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; background: #FAFAFA; transition: 0.2s; padding: 0.75rem 1rem; width: 100%;}
        .form-control-custom:focus { outline: none; border-color: #1B6060; background: var(--white); }
        .form-control-custom[readonly] { background: #F1F5F9; cursor: not-allowed; color: var(--muted);}
        textarea.form-control-custom { resize: vertical; min-height: 80px; }
        
        .file-upload-box { border: 2px dashed var(--border); border-radius: 8px; padding: 2rem; text-align: center; background: #FAFAFA; cursor: pointer; transition: 0.2s;}
        .file-upload-box:hover { border-color: var(--primary); background: #F0FDF4; }
        
        .btn-add-spec { background: none; border: none; color: var(--primary); font-weight: 500; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 5px; margin-top: 0.5rem; transition: 0.2s;}
        .btn-add-spec:hover { color: #1B6060; text-decoration: underline;}
        
        .btn-cancel { padding: 0.75rem 1.5rem; border-radius: 8px; border: 1px solid var(--border); background: var(--white); color: var(--text); font-weight: 500; cursor: pointer; transition: 0.2s; font-family: 'Outfit', sans-serif;}
        .btn-cancel:hover { background: #F1F5F9; }
        .btn-submit { padding: 0.75rem 2rem; border-radius: 8px; border: none; background: #1B6060; color: var(--white); font-weight: 500; cursor: pointer; transition: 0.2s; font-family: 'Outfit', sans-serif;}
        .btn-submit:hover { background: #0F172A; }
        
        /* TOAST */
        .toast-box { position: fixed; top: 20px; right: 20px; background: var(--white); border-left: 4px solid var(--primary); padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 12px; z-index: 9999; transform: translateX(120%); transition: transform 0.3s ease; font-family: 'Poppins', sans-serif;}
        .toast-box.show { transform: translateX(0); }
        .toast-box.error { border-left-color: #DC2626; }
        .toast-box i.success { color: var(--primary); font-size: 1.2rem;}
        .toast-box i.error { color: #DC2626; font-size: 1.2rem;}

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1.5rem; }
        }
    </style>
</head>
<body>

    <?php if(isset($_SESSION['toast_msg'])): ?>
    <div class="toast-box <?= $_SESSION['toast_type'] ?>" id="toastBox">
        <i class="fas <?= $_SESSION['toast_type'] == 'success' ? 'fa-check-circle success' : 'fa-exclamation-circle error' ?>"></i>
        <div class="toast-msg" style="font-size: 0.95rem;"><?= $_SESSION['toast_msg'] ?></div>
    </div>
    <script>
        setTimeout(() => { document.getElementById('toastBox').classList.add('show'); }, 100);
        setTimeout(() => { document.getElementById('toastBox').classList.remove('show'); }, 3000);
    </script>
    <?php unset($_SESSION['toast_msg']); unset($_SESSION['toast_type']); endif; ?>

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
            <li><a href="inventaris.php" class="active"><i class="fas fa-cube"></i> Inventaris</a></li>
            <li><a href="penyewaan.php"><i class="fas fa-file-alt"></i> Transaksi</a></li>
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

        <div class="bg-white border rounded-3 p-3 p-md-4 mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <form method="GET" action="" class="d-flex gap-2 w-100" style="max-width: 450px;">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" id="searchInput" class="search-input" placeholder="Cari berdasarkan nama atau kode..." value="<?= htmlspecialchars($search) ?>" oninput="toggleClearBtn()">
                    <button type="button" class="btn-clear" id="clearBtn" onclick="clearSearch()"><i class="fas fa-times" style="position:static; transform:none;"></i></button>
                </div>
                <button type="submit" class="btn-search text-poppins px-3"><i class="fas fa-search"></i></button>
            </form>
            <button type="button" class="btn-add w-10 w-md-auto text-poppins" onclick="openModal('add')"><i class="fas fa-plus"></i> Tambah Barang</button>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="stat-card c-blue">
                    <p class="text-poppins mb-1">Total Barang</p>
                    <h3 class="mb-0 fw-bold"><?= $stat_total ?></h3>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="stat-card c-green">
                    <p class="text-poppins mb-1">Tersedia</p>
                    <h3 class="mb-0 fw-bold"><?= $stat_tersedia ?></h3>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="stat-card c-yellow">
                    <p class="text-poppins mb-1">Perlu Perbaikan</p>
                    <h3 class="mb-0 fw-bold"><?= $stat_perbaikan ?></h3>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="stat-card c-purple">
                    <p class="text-poppins mb-1">Total Stok</p>
                    <h3 class="mb-0 fw-bold"><?= $stat_stok ?></h3>
                </div>
            </div>
        </div>

        <div class="table-container p-4 text-poppins">
            <?php if (empty($inventaris)): ?>
                <div class="text-center py-5 text-muted">Belum ada data barang atau hasil pencarian tidak ditemukan.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-borderless align-middle mb-0" style="min-width: 900px;">
                    <thead>
                        <tr>
                            <th class="small fw-semibold text-uppercase text-muted pb-3" style="letter-spacing: 0.5px;">Kode</th>
                            <th class="small fw-semibold text-uppercase text-muted pb-3" style="letter-spacing: 0.5px;">Nama Barang</th>
                            <th class="small fw-semibold text-uppercase text-muted pb-3" style="letter-spacing: 0.5px;">Kategori</th>
                            <th class="small fw-semibold text-uppercase text-muted pb-3" style="letter-spacing: 0.5px;">Kondisi</th>
                            <th class="small fw-semibold text-uppercase text-muted pb-3" style="letter-spacing: 0.5px;">Status</th>
                            <th class="small fw-semibold text-uppercase text-muted text-center pb-3" style="letter-spacing: 0.5px;">Stok</th>
                            <th class="small fw-semibold text-uppercase text-muted pb-3" style="letter-spacing: 0.5px;">Harga Sewa</th>
                            <th class="small fw-semibold text-uppercase text-muted text-center pb-3" style="letter-spacing: 0.5px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($inventaris as $inv): 
                            $kode = "INV" . str_pad($inv['id_inventaris'], 3, '0', STR_PAD_LEFT);
                        ?>
                        <tr>
                            <td class="fw-bold text-dark" style="font-family: 'Outfit', sans-serif; font-size: 0.95rem;"><?= $kode ?></td>
                            <td class="py-3">
                                <span class="fw-semibold text-dark d-block" style="font-size: 0.95rem;"><?= htmlspecialchars($inv['nama_barang']) ?></span>
                                <span class="small text-muted d-block mt-1">Lokasi: <?= htmlspecialchars($inv['lokasi'] ?? '-') ?></span>
                            </td>
                            <td><span class="badge-cat"><?= htmlspecialchars($map_kategori[$inv['id_kategori']] ?? 'Tidak ada') ?></span></td>
                            <td>
                                <?php 
                                    if($inv['kondisi_barang'] == 'Baik') echo '<span class="badge-cond-baik">Baik</span>';
                                    elseif($inv['kondisi_barang'] == 'Rusak Ringan') echo '<span class="badge-cond-ringan">Rusak Ringan</span>';
                                    else echo '<span class="badge-cond-berat">Rusak Berat</span>';
                                ?>
                            </td>
                            <td><span class="fw-semibold text-success" style="font-size: 0.9rem;"><?= htmlspecialchars($inv['status_barang'] ?? 'Tersedia') ?></span></td>
                            <td class="fw-bold text-center fs-5 text-dark" style="font-family: 'Outfit', sans-serif;"><?= $inv['stok'] ?></td>
                            <td>
                                <span class="sewa-harga text-poppins">Rp <?= number_format($inv['harga_sewa_per_hari'],0,',','.') ?>/hari</span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <button class="btn-action" onclick='openEdit(<?= json_encode($inv) ?>)'><i class="far fa-edit"></i></button>
                                    <form method="POST" class="d-inline m-0" onsubmit="return confirm('Apakah anda ingin menghapus barang ini?');">
                                        <input type="hidden" name="id_inventaris" value="<?= $inv['id_inventaris'] ?>">
                                        <button type="submit" name="hapus_barang" class="btn-action del"><i class="far fa-trash-alt"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <div class="modal-overlay" id="modalForm">
        <div class="modal-box p-4 p-md-5">
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            <div class="border-bottom pb-3 mb-4">
                <h3 class="fs-4 fw-bold text-dark mb-0" id="modalTitle" style="font-family: 'Outfit', sans-serif;">Tambah Barang</h3>
            </div>
            
            <form method="POST" action="" id="inventarisForm" enctype="multipart/form-data" class="text-poppins">
                <input type="hidden" name="id_inventaris" id="form_id" value="">
                <input type="hidden" name="gambar_lama" id="form_gambar_lama" value="">

                <div class="mb-5">
                    <h4 class="fs-6 fw-bold text-dark mb-3" style="font-family: 'Outfit', sans-serif;">Data Dasar</h4>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-medium text-dark mb-1">Kode Barang *</label>
                            <input type="text" class="form-control form-control-custom" id="form_kode" readonly placeholder="(Otomatis)">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-medium text-dark mb-1">Nama Barang *</label>
                            <input type="text" name="nama_barang" class="form-control form-control-custom" id="form_nama" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-medium text-dark mb-1">Kategori *</label>
                            <select name="id_kategori" class="form-select form-control-custom" id="form_kategori" required>
                                <?php foreach($kategori_data as $kat): ?>
                                    <option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-medium text-dark mb-1">Kondisi *</label>
                            <select name="kondisi_barang" class="form-select form-control-custom" id="form_kondisi" required>
                                <option value="Baik">Baik</option>
                                <option value="Rusak Ringan">Rusak Ringan</option>
                                <option value="Rusak Berat">Rusak Berat</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h4 class="fs-6 fw-bold text-dark mb-3" style="font-family: 'Outfit', sans-serif;">Lokasi & Stok</h4>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-medium text-dark mb-1">Lokasi Penyimpanan *</label>
                            <input type="text" name="lokasi" class="form-control form-control-custom" id="form_lokasi" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-medium text-dark mb-1">Jumlah/Stok *</label>
                            <input type="number" name="stok" class="form-control form-control-custom" id="form_stok" min="0" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-medium text-dark mb-1">Status Barang *</label>
                            <select name="status_barang" class="form-select form-control-custom" id="form_status">
                                <option value="Tersedia">Tersedia</option>
                                <option value="Tidak Tersedia">Tidak Tersedia</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-medium text-dark mb-1">Harga Sewa (per hari)</label>
                            <input type="number" name="harga_sewa" class="form-control form-control-custom" id="form_harga" min="0" value="0">
                        </div>
                            
                        <div class="col-sm-6">
                            <label class="form-label small fw-medium text-dark mb-1">Foto Barang (Max 5MB)</label>
                            <div class="file-upload-box d-flex flex-column justify-content-center align-items-center" onclick="document.getElementById('fileInput').click()" style="min-height: 150px;">
                                <div id="uploadPrompt">
                                    <i class="fas fa-cloud-upload-alt fs-1 text-muted mb-2"></i>
                                    <p class="small text-muted mb-0">Klik untuk upload gambar (JPG/PNG)</p>
                                </div>
                                <img id="imagePreview" src="" alt="Preview" class="rounded object-fit-contain w-100" style="display: none; max-height: 160px;">
                            </div>
                            <input type="file" name="foto" id="fileInput" accept="image/png, image/jpeg, image/jpg" class="d-none">
                            <p id="fileNameDisplay" class="small text-center mt-2 mb-0" style="color: var(--primary);"></p>
                        </div>
                        
                        <div class="col-sm-6 d-flex flex-column">
                            <label class="form-label small fw-medium text-dark mb-1">Deskripsi Barang</label>
                            <textarea name="deskripsi" class="form-control form-control-custom flex-grow-1" id="form_deskripsi" placeholder="Tuliskan deskripsi umum barang..."></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-medium text-dark mb-1">Spesifikasi Barang</label>
                            <div id="specContainer" class="d-flex flex-column gap-2 mb-2"></div>
                            <button type="button" class="btn-add-spec text-poppins" onclick="addSpec('', true)"><i class="fas fa-plus"></i> Tambah Spesifikasi</button>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 pt-4 border-top mt-4">
                    <button type="button" class="btn-cancel text-poppins" onclick="closeModal()">Batal</button>
                    <button type="submit" name="simpan_barang" class="btn-submit text-poppins" id="btnSubmit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleMenu() {
            sidebar.classList.toggle('active');
            overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
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
        
        window.onload = function() { toggleClearBtn(); };

        // VALIDASI FILE SIZE
        document.getElementById('fileInput').addEventListener('change', function(e) {
            if(e.target.files.length > 0) {
                const file = e.target.files[0];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if(file.size > maxSize) {
                    alert('Gagal: Ukuran file foto maksimal 5MB!');
                    this.value = ''; 
                    document.getElementById('fileNameDisplay').textContent = '';
                    // Kembali ke State 1
                    document.getElementById('imagePreview').style.display = 'none';
                    document.getElementById('uploadPrompt').style.display = 'block';
                } else {
                    document.getElementById('fileNameDisplay').textContent = "" + file.name;
                    
                    // Ke State 2 (Tampilkan Gambar, Sembunyikan Tulisan)
                    const fileURL = URL.createObjectURL(file);
                    document.getElementById('imagePreview').src = fileURL;
                    document.getElementById('imagePreview').style.display = 'block';
                    document.getElementById('uploadPrompt').style.display = 'none';
                }
            }
        });

        const modal = document.getElementById('modalForm');

        function openModal(mode) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            if(mode === 'add') {
                document.getElementById('modalTitle').textContent = 'Tambah Barang';
                
                document.getElementById('imagePreview').style.display = 'none';
                document.getElementById('imagePreview').src = '';
                document.getElementById('uploadPrompt').style.display = 'block';
                document.getElementById('fileNameDisplay').textContent = '';
                document.getElementById('fileInput').value = '';
                
                document.getElementById('form_id').value = '';
                document.getElementById('form_kode').value = '';
                document.getElementById('form_nama').value = '';
                document.getElementById('form_stok').value = '';
                document.getElementById('form_lokasi').value = '';
                document.getElementById('form_deskripsi').value = '';
                document.getElementById('form_harga').value = '0';
                document.getElementById('fileNameDisplay').textContent = '';
                document.getElementById('fileInput').value = '';
                
                document.getElementById('specContainer').innerHTML = '';
                addSpec('', false); 
            }
        }

        function openEdit(data) {
            openModal('edit');
            document.getElementById('modalTitle').textContent = 'Edit Barang';
        
            const previewImg = document.getElementById('imagePreview');
            const uploadPrompt = document.getElementById('uploadPrompt');
            
            if (data.gambar && data.gambar !== '') {
                previewImg.src = "../assets/img/inventaris/" + data.gambar;
                previewImg.style.display = "block";
                uploadPrompt.style.display = "none";
            } else {
                previewImg.style.display = "none";
                uploadPrompt.style.display = "block";
            }
            
            document.getElementById('form_id').value = data.id_inventaris;
            document.getElementById('form_kode').value = 'INV' + String(data.id_inventaris).padStart(3, '0');
            document.getElementById('form_nama').value = data.nama_barang;
            document.getElementById('form_kategori').value = data.id_kategori;
            document.getElementById('form_kondisi').value = data.kondisi_barang;
            document.getElementById('form_lokasi').value = data.lokasi || '';
            document.getElementById('form_stok').value = data.stok;
            document.getElementById('form_status').value = data.status_barang || 'Tersedia';
            document.getElementById('form_harga').value = data.harga_sewa_per_hari;
            document.getElementById('form_deskripsi').value = data.deskripsi || '';
            document.getElementById('form_gambar_lama').value = data.gambar || '';
            document.getElementById('fileInput').value = '';
            
            document.getElementById('fileNameDisplay').textContent = data.gambar ? "Gambar saat ini: " + data.gambar : "";

            document.getElementById('specContainer').innerHTML = '';
            if (data.spesifikasi && data.spesifikasi !== 'null' && data.spesifikasi !== '') {
                try {
                    let specs = JSON.parse(data.spesifikasi);
                    if(specs.length > 0) {
                        specs.forEach(s => addSpec(s, false));
                    } else {
                        addSpec('', false);
                    }
                } catch(e) {
                    addSpec('', false);
                }
            } else {
                addSpec('', false);
            }
        }

        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function addSpec(val, autoFocus = true) {
            const container = document.getElementById('specContainer');
            const div = document.createElement('div');
            div.className = 'd-flex align-items-center gap-2 w-100';
            
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'spesifikasi[]';
            input.className = 'form-control form-control-custom w-100 m-0';
            input.placeholder = 'Contoh: Kabel HDMI';
            input.value = val;
            
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addSpec('', true);
                }
            });

            const btnDel = document.createElement('button');
            btnDel.type = 'button';
            btnDel.className = 'btn border-0 text-danger p-2';
            btnDel.innerHTML = '<i class="far fa-trash-alt"></i>';
            btnDel.onclick = function() {
                container.removeChild(div);
            };

            div.appendChild(input);
            div.appendChild(btnDel);
            container.appendChild(div);
            
            if(autoFocus) {
                input.focus();
            }
        }
    </script>
</body>
</html>