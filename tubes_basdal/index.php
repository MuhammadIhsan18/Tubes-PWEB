<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>ConcertHub | Tiket Konser Modern</title>
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

        /* Glow & Animations */
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes disappearLetter {
            0% {
                opacity: 1;
                transform: translateX(0) scale(1);
                filter: blur(0px);
            }
            50% {
                opacity: 0.5;
                transform: translateX(10px) scale(0.8);
                filter: blur(2px);
            }
            100% {
                opacity: 0;
                transform: translateX(20px) scale(0.5);
                filter: blur(4px);
                display: none;
            }
        }

        @keyframes appearLetter {
            0% {
                opacity: 0;
                transform: translateX(-20px) scale(0.5);
                filter: blur(4px);
            }
            50% {
                opacity: 0.5;
                transform: translateX(-10px) scale(0.8);
                filter: blur(2px);
            }
            100% {
                opacity: 1;
                transform: translateX(0) scale(1);
                filter: blur(0px);
            }
        }

        .letter {
            display: inline-block;
            animation-duration: 0.4s;
            animation-fill-mode: forwards;
        }

        .letter.disappearing {
            animation-name: disappearLetter;
        }

        .letter.appearing {
            animation-name: appearLetter;
        }

        .walking-title {
            display: block;
            font-size: 4.2rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #FFFFFF, #FFD6E0, #FF8C42);
            background-size: 200% auto;
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: gradientShift 6s ease infinite;
            letter-spacing: 2px;
        }

        /* Wrapper untuk posisi tengah */
        .title-wrapper {
            text-align: center;
            width: 100%;
            margin-bottom: 2rem;
            position: relative;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .title-line {
            display: block;
            margin: 0;
            padding: 0;
        }

        .container {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 2rem;
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 1.5rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(12px);
            background: rgba(10, 10, 10, 0.6);
            z-index: 100;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .logo {
            font-size: 1.8rem;
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

        /* Hero Section */
        .hero {
            max-width: 900px;
            margin: 0 auto;
        }

        .badge {
            display: inline-block;
            background: rgba(255, 71, 136, 0.15);
            backdrop-filter: blur(8px);
            padding: 0.5rem 1.2rem;
            border-radius: 100px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 71, 136, 0.3);
            color: #FF8C8C;
        }

        .hero p {
            font-size: 1.2rem;
            opacity: 0.8;
            margin-bottom: 2.5rem;
            max-width: 650px;
            margin-left: auto;
            margin-right: auto;
        }

        /* CTA Button */
        .cta-btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(95deg, #FF4788, #FF8C42);
            padding: 1rem 2.8rem;
            border-radius: 60px;
            font-weight: 700;
            font-size: 1.1rem;
            color: #fff;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 8px 20px rgba(255, 71, 136, 0.3);
            border: none;
            cursor: pointer;
        }

        .cta-btn:hover {
            transform: scale(1.03);
            box-shadow: 0 12px 28px rgba(255, 71, 136, 0.5);
        }

        /* Stats Section */
        .stats {
            display: flex;
            gap: 4rem;
            justify-content: center;
            margin-top: 5rem;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
            backdrop-filter: blur(8px);
            background: rgba(255, 255, 255, 0.03);
            padding: 1rem 2rem;
            border-radius: 48px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #FFB347, #FF4788);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        /* Cards (Upcoming Events) */
        .events-section {
            margin-top: 6rem;
            width: 100%;
            max-width: 1200px;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: center;
        }

        .cards {
            display: flex;
            gap: 2rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .event-card {
            background: rgba(20, 20, 30, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            padding: 1.5rem;
            width: 280px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
        }

        .event-card:hover {
            transform: translateY(-10px);
            border-color: #FF4788;
            box-shadow: 0 20px 35px -10px rgba(255, 71, 136, 0.3);
        }

        .event-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .event-card h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .event-date {
            font-size: 0.85rem;
            opacity: 0.7;
            margin-bottom: 1rem;
        }

        .event-price {
            font-weight: 700;
            color: #FF8C42;
        }

        /* Footer */
        .footer {
            margin-top: 6rem;
            text-align: center;
            opacity: 0.6;
            font-size: 0.85rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .walking-title {
                font-size: 2.5rem;
            }
            .title-wrapper {
                min-height: 150px;
            }
            .stats {
                gap: 1.5rem;
            }
            .navbar {
                padding: 1rem 5%;
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
</div>

<div class="container">
    <div class="hero" data-aos="fade-up" data-aos-duration="1000">
        <div class="badge">
            <i class="fas fa-music"></i> Live in Concert • 2026 World Tour
        </div>
        
        <!-- MODIFIKASI: Judul dengan efek satu huruf berjalan menghilang -->
        <div class="title-wrapper">
            <h1 class="walking-title" id="walkingTitle">
                <span id="line1">
                    <span class="letter">R</span><span class="letter">a</span><span class="letter">s</span><span class="letter">a</span><span class="letter">k</span><span class="letter">a</span><span class="letter">n</span>
                    <span class="letter"> </span>
                    <span class="letter">G</span><span class="letter">e</span><span class="letter">t</span><span class="letter">a</span><span class="letter">r</span><span class="letter">a</span><span class="letter">n</span>
                </span>
                <br>
                <span id="line2">
                    <span class="letter">K</span><span class="letter">o</span><span class="letter">n</span><span class="letter">s</span><span class="letter">e</span><span class="letter">r</span>
                    <span class="letter"> </span>
                    <span class="letter">T</span><span class="letter">e</span><span class="letter">r</span><span class="letter">b</span><span class="letter">a</span><span class="letter">i</span><span class="letter">k</span>
                </span>
            </h1>
        </div>
        
        <p>Dapatkan tiket untuk artis favoritmu dengan pengalaman digital yang mulus, cepat, dan modern. Jadilah bagian dari momen epik!</p>
        
        <!-- Tombol selalu mengarah ke halaman login -->
        <a href="auth/login.php" class="cta-btn">
            <i class="fas fa-arrow-right-to-bracket"></i> Login
        </a>
        
        <div class="stats">
            <div class="stat-item" data-aos="zoom-in" data-aos-delay="200">
                <div class="stat-number">50K+</div>
                <div>Penggemar Terhubung</div>
            </div>
            <div class="stat-item" data-aos="zoom-in" data-aos-delay="400">
                <div class="stat-number">30+</div>
                <div>Konser Epik</div>
            </div>
            <div class="stat-item" data-aos="zoom-in" data-aos-delay="600">
                <div class="stat-number">100%</div>
                <div>Pengalaman Terpercaya</div>
            </div>
        </div>
    </div>

    <div class="events-section" id="events">
        <div class="section-title" data-aos="fade-right">
            <i class="fas fa-guitar"></i> Upcoming Concert Picks
        </div>
        <div class="cards">
            <div class="event-card" data-aos="flip-left" data-aos-delay="100">
                <div class="event-icon">🎸</div>
                <h3>Rock Symphony</h3>
                <div class="event-date"><i class="far fa-calendar-alt"></i> 20 Mei 2026</div>
                <div class="event-price">Mulai IDR 350K</div>
            </div>
            <div class="event-card" data-aos="flip-left" data-aos-delay="300">
                <div class="event-icon">🎧</div>
                <h3>EDM Festival</h3>
                <div class="event-date"><i class="far fa-calendar-alt"></i> 5 Juni 2026</div>
                <div class="event-price">Mulai IDR 500K</div>
            </div>
            <div class="event-card" data-aos="flip-left" data-aos-delay="500">
                <div class="event-icon">🎤</div>
                <h3>Pop Grandeur</h3>
                <div class="event-date"><i class="far fa-calendar-alt"></i> 18 Juli 2026</div>
                <div class="event-price">Mulai IDR 275K</div>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>© 2026 ConcertHub — Experience the beat, live.</p>
    </div>
</div>

<!-- Scripts -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 120 });

    // Efek Satu Huruf Berjalan Menghilang Satu per Satu
    const letters = document.querySelectorAll('.letter');
    const totalLetters = letters.length;
    
    // Fungsi untuk reset semua huruf ke kondisi normal
    function resetAllLetters() {
        letters.forEach(letter => {
            letter.classList.remove('disappearing', 'appearing');
            letter.style.opacity = '1';
            letter.style.transform = '';
            letter.style.filter = '';
        });
    }
    
    // Fungsi untuk membuat huruf menghilang satu per satu
    function disappearLettersSequentially(index) {
        if (index >= totalLetters) {
            // Setelah semua huruf hilang, munculkan kembali satu per satu
            setTimeout(() => {
                appearLettersSequentially(0);
            }, 500);
            return;
        }
        
        // Tambahkan animasi menghilang pada huruf saat ini
        letters[index].classList.add('disappearing');
        
        // Panggil huruf berikutnya setelah jeda
        setTimeout(() => {
            disappearLettersSequentially(index + 1);
        }, 100); // Jeda 100ms antar huruf
    }
    
    // Fungsi untuk membuat huruf muncul satu per satu
    function appearLettersSequentially(index) {
        if (index >= totalLetters) {
            // Setelah semua huruf muncul, tunggu 2 detik lalu ulangi siklus
            setTimeout(() => {
                resetAllLetters();
                disappearLettersSequentially(0);
            }, 2000);
            return;
        }
        
        // Hapus class disappearing dan tambah class appearing
        letters[index].classList.remove('disappearing');
        letters[index].classList.add('appearing');
        
        // Hapus class appearing setelah animasi selesai
        setTimeout(() => {
            letters[index].classList.remove('appearing');
        }, 400);
        
        // Panggil huruf berikutnya setelah jeda
        setTimeout(() => {
            appearLettersSequentially(index + 1);
        }, 80); // Jeda 80ms antar huruf saat muncul
    }
    
    // Mulai efek setelah halaman dimuat dengan jeda 1 detik
    setTimeout(() => {
        disappearLettersSequentially(0);
    }, 1000);
    
    // Particle Background Effect (Keren & Modern)
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