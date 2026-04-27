<?php
include "../includes/auth.php";
include "../config/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Get user data
$user = $_SESSION['user'];
$user_id = $user['user_id'] ?? $user['id'] ?? null;
$user_name = $user['username'] ?? $user['name'] ?? 'User';
$user_email = $user['email'] ?? '';
$user_role = $user['role'] ?? 'user';

if (!$user_id) {
    die("Error: User ID not found");
}

// Get user details from database
$user_query = $conn->query("SELECT * FROM users WHERE user_id = $user_id");
if ($user_query && $user_query->num_rows > 0) {
    $user_data = $user_query->fetch_assoc();
    $username = $user_data['username'] ?? '';
    $email = $user_data['email'] ?? '';
    $role = $user_data['role'] ?? 'user';
    $is_active = $user_data['is_active'] ?? 1;
    $created_at = $user_data['created_at'] ?? null;
} else {
    $username = $user_name;
    $email = $user_email;
    $role = $user_role;
    $is_active = 1;
    $created_at = null;
}

$message = '';
$message_type = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $new_username = mysqli_real_escape_string($conn, $_POST['username']);
        $new_email = mysqli_real_escape_string($conn, $_POST['email']);
        
        // Check if email already exists for other users
        $check_email = $conn->query("SELECT user_id FROM users WHERE email = '$new_email' AND user_id != $user_id");
        if ($check_email && $check_email->num_rows > 0) {
            $message = "Email sudah digunakan oleh pengguna lain!";
            $message_type = "error";
        } else {
            $update_sql = "UPDATE users SET username = '$new_username', email = '$new_email' WHERE user_id = $user_id";
            if ($conn->query($update_sql)) {
                // Update session
                $_SESSION['user']['username'] = $new_username;
                $_SESSION['user']['email'] = $new_email;
                $username = $new_username;
                $email = $new_email;
                $message = "Profil berhasil diupdate!";
                $message_type = "success";
            } else {
                $message = "Gagal mengupdate profil: " . $conn->error;
                $message_type = "error";
            }
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Get current password from database
        $pass_query = $conn->query("SELECT password FROM users WHERE user_id = $user_id");
        $pass_data = $pass_query->fetch_assoc();
        $db_password = $pass_data['password'];
        
        if ($current_password != $db_password) {
            $message = "Password saat ini salah!";
            $message_type = "error";
        } elseif (strlen($new_password) < 4) {
            $message = "Password baru minimal 4 karakter!";
            $message_type = "error";
        } elseif ($new_password != $confirm_password) {
            $message = "Konfirmasi password tidak cocok!";
            $message_type = "error";
        } else {
            $update_pass = $conn->query("UPDATE users SET password = '$new_password' WHERE user_id = $user_id");
            if ($update_pass) {
                $message = "Password berhasil diubah!";
                $message_type = "success";
            } else {
                $message = "Gagal mengubah password: " . $conn->error;
                $message_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Profil Saya | ConcertHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #0a0a0a;
            color: #fff;
            overflow-x: hidden;
        }

        #particle-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }

        .container {
            position: relative;
            z-index: 2;
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(12px);
            background: rgba(10, 10, 10, 0.8);
            z-index: 100;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #FF4788, #FF8C42);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
        }

        .logo i {
            color: #FF4788;
            margin-right: 8px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-name {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .logout-btn {
            background: rgba(255, 71, 136, 0.2);
            border: 1px solid rgba(255, 71, 136, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 12px;
            color: #FF8C8C;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background: rgba(255, 71, 136, 0.4);
            border-color: #FF4788;
        }

        .main-content {
            margin-top: 80px;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #FFFFFF, #FFD6E0);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: rgba(255, 255, 255, 0.6);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            margin-bottom: 1rem;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #FF4788;
        }

        .profile-card {
            background: rgba(20, 20, 30, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            flex-wrap: wrap;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #FF4788, #FF8C42);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }

        .profile-info h2 {
            font-size: 1.5rem;
            margin-bottom: 0.3rem;
        }

        .profile-info p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        .role-badge {
            display: inline-block;
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        .role-admin {
            background: rgba(255, 71, 136, 0.2);
            color: #FF4788;
            border: 1px solid rgba(255, 71, 136, 0.3);
        }

        .role-user {
            background: rgba(33, 150, 243, 0.2);
            color: #2196F3;
            border: 1px solid rgba(33, 150, 243, 0.3);
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            flex-wrap: wrap;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.6);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .tab-btn:hover {
            color: #FF4788;
        }

        .tab-btn.active {
            color: #FF4788;
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(95deg, #FF4788, #FF8C42);
        }

        .tab-pane {
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .tab-pane.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #FF8C42;
        }

        .form-group label i {
            margin-right: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #FF4788;
            background: rgba(255, 71, 136, 0.1);
        }

        .form-group input:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn-submit {
            background: linear-gradient(95deg, #FF4788, #FF8C42);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(255, 71, 136, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .alert {
            padding: 1rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.15);
            border: 1px solid rgba(76, 175, 80, 0.3);
            color: #4CAF50;
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.15);
            border: 1px solid rgba(244, 67, 54, 0.3);
            color: #f44336;
        }

        .info-box {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 16px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #FF8C42;
            font-weight: 500;
        }

        .info-value {
            color: rgba(255, 255, 255, 0.9);
        }

        .footer {
            margin-top: 4rem;
            text-align: center;
            opacity: 0.6;
            font-size: 0.85rem;
            padding: 2rem 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            .page-title {
                font-size: 1.5rem;
            }
            .navbar {
                padding: 0.75rem 1rem;
            }
            .logo {
                font-size: 1.2rem;
            }
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
            .tabs {
                justify-content: center;
            }
            .tab-btn {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>

<canvas id="particle-canvas"></canvas>

<div class="navbar">
    <div class="logo">
        <i class="fas fa-ticket-alt"></i> ConcertHub
    </div>
    <div class="user-info">
        <span class="user-name">
            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($username); ?>
        </span>
        <a href="../auth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<div class="container">
    <div class="main-content">
        <a href="dashboard.php" class="back-link" data-aos="fade-right">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>

        <div class="page-header" data-aos="fade-right" data-aos-delay="100">
            <div class="page-title">
                <i class="fas fa-user-circle"></i> Profil Saya
            </div>
            <div class="page-subtitle">
                Kelola informasi akun Anda
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" data-aos="fade-up">
                <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <div class="profile-card" data-aos="fade-up" data-aos-delay="200">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="profile-info">
                    <h2>
                        <?php echo htmlspecialchars($username); ?>
                        <span class="role-badge role-<?php echo $role; ?>">
                            <i class="fas <?php echo $role == 'admin' ? 'fa-crown' : 'fa-user'; ?>"></i>
                            <?php echo ucfirst($role); ?>
                        </span>
                    </h2>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($email); ?></p>
                    <p><i class="fas fa-calendar-alt"></i> Bergabung sejak <?php echo $created_at ? date('d F Y', strtotime($created_at)) : 'Belum diketahui'; ?></p>
                </div>
            </div>

            <div class="tabs">
                <button class="tab-btn active" onclick="openTab('profile')">
                    <i class="fas fa-user-edit"></i> Edit Profil
                </button>
                <button class="tab-btn" onclick="openTab('password')">
                    <i class="fas fa-lock"></i> Ubah Password
                </button>
                <button class="tab-btn" onclick="openTab('info')">
                    <i class="fas fa-info-circle"></i> Informasi Akun
                </button>
            </div>

            <!-- Tab Edit Profil -->
            <div id="profileTab" class="tab-pane active">
                <form method="POST" action="">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Username</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <button type="submit" name="update_profile" class="btn-submit">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </form>
            </div>

            <!-- Tab Ubah Password -->
            <div id="passwordTab" class="tab-pane">
                <form method="POST" action="">
                    <div class="form-group">
                        <label><i class="fas fa-key"></i> Password Saat Ini</label>
                        <input type="password" name="current_password" required placeholder="Masukkan password saat ini">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Password Baru</label>
                            <input type="password" name="new_password" required placeholder="Minimal 4 karakter">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-check-circle"></i> Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" required placeholder="Ulangi password baru">
                        </div>
                    </div>
                    <button type="submit" name="change_password" class="btn-submit">
                        <i class="fas fa-key"></i> Ubah Password
                    </button>
                </form>
            </div>

            <!-- Tab Informasi Akun -->
            <div id="infoTab" class="tab-pane">
                <div class="info-box">
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-user-tag"></i> Username</span>
                        <span class="info-value"><?php echo htmlspecialchars($username); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-envelope"></i> Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($email); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-shield-alt"></i> Role</span>
                        <span class="info-value"><?php echo ucfirst($role); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-calendar-alt"></i> Bergabung Sejak</span>
                        <span class="info-value"><?php echo $created_at ? date('d F Y, H:i', strtotime($created_at)) : 'Belum diketahui'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-check-circle"></i> Status Akun</span>
                        <span class="info-value">
                            <span style="color: <?php echo $is_active ? '#4CAF50' : '#f44336'; ?>">
                                <i class="fas <?php echo $is_active ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                <?php echo $is_active ? 'Aktif' : 'Nonaktif'; ?>
                            </span>
                        </span>
                    </div>
                </div>
                <p style="color: rgba(255,255,255,0.5); font-size: 0.8rem;">
                    <i class="fas fa-info-circle"></i> Jika ada masalah dengan akun Anda, silakan hubungi admin.
                </p>
            </div>
        </div>

        <div class="footer">
            <p>© 2026 ConcertHub — Experience the beat, live.</p>
        </div>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 120, duration: 800 });

    function openTab(tabName) {
        // Hide all tabs
        document.getElementById('profileTab').classList.remove('active');
        document.getElementById('passwordTab').classList.remove('active');
        document.getElementById('infoTab').classList.remove('active');
        
        // Remove active class from all buttons
        const btns = document.querySelectorAll('.tab-btn');
        btns.forEach(btn => btn.classList.remove('active'));
        
        // Show selected tab
        if (tabName === 'profile') {
            document.getElementById('profileTab').classList.add('active');
            btns[0].classList.add('active');
        } else if (tabName === 'password') {
            document.getElementById('passwordTab').classList.add('active');
            btns[1].classList.add('active');
        } else if (tabName === 'info') {
            document.getElementById('infoTab').classList.add('active');
            btns[2].classList.add('active');
        }
    }

    // Particle Background Effect
    const canvas = document.getElementById('particle-canvas');
    const ctx = canvas.getContext('2d');
    let width = window.innerWidth;
    let height = window.innerHeight;
    let particles = [];

    function resizeCanvas() {
        width = window.innerWidth;
        height = window.innerHeight;
        canvas.width = width;
        canvas.height = height;
    }

    class Particle {
        constructor() {
            this.x = Math.random() * width;
            this.y = Math.random() * height;
            this.size = Math.random() * 2.5 + 0.8;
            this.speedX = (Math.random() - 0.5) * 0.6;
            this.speedY = (Math.random() - 0.5) * 0.4;
            this.color = `rgba(255, ${Math.floor(100 + Math.random() * 100)}, ${Math.floor(120 + Math.random() * 80)}, ${Math.random() * 0.5 + 0.2})`;
        }
        update() {
            this.x += this.speedX;
            this.y += this.speedY;
            if (this.x < 0 || this.x > width) this.speedX *= -1;
            if (this.y < 0 || this.y > height) this.speedY *= -1;
        }
        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fillStyle = this.color;
            ctx.fill();
        }
    }

    function initParticles() {
        particles = [];
        let numberOfParticles = (width * height) / 8000;
        if (numberOfParticles > 180) numberOfParticles = 180;
        for (let i = 0; i < numberOfParticles; i++) {
            particles.push(new Particle());
        }
    }

    function animateParticles() {
        ctx.clearRect(0, 0, width, height);
        for (let p of particles) {
            p.update();
            p.draw();
        }
        requestAnimationFrame(animateParticles);
    }

    window.addEventListener('resize', () => {
        resizeCanvas();
        initParticles();
    });

    resizeCanvas();
    initParticles();
    animateParticles();
</script>
</body>
</html>