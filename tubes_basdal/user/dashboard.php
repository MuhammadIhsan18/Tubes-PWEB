<?php
include "../includes/auth.php";

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Get user data
$user = $_SESSION['user'];

// Get user name from various possible column names
$user_name = $user['name'] ?? $user['username'] ?? $user['fullname'] ?? $user['nama'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Dashboard User | ConcertHub</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Navbar */
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

        /* Main Content */
        .main-content {
            margin-top: 80px;
        }

        /* Welcome Section */
        .welcome-section {
            margin-bottom: 3rem;
            text-align: center;
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #FFFFFF, #FFD6E0, #FF8C42);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
            animation: gradientShift 6s ease infinite;
            background-size: 200% auto;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .welcome-subtitle {
            color: rgba(255, 255, 255, 0.6);
            font-size: 1rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: rgba(20, 20, 30, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            text-align: center;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: #FF4788;
            box-shadow: 0 10px 30px -10px rgba(255, 71, 136, 0.3);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #FFB347, #FF4788);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.7;
        }

        /* Menu Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .menu-card {
            background: rgba(20, 20, 30, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            text-decoration: none;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            text-align: left;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            border-color: #FF4788;
            box-shadow: 0 10px 30px -10px rgba(255, 71, 136, 0.3);
        }

        .menu-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(255, 71, 136, 0.2), rgba(255, 140, 66, 0.2));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .menu-content {
            flex: 1;
        }

        .menu-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .menu-desc {
            font-size: 0.8rem;
            opacity: 0.6;
        }

        .menu-arrow {
            font-size: 1.2rem;
            opacity: 0.5;
            transition: all 0.3s ease;
        }

        .menu-card:hover .menu-arrow {
            opacity: 1;
            transform: translateX(5px);
        }

        /* Upcoming Events Section */
        .events-section {
            margin-top: 1rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .view-all {
            color: #FF4788;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .view-all:hover {
            color: #FF8C42;
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .event-card {
            background: rgba(20, 20, 30, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .event-card:hover {
            transform: translateY(-5px);
            border-color: #FF4788;
        }

        .event-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .event-card h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .event-date {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-bottom: 0.5rem;
        }

        .event-location {
            font-size: 0.8rem;
            color: #FF8C42;
            margin-bottom: 1rem;
        }

        .event-price {
            font-size: 1rem;
            font-weight: 700;
            color: #4CAF50;
            margin-bottom: 1rem;
        }

        .btn-buy {
            background: linear-gradient(95deg, #FF4788, #FF8C42);
            padding: 0.5rem 1rem;
            border-radius: 12px;
            color: #fff;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-buy:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px -5px rgba(255, 71, 136, 0.4);
        }

        /* Footer */
        .footer {
            margin-top: 4rem;
            text-align: center;
            opacity: 0.6;
            font-size: 0.85rem;
            padding: 2rem 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            .welcome-title {
                font-size: 1.8rem;
            }
            .navbar {
                padding: 0.75rem 1rem;
            }
            .logo {
                font-size: 1.2rem;
            }
            .menu-card {
                padding: 1.5rem;
            }
            .menu-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
        }

        /* Animations */
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

        .stat-card, .menu-card, .event-card {
            animation: fadeInUp 0.5s ease-out forwards;
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
            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?>
        </span>
        <a href="../auth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<div class="container">
    <div class="main-content">
        <!-- Welcome Section -->
        <div class="welcome-section" data-aos="fade-up" data-aos-duration="800">
            <h1 class="welcome-title">
                Selamat Datang, <?php echo htmlspecialchars($user_name); ?>! 👋
            </h1>
            <p class="welcome-subtitle">
                Temukan dan pesan tiket konser favoritmu dengan mudah
            </p>
        </div>

        <!-- Stats Grid (Placeholder) -->
        <div class="stats-grid" data-aos="fade-up" data-aos-delay="100">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="stat-value">-</div>
                <div class="stat-label">Total Tiket Dibeli</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-music"></i>
                </div>
                <div class="stat-value">-</div>
                <div class="stat-label">Event Dihadiri</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-value">-</div>
                <div class="stat-label">Event Mendatang</div>
            </div>
        </div>

        <!-- Menu Grid -->
        <div class="menu-grid" data-aos="fade-up" data-aos-delay="200">
            <a href="events.php" class="menu-card">
                <div class="menu-icon">
                    <i class="fas fa-music"></i>
                </div>
                <div class="menu-content">
                    <div class="menu-title">Lihat Event</div>
                    <div class="menu-desc">Cari dan temukan event konser favoritmu</div>
                </div>
                <div class="menu-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
            <a href="my_orders.php" class="menu-card">
                <div class="menu-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="menu-content">
                    <div class="menu-title">Pesanan Saya</div>
                    <div class="menu-desc">Lihat riwayat pemesanan tiketmu</div>
                </div>
                <div class="menu-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
            <a href="profile.php" class="menu-card">
                <div class="menu-icon">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="menu-content">
                    <div class="menu-title">Profil Saya</div>
                    <div class="menu-desc">Kelola informasi akunmu</div>
                </div>
                <div class="menu-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>

        <!-- Upcoming Events Section -->
        <div class="events-section" data-aos="fade-up" data-aos-delay="300">
            <div class="section-header">
                <div class="section-title">
                    <i class="fas fa-calendar-alt" style="color: #FF4788;"></i>
                    Event Mendatang
                </div>
                <a href="events.php" class="view-all">
                    Lihat Semua <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="events-grid">
                <div class="event-card">
                    <div class="event-icon">🎸</div>
                    <h3>Rock Symphony</h3>
                    <div class="event-date"><i class="far fa-calendar-alt"></i> 20 Mei 2026</div>
                    <div class="event-location"><i class="fas fa-map-marker-alt"></i> Jakarta</div>
                    <div class="event-price">Mulai IDR 350K</div>
                    <a href="event-detail.php?id=1" class="btn-buy">
                        <i class="fas fa-ticket-alt"></i> Pesan Tiket
                    </a>
                </div>
                <div class="event-card">
                    <div class="event-icon">🎧</div>
                    <h3>EDM Festival</h3>
                    <div class="event-date"><i class="far fa-calendar-alt"></i> 5 Juni 2026</div>
                    <div class="event-location"><i class="fas fa-map-marker-alt"></i> Surabaya</div>
                    <div class="event-price">Mulai IDR 500K</div>
                    <a href="event-detail.php?id=2" class="btn-buy">
                        <i class="fas fa-ticket-alt"></i> Pesan Tiket
                    </a>
                </div>
                <div class="event-card">
                    <div class="event-icon">🎤</div>
                    <h3>Pop Grandeur</h3>
                    <div class="event-date"><i class="far fa-calendar-alt"></i> 18 Juli 2026</div>
                    <div class="event-location"><i class="fas fa-map-marker-alt"></i> Bandung</div>
                    <div class="event-price">Mulai IDR 275K</div>
                    <a href="event-detail.php?id=3" class="btn-buy">
                        <i class="fas fa-ticket-alt"></i> Pesan Tiket
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© 2026 ConcertHub — Experience the beat, live.</p>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 120, duration: 800 });

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