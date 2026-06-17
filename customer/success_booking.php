<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: ../index.php");
    exit;
}

$id_pem = (int)$_GET['id'];
$id_user = $_SESSION['user_id'];

$q = "SELECT p.*, i.nama_barang, u.nama_lengkap, u.username 
      FROM peminjaman p 
      JOIN detail_peminjaman d ON p.id_peminjaman = d.id_peminjaman 
      JOIN inventaris i ON d.id_inventaris = i.id_inventaris 
      JOIN users u ON p.id_user = u.id_user 
      WHERE p.id_peminjaman = $id_pem AND p.id_user = $id_user";
$res = mysqli_query($conn, $q);
$data = mysqli_fetch_assoc($res);

if (!$data) {
    header("Location: ../index.php");
    exit;
}

$date1 = new DateTime($data['tanggal_pinjam']);
$date2 = new DateTime($data['tanggal_kembali_rencana']);
$durasi = max(1, $date1->diff($date2)->days);

// Dinamisasi Status
$status_text = 'Menunggu Konfirmasi';
$status_color = '#d97706'; // Amber

switch($data['status']) {
    case 'request': 
        $status_text = 'Menunggu Konfirmasi'; 
        $status_color = '#d97706'; // Kuning/Amber
        break;
    case 'disetujui': 
        $status_text = 'Disetujui / Silakan Diambil'; 
        $status_color = '#059669'; // Hijau
        break;
    case 'selesai': 
        $status_text = 'Transaksi Selesai'; 
        $status_color = '#2563EB'; // Biru
        break;
}

$wa_text = "Halo Admin, saya " . $data['nama_lengkap'] . " dengan NPM " . $data['username'] . " baru saja menyewa " . $data['nama_barang'] . " dengan durasi " . $durasi . " hari mulai tanggal " . date('d/m/Y', strtotime($data['tanggal_pinjam'])) . " hingga " . date('d/m/Y', strtotime($data['tanggal_kembali_rencana'])) . ". Mohon di-acc ya!";
$wa_link = "https://wa.me/6289677778190?text=" . urlencode($wa_text);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Berhasil - UKKI</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Aclonica&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../assets/img/logo-ukki.png">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: #F8FAFC; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            padding: 1.5rem;
        }
        .card { 
            background-color: #FFFFFF; 
            width: 100%; 
            max-width: 400px; 
            padding: 3rem; 
            border-radius: 16px; 
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); 
            border: 1px solid #E2E8F0;
            text-align: center;
        }
        .title { 
            font-family: 'Outfit', sans-serif; 
            font-size: 2rem; 
            color: #0F766E; 
            margin-bottom: 0.5rem; 
        }
        .subtitle { 
            font-size: 0.85rem; 
            color: #64748B; 
            margin-bottom: 2rem; 
        }
        .receipt-box {
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        .receipt-header {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #1E293B;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #E2E8F0;
            font-size: 0.95rem;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            padding: 0.4rem 0;
        }
        .receipt-label { color: #64748B; }
        .receipt-value { font-weight: 500; color: #1E293B; text-align: right;}
        
        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 0.8rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
            gap: 0.5rem;
            cursor: pointer;
        }
        .btn-wa {
            background-color: #22C55E;
            color: #FFFFFF;
            margin-bottom: 1rem;
        }
        .btn-wa:hover { background-color: #16A34A; }
        
        .btn-outline {
            background-color: transparent;
            color: #0F766E;
            border: 1px solid #0F766E;
        }
        .btn-outline:hover { 
            background-color: #0F766E; 
            color: white;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1 class="title">Berhasil!</h1>
        <p class="subtitle">Data peminjaman dan foto KTM telah diterima. Segera hubungi admin untuk verifikasi.</p>

        <div class="receipt-box">
            <div class="receipt-header">
                 Detail Pesanan
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Barang</span>
                <span class="receipt-value"><?= htmlspecialchars($data['nama_barang']); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Durasi</span>
                <span class="receipt-value"><?= $durasi; ?> hari</span>
            </div>
            <div class="receipt-row" style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #E2E8F0;">
                <span class="receipt-label">Status</span>
                <span class="receipt-value" style="color: <?= $status_color; ?>;"><?= $status_text; ?></span>
            </div>
        </div>

        <a href="<?= $wa_link; ?>" target="_blank" class="btn btn-wa">
            <i class="fab fa-whatsapp" style="font-size: 1.1rem;"></i> Kabari Admin via WA
        </a>
        <a href="dashboard.php" class="btn btn-outline">
            Kembali ke Dashboard
        </a>
    </div>
</body>
</html>