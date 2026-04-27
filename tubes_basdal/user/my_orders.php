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
$user_name = $user['username'] ?? $user['name'] ?? 'User';
$user_id = $user['user_id'] ?? $user['id'] ?? null;

if (!$user_id) {
    die("Error: User ID not found");
}

// Get filter status
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

// Query untuk mengambil pesanan dari tabel orders
$where_conditions = ["o.user_id = $user_id"];

if (!empty($status_filter)) {
    $where_conditions[] = "o.status = '$status_filter'";
}

$where_clause = implode(" AND ", $where_conditions);

// Query dengan JOIN ke events
$sql = "SELECT o.*, 
        e.title as event_name,
        e.event_date,
        e.location,
        e.venue,
        e.image_path
        FROM orders o
        LEFT JOIN events e ON o.event_id = e.event_id
        WHERE $where_clause
        ORDER BY o.created_at DESC";

$result = $conn->query($sql);

$orders = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Get total spent (hanya yang status paid)
$total_sql = "SELECT SUM(total_amount) as total FROM orders WHERE user_id = $user_id AND status = 'paid'";
$total_result = $conn->query($total_sql);
$total_spent = 0;
if ($total_result && $total_result->num_rows > 0) {
    $total_spent = $total_result->fetch_assoc()['total'] ?? 0;
}

// Get total orders count
$count_sql = "SELECT COUNT(*) as count FROM orders WHERE user_id = $user_id";
$count_result = $conn->query($count_sql);
$total_orders = 0;
if ($count_result && $count_result->num_rows > 0) {
    $total_orders = $count_result->fetch_assoc()['count'] ?? 0;
}

// Get status counts for filter
$status_counts = [
    'pending' => 0,
    'paid' => 0,
    'cancelled' => 0
];

$status_query = "SELECT status, COUNT(*) as count FROM orders WHERE user_id = $user_id GROUP BY status";
$status_result = $conn->query($status_query);
if ($status_result) {
    while ($row = $status_result->fetch_assoc()) {
        if (isset($status_counts[$row['status']])) {
            $status_counts[$row['status']] = $row['count'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Pesanan Saya | ConcertHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0a0a0a; color: #fff; overflow-x: hidden; }
        #particle-canvas { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; }
        .container { position: relative; z-index: 2; max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .navbar { position: fixed; top: 0; left: 0; width: 100%; padding: 1rem 5%; display: flex; justify-content: space-between; align-items: center; backdrop-filter: blur(12px); background: rgba(10, 10, 10, 0.8); z-index: 100; border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
        .logo { font-size: 1.5rem; font-weight: 800; background: linear-gradient(135deg, #FF4788, #FF8C42); -webkit-background-clip: text; background-clip: text; color: transparent; letter-spacing: -0.5px; }
        .logo i { color: #FF4788; margin-right: 8px; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .user-name { font-size: 0.9rem; font-weight: 500; }
        .logout-btn { background: rgba(255, 71, 136, 0.2); border: 1px solid rgba(255, 71, 136, 0.3); padding: 0.5rem 1rem; border-radius: 12px; color: #FF8C8C; text-decoration: none; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; }
        .logout-btn:hover { background: rgba(255, 71, 136, 0.4); border-color: #FF4788; }
        .main-content { margin-top: 80px; }
        .page-header { margin-bottom: 2rem; }
        .page-title { font-size: 2rem; font-weight: 700; background: linear-gradient(135deg, #FFFFFF, #FFD6E0); -webkit-background-clip: text; background-clip: text; color: transparent; margin-bottom: 0.5rem; }
        .page-subtitle { color: rgba(255, 255, 255, 0.6); }
        .back-link { display: inline-flex; align-items: center; gap: 8px; color: rgba(255, 255, 255, 0.6); text-decoration: none; margin-bottom: 1rem; transition: color 0.3s ease; }
        .back-link:hover { color: #FF4788; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: rgba(20, 20, 30, 0.7); backdrop-filter: blur(10px); border-radius: 20px; padding: 1.2rem; border: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.3s ease; text-align: center; }
        .stat-card:hover { transform: translateY(-3px); border-color: #FF4788; }
        .stat-value { font-size: 1.8rem; font-weight: 800; background: linear-gradient(135deg, #FFB347, #FF4788); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .stat-label { font-size: 0.8rem; opacity: 0.7; margin-top: 0.3rem; }
        .filter-tabs { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .filter-tab { padding: 0.5rem 1.2rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 30px; color: #fff; text-decoration: none; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; }
        .filter-tab:hover, .filter-tab.active { background: rgba(255, 71, 136, 0.2); border-color: #FF4788; color: #FF4788; }
        .filter-tab .count { background: rgba(255, 255, 255, 0.2); padding: 0.1rem 0.5rem; border-radius: 20px; font-size: 0.7rem; }
        .orders-grid { display: flex; flex-direction: column; gap: 1rem; }
        .order-card { background: rgba(20, 20, 30, 0.7); backdrop-filter: blur(10px); border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.1); overflow: hidden; transition: all 0.3s ease; }
        .order-card:hover { transform: translateX(5px); border-color: #FF4788; }
        .order-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; background: rgba(255, 71, 136, 0.1); border-bottom: 1px solid rgba(255, 255, 255, 0.1); flex-wrap: wrap; gap: 0.5rem; }
        .order-code { font-weight: 700; font-size: 1rem; color: #FF4788; }
        .order-date { font-size: 0.8rem; opacity: 0.7; }
        .order-body { display: flex; padding: 1.5rem; gap: 1.5rem; flex-wrap: wrap; }
        .order-image { width: 100px; height: 100px; background: linear-gradient(135deg, #FF4788, #FF8C42); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 2rem; }
        .order-details { flex: 1; min-width: 200px; }
        .order-event-name { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; }
        .order-info-item { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: rgba(255, 255, 255, 0.7); margin-bottom: 0.3rem; }
        .order-info-item i { width: 20px; color: #FF8C42; }
        .order-total { text-align: right; min-width: 150px; }
        .order-total-label { font-size: 0.8rem; opacity: 0.7; }
        .order-total-value { font-size: 1.3rem; font-weight: 700; color: #4CAF50; }
        .order-footer { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; background: rgba(0, 0, 0, 0.3); border-top: 1px solid rgba(255, 255, 255, 0.1); flex-wrap: wrap; gap: 1rem; }
        .order-status { display: inline-flex; align-items: center; gap: 8px; padding: 0.3rem 1rem; border-radius: 30px; font-size: 0.8rem; font-weight: 600; }
        .status-pending { background: rgba(255, 152, 0, 0.2); color: #FF9800; border: 1px solid rgba(255, 152, 0, 0.3); }
        .status-paid { background: rgba(76, 175, 80, 0.2); color: #4CAF50; border: 1px solid rgba(76, 175, 80, 0.3); }
        .status-cancelled { background: rgba(244, 67, 54, 0.2); color: #f44336; border: 1px solid rgba(244, 67, 54, 0.3); }
        .order-actions { display: flex; gap: 0.5rem; }
        .btn-action { padding: 0.4rem 1rem; border-radius: 10px; text-decoration: none; font-size: 0.8rem; font-weight: 500; transition: all 0.3s ease; }
        .btn-detail { background: rgba(33, 150, 243, 0.2); border: 1px solid rgba(33, 150, 243, 0.3); color: #2196F3; }
        .btn-detail:hover { background: rgba(33, 150, 243, 0.4); }
        .btn-invoice { background: rgba(255, 71, 136, 0.2); border: 1px solid rgba(255, 71, 136, 0.3); color: #FF4788; }
        .btn-invoice:hover { background: rgba(255, 71, 136, 0.4); }
        .empty-state { text-align: center; padding: 4rem; background: rgba(20, 20, 30, 0.7); backdrop-filter: blur(10px); border-radius: 24px; border: 1px solid rgba(255, 255, 255, 0.1); }
        .empty-state i { font-size: 4rem; margin-bottom: 1rem; opacity: 0.5; }
        .empty-state p { color: rgba(255, 255, 255, 0.6); }
        .btn-shop { display: inline-block; margin-top: 1rem; padding: 0.75rem 1.5rem; background: linear-gradient(95deg, #FF4788, #FF8C42); border-radius: 12px; color: #fff; text-decoration: none; font-weight: 600; }
        .footer { margin-top: 4rem; text-align: center; opacity: 0.6; font-size: 0.85rem; padding: 2rem 0; border-top: 1px solid rgba(255, 255, 255, 0.1); }
        @media (max-width: 768px) { .container { padding: 1rem; } .page-title { font-size: 1.5rem; } .navbar { padding: 0.75rem 1rem; } .logo { font-size: 1.2rem; } .order-body { flex-direction: column; align-items: center; text-align: center; } .order-total { text-align: center; } .order-footer { flex-direction: column; text-align: center; } .filter-tabs { justify-content: center; } }
    </style>
</head>
<body>
<canvas id="particle-canvas"></canvas>
<div class="navbar">
    <div class="logo"><i class="fas fa-ticket-alt"></i> ConcertHub</div>
    <div class="user-info">
        <span class="user-name"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?></span>
        <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
<div class="container">
    <div class="main-content">
        <a href="dashboard.php" class="back-link" data-aos="fade-right"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
        <div class="page-header" data-aos="fade-right" data-aos-delay="100">
            <div class="page-title"><i class="fas fa-shopping-bag"></i> Pesanan Saya</div>
            <div class="page-subtitle">Lihat riwayat pemesanan tiket konsermu</div>
        </div>
        <div class="stats-grid" data-aos="fade-up" data-aos-delay="150">
            <div class="stat-card"><div class="stat-value"><?php echo $total_orders; ?></div><div class="stat-label">Total Pesanan</div></div>
            <div class="stat-card"><div class="stat-value"><?php echo $status_counts['paid']; ?></div><div class="stat-label">Selesai</div></div>
            <div class="stat-card"><div class="stat-value"><?php echo $status_counts['pending']; ?></div><div class="stat-label">Menunggu</div></div>
            <div class="stat-card"><div class="stat-value">Rp <?php echo number_format($total_spent, 0, ',', '.'); ?></div><div class="stat-label">Total Belanja</div></div>
        </div>
        <div class="filter-tabs" data-aos="fade-up" data-aos-delay="200">
            <a href="my_orders.php" class="filter-tab <?php echo empty($status_filter) ? 'active' : ''; ?>"><i class="fas fa-list"></i> Semua <span class="count"><?php echo $total_orders; ?></span></a>
            <a href="?status=pending" class="filter-tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>"><i class="fas fa-clock"></i> Menunggu <span class="count"><?php echo $status_counts['pending']; ?></span></a>
            <a href="?status=paid" class="filter-tab <?php echo $status_filter == 'paid' ? 'active' : ''; ?>"><i class="fas fa-check-circle"></i> Selesai <span class="count"><?php echo $status_counts['paid']; ?></span></a>
            <a href="?status=cancelled" class="filter-tab <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>"><i class="fas fa-times-circle"></i> Dibatalkan <span class="count"><?php echo $status_counts['cancelled']; ?></span></a>
        </div>
        <?php if (!empty($orders)): ?>
            <div class="orders-grid" data-aos="fade-up" data-aos-delay="250">
                <?php foreach($orders as $order): 
                    $order_id = $order['order_id'] ?? '-';
                    $order_code = $order['order_code'] ?? '#' . $order_id;
                    $status = $order['status'] ?? 'pending';
                    $total_amount = $order['total_amount'] ?? 0;
                    $quantity = $order['quantity'] ?? 1;
                    $price_per_ticket = $order['price_per_ticket'] ?? 0;
                    $created_at = $order['created_at'] ?? null;
                    $event_name = $order['event_name'] ?? '-';
                    $event_date = $order['event_date'] ?? '';
                    $event_location = $order['location'] ?? '-';
                    $event_venue = $order['venue'] ?? '';
                ?>
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-code"><i class="fas fa-receipt"></i> <?php echo htmlspecialchars($order_code); ?></span>
                            <span class="order-date"><i class="far fa-calendar-alt"></i> <?php echo $created_at ? date('d F Y H:i', strtotime($created_at)) : '-'; ?></span>
                        </div>
                        <div class="order-body">
                            <div class="order-image"><i class="fas fa-ticket-alt"></i></div>
                            <div class="order-details">
                                <div class="order-event-name"><?php echo htmlspecialchars($event_name); ?></div>
                                <div class="order-info-item"><i class="fas fa-calendar-alt"></i><span><?php echo $event_date ? date('d F Y', strtotime($event_date)) : 'TBA'; ?></span></div>
                                <div class="order-info-item"><i class="fas fa-map-marker-alt"></i><span><?php echo htmlspecialchars($event_location); ?><?php echo $event_venue ? ' - ' . htmlspecialchars($event_venue) : ''; ?></span></div>
                                <div class="order-info-item"><i class="fas fa-ticket-alt"></i><span><?php echo $quantity; ?> tiket x Rp <?php echo number_format($price_per_ticket, 0, ',', '.'); ?></span></div>
                            </div>
                            <div class="order-total">
                                <div class="order-total-label">Total Pembayaran</div>
                                <div class="order-total-value">Rp <?php echo number_format($total_amount, 0, ',', '.'); ?></div>
                            </div>
                        </div>
                        <div class="order-footer">
                            <div>
                                <span class="order-status status-<?php echo $status; ?>">
                                    <i class="fas <?php echo $status == 'paid' ? 'fa-check-circle' : ($status == 'pending' ? 'fa-clock' : 'fa-times-circle'); ?>"></i>
                                    <?php echo $status == 'paid' ? 'Selesai' : ($status == 'pending' ? 'Menunggu Pembayaran' : 'Dibatalkan'); ?>
                                </span>
                            </div>
                            <div class="order-actions">
                                <a href="order-detail.php?id=<?php echo $order_id; ?>" class="btn-action btn-detail"><i class="fas fa-eye"></i> Detail</a>
                                <?php if ($status == 'pending'): ?>
                                    <a href="payment.php?order_id=<?php echo $order_id; ?>" class="btn-action btn-invoice"><i class="fas fa-credit-card"></i> Bayar</a>
                                <?php endif; ?>
                                <?php if ($status == 'paid'): ?>
                                    <a href="download-ticket.php?id=<?php echo $order_id; ?>" class="btn-action btn-invoice"><i class="fas fa-download"></i> Download Tiket</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state" data-aos="fade-up">
                <i class="fas fa-shopping-bag"></i>
                <p>Belum ada pesanan</p>
                <p style="font-size: 0.85rem; margin-top: 0.5rem;">Yuk, pesan tiket konser favoritmu sekarang!</p>
                <a href="events.php" class="btn-shop"><i class="fas fa-music"></i> Lihat Event</a>
            </div>
        <?php endif; ?>
        <div class="footer"><p>© 2026 ConcertHub — Experience the beat, live.</p></div>
    </div>
</div>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 120, duration: 800 });
    const canvas = document.getElementById('particle-canvas'); const ctx = canvas.getContext('2d'); let width = window.innerWidth; let height = window.innerHeight; let particles = [];
    function resizeCanvas() { width = window.innerWidth; height = window.innerHeight; canvas.width = width; canvas.height = height; }
    class Particle { constructor() { this.x = Math.random() * width; this.y = Math.random() * height; this.size = Math.random() * 2.5 + 0.8; this.speedX = (Math.random() - 0.5) * 0.6; this.speedY = (Math.random() - 0.5) * 0.4; this.color = `rgba(255, ${Math.floor(100 + Math.random() * 100)}, ${Math.floor(120 + Math.random() * 80)}, ${Math.random() * 0.5 + 0.2})`; } update() { this.x += this.speedX; this.y += this.speedY; if (this.x < 0 || this.x > width) this.speedX *= -1; if (this.y < 0 || this.y > height) this.speedY *= -1; } draw() { ctx.beginPath(); ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2); ctx.fillStyle = this.color; ctx.fill(); } }
    function initParticles() { particles = []; let numberOfParticles = (width * height) / 8000; if (numberOfParticles > 180) numberOfParticles = 180; for (let i = 0; i < numberOfParticles; i++) { particles.push(new Particle()); } }
    function animateParticles() { ctx.clearRect(0, 0, width, height); for (let p of particles) { p.update(); p.draw(); } requestAnimationFrame(animateParticles); }
    window.addEventListener('resize', () => { resizeCanvas(); initParticles(); }); resizeCanvas(); initParticles(); animateParticles();
</script>
</body>
</html>