<?php
include "../includes/auth.php";
include "../config/db.php";

if ($_SESSION['user']['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Get table structure to detect column names
$columns = [];
$col_query = $conn->query("SHOW COLUMNS FROM events");
if ($col_query) {
    while ($col = $col_query->fetch_assoc()) {
        $columns[] = $col['Field'];
    }
}

// Detect primary key column
$primary_key = 'id';
$possible_keys = ['id', 'event_id', 'eventId', 'ID', 'EventID', 'id_event'];
foreach ($possible_keys as $key) {
    if (in_array($key, $columns)) {
        $primary_key = $key;
        break;
    }
}

// Detect status column
$status_column = 'is_active';
$possible_status = ['is_active', 'status', 'active', 'aktif'];
foreach ($possible_status as $status) {
    if (in_array($status, $columns)) {
        $status_column = $status;
        break;
    }
}

/* =======================
   HANDLE DELETE
======================= */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $event_id = (int) $_GET['delete'];
    
    $stmt = $conn->prepare("DELETE FROM events WHERE $primary_key = ?");
    if ($stmt === false) {
        $error_message = "Error preparing delete: " . $conn->error;
    } else {
        $stmt->bind_param("i", $event_id);
        if ($stmt->execute()) {
            $success_message = "Event berhasil dihapus!";
        } else {
            $error_message = "Gagal menghapus event: " . $stmt->error;
        }
        $stmt->close();
    }
}

/* =======================
   HANDLE TOGGLE STATUS
======================= */
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $event_id = (int) $_GET['toggle'];
    
    $stmt = $conn->prepare("UPDATE events SET $status_column = NOT $status_column WHERE $primary_key = ?");
    if ($stmt === false) {
        $error_message = "Error preparing toggle: " . $conn->error;
    } else {
        $stmt->bind_param("i", $event_id);
        if ($stmt->execute()) {
            $success_message = "Status event berhasil diubah!";
        } else {
            $error_message = "Gagal mengubah status event: " . $stmt->error;
        }
        $stmt->close();
    }
}

/* =======================
   FETCH DATA
======================= */
$result = $conn->query("SELECT * FROM events ORDER BY event_date DESC");

// If query fails, show error
if ($result === false) {
    $error_message = "Error fetching data: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Kelola Event | ConcertHub Admin</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #FFFFFF, #FFD6E0);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .btn-add {
            background: linear-gradient(95deg, #FF4788, #FF8C42);
            padding: 0.75rem 1.5rem;
            border-radius: 16px;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(255, 71, 136, 0.4);
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

        /* Table Card */
        .table-card {
            background: rgba(20, 20, 30, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow-x: auto;
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

        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
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

        /* Status Badge */
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

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-icon {
            padding: 0.5rem 1rem;
            border-radius: 10px;
            color: #fff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-edit {
            background: rgba(33, 150, 243, 0.2);
            border: 1px solid rgba(33, 150, 243, 0.3);
            color: #2196F3;
        }

        .btn-edit:hover {
            background: rgba(33, 150, 243, 0.4);
            transform: translateY(-2px);
        }

        .btn-toggle {
            background: rgba(255, 152, 0, 0.2);
            border: 1px solid rgba(255, 152, 0, 0.3);
            color: #FF9800;
        }

        .btn-toggle:hover {
            background: rgba(255, 152, 0, 0.4);
            transform: translateY(-2px);
        }

        .btn-delete {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.3);
            color: #f44336;
        }

        .btn-delete:hover {
            background: rgba(244, 67, 54, 0.4);
            transform: translateY(-2px);
        }

        /* Back to Dashboard */
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
            .page-header {
                flex-direction: column;
                align-items: flex-start;
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
            .data-table th,
            .data-table td {
                padding: 0.75rem;
                font-size: 0.8rem;
            }
            .btn-icon {
                padding: 0.4rem 0.8rem;
                font-size: 0.7rem;
            }
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
            <div>
                <div class="page-title">
                    <i class="fas fa-calendar-alt"></i> Kelola Event
                </div>
                <p style="color: rgba(255,255,255,0.6); margin-top: 0.5rem;">Kelola semua event konser di platform ConcertHub</p>
            </div>
            <a href="event-add.php" class="btn-add">
                <i class="fas fa-plus"></i> Tambah Event Baru
            </a>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" data-aos="fade-up">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error" data-aos="fade-up">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <div class="table-card" data-aos="fade-up" data-aos-delay="200">
            <?php if ($result && $result->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Event</th>
                            <th>Tanggal</th>
                            <th>Lokasi</th>
                            <th>Harga</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($event = $result->fetch_assoc()): 
                            // Get event ID from various possible field names
                            $event_id = null;
                            foreach ($possible_keys as $key) {
                                if (isset($event[$key])) {
                                    $event_id = $event[$key];
                                    break;
                                }
                            }
                            
                            // Get status value
                            $is_active = 1;
                            foreach ($possible_status as $status) {
                                if (isset($event[$status])) {
                                    $is_active = $event[$status];
                                    break;
                                }
                            }
                        ?>
                            <tr>
                                <td><?php echo $event_id ?? '-'; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($event['name'] ?? $event['title'] ?? $event['event_name'] ?? '-'); ?></strong>
                                    <?php if (!empty($event['category'])): ?>
                                        <br><small style="color: #FF8C42;"><?php echo htmlspecialchars($event['category']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="far fa-calendar-alt" style="color: #FF8C42;"></i>
                                    <?php 
                                    $date_value = $event['event_date'] ?? $event['tanggal'] ?? null;
                                    echo $date_value ? date('d/m/Y', strtotime($date_value)) : '-'; 
                                    ?>
                                    <?php if (!empty($event['event_time']) || !empty($event['waktu'])): ?>
                                        <br><small><?php echo htmlspecialchars($event['event_time'] ?? $event['waktu'] ?? ''); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="fas fa-map-marker-alt" style="color: #FF4788;"></i>
                                    <?php echo htmlspecialchars($event['location'] ?? $event['lokasi'] ?? '-'); ?>
                                </td>
                                <td>
                                    <i class="fas fa-ticket-alt" style="color: #4CAF50;"></i>
                                    <?php 
                                    $price_value = $event['price'] ?? $event['harga'] ?? 0;
                                    echo 'Rp ' . number_format($price_value, 0, ',', '.'); 
                                    ?>
                                  </td>
                                <td>
                                    <span class="status-badge <?php echo $is_active ? 'status-active' : 'status-inactive'; ?>">
                                        <i class="fas <?php echo $is_active ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                        <?php echo $is_active ? 'Aktif' : 'Nonaktif'; ?>
                                    </span>
                                  </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($event_id): ?>
                                            <a href="event-edit.php?id=<?php echo $event_id; ?>" class="btn-icon btn-edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                                <span>Edit</span>
                                            </a>
                                            <a href="?toggle=<?php echo $event_id; ?>" class="btn-icon btn-toggle" title="Toggle Status" onclick="return confirm('Ubah status event ini?')">
                                                <i class="fas fa-power-off"></i>
                                                <span>Toggle</span>
                                            </a>
                                            <a href="?delete=<?php echo $event_id; ?>" class="btn-icon btn-delete" title="Hapus" onclick="return confirm('Yakin ingin menghapus event ini? Data tidak dapat dikembalikan!')">
                                                <i class="fas fa-trash"></i>
                                                <span>Hapus</span>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: red;">ID Error</span>
                                        <?php endif; ?>
                                    </div>
                                  </td>
                              </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>Belum ada event yang tersedia</p>
                    <a href="event-add.php" class="btn-add" style="margin-top: 1rem; display: inline-flex;">
                        <i class="fas fa-plus"></i> Tambah Event Pertama
                    </a>
                </div>
            <?php endif; ?>
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