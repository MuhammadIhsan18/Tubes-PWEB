<?php
include "../includes/auth.php";
include "../config/db.php";

if ($_SESSION['user']['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Handle update status (admin konfirmasi pembayaran)
if (isset($_GET['update_status']) && is_numeric($_GET['update_status'])) {
    $order_id = (int) $_GET['update_status'];
    $new_status = isset($_GET['status_value']) ? $_GET['status_value'] : 'paid';
    
    $allowed_status = ['pending', 'paid', 'cancelled'];
    if (in_array($new_status, $allowed_status)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $new_status, $order_id);
            if ($stmt->execute()) {
                $success_message = "Status transaksi berhasil diupdate!";
            } else {
                $error_message = "Gagal mengupdate status: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $order_id = (int) $_GET['delete'];
    $conn->query("DELETE FROM orders WHERE order_id = $order_id");
    $success_message = "Transaksi berhasil dihapus!";
}

// Fetch data dari tabel orders
$sql = "SELECT o.*, u.username as user_name, u.email as user_email, e.title as event_name, e.event_date, e.location, e.venue 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.user_id 
        LEFT JOIN events e ON o.event_id = e.event_id 
        ORDER BY o.created_at DESC";
$result = $conn->query($sql);

// Summary statistics
$total_orders = 0;
$paid_count = 0;
$pending_count = 0;
$cancelled_count = 0;
$total_revenue = 0;

$summary = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) as paid, SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled, COALESCE(SUM(CASE WHEN status='paid' THEN total_amount ELSE 0 END), 0) as revenue FROM orders");
if ($summary && $summary->num_rows > 0) {
    $stats = $summary->fetch_assoc();
    $total_orders = $stats['total'];
    $paid_count = $stats['paid'];
    $pending_count = $stats['pending'];
    $cancelled_count = $stats['cancelled'];
    $total_revenue = $stats['revenue'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Kelola Transaksi | ConcertHub Admin</title>
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
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: rgba(20, 20, 30, 0.7); backdrop-filter: blur(10px); border-radius: 24px; padding: 1.5rem; border: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); border-color: #FF4788; box-shadow: 0 10px 30px -10px rgba(255, 71, 136, 0.3); }
        .stat-icon { font-size: 2rem; margin-bottom: 1rem; }
        .stat-value { font-size: 1.8rem; font-weight: 800; background: linear-gradient(135deg, #FFB347, #FF4788); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .stat-label { font-size: 0.85rem; opacity: 0.7; }
        .alert { padding: 1rem; border-radius: 16px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; animation: slideDown 0.3s ease-out; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .alert-success { background: rgba(76, 175, 80, 0.15); border: 1px solid rgba(76, 175, 80, 0.3); color: #4CAF50; }
        .alert-error { background: rgba(244, 67, 54, 0.15); border: 1px solid rgba(244, 67, 54, 0.3); color: #f44336; }
        .table-card { background: rgba(20, 20, 30, 0.7); backdrop-filter: blur(10px); border-radius: 24px; padding: 1.5rem; border: 1px solid rgba(255, 255, 255, 0.1); overflow-x: auto; animation: fadeInUp 0.5s ease-out; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .data-table { width: 100%; border-collapse: collapse; min-width: 1000px; }
        .data-table th, .data-table td { padding: 1rem; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .data-table th { font-weight: 600; color: #FF8C42; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .data-table td { font-size: 0.9rem; }
        .data-table tr:hover { background: rgba(255, 255, 255, 0.03); }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-paid { background: rgba(76, 175, 80, 0.2); color: #4CAF50; border: 1px solid rgba(76, 175, 80, 0.3); }
        .status-pending { background: rgba(255, 152, 0, 0.2); color: #FF9800; border: 1px solid rgba(255, 152, 0, 0.3); }
        .status-cancelled { background: rgba(244, 67, 54, 0.2); color: #f44336; border: 1px solid rgba(244, 67, 54, 0.3); }
        .action-buttons { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .btn-icon { padding: 0.5rem 0.8rem; border-radius: 10px; color: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; font-size: 0.75rem; font-weight: 500; transition: all 0.3s ease; border: none; cursor: pointer; }
        .btn-view { background: rgba(33, 150, 243, 0.2); border: 1px solid rgba(33, 150, 243, 0.3); color: #2196F3; }
        .btn-view:hover { background: rgba(33, 150, 243, 0.4); transform: translateY(-2px); }
        .btn-complete { background: rgba(76, 175, 80, 0.2); border: 1px solid rgba(76, 175, 80, 0.3); color: #4CAF50; }
        .btn-complete:hover { background: rgba(76, 175, 80, 0.4); transform: translateY(-2px); }
        .btn-cancel-action { background: rgba(244, 67, 54, 0.2); border: 1px solid rgba(244, 67, 54, 0.3); color: #f44336; }
        .btn-cancel-action:hover { background: rgba(244, 67, 54, 0.4); transform: translateY(-2px); }
        .btn-delete { background: rgba(244, 67, 54, 0.2); border: 1px solid rgba(244, 67, 54, 0.3); color: #f44336; }
        .btn-delete:hover { background: rgba(244, 67, 54, 0.4); transform: translateY(-2px); }
        .empty-state { text-align: center; padding: 3rem; color: rgba(255, 255, 255, 0.6); }
        .empty-state i { font-size: 4rem; margin-bottom: 1rem; opacity: 0.5; }
        @media (max-width: 768px) { .container { padding: 1rem; } .page-title { font-size: 1.5rem; } .navbar { padding: 0.75rem 1rem; } .logo { font-size: 1.2rem; } .stats-grid { grid-template-columns: 1fr; } .data-table th, .data-table td { padding: 0.75rem; font-size: 0.8rem; } .btn-icon { padding: 0.3rem 0.6rem; font-size: 0.7rem; } }
    </style>
</head>
<body>
<canvas id="particle-canvas"></canvas>
<div class="navbar">
    <div class="logo"><i class="fas fa-ticket-alt"></i> ConcertHub Admin</div>
    <div class="user-info">
        <span class="user-name"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'Admin'); ?></span>
        <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
<div class="container">
    <div class="main-content">
        <a href="dashboard.php" class="back-link" data-aos="fade-right"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
        <div class="page-header" data-aos="fade-right" data-aos-delay="100">
            <div class="page-title"><i class="fas fa-credit-card"></i> Kelola Transaksi</div>
            <div class="page-subtitle">Kelola dan pantau semua transaksi penjualan tiket</div>
        </div>
        <div class="stats-grid" data-aos="fade-up" data-aos-delay="150">
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-shopping-cart"></i></div><div class="stat-value"><?php echo $total_orders; ?></div><div class="stat-label">Total Transaksi</div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-value"><?php echo $paid_count; ?></div><div class="stat-label">Transaksi Selesai</div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-value"><?php echo $pending_count; ?></div><div class="stat-label">Menunggu Konfirmasi</div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-dollar-sign"></i></div><div class="stat-value">Rp <?php echo number_format($total_revenue, 0, ',', '.'); ?></div><div class="stat-label">Total Pendapatan</div></div>
        </div>
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" data-aos="fade-up"><i class="fas fa-check-circle"></i><span><?php echo $success_message; ?></span></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error" data-aos="fade-up"><i class="fas fa-exclamation-circle"></i><span><?php echo $error_message; ?></span></div>
        <?php endif; ?>
        <div class="table-card" data-aos="fade-up" data-aos-delay="200">
            <?php if ($result && $result->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr><th>ID Order</th><th>Pembeli</th><th>Event</th><th>Jml Tiket</th><th>Total Harga</th><th>Tgl Order</th><th>Tgl Event</th><th>Status</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo $row['order_id']; ?></strong></td>
                                <td><i class="fas fa-user"></i> <?php echo htmlspecialchars($row['user_name']); ?><br><small><?php echo htmlspecialchars($row['user_email']); ?></small></td>
                                <td><i class="fas fa-music"></i> <?php echo htmlspecialchars($row['event_name']); ?><br><small><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($row['location']); ?></small></td>
                                <td><?php echo $row['quantity']; ?> tiket</td>
                                <td style="color: #4CAF50; font-weight: 700;">Rp <?php echo number_format($row['total_amount'], 0, ',', '.'); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['event_date'])); ?></td>
                                <td><span class="status-badge status-<?php echo $row['status']; ?>"><i class="fas <?php echo $row['status'] == 'paid' ? 'fa-check-circle' : ($row['status'] == 'pending' ? 'fa-clock' : 'fa-times-circle'); ?>"></i> <?php echo $row['status'] == 'paid' ? 'Lunas' : ($row['status'] == 'pending' ? 'Pending' : 'Dibatalkan'); ?></span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="showDetail(<?php echo $row['order_id']; ?>)" class="btn-icon btn-view"><i class="fas fa-eye"></i> Detail</button>
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <a href="?update_status=<?php echo $row['order_id']; ?>&status_value=paid" class="btn-icon btn-complete" onclick="return confirm('Konfirmasi pembayaran ini?')"><i class="fas fa-check"></i> Konfirmasi</a>
                                            <a href="?update_status=<?php echo $row['order_id']; ?>&status_value=cancelled" class="btn-icon btn-cancel-action" onclick="return confirm('Batalkan transaksi ini?')"><i class="fas fa-times"></i> Batal</a>
                                        <?php endif; ?>
                                        <a href="?delete=<?php echo $row['order_id']; ?>" class="btn-icon btn-delete" onclick="return confirm('Yakin ingin menghapus transaksi ini?')"><i class="fas fa-trash"></i> Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state"><i class="fas fa-credit-card"></i><p>Belum ada transaksi</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 120, duration: 800 });
    function showDetail(orderId) { alert('Detail Transaksi #' + orderId + '\nFitur detail sedang dalam pengembangan.'); }
    const canvas = document.getElementById('particle-canvas'); const ctx = canvas.getContext('2d'); let width = window.innerWidth; let height = window.innerHeight; let particles = [];
    function resizeCanvas() { width = window.innerWidth; height = window.innerHeight; canvas.width = width; canvas.height = height; }
    class Particle { constructor() { this.x = Math.random() * width; this.y = Math.random() * height; this.size = Math.random() * 2.5 + 0.8; this.speedX = (Math.random() - 0.5) * 0.6; this.speedY = (Math.random() - 0.5) * 0.4; this.color = `rgba(255, ${Math.floor(100 + Math.random() * 100)}, ${Math.floor(120 + Math.random() * 80)}, ${Math.random() * 0.5 + 0.2})`; } update() { this.x += this.speedX; this.y += this.speedY; if (this.x < 0 || this.x > width) this.speedX *= -1; if (this.y < 0 || this.y > height) this.speedY *= -1; } draw() { ctx.beginPath(); ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2); ctx.fillStyle = this.color; ctx.fill(); } }
    function initParticles() { particles = []; let numberOfParticles = (width * height) / 8000; if (numberOfParticles > 180) numberOfParticles = 180; for (let i = 0; i < numberOfParticles; i++) { particles.push(new Particle()); } }
    function animateParticles() { ctx.clearRect(0, 0, width, height); for (let p of particles) { p.update(); p.draw(); } requestAnimationFrame(animateParticles); }
    window.addEventListener('resize', () => { resizeCanvas(); initParticles(); }); resizeCanvas(); initParticles(); animateParticles();
</script>
</body>
</html>