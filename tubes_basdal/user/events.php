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
$user_name = $user['name'] ?? $user['username'] ?? $user['fullname'] ?? $user['nama'] ?? 'User';

// Get filter parameters
$category = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date';

// Get all events first (simple query without prepared statements)
$sql = "SELECT * FROM events WHERE 1=1";

// Add filters
if (!empty($category)) {
    $sql .= " AND category = '$category'";
}

if (!empty($search)) {
    $sql .= " AND (name LIKE '%$search%' OR location LIKE '%$search%' OR venue LIKE '%$search%')";
}

// Add sorting
switch ($sort) {
    case 'date':
        $sql .= " ORDER BY event_date ASC";
        break;
    case 'price_asc':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY price DESC";
        break;
    case 'name':
        $sql .= " ORDER BY name ASC";
        break;
    default:
        $sql .= " ORDER BY event_date ASC";
}

// Execute query
$result = $conn->query($sql);

// Check if query failed
if ($result === false) {
    $error_message = "Error fetching data: " . $conn->error;
    $result = null;
}

// Get categories for filter
$category_result = $conn->query("SELECT DISTINCT category FROM events WHERE category IS NOT NULL AND category != '' ORDER BY category");
if ($category_result === false) {
    $category_result = null;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Daftar Event | ConcertHub</title>
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
            max-width: 1400px;
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

        /* Page Header */
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

        /* Back Link */
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

        /* Alert */
        .alert-error {
            background: rgba(244, 67, 54, 0.15);
            border: 1px solid rgba(244, 67, 54, 0.3);
            color: #f44336;
            padding: 1rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Filter Section */
        .filter-section {
            background: rgba(20, 20, 30, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }

        .filter-form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 150px;
        }

        .filter-group label {
            display: block;
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #fff;
            font-family: 'Inter', sans-serif;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #FF4788;
        }

        .btn-filter {
            background: linear-gradient(95deg, #FF4788, #FF8C42);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(255, 71, 136, 0.4);
        }

        .btn-reset {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-reset:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Sort Section */
        .sort-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .result-count {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .sort-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .sort-btn {
            padding: 0.4rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            color: #fff;
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .sort-btn:hover,
        .sort-btn.active {
            background: rgba(255, 71, 136, 0.2);
            border-color: #FF4788;
            color: #FF4788;
        }

        /* Events Grid */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .event-card {
            background: rgba(20, 20, 30, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .event-card:hover {
            transform: translateY(-5px);
            border-color: #FF4788;
            box-shadow: 0 20px 35px -10px rgba(255, 71, 136, 0.3);
        }

        .event-image {
            height: 180px;
            background: linear-gradient(135deg, #FF4788, #FF8C42);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            position: relative;
        }

        .event-category {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .event-content {
            padding: 1.5rem;
        }

        .event-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .event-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .event-info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .event-info-item i {
            width: 20px;
            color: #FF8C42;
        }

        .event-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #4CAF50;
            margin: 1rem 0;
        }

        .event-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn-detail {
            flex: 1;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.6rem;
            border-radius: 12px;
            color: #fff;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-detail:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .btn-order {
            flex: 1;
            background: linear-gradient(95deg, #FF4788, #FF8C42);
            padding: 0.6rem;
            border-radius: 12px;
            color: #fff;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px -5px rgba(255, 71, 136, 0.4);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem;
            background: rgba(20, 20, 30, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state p {
            color: rgba(255, 255, 255, 0.6);
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
            .page-title {
                font-size: 1.5rem;
            }
            .navbar {
                padding: 0.75rem 1rem;
            }
            .logo {
                font-size: 1.2rem;
            }
            .filter-form {
                flex-direction: column;
            }
            .filter-group {
                width: 100%;
            }
            .events-grid {
                grid-template-columns: 1fr;
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
            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?>
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
                <i class="fas fa-music"></i> Daftar Event
            </div>
            <div class="page-subtitle">
                Temukan event konser favoritmu dan pesan tiket sekarang
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert-error" data-aos="fade-up">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section" data-aos="fade-up" data-aos-delay="150">
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <label><i class="fas fa-search"></i> Cari Event</label>
                    <input type="text" name="search" placeholder="Nama event, lokasi..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-tag"></i> Kategori</label>
                    <select name="category">
                        <option value="">Semua Kategori</option>
                        <?php if ($category_result && $category_result->num_rows > 0): ?>
                            <?php while($cat = $category_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo ($category == $cat['category']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
                <div class="filter-group">
                    <a href="events.php" class="btn-reset">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Sort Section -->
        <div class="sort-section" data-aos="fade-up" data-aos-delay="200">
            <div class="result-count">
                <i class="fas fa-list"></i> Menampilkan <?php echo ($result) ? $result->num_rows : 0; ?> event
            </div>
            <div class="sort-buttons">
                <span style="font-size: 0.8rem; opacity: 0.6;">Urutkan:</span>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'date'])); ?>" class="sort-btn <?php echo ($sort == 'date') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar"></i> Tanggal
                </a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name'])); ?>" class="sort-btn <?php echo ($sort == 'name') ? 'active' : ''; ?>">
                    <i class="fas fa-sort-alpha-down"></i> Nama
                </a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_asc'])); ?>" class="sort-btn <?php echo ($sort == 'price_asc') ? 'active' : ''; ?>">
                    <i class="fas fa-arrow-up"></i> Harga (Rendah - Tinggi)
                </a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_desc'])); ?>" class="sort-btn <?php echo ($sort == 'price_desc') ? 'active' : ''; ?>">
                    <i class="fas fa-arrow-down"></i> Harga (Tinggi - Rendah)
                </a>
            </div>
        </div>

        <!-- Events Grid -->
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="events-grid" data-aos="fade-up" data-aos-delay="250">
                <?php while($event = $result->fetch_assoc()): 
                    $event_id = $event['id'] ?? $event['event_id'] ?? $event['id_event'] ?? null;
                    $event_name = $event['name'] ?? $event['title'] ?? '-';
                    $event_category = $event['category'] ?? 'Umum';
                    $event_date = $event['event_date'] ?? '';
                    $event_time = $event['event_time'] ?? '';
                    $event_location = $event['location'] ?? $event['lokasi'] ?? '-';
                    $event_venue = $event['venue'] ?? $event['tempat'] ?? '';
                    $event_price = $event['price'] ?? $event['harga'] ?? 0;
                ?>
                    <div class="event-card">
                        <div class="event-image">
                            <span class="event-category"><?php echo htmlspecialchars($event_category); ?></span>
                            <i class="fas fa-music"></i>
                        </div>
                        <div class="event-content">
                            <h3 class="event-title"><?php echo htmlspecialchars($event_name); ?></h3>
                            <div class="event-info">
                                <div class="event-info-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?php echo $event_date ? date('d F Y', strtotime($event_date)) : 'TBA'; ?></span>
                                    <?php if($event_time): ?>
                                        <span>• <?php echo date('H:i', strtotime($event_time)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="event-info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($event_location); ?><?php echo $event_venue ? ' - ' . htmlspecialchars($event_venue) : ''; ?></span>
                                </div>
                            </div>
                            <div class="event-price">
                                Rp <?php echo number_format($event_price, 0, ',', '.'); ?>
                            </div>
                            <div class="event-actions">
                                <a href="event-detail.php?id=<?php echo $event_id; ?>" class="btn-detail">
                                    <i class="fas fa-info-circle"></i> Detail
                                </a>
                                <!-- PERBAIKAN: Link ke order.php dengan event_id -->
                                <a href="order.php?event_id=<?php echo $event_id; ?>" class="btn-order">
                                    <i class="fas fa-ticket-alt"></i> Pesan Tiket
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state" data-aos="fade-up">
                <i class="fas fa-calendar-times"></i>
                <p>Belum ada event yang tersedia</p>
                <p style="font-size: 0.85rem; margin-top: 0.5rem;">Silakan cek kembali nanti untuk event terbaru</p>
            </div>
        <?php endif; ?>

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