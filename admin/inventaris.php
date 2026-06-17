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
        
        .toolbar-container { background: var(--white); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;}
        .search-form { display: flex; gap: 1rem; align-items: center; width: 50%; max-width: 450px;} 
        .search-box { position: relative; flex-grow: 1; }
        .search-box i.fa-search { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 0.9rem;}
        .btn-clear { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--muted); cursor: pointer; font-size: 1rem; display: none;}
        .btn-clear:hover { color: var(--text); }
        .search-input { width: 100%; padding: 0.75rem 2.5rem 0.75rem 2.5rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; color: var(--text); font-family: 'Poppins', sans-serif; outline: none; background: #F8FAFC; transition: 0.2s;}
        .search-input:focus { border-color: #1B6060; background: var(--white); }
        .btn-search { background: #1B6060; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 500; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s;}
        .btn-search:hover { background: #124040; }
        
        .btn-add { background: #1B6060; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 500; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s; white-space: nowrap;}
        .btn-add:hover { background: #124040; }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 1.5rem; }
        .stat-card { background: var(--white); padding: 1.2rem 1.5rem; border-radius: 12px; border: 1px solid var(--border); }
        .stat-card p { font-size: 0.85rem; font-weight: 500; margin-bottom: 0.5rem;}
        .stat-card h3 { font-family: 'Outfit', sans-serif; font-size: 1.8rem; }
        
        .c-blue p { color: #2563EB; } .c-blue h3 { color: #1E3A8A; }
        .c-green { background: #F0FDF4; border-color: #DCFCE7;} .c-green p { color: #16A34A; } .c-green h3 { color: #14532D; }
        .c-yellow { background: #FEFCE8; border-color: #FEF08A;} .c-yellow p { color: #D97706; } .c-yellow h3 { color: #78350F; }
        .c-purple p { color: #9333EA; } .c-purple h3 { color: #581C87; }

        .table-container { background: var(--white); border-radius: 12px; border: 1px solid var(--border); overflow-x: auto; padding: 1.5rem;}
        table { width: 100%; border-collapse: collapse; min-width: 900px;}
        th { font-size: 0.75rem; color: var(--muted); font-weight: 600; text-transform: uppercase; padding: 1rem 0.5rem; border-bottom: 1px solid var(--border); text-align: left; letter-spacing: 0.5px;}
        td { padding: 1.2rem 0.5rem; border-bottom: 1px solid #F1F5F9; vertical-align: middle; color: var(--text); font-size: 0.85rem;}
        
        .td-kode { font-weight: 600; font-family: 'Outfit', sans-serif;}
        .td-nama { font-weight: 500; color: var(--text); }
        .td-lokasi { font-size: 0.75rem; color: var(--muted); font-weight: 400; display: block; margin-top: 0.2rem;}
        
        .badge-cat { background: #E0F2FE; color: #0284C7; padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 500;}
        .badge-cond-baik { color: #16A34A; font-weight: 500;}
        .badge-cond-ringan { background: #FEF3C7; color: #D97706; padding: 4px 10px; border-radius: 50px; font-weight: 500; font-size: 0.75rem;}
        .badge-cond-berat { background: #DC2626; color: white; padding: 4px 10px; border-radius: 50px; font-weight: 500; font-size: 0.75rem;}
        
        .td-status { color: #16A34A; font-weight: 500; }
        .td-stok { font-weight: 600; font-size: 0.95rem; }
        
        .td-sewa { text-align: center;}
        .sewa-harga { background: #F1F5F9; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: inline-block;}

        .btn-action { background: none; border: none; color: var(--muted); font-size: 1rem; cursor: pointer; transition: 0.2s; padding: 0.3rem;}
        .btn-action:hover { color: var(--primary); }
        .btn-action.del:hover { color: #DC2626; }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); z-index: 2000; align-items: center; justify-content: center;}
        .modal-box { background: var(--white); width: 95%; max-width: 900px; border-radius: 16px; padding: 2rem; position: relative; max-height: 90vh; overflow-y: auto;}
        .modal-header { margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #F1F5F9; display: flex; justify-content: space-between; align-items: center;}
        .modal-header h3 { font-family: 'Outfit', sans-serif; font-size: 1.4rem; color: var(--text); font-weight: 600;}
        .modal-close { background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--muted); transition: 0.2s;}
        .modal-close:hover { color: var(--text); }
        
        .form-section { margin-bottom: 2rem;}
        .form-section-title { font-size: 1rem; font-weight: 600; color: var(--text); margin-bottom: 1rem; font-family: 'Outfit', sans-serif;}
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { display: block; font-size: 0.8rem; color: var(--text); margin-bottom: 0.5rem; font-weight: 500;}
        .form-control { width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; font-family: 'Poppins', sans-serif; background: #FAFAFA; transition: 0.2s;}
        .form-control:focus { outline: none; border-color: #1B6060; background: var(--white); }
        .form-control[readonly] { background: #F1F5F9; cursor: not-allowed; color: var(--muted);}
        textarea.form-control { resize: vertical; min-height: 80px; }
        
        .file-upload-box { border: 2px dashed var(--border); border-radius: 8px; padding: 2rem; text-align: center; background: #FAFAFA; cursor: pointer; transition: 0.2s;}
        .file-upload-box:hover { border-color: var(--primary); background: #F0FDF4; }
        .file-upload-box i { font-size: 2rem; color: var(--muted); margin-bottom: 0.5rem;}
        .file-upload-box p { font-size: 0.85rem; color: var(--muted);}
        
        .spec-item { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;}
        .btn-add-spec { background: none; border: none; color: var(--primary); font-weight: 500; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 5px; margin-top: 0.5rem; transition: 0.2s;}
        .btn-add-spec:hover { color: #1B6060; text-decoration: underline;}
        
        .modal-footer { display: flex; justify-content: flex-end; gap: 1rem; border-top: 1px solid var(--border); padding-top: 1.5rem;}
        .btn-cancel { padding: 0.75rem 1.5rem; border-radius: 8px; border: 1px solid var(--border); background: var(--white); color: var(--text); font-weight: 500; cursor: pointer; transition: 0.2s;}
        .btn-cancel:hover { background: #F1F5F9; }
        .btn-submit { padding: 0.75rem 2rem; border-radius: 8px; border: none; background: #1B6060; color: var(--white); font-weight: 500; cursor: pointer; transition: 0.2s;}
        .btn-submit:hover { background: #0F172A; }
        
        .empty-state { text-align: center; padding: 3rem 1rem; color: var(--muted); }
        
        .toast { position: fixed; top: 20px; right: 20px; background: var(--white); border-left: 4px solid var(--primary); padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 12px; z-index: 9999; transform: translateX(120%); transition: transform 0.3s ease; }
        .toast.show { transform: translateX(0); }
        .toast.error { border-left-color: #DC2626; }
        .toast i.success { color: var(--primary); font-size: 1.2rem;}
        .toast i.error { color: #DC2626; font-size: 1.2rem;}

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1.5rem; }
            .mobile-header { display: flex; }
            .top-navbar { display: none; }
            .toolbar-container { flex-direction: column; align-items: stretch; }
            .search-form { flex-direction: column; width: 100%; max-width: none;}
            .btn-add { padding: 0.8rem; justify-content: center; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .form-grid { grid-template-columns: 1fr; }
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
            <li><a href="inventaris.php" class="active"><i class="fas fa-cube"></i> Inventaris</a></li>
            <li><a href="penyewaan.php"><i class="fas fa-file-alt"></i> Transaksi</a></li>
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
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" id="searchInput" class="search-input" placeholder="Cari berdasarkan nama atau kode..." value="<?= htmlspecialchars($search) ?>" oninput="toggleClearBtn()">
                    <button type="button" class="btn-clear" id="clearBtn" onclick="clearSearch()"><i class="fas fa-times" style="position:static; transform:none;"></i></button>
                </div>
                <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
            </form>
            <button type="button" class="btn-add" onclick="openModal('add')"><i class="fas fa-plus"></i> Tambah Barang</button>
        </div>

        <div class="stats-grid">
            <div class="stat-card c-blue">
                <p>Total Barang</p>
                <h3><?= $stat_total ?></h3>
            </div>
            <div class="stat-card c-green">
                <p>Tersedia</p>
                <h3><?= $stat_tersedia ?></h3>
            </div>
            <div class="stat-card c-yellow">
                <p>Perlu Perbaikan</p>
                <h3><?= $stat_perbaikan ?></h3>
            </div>
            <div class="stat-card c-purple">
                <p>Total Stok</p>
                <h3><?= $stat_stok ?></h3>
            </div>
        </div>

        <div class="table-container">
            <?php if (empty($inventaris)): ?>
                <div class="empty-state">Belum ada data barang atau hasil pencarian tidak ditemukan.</div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Kondisi</th>
                        <th>Status</th>
                        <th style="text-align:center;">Stok</th>
                        <th style="text-align:center;">Harga Sewa</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($inventaris as $inv): 
                        $kode = "INV" . str_pad($inv['id_inventaris'], 3, '0', STR_PAD_LEFT);
                    ?>
                    <tr>
                        <td class="td-kode"><?= $kode ?></td>
                        <td>
                            <span class="td-nama"><?= htmlspecialchars($inv['nama_barang']) ?></span>
                            <span class="td-lokasi">Lokasi: <?= htmlspecialchars($inv['lokasi'] ?? '-') ?></span>
                        </td>
                        <td><span class="badge-cat"><?= htmlspecialchars($map_kategori[$inv['id_kategori']] ?? 'Tidak ada') ?></span></td>
                        <td>
                            <?php 
                                if($inv['kondisi_barang'] == 'Baik') echo '<span class="badge-cond-baik">Baik</span>';
                                elseif($inv['kondisi_barang'] == 'Rusak Ringan') echo '<span class="badge-cond-ringan">Rusak Ringan</span>';
                                else echo '<span class="badge-cond-berat">Rusak Berat</span>';
                            ?>
                        </td>
                        <td><span class="td-status"><?= htmlspecialchars($inv['status_barang'] ?? 'Tersedia') ?></span></td>
                        <td class="td-stok" style="text-align:center;"><?= $inv['stok'] ?></td>
                        <td class="td-sewa">
                            <span class="sewa-harga">Rp <?= number_format($inv['harga_sewa_per_hari'],0,',','.') ?>/hari</span>
                        </td>
                        <td style="text-align:center;">
                            <button class="btn-action" onclick='openEdit(<?= json_encode($inv) ?>)'><i class="far fa-edit"></i></button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Apakah anda ingin menghapus barang ini?');">
                                <input type="hidden" name="id_inventaris" value="<?= $inv['id_inventaris'] ?>">
                                <button type="submit" name="hapus_barang" class="btn-action del"><i class="far fa-trash-alt"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </main>

    <div class="modal-overlay" id="modalForm">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="modalTitle">Tambah Barang</h3>
                <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            
            <form method="POST" action="" id="inventarisForm" enctype="multipart/form-data">
                <input type="hidden" name="id_inventaris" id="form_id" value="">
                <input type="hidden" name="gambar_lama" id="form_gambar_lama" value="">

                <div class="form-section">
                    <h4 class="form-section-title">Data Dasar</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Kode Barang *</label>
                            <input type="text" class="form-control" id="form_kode" readonly placeholder="(Otomatis)">
                        </div>
                        <div class="form-group">
                            <label>Nama Barang *</label>
                            <input type="text" name="nama_barang" class="form-control" id="form_nama" required>
                        </div>
                        <div class="form-group">
                            <label>Kategori *</label>
                            <select name="id_kategori" class="form-control" id="form_kategori" required>
                                <?php foreach($kategori_data as $kat): ?>
                                    <option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Kondisi *</label>
                            <select name="kondisi_barang" class="form-control" id="form_kondisi" required>
                                <option value="Baik">Baik</option>
                                <option value="Rusak Ringan">Rusak Ringan</option>
                                <option value="Rusak Berat">Rusak Berat</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4 class="form-section-title">Lokasi & Stok</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Lokasi Penyimpanan *</label>
                            <input type="text" name="lokasi" class="form-control" id="form_lokasi" required>
                        </div>
                        <div class="form-group">
                            <label>Jumlah/Stok *</label>
                            <input type="number" name="stok" class="form-control" id="form_stok" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Status Barang *</label>
                            <select name="status_barang" class="form-control" id="form_status">
                                <option value="Tersedia">Tersedia</option>
                                <option value="Tidak Tersedia">Tidak Tersedia</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Harga Sewa (per hari)</label>
                            <input type="number" name="harga_sewa" class="form-control" id="form_harga" min="0" value="0">
                        </div>

                        <div class="form-group">
                            <label>Foto Barang (Max 5MB)</label>
                            
                            <div id="imagePreviewContainer" style="margin-bottom: 1.2rem; display: none; text-align: center; background: #FAFAFA; padding: 10px; border-radius: 8px; border: 1px dashed var(--border);">
                                <p style="font-size: 0.75rem; color: var(--muted); margin-bottom: 0.5rem; font-weight: 500;">Gambar Saat Ini:</p>
                                <img id="imagePreview" src="" alt="Preview" style="max-width: 100%; height: 120px; border-radius: 6px; object-fit: contain;">
                            </div>
                            
                            <div class="file-upload-box" onclick="document.getElementById('fileInput').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Klik untuk upload gambar (JPG/PNG)</p>
                            </div>
                            <input type="file" name="foto" id="fileInput" accept="image/png, image/jpeg, image/jpg" style="display:none;">
                            <p id="fileNameDisplay" style="font-size:0.75rem; color:var(--primary); margin-top:0.5rem; text-align:center;"></p>
                        </div>
                        
                        <div class="form-group full">
                            <label>Deskripsi Barang</label>
                            <textarea name="deskripsi" class="form-control" id="form_deskripsi" placeholder="Tuliskan deskripsi umum barang..."></textarea>
                        </div>

                        <div class="form-group full">
                            <label>Spesifikasi Barang</label>
                            <div id="specContainer">
                                </div>
                            <button type="button" class="btn-add-spec" onclick="addSpec('', true)"><i class="fas fa-plus"></i> Tambah Spesifikasi</button>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                    <button type="submit" name="simpan_barang" class="btn-submit" id="btnSubmit">Simpan Perubahan</button>
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

        // VALIDASI FILE SIZE DI SISI JAVASCRIPT
        document.getElementById('fileInput').addEventListener('change', function(e) {
            if(e.target.files.length > 0) {
                const file = e.target.files[0];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if(file.size > maxSize) {
                    alert('Gagal: Ukuran file foto maksimal 5MB!');
                    this.value = ''; // Reset input
                    document.getElementById('fileNameDisplay').textContent = '';
                } else {
                    document.getElementById('fileNameDisplay').textContent = "File terpilih: " + file.name;
                }
            }
        });

        const modal = document.getElementById('modalForm');

        function openModal(mode) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            if(mode === 'add') {
                document.getElementById('modalTitle').textContent = 'Tambah Barang';
                
                document.getElementById('imagePreviewContainer').style.display = 'none';
                document.getElementById('imagePreview').src = '';
                
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
            const previewCont = document.getElementById('imagePreviewContainer');
            if (data.gambar && data.gambar !== '') {
                previewImg.src = "../assets/img/inventaris/" + data.gambar;
                previewCont.style.display = "block";
            } else {
                previewCont.style.display = "none";
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
            div.className = 'spec-item';
            
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'spesifikasi[]';
            input.className = 'form-control';
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
            btnDel.className = 'btn-action del';
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