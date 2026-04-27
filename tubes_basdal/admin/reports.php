<?php
include "../includes/auth.php";
include "../config/db.php";

if ($_SESSION['user']['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Initialize default values
$summary = [
    'total_events' => 0,
    'total_transactions' => 0,
    'overall_revenue' => 0,
    'avg_transaction' => 0
];

// Fetch report data from stored procedure
$result = $conn->query("CALL sp_report()");

// Check if stored procedure call was successful
if ($result === false) {
    $error_message = "Gagal memuat data laporan: " . $conn->error;
    $result = null;
}

// Fetch summary statistics with error handling
$summary_query = "SELECT 
    COALESCE((SELECT COUNT(*) FROM events), 0) as total_events,
    COALESCE((SELECT COUNT(*) FROM transactions WHERE status = 'completed'), 0) as total_transactions,
    COALESCE((SELECT SUM(total_amount) FROM transactions WHERE status = 'completed'), 0) as overall_revenue,
    COALESCE((SELECT AVG(total_amount) FROM transactions WHERE status = 'completed'), 0) as avg_transaction";

$summary_result = $conn->query($summary_query);

if ($summary_result && $summary_result->num_rows > 0) {
    $summary = $summary_result->fetch_assoc();
}

// Get date range for filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Laporan Penjualan | ConcertHub Admin</title>
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: #FF4788;
            box-shadow: 0 10px 30px -10px rgba(255, 71, 136, 0.3);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #FFB347, #FF4788);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .stat-label {
            font-size: 0.85rem;
            opacity: 0.7;
        }

        /* Alert Messages */
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

        .alert-warning {
            background: rgba(255, 152, 0, 0.15);
            border: 1px solid rgba(255, 152, 0, 0.3);
            color: #FF9800;
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

        .filter-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .filter-group input {
            width: 100%;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #fff;
            font-family: 'Inter', sans-serif;
        }

        .filter-group input:focus {
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

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
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
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        canvas {
            max-height: 300px;
        }

        /* Table Card */
        .table-card {
            background: rgba(20, 20, 30, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow-x: auto;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .table-title {
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-export {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 12px;
            color: #4CAF50;
            text-decoration: none;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-export:hover {
            background: rgba(76, 175, 80, 0.4);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .data-table th {
            font-weight: 600;
            color: #FF8C42;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table td {
            font-size: 0.9rem;
        }

        .data-table tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        /* Revenue Highlight */
        .revenue-highlight {
            color: #4CAF50;
            font-weight: 700;
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

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
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
            .charts-section {
                grid-template-columns: 1fr;
            }
            .filter-form {
                flex-direction: column;
            }
            .filter-group {
                width: 100%;
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
        .filter-section,
        .chart-card,
        .table-card {
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
            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Admin'); ?>
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
                <i class="fas fa-chart-line"></i> Laporan Penjualan
            </div>
            <div class="page-subtitle">
                Analisis lengkap penjualan tiket dan pendapatan konser
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-warning" data-aos="fade-up">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Summary Stats -->
        <div class="stats-grid" data-aos="fade-up" data-aos-delay="150">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-value"><?php echo number_format($summary['total_events'] ?? 0); ?></div>
                <div class="stat-label">Total Event</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-value"><?php echo number_format($summary['total_transactions'] ?? 0); ?></div>
                <div class="stat-label">Total Transaksi</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-value">Rp <?php echo number_format($summary['overall_revenue'] ?? 0, 0, ',', '.'); ?></div>
                <div class="stat-label">Total Pendapatan</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="stat-value">Rp <?php echo number_format($summary['avg_transaction'] ?? 0, 0, ',', '.'); ?></div>
                <div class="stat-label">Rata-rata Transaksi</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section" data-aos="fade-up" data-aos-delay="200">
            <div class="filter-title">
                <i class="fas fa-filter"></i>
                Filter Laporan
            </div>
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <label>Dari Tanggal</label>
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="filter-group">
                    <label>Sampai Tanggal</label>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search"></i> Terapkan Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Charts -->
        <div class="charts-section" data-aos="fade-up" data-aos-delay="250">
            <div class="chart-card">
                <div class="chart-title">
                    <i class="fas fa-chart-pie" style="color: #FF4788;"></i>
                    Distribusi Penjualan per Event
                </div>
                <canvas id="salesDistributionChart"></canvas>
            </div>
            <div class="chart-card">
                <div class="chart-title">
                    <i class="fas fa-chart-line" style="color: #FF8C42;"></i>
                    Tren Pendapatan per Event
                </div>
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>

        <!-- Report Table -->
        <div class="table-card" data-aos="fade-up" data-aos-delay="300">
            <div class="table-header">
                <div class="table-title">
                    <i class="fas fa-table"></i>
                    Detail Laporan Penjualan
                </div>
                <button onclick="exportToExcel()" class="btn-export">
                    <i class="fas fa-file-excel"></i> Export ke Excel
                </button>
            </div>
            
            <?php if ($result && $result->num_rows > 0): ?>
                <table class="data-table" id="reportTable">
                    <thead>
                        <tr>
                            <th>ID Event</th>
                            <th>Nama Event</th>
                            <th>Total Order</th>
                            <th>Total Pendapatan</th>
                            <th>Rata-rata Order</th>
                            <th>Kinerja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $chart_labels = [];
                        $chart_sales = [];
                        $chart_revenues = [];
                        while($row = $result->fetch_assoc()): 
                            $event_name = $row['title'] ?? $row['name'] ?? '-';
                            $total_order = $row['total_order'] ?? 0;
                            $total_revenue = $row['total_revenue'] ?? 0;
                            $avg_order = $row['avg_order'] ?? 0;
                            
                            $chart_labels[] = addslashes($event_name);
                            $chart_sales[] = $total_order;
                            $chart_revenues[] = $total_revenue;
                            
                            // Determine performance level
                            $performance = '';
                            if ($total_revenue > 10000000) {
                                $performance = '<span style="color: #4CAF50;"><i class="fas fa-crown"></i> Excellent</span>';
                            } elseif ($total_revenue > 5000000) {
                                $performance = '<span style="color: #FF9800;"><i class="fas fa-chart-line"></i> Good</span>';
                            } elseif ($total_revenue > 0) {
                                $performance = '<span style="color: #FF9800;"><i class="fas fa-chart-simple"></i> Fair</span>';
                            } else {
                                $performance = '<span style="color: #f44336;"><i class="fas fa-chart-line"></i> Need Improvement</span>';
                            }
                        ?>
                            <tr>
                                <td><?php echo $row['event_id'] ?? '-'; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($event_name); ?></strong>
                                  </td>
                                <td><?php echo number_format($total_order); ?> tiket</td>
                                <td class="revenue-highlight">Rp <?php echo number_format($total_revenue, 0, ',', '.'); ?></td>
                                <td>Rp <?php echo number_format($avg_order, 0, ',', '.'); ?></td>
                                <td><?php echo $performance; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <p>Belum ada data penjualan</p>
                    <p style="font-size: 0.85rem; margin-top: 0.5rem;">Data akan muncul setelah ada transaksi penjualan tiket</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
    AOS.init({ once: true, offset: 120, duration: 800 });

    // Chart data from PHP
    const chartLabels = <?php echo json_encode($chart_labels); ?>;
    const chartSales = <?php echo json_encode($chart_sales); ?>;
    const chartRevenues = <?php echo json_encode($chart_revenues); ?>;

    // Sales Distribution Chart (Pie)
    if (document.getElementById('salesDistributionChart')) {
        const ctx1 = document.getElementById('salesDistributionChart').getContext('2d');
        new Chart(ctx1, {
            type: 'pie',
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartSales,
                    backgroundColor: [
                        '#FF4788',
                        '#FF8C42',
                        '#FFD93D',
                        '#6BCB77',
                        '#4D96FF',
                        '#9B59B6',
                        '#E74C3C',
                        '#3498DB'
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
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.raw + ' tiket';
                            }
                        }
                    }
                }
            }
        });
    }

    // Revenue Trend Chart (Bar)
    if (document.getElementById('revenueTrendChart')) {
        const ctx2 = document.getElementById('revenueTrendChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: chartRevenues,
                    backgroundColor: 'rgba(255, 71, 136, 0.6)',
                    borderColor: '#FF4788',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        labels: { color: '#fff' }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Pendapatan: Rp ' + context.raw.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        grid: { color: 'rgba(255, 255, 255, 0.1)' },
                        ticks: { 
                            color: '#fff',
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    },
                    x: {
                        grid: { color: 'rgba(255, 255, 255, 0.1)' },
                        ticks: { color: '#fff' }
                    }
                }
            }
        });
    }

    // Export to Excel function
    function exportToExcel() {
        const table = document.getElementById('reportTable');
        if (table) {
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table);
            XLSX.utils.book_append_sheet(wb, ws, 'Laporan Penjualan');
            XLSX.writeFile(wb, 'laporan_penjualan_' + new Date().toISOString().slice(0,19) + '.xlsx');
        }
    }

    // Particle Background Effect
    const canvas = document.getElementById('particle-canvas');
    if (canvas) {
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
    }
</script>
</body>
</html>

<?php 
// Clean up
if ($result) {
    $result->free();
}
$conn->next_result(); 
?>