<?php
session_start();
require_once '../config/koneksi.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] == 'admin') {
        header("Location: ../admin/index.php");
    } else {
        header("Location: ../index.php");
    }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $identifier = mysqli_real_escape_string($conn, $_POST['identifier']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = '$identifier' OR email = '$identifier'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {  
        $user = mysqli_fetch_assoc($result);

        // Validasi
        if (password_verify($password, $user['password']) || md5($password) === $user['password']) {
            
            // Regenerate Session ID
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_nama'] = $user['nama_lengkap'];

            if ($user['role'] == 'admin') {
                header("Location: ../admin/index.php");
            } else {
                header("Location: ../index.php");
            }
            exit;
        } else {
            $error = 'Username/Email atau Password salah!';
        }
    } else {
        $error = 'Username/Email atau Password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- BOOTSTRAP 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/logo-ukki.png">
    
    <style>
        :root { --teal-dark: #0F766E; --teal-hover: #134E4A; }
        body { font-family: 'Outfit', sans-serif; background-color: #F8FAFC; }
        
        .auth-card { box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); border: 1px solid #E2E8F0; border-radius: 16px; }
        
        .form-control-custom { font-family: 'Poppins', sans-serif; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem; background: #F8FAFC; transition: 0.3s; }
        .form-control-custom:focus { border-color: var(--teal-dark); background: white; box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1); outline: none;}
        
        .btn-teal { background: var(--teal-dark); color: white; border-radius: 8px; transition: 0.3s; border: none; font-family: 'Outfit', sans-serif;}
        .btn-teal:hover { background: var(--teal-hover); color: white; transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        
        .auth-link { color: var(--teal-dark); font-weight: 600; transition: 0.3s; text-decoration: none; }
        .auth-link:hover { color: var(--teal-hover); }
        .text-poppins { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 p-3 p-md-4">
    
    <div class="auth-card bg-white w-100 p-4 p-md-5 text-center" style="max-width: 420px;">
        <div class="mb-4">
            <a href="../index.php" class="text-decoration-none">
                <h2 class="fs-2 mb-2 d-flex align-items-center justify-content-center gap-2" style="color: var(--teal-dark); font-weight: 700;">UKKI Inventory</h2>
            </a> 
            <p class="small text-muted text-poppins" style="color: #64748B;">Silakan login untuk melanjutkan.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 text-start p-3 rounded-3 mb-4 border text-poppins" style="background: #FEE2E2; color: #B91C1C; border-color: #FCA5A5; font-size: 0.9rem;">
                <i class="fas fa-exclamation-circle"></i> <?= $error; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="text-start">
            <div class="mb-3">
                <label for="identifier" class="form-label small fw-medium text-poppins" style="color: #1E293B;">Username / Email</label>
                <input type="text" id="identifier" name="identifier" class="form-control form-control-custom py-2" required placeholder="Masukkan Username atau Email" autofocus>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label small fw-medium text-poppins" style="color: #1E293B;">Password</label>
                <input type="password" id="password" name="password" class="form-control form-control-custom py-2" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-teal w-100 py-2 mt-2 fw-semibold d-flex align-items-center justify-content-center gap-2">
                <i class="fas fa-sign-in-alt"></i> Masuk Sekarang
            </button>
        </form>

        <div class="mt-4 small text-poppins" style="color: #64748B;">
            Belum punya akun? <a href="register.php" class="auth-link">Daftar di sini</a>
        </div>
    </div>

</body>
</html>