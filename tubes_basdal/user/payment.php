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

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id == 0) {
    header("Location: my_orders.php");
    exit;
}

// Get order details
$order_query = $conn->query("SELECT o.*, e.title as event_name, e.event_date, e.location, e.venue 
                              FROM orders o 
                              LEFT JOIN events e ON o.event_id = e.event_id 
                              WHERE o.order_id = $order_id AND o.user_id = $user_id");

if (!$order_query || $order_query->num_rows == 0) {
    header("Location: my_orders.php");
    exit;
}

$order = $order_query->fetch_assoc();

// If order is already paid, redirect to my_orders
if ($order['status'] == 'paid') {
    header("Location: my_orders.php");
    exit;
}

$message = '';
$message_type = '';

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = isset($_POST['payment_method']) ? mysqli_real_escape_string($conn, $_POST['payment_method']) : '';
    $payment_proof = null;
    
    // Handle payment proof upload
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
        $filename = $_FILES['payment_proof']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $file_size = $_FILES['payment_proof']['size'];
        
        if (in_array($ext, $allowed) && $file_size <= 2097152) {
            $new_filename = "payment_" . $order_id . "_" . time() . '.' . $ext;
            $upload_path = "../uploads/payments/" . $new_filename;
            
            if (!file_exists("../uploads/payments/")) {
                mkdir("../uploads/payments/", 0777, true);
            }
            
            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_path)) {
                $payment_proof = "uploads/payments/" . $new_filename;
            }
        }
    }
    
    // Update order with payment info (status tetap pending sampai admin konfirmasi)
    $update_sql = "UPDATE orders SET 
                    payment_method = '$payment_method',
                    payment_proof = " . ($payment_proof ? "'$payment_proof'" : "NULL") . "
                  WHERE order_id = $order_id";
    
    if ($conn->query($update_sql)) {
        $message = "Bukti pembayaran berhasil diupload! Menunggu konfirmasi admin.";
        $message_type = "success";
        
        // Redirect after 2 seconds
        echo "<script>setTimeout(function() { window.location.href = 'my_orders.php'; }, 2000);</script>";
    } else {
        $message = "Gagal upload bukti pembayaran: " . $conn->error;
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Pembayaran | ConcertHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0a0a0a; color: #fff; overflow-x: hidden; }
        #particle-canvas { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; }
        .container { position: relative; z-index: 2; max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .navbar { position: fixed; top: 0; left: 0; width: 100%; padding: 1rem 5%; display: flex; justify-content: space-between; align-items: center; backdrop-filter: blur(12px); background: rgba(10, 10, 10, 0.8); z-index: 100; border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
        .logo { font-size: 1.5rem; font-weight: 800; background: linear-gradient(135deg, #FF4788, #FF8C42); -webkit-background-clip: text; background-clip: text; color: transparent; }
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
        .two-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .order-card, .payment-card { background: rgba(20, 20, 30, 0.7); backdrop-filter: blur(10px); border-radius: 24px; padding: 1.5rem; border: 1px solid rgba(255, 255, 255, 0.1); }
        .order-card:hover, .payment-card:hover { border-color: #FF4788; }
        .section-title { font-size: 1.3rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .order-info { margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .order-label { font-size: 0.8rem; color: #FF8C42; margin-bottom: 0.25rem; }
        .order-value { font-size: 1rem; font-weight: 600; }
        .total-amount { font-size: 1.5rem; font-weight: 800; color: #4CAF50; margin-top: 1rem; text-align: right; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem; color: #FF8C42; }
        .form-group label i { margin-right: 8px; }
        .form-group select, .form-group input { width: 100%; padding: 0.75rem 1rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; color: #fff; font-size: 1rem; }
        .form-group select:focus, .form-group input:focus { outline: none; border-color: #FF4788; }
        .bank-info { background: rgba(0, 0, 0, 0.3); border-radius: 16px; padding: 1rem; margin: 1rem 0; }
        .bank-name { font-weight: 700; color: #FF4788; margin-bottom: 0.5rem; }
        .bank-account { font-family: monospace; font-size: 1.1rem; }
        .alert { padding: 1rem; border-radius: 16px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; animation: slideDown 0.3s ease-out; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .alert-success { background: rgba(76, 175, 80, 0.15); border: 1px solid rgba(76, 175, 80, 0.3); color: #4CAF50; }
        .alert-error { background: rgba(244, 67, 54, 0.15); border: 1px solid rgba(244, 67, 54, 0.3); color: #f44336; }
        .btn-submit { width: 100%; background: linear-gradient(95deg, #FF4788, #FF8C42); padding: 1rem; border-radius: 16px; color: #fff; border: none; cursor: pointer; font-weight: 700; font-size: 1rem; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s ease; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(255, 71, 136, 0.4); }
        .footer { margin-top: 4rem; text-align: center; opacity: 0.6; font-size: 0.85rem; padding: 2rem 0; border-top: 1px solid rgba(255, 255, 255, 0.1); }
        @media (max-width: 768px) { .container { padding: 1rem; } .page-title { font-size: 1.5rem; } .navbar { padding: 0.75rem 1rem; } .logo { font-size: 1.2rem; } .two-columns { grid-template-columns: 1fr; } }
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
        <a href="my_orders.php" class="back-link" data-aos="fade-right"><i class="fas fa-arrow-left"></i> Kembali ke Pesanan Saya</a>
        <div class="page-header" data-aos="fade-right" data-aos-delay="100">
            <div class="page-title"><i class="fas fa-credit-card"></i> Pembayaran</div>
            <div class="page-subtitle">Lengkapi pembayaran untuk menyelesaikan pemesanan</div>
        </div>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" data-aos="fade-up">
                <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>
        <div class="two-columns" data-aos="fade-up" data-aos-delay="150">
            <div class="order-card">
                <div class="section-title"><i class="fas fa-receipt"></i> Detail Pesanan</div>
                <div class="order-info"><div class="order-label">Kode Pesanan</div><div class="order-value"><?php echo htmlspecialchars($order['order_code'] ?? '#' . $order_id); ?></div></div>
                <div class="order-info"><div class="order-label">Event</div><div class="order-value"><?php echo htmlspecialchars($order['event_name']); ?></div></div>
                <div class="order-info"><div class="order-label">Tanggal Event</div><div class="order-value"><?php echo date('d F Y', strtotime($order['event_date'])); ?></div></div>
                <div class="order-info"><div class="order-label">Lokasi</div><div class="order-value"><?php echo htmlspecialchars($order['location']); ?><?php echo $order['venue'] ? ' - ' . htmlspecialchars($order['venue']) : ''; ?></div></div>
                <div class="order-info"><div class="order-label">Jumlah Tiket</div><div class="order-value"><?php echo $order['quantity']; ?> tiket x Rp <?php echo number_format($order['price_per_ticket'], 0, ',', '.'); ?></div></div>
                <div class="total-amount">Total: Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></div>
            </div>
            <div class="payment-card">
                <div class="section-title"><i class="fas fa-university"></i> Informasi Pembayaran</div>
                <div class="bank-info">
                    <div class="bank-name"><i class="fas fa-building"></i> Bank BCA</div>
                    <div class="bank-account">No. Rekening: 1234567890</div>
                    <div>a.n. ConcertHub Official</div>
                </div>
                <div class="bank-info">
                    <div class="bank-name"><i class="fas fa-building"></i> Bank Mandiri</div>
                    <div class="bank-account">No. Rekening: 9876543210</div>
                    <div>a.n. ConcertHub Official</div>
                </div>
                <div class="bank-info">
                    <div class="bank-name"><i class="fas fa-building"></i> Bank BRI</div>
                    <div class="bank-account">No. Rekening: 5678901234</div>
                    <div>a.n. ConcertHub Official</div>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label><i class="fas fa-credit-card"></i> Metode Pembayaran</label>
                        <select name="payment_method" required>
                            <option value="">Pilih Metode</option>
                            <option value="BCA Transfer">BCA Transfer</option>
                            <option value="Mandiri Transfer">Mandiri Transfer</option>
                            <option value="BRI Transfer">BRI Transfer</option>
                            <option value="QRIS">QRIS</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-image"></i> Bukti Transfer</label>
                        <input type="file" name="payment_proof" accept="image/*,application/pdf" required>
                        <small style="color: rgba(255,255,255,0.5); display: block; margin-top: 0.5rem;">Format: JPG, PNG, PDF. Maksimal 2MB</small>
                    </div>
                    <button type="submit" class="btn-submit"><i class="fas fa-upload"></i> Upload Bukti Pembayaran</button>
                </form>
            </div>
        </div>
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