<?php
session_start();
require_once '../config/koneksi.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']); 
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telp = mysqli_real_escape_string($conn, $_POST['no_telp']);
    
    $raw_password = $_POST['password']; 
    $raw_konfirmasi = $_POST['konfirmasi_password'];

    $panjang_wa = strlen($no_telp);
    $panjang_npm = strlen($username);

    // Double Validasi
    if (!preg_match("/^[a-zA-Z\s]+$/", $nama_lengkap)) {
        $error = "Nama Lengkap hanya boleh berisi huruf dan spasi!";
    } elseif ($panjang_npm < 10 || $panjang_npm > 13 || !ctype_digit($username)) {
        $error = "NPM harus berupa angka dan terdiri dari 10-13 digit!";
    } elseif ($panjang_wa < 10 || $panjang_wa > 13 || !ctype_digit($no_telp) || strpos($no_telp, '08') !== 0) {
        $error = "Nomor WhatsApp harus diawali '08' dan terdiri dari 10-13 digit angka!";
    } elseif ($raw_password !== $raw_konfirmasi) {
        $error = "Password dan Konfirmasi Password tidak cocok!";
    } else {
        $cek_user = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username' OR email = '$email'");
        if (mysqli_num_rows($cek_user) > 0) {
            $error = "NPM atau Email sudah terdaftar! Silakan gunakan yang lain atau login.";
        } else {
            $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

            $query_insert = "INSERT INTO users (username, password, role, nama_lengkap, email, no_telp, alamat) 
                             VALUES ('$username', '$hashed_password', 'customer', '$nama_lengkap', '$email', '$no_telp', '')";
            
            if (mysqli_query($conn, $query_insert)) {
                echo "<script>
                        alert('Pendaftaran berhasil! Silakan login menggunakan akun baru Anda.');
                        window.location='login.php';
                      </script>";
                exit;
            } else {
                $error = "Terjadi kesalahan sistem: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - UKKI Inventory</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="assets/img/logo-ukki.png">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #F8FAFC; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 2rem 0;}
        .register-container { background: white; padding: 2.5rem 3rem; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); width: 100%; max-width: 600px; border: 1px solid #E2E8F0;}
        
        .header-text { text-align: center; margin-bottom: 2rem; }
        .header-text h2 { font-family: 'Outfit', sans-serif; color: #0F766E; font-size: 2.2rem; display: flex; align-items: center; justify-content: center; gap: 10px;}
        .header-text p { color: #64748B; font-size: 0.95rem; margin-top: 0.5rem;}
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; }
        .form-group { margin-bottom: 1.2rem; position: relative;}
        .form-group.full-width { grid-column: 1 / -1; }
        
        label { display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: #1E293B; font-weight: 500;}
        
        .input-wrapper { position: relative; }
        
        input { width: 100%; padding: 0.8rem 1rem; border: 1px solid #CBD5E1; border-radius: 8px; outline: none; transition: 0.3s; background: #F8FAFC; font-size: 0.95rem;}
        input:focus { border-color: #0F766E; background: white; box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);}
        
        .toggle-password { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94A3B8; transition: 0.2s;}
        .toggle-password:hover { color: #0F766E; }

        .btn { width: 100%; padding: 0.9rem; background: #0F766E; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 1rem; font-size: 1rem; display: flex; align-items: center; justify-content: center; gap: 8px;}
        .btn:hover { background: #134E4A; transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);}
        
        .alert { background: #FEE2E2; color: #B91C1C; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid #FCA5A5; display: flex; align-items: center; gap: 10px;}
        
        .login-link { text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: #64748B; }
        .login-link a { color: #0F766E; text-decoration: none; font-weight: 600; transition: 0.3s;}
        .login-link a:hover { color: #134E4A; }

        @media (max-width: 640px) {
            .form-grid { grid-template-columns: 1fr; gap: 0;}
            .register-container { padding: 2rem; margin: 1rem;}
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="header-text">
            <h2>Daftar Akun</h2>
            <p>Bergabunglah untuk mulai menyewa inventaris UKKI.</p>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="alert"><i class="fas fa-exclamation-triangle"></i> <?= $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" id="nama_input" required placeholder="Sesuai identitas asli" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '');">
                </div>
                
                <div class="form-group">
                    <label>NPM *</label>
                    <input type="text" name="username" id="npm_input" required placeholder="Masukkan NPM Anda" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, ''); validateNPM();">
                </div>

                <div class="form-group">
                    <label>Email Aktif *</label>
                    <input type="email" name="email" required placeholder="email@student.upnjatim.ac.id">
                </div>

                <div class="form-group full-width">
                    <label>Nomor WhatsApp *</label>
                    <input type="tel" name="no_telp" id="wa_input" required placeholder="08123456789" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, ''); validateWA();">
                </div>

                <div class="form-group">
                    <label>Password *</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="pass_input" required placeholder="Minimal 6 karakter" oninput="validatePass();">
                        <i class="fas fa-eye toggle-password" onclick="toggleVisibility('pass_input', this)"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Konfirmasi Password *</label>
                    <div class="input-wrapper">
                        <input type="password" name="konfirmasi_password" id="pass_confirm" required placeholder="Ketik ulang password" oninput="validatePass();">
                        <i class="fas fa-eye toggle-password" onclick="toggleVisibility('pass_confirm', this)"></i>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn"><i class="fas fa-paper-plane"></i> Buat Akun Sekarang</button>
        </form>

        <div class="login-link">
            Sudah punya akun? <a href="login.php">Masuk di sini</a>
        </div>
    </div>

    <script>
        const npmInput = document.getElementById('npm_input');
        const waInput = document.getElementById('wa_input');
        const passInput = document.getElementById('pass_input');
        const passConfirm = document.getElementById('pass_confirm');

        function validateNPM() {
            if (npmInput.value.length > 0 && (npmInput.value.length < 10 || npmInput.value.length > 13)) {
                npmInput.setCustomValidity("NPM harus terdiri dari 10-13 digit angka.");
            } else {
                npmInput.setCustomValidity("");
            }
        }

        function validateWA() {
            let val = waInput.value;
            if (val.length > 0 && (!val.startsWith('08') || val.length < 10 || val.length > 13)) {
                waInput.setCustomValidity("Nomor harus diawali 08 dan terdiri dari 10-13 digit.");
            } else {
                waInput.setCustomValidity("");
            }
        }

        function validatePass() {
            if (passInput.value.length > 0 && passInput.value.length < 6) {
                passInput.setCustomValidity("Password minimal 6 karakter.");
            } else {
                passInput.setCustomValidity("");
            }

            if (passConfirm.value !== passInput.value) {
                passConfirm.setCustomValidity("Password tidak cocok!");
            } else {
                passConfirm.setCustomValidity("");
            }
        }

        function toggleVisibility(inputId, iconElement) {
            const inputField = document.getElementById(inputId);
            if (inputField.type === "password") {
                inputField.type = "text";
                iconElement.classList.remove("fa-eye");
                iconElement.classList.add("fa-eye-slash");
            } else {
                inputField.type = "password";
                iconElement.classList.remove("fa-eye-slash");
                iconElement.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>