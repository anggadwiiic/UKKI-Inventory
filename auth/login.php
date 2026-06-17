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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../assets/img/logo-ukki.png">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #F8FAFC; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 2rem;}
        
        .auth-container { background: white; padding: 2.5rem; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); width: 100%; max-width: 420px; border: 1px solid #E2E8F0; text-align: center;}
        
        .header-text { margin-bottom: 2rem; }
        .header-text h2 { font-family: 'Outfit', sans-serif; color: #0F766E; font-size: 2rem; display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 0.5rem;}
        .header-text p { color: #64748B; font-size: 0.95rem; }
        
        .form-group { margin-bottom: 1.2rem; text-align: left;}
        label { display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: #1E293B; font-weight: 500;}
        
        input { width: 100%; padding: 0.8rem 1rem; border: 1px solid #CBD5E1; border-radius: 8px; outline: none; transition: 0.3s; background: #F8FAFC; font-size: 0.95rem;}
        input:focus { border-color: #0F766E; background: white; box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);}
        
        .btn { width: 100%; padding: 0.9rem; background: #0F766E; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 0.5rem; font-size: 1rem; display: flex; align-items: center; justify-content: center; gap: 8px;}
        .btn:hover { background: #134E4A; transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);}
        
        .alert { background: #FEE2E2; color: #B91C1C; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid #FCA5A5; display: flex; align-items: center; gap: 10px; text-align: left;}
        
        .auth-footer { margin-top: 1.5rem; font-size: 0.9rem; color: #64748B; }
        .auth-footer a { color: #0F766E; text-decoration: none; font-weight: 600; transition: 0.3s;}
        .auth-footer a:hover { color: #134E4A; }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="header-text">
        <a href="../index.php" style="text-decoration: none;">
            <h2>UKKI Inventory</h2>
        </a> 
            <p>Silakan login untuk melanjutkan.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert"><i class="fas fa-exclamation-circle"></i> <?= $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="identifier">Username / Email</label>
                <input type="text" id="identifier" name="identifier" required placeholder="Masukkan Username atau Email" autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn"><i class="fas fa-sign-in-alt"></i> Masuk Sekarang</button>
        </form>

        <div class="auth-footer">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </div>
    </div>
</body>
</html>