<?php
session_start();
include "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") { 
    $email = mysqli_real_escape_string($conn, $_POST['email']); 
    $password = $_POST['password']; 

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND is_active=1"); 
    $stmt->bind_param("s", $email); 
    $stmt->execute(); 
    $result = $stmt->get_result(); 
    $user = $result->fetch_assoc(); 

    if ($user && $password == $user['password']) { 
        $_SESSION['user'] = $user; 

        if ($user['role'] == 'admin') { 
            header("Location: ../admin/dashboard.php"); 
        } else { 
            header("Location: ../user/dashboard.php"); 
        } 
        exit; 
    } else { 
        $error = "Email atau password salah!";
    } 
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Login | ConcertHub</title>
    <!-- Google Fonts & Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- AOS Library -->
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Particle Canvas Background */
        #particle-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }

        /* Container */
        .container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 450px;
            padding: 2rem;
        }

        /* Login Card */
        .login-card {
            background: rgba(20, 20, 30, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 32px;
            padding: 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 71, 136, 0.3);
        }

        /* Header */
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #FF4788, #FF8C42);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1rem;
            display: inline-block;
        }

        .logo i {
            color: #FF4788;
            margin-right: 8px;
        }

        .login-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            font-size: 0.9rem;
            opacity: 0.7;
        }

        /* Form Group */
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #FF4788;
            font-size: 1.1rem;
            z-index: 1;
        }

        .form-group input {
            width: 100%;
            padding: 1rem 1rem 1rem 2.8rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            font-size: 1rem;
            color: #fff;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #FF4788;
            background: rgba(255, 71, 136, 0.1);
            box-shadow: 0 0 0 3px rgba(255, 71, 136, 0.1);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        /* Button Login */
        .login-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(95deg, #FF4788, #FF8C42);
            border: none;
            border-radius: 16px;
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 25px -5px rgba(255, 71, 136, 0.4);
        }

        /* Error Message */
        .error-message {
            background: rgba(255, 71, 136, 0.15);
            border: 1px solid rgba(255, 71, 136, 0.3);
            border-radius: 12px;
            padding: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #FF8C8C;
            font-size: 0.85rem;
        }

        .error-message i {
            font-size: 1rem;
        }

        /* Register Link */
        .register-link {
            text-align: center;
            font-size: 0.85rem;
            opacity: 0.7;
            margin-top: 1.5rem;
        }

        .register-link a {
            color: #FF4788;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: #FF8C42;
            text-decoration: underline;
        }

        /* Back to Home */
        .back-home {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-home a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s ease;
        }

        .back-home a:hover {
            color: #FF4788;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            .login-card {
                padding: 1.8rem;
            }
            .logo {
                font-size: 2rem;
            }
            .login-header h2 {
                font-size: 1.5rem;
            }
        }

        /* Animasi fade in */
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

        .login-card {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>

<canvas id="particle-canvas"></canvas>

<div class="container">
    <div class="login-card" data-aos="fade-up" data-aos-duration="800">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-ticket-alt"></i> ConcertHub
            </div>
            <h2>Selamat Datang Kembali!</h2>
            <p>Masuk ke akun Anda untuk melanjutkan</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message" data-aos="fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Alamat Email" required autocomplete="email">
            </div>
            
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
            </div>
            
            <button type="submit" class="login-btn">
                <i class="fas fa-arrow-right-to-bracket"></i>
                Masuk Sekarang
            </button>
        </form>

        <div class="register-link">
            Belum punya akun? <a href="register.php">Daftar Sekarang</a>
        </div>
        
        <div class="back-home">
            <a href="../index.php">
                <i class="fas fa-home"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 120 });

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