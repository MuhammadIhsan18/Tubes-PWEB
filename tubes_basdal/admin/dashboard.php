<?php
include "../includes/auth.php";
include "../config/db.php";

// Cek role admin
if ($_SESSION['user']['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Inisialisasi default values
$stats = [
    'total_users' => 0,
    'total_events' => 0,
    'total_tickets_sold' => 0,
    'total_revenue' => 0
];

// Cek apakah stored procedure ada
$sp_check = $conn->query("SHOW PROCEDURE STATUS WHERE Name = 'sp_admin_dashboard'");
if ($sp_check && $sp_check->num_rows > 0) {
    // Ambil data dari stored procedure jika ada
    $result = $conn->query("CALL sp_admin_dashboard()");
    if ($result && $result->num_rows > 0) {
        $stats = $result->fetch_assoc();
    }
    // Clear stored procedure results
    while ($conn->more_results() && $conn->next_result()) {
        if ($inner_result = $conn->store_result()) {
            $inner_result->free();
        }
    }
} else {
    // Jika stored procedure tidak ada, ambil data dengan query biasa
    // Total Users
    $user_query = $conn->query("SELECT COUNT(*) as total FROM users");
    if ($user_query && $user_query->num_rows > 0) {
        $stats['total_users'] = $user_query->fetch_assoc()['total'];
    }
    
    // Total Events
    $event_query = $conn->query("SELECT COUNT(*) as total FROM events");
    if ($event_query && $event_query->num_rows > 0) {
        $stats['total_events'] = $event_query->fetch_assoc()['total'];
    }
    
    // Total Tickets Sold (dari orders)
    $tickets_query = $conn->query("SELECT COALESCE(SUM(quantity), 0) as total FROM orders WHERE status = 'paid'");
    if ($tickets_query && $tickets_query->num_rows > 0) {
        $stats['total_tickets_sold'] = $tickets_query->fetch_assoc()['total'];
    }
    
    // Total Revenue
    $revenue_query = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'paid'");
    if ($revenue_query && $revenue_query->num_rows > 0) {
        $stats['total_revenue'] = $revenue_query->fetch_assoc()['total'];
    }
}

// Ambil data event terbaru
$events_query = "SELECT * FROM events ORDER BY event_date DESC LIMIT 5";
$events_result = $conn->query($events_query);

// Ambil data user terbaru - cek kolom yang tersedia
$users_columns = [];
$col_check = $conn->query("SHOW COLUMNS FROM users");
if ($col_check) {
    while ($col = $col_check->fetch_assoc()) {
        $users_columns[] = $col['Field'];
    }
}

// Tentukan kolom yang akan digunakan
$user_name_col = in_array('name', $users_columns) ? 'name' : (in_array('username', $users_columns) ? 'username' : 'id');
$user_email_col = in_array('email', $users_columns) ? 'email' : (in_array('email_address', $users_columns) ? 'email_address' : 'id');
$user_status_col = in_array('is_active', $users_columns) ? 'is_active' : (in_array('status', $users_columns) ? 'status' : '1');
$user_date_col = in_array('created_at', $users_columns) ? 'created_at' : (in_array('register_date', $users_columns) ? 'register_date' : 'id');

$users_query = "SELECT * FROM users ORDER BY $user_date_col DESC LIMIT 5";
$users_result = $conn->query($users_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Dashboard Admin | ConcertHub</title>
    <!-- Google Fonts & Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* Page Title */
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #FFFFFF, #FFD6E0);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .page-subtitle {
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 2rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(20, 20, 30, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
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

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: rgba(20, 20, 30, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        canvas {
            max-height: 300px;
        }

        /* Tables Section */
        .tables-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .table-card {
            background: rgba(20, 20, 30, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .table-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: space-between;
        }

        .view-all {
            font-size: 0.8rem;
            color: #FF4788;
            text-decoration: none;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .data-table th {
            font-weight: 600;
            color: #FF8C42;
            font-size: 0.85rem;
        }

        .data-table td {
            font-size: 0.85rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-active {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .status-inactive {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        /* Navigation Menu */
        .nav-menu {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }

        .nav-item {
            background: rgba(20, 20, 30, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.75rem 1.5rem;
            border-radius: 16px;
            text-decoration: none;
            color: #fff;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .nav-item:hover {
            background: rgba(255, 71, 136, 0.2);
            border-color: #FF4788;
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .charts-section,
            .tables-section {
                grid-template-columns: 1fr;
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

        .stat-card,
        .chart-card,
        .table-card,
        .nav-item {
            animation: fadeInUp 0.5s ease-out forwards;
        }
    </style>
</head>
<body>

<canvas id="particle-canvas"></canvas>

<div class="navbar">
    <div class="logo">
        <i class="fas fa-ticket-alt"></i> ConcertHub Admin
    </div>
    <div class="user-info">
        <span class="user-name">
            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user']['username'] ?? $_SESSION['user']['name'] ?? 'Admin'); ?>
        </span>
        <a href="../auth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<div class="container">
    <div class="main-content">
        <div class="page-title" data-aos="fade-right">
            Dashboard Admin
        </div>
        <div class="page-subtitle" data-aos="fade-right" data-aos-delay="100">
            Selamat datang kembali! Berikut adalah statistik lengkap platform Anda
        </div>

        <!-- Navigation Menu -->
        <div class="nav-menu" data-aos="fade-up" data-aos-delay="200">
            <a href="events.php" class="nav-item">
                <i class="fas fa-calendar-alt"></i> Kelola Event
            </a>
            <a href="users.php" class="nav-item">
                <i class="fas fa-users"></i> Kelola User
            </a>
            <a href="reports.php" class="nav-item">
                <i class="fas fa-chart-line"></i> Laporan
            </a>
            <a href="transactions.php" class="nav-item">
                <i class="fas fa-credit-card"></i> Transaksi
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid" data-aos="fade-up" data-aos-delay="300">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_users'] ?? 0); ?></div>
                <div class="stat-label">Total Pengguna</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-music"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_events'] ?? 0); ?></div>
                <div class="stat-label">Total Event</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_tickets_sold'] ?? 0); ?></div>
                <div class="stat-label">Tiket Terjual</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-value">Rp <?php echo number_format($stats['total_revenue'] ?? 0, 0, ',', '.'); ?></div>
                <div class="stat-label">Total Pendapatan</div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-section">
            <div class="chart-card" data-aos="fade-up" data-aos-delay="400">
                <div class="chart-title">
                    <i class="fas fa-chart-pie" style="color: #FF4788;"></i>
                    Distribusi Kategori Event
                </div>
                <canvas id="categoryChart"></canvas>
            </div>
            <div class="chart-card" data-aos="fade-up" data-aos-delay="500">
                <div class="chart-title">
                    <i class="fas fa-chart-line" style="color: #FF8C42;"></i>
                    Penjualan Tiket per Bulan
                </div>
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Recent Data Tables -->
        <div class="tables-section">
            <div class="table-card" data-aos="fade-up" data-aos-delay="600">
                <div class="table-title">
                    <span><i class="fas fa-calendar"></i> Event Terbaru</span>
                    <a href="events.php" class="view-all">Lihat Semua →</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama Event</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($events_result && $events_result->num_rows > 0): ?>
                            <?php while($event = $events_result->fetch_assoc()): 
                                $event_name = $event['title'] ?? $event['name'] ?? '-';
                                $event_date = $event['event_date'] ?? '';
                                $event_status = isset($event['status']) ? $event['status'] : (isset($event['is_active']) && $event['is_active'] ? 'upcoming' : 'completed');
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event_name); ?></td>
                                    <td><?php echo $event_date ? date('d/m/Y', strtotime($event_date)) : '-'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo ($event_status == 'upcoming' || $event_status == 'active' || $event_status == 1) ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo ($event_status == 'upcoming' || $event_status == 'active' || $event_status == 1) ? 'Aktif' : 'Nonaktif'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">Belum ada event</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-card" data-aos="fade-up" data-aos-delay="700">
                <div class="table-title">
                    <span><i class="fas fa-user-plus"></i> Pengguna Terbaru</span>
                    <a href="users.php" class="view-all">Lihat Semua →</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users_result && $users_result->num_rows > 0): ?>
                            <?php while($user = $users_result->fetch_assoc()): 
                                $user_name = $user['username'] ?? $user['name'] ?? '-';
                                $user_email = $user['email'] ?? '-';
                                $user_status = $user['is_active'] ?? 1;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user_name); ?></td>
                                    <td><?php echo htmlspecialchars($user_email); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $user_status ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $user_status ? 'Aktif' : 'Nonaktif'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">Belum ada pengguna</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 120, duration: 800 });

    // Chart.js - Category Distribution
    const ctx1 = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctx1, {
        type: 'doughnut',
        data: {
            labels: ['Rock', 'EDM', 'Pop', 'Jazz', 'Classical'],
            datasets: [{
                data: [30, 25, 35, 5, 5],
                backgroundColor: [
                    '#FF4788',
                    '#FF8C42',
                    '#FFD93D',
                    '#6BCB77',
                    '#4D96FF'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#fff',
                        font: { size: 11 }
                    }
                }
            }
        }
    });

    // Chart.js - Monthly Sales
    const ctx2 = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx2, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
            datasets: [{
                label: 'Tiket Terjual',
                data: [65, 89, 120, 145, 178, 210, 245, 278, 310, 345, 380, 420],
                borderColor: '#FF4788',
                backgroundColor: 'rgba(255, 71, 136, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#FF8C42',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    labels: { color: '#fff' }
                }
            },
            scales: {
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.1)' },
                    ticks: { color: '#fff' }
                },
                x: {
                    grid: { color: 'rgba(255, 255, 255, 0.1)' },
                    ticks: { color: '#fff' }
                }
            }
        }
    });

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