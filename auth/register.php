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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- BOOTSTRAP 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/logo-ukki.png">
    
    <style>
        :root { --teal-dark: #0F766E; --teal-hover: #134E4A; }
        body { font-family: 'Outfit', sans-serif; background-color: #F8FAFC; }
        
        .auth-card { box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); border: 1px solid #E2E8F0; border-radius: 16px; }
        
        .form-control-custom { font-family: 'Poppins', sans-serif; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem; background: #F8FAFC; transition: 0.3s; padding-right: 2.5rem; }
        .form-control-custom:focus { border-color: var(--teal-dark); background: white; box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1); outline: none;}
        
        .toggle-password { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94A3B8; transition: 0.2s;}
        .toggle-password:hover { color: var(--teal-dark); }

        .btn-teal { background: var(--teal-dark); color: white; border-radius: 8px; transition: 0.3s; border: none; font-family: 'Outfit', sans-serif;}
        .btn-teal:hover { background: var(--teal-hover); color: white; transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        
        .auth-link { color: var(--teal-dark); font-weight: 600; transition: 0.3s; text-decoration: none; }
        .auth-link:hover { color: var(--teal-hover); }
        .text-poppins { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 py-5 px-3 bg-light">

    <div class="auth-card bg-white w-100 p-4 p-md-5" style="max-width: 600px;">
        <div class="text-center mb-4 pb-2">
            <h2 class="fs-2 mb-2 d-flex align-items-center justify-content-center gap-2" style="color: var(--teal-dark); font-weight: 700;">Daftar Akun</h2>
            <p class="small text-muted mb-0 text-poppins" style="color: #64748B;">Bergabunglah untuk mulai menyewa inventaris UKKI.</p>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 p-3 rounded-3 mb-4 border text-poppins" style="background: #FEE2E2; color: #B91C1C; border-color: #FCA5A5; font-size: 0.9rem;">
                <i class="fas fa-exclamation-triangle"></i> <?= $error; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small fw-medium text-poppins" style="color: #1E293B;">Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" id="nama_input" class="form-control form-control-custom py-2" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '');">
                </div>
                
                <div class="col-sm-6">
                    <label class="form-label small fw-medium text-poppins" style="color: #1E293B;">NPM *</label>
                    <input type="text" name="username" id="npm_input" class="form-control form-control-custom py-2" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, ''); validateNPM();">
                </div>

                <div class="col-sm-6">
                    <label class="form-label small fw-medium text-poppins" style="color: #1E293B;">Email Aktif *</label>
                    <input type="email" name="email" class="form-control form-control-custom py-2" >
                </div>

                <div class="col-12">
                    <label class="form-label small fw-medium text-poppins" style="color: #1E293B;">Nomor WhatsApp *</label>
                    <input type="tel" name="no_telp" id="wa_input" class="form-control form-control-custom py-2" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, ''); validateWA();">
                </div>

                <div class="col-sm-6 position-relative">
                    <label class="form-label small fw-medium text-poppins" style="color: #1E293B;">Password *</label>
                    <div class="position-relative">
                        <input type="password" name="password" id="pass_input" class="form-control form-control-custom py-2" required placeholder="Ketikkan password" oninput="validatePass();">
                        <i class="fas fa-eye toggle-password" onclick="toggleVisibility('pass_input', this)"></i>
                    </div>
                </div>

                <div class="col-sm-6 position-relative">
                    <label class="form-label small fw-medium text-poppins" style="color: #1E293B;">Konfirmasi Password *</label>
                    <div class="position-relative">
                        <input type="password" name="konfirmasi_password" id="pass_confirm" class="form-control form-control-custom py-2" required placeholder="Ketik ulang password" oninput="validatePass();">
                        <i class="fas fa-eye toggle-password" onclick="toggleVisibility('pass_confirm', this)"></i>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-teal w-100 py-3 mt-4 fw-semibold d-flex align-items-center justify-content-center gap-2">
                Buat Akun Sekarang
            </button>
        </form>

        <div class="text-center mt-4 small text-poppins" style="color: #64748B;">
            Sudah punya akun? <a href="login.php" class="auth-link">Masuk di sini</a>
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