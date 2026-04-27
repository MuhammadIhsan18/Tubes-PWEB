<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../includes/auth.php";
include "../config/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Get user data
$user = $_SESSION['user'];
$user_name = $user['username'] ?? 'User';
$user_id = $user['user_id'] ?? $user['id'] ?? null;

if (!$user_id) {
    die("Error: User ID not found");
}

// Get event ID from URL
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if ($event_id == 0) {
    header("Location: events.php");
    exit;
}

// Get event details (harga diambil dari database)
$event_query = $conn->query("SELECT * FROM events WHERE event_id = $event_id AND status = 'upcoming'");
if ($event_query && $event_query->num_rows > 0) {
    $event = $event_query->fetch_assoc();
    $event_name = $event['title'] ?? '-';
    $event_date = $event['event_date'] ?? '';
    $event_artist = $event['artist'] ?? '-';
    $event_price = $event['price'] ?? 0; // Harga dari database
    $event_location = $event['location'] ?? '-';
    $event_venue = $event['venue'] ?? '';
    $event_category = $event['category'] ?? 'Konser';
} else {
    header("Location: events.php");
    exit;
}

// Cek apakah event memiliki kategori tiket (ticket_categories)
$categories_query = $conn->query("SELECT * FROM ticket_categories WHERE event_id = $event_id AND quota > sold_count");
$has_categories = ($categories_query && $categories_query->num_rows > 0);

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $quantity = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
    
    // Jika ada kategori, ambil harga dari kategori yang dipilih
    if ($has_categories) {
        $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        if ($category_id == 0) {
            $message = "Pilih kategori tiket terlebih dahulu!";
            $message_type = "error";
        } else {
            // Ambil harga dari kategori
            $cat_query = $conn->query("SELECT price, (quota - sold_count) as stock FROM ticket_categories WHERE category_id = $category_id");
            $category = $cat_query->fetch_assoc();
            $price = $category['price'];
            $stock = $category['stock'];
            
            if ($stock < $quantity) {
                $message = "Stok tiket tidak mencukupi! Sisa stok: $stock";
                $message_type = "error";
            }
        }
    } else {
        // Jika tidak ada kategori, pakai harga dari tabel events
        $price = $event_price;
    }
    
    if (empty($message)) {
        if ($quantity < 1) {
            $message = "Jumlah tiket minimal 1!";
            $message_type = "error";
        } elseif ($quantity > 10) {
            $message = "Maksimal pembelian 10 tiket per transaksi!";
            $message_type = "error";
        } elseif ($price <= 0) {
            $message = "Harga tiket tidak valid!";
            $message_type = "error";
        } else {
            $total_amount = $price * $quantity;
            
            // Cek apakah tabel orders ada
            $table_check = $conn->query("SHOW TABLES LIKE 'orders'");
            if ($table_check && $table_check->num_rows == 0) {
                $create_orders = "CREATE TABLE IF NOT EXISTS orders (
                    order_id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    event_id INT NOT NULL,
                    quantity INT NOT NULL DEFAULT 1,
                    price_per_ticket DECIMAL(12,2) NOT NULL,
                    total_amount DECIMAL(12,2) NOT NULL,
                    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
                    order_code VARCHAR(50) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                $conn->query($create_orders);
            }
            
            // Insert into orders table
            $order_code = "ORD-" . strtoupper(uniqid());
            $insert_sql = "INSERT INTO orders (user_id, event_id, quantity, price_per_ticket, total_amount, status, order_code, created_at) 
                           VALUES ('$user_id', '$event_id', '$quantity', '$price', '$total_amount', 'pending', '$order_code', NOW())";
            
            if ($conn->query($insert_sql)) {
                $message = "Pesanan berhasil dibuat! Kode Order: $order_code";
                $message_type = "success";
                
                echo "<script>setTimeout(function() { window.location.href = 'my_orders.php'; }, 2000);</script>";
            } else {
                $message = "Gagal membuat pesanan: " . $conn->error;
                $message_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Pesan Tiket | ConcertHub</title>
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
        .event-card, .order-card { background: rgba(20, 20, 30, 0.7); backdrop-filter: blur(10px); border-radius: 24px; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.3s ease; }
        .event-card:hover, .order-card:hover { transform: translateY(-5px); border-color: #FF4788; box-shadow: 0 20px 35px -10px rgba(255, 71, 136, 0.3); }
        .event-image { height: 200px; background: linear-gradient(135deg, #FF4788, #FF8C42); display: flex; align-items: center; justify-content: center; font-size: 4rem; position: relative; }
        .event-category { position: absolute; top: 1rem; right: 1rem; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(5px); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
        .event-content { padding: 1.5rem; }
        .event-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        .event-artist { color: #FF8C42; margin-bottom: 1rem; font-size: 1rem; }
        .event-info { display: flex; flex-direction: column; gap: 0.8rem; margin: 1rem 0; }
        .event-info-item { display: flex; align-items: center; gap: 12px; font-size: 0.9rem; color: rgba(255, 255, 255, 0.7); }
        .event-info-item i { width: 24px; color: #FF8C42; }
        .order-card { padding: 1.5rem; }
        .order-title { font-size: 1.3rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem; color: #FF8C42; }
        .form-group label i { margin-right: 8px; }
        .form-group select { width: 100%; padding: 0.75rem 1rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; color: #fff; font-size: 1rem; font-family: 'Inter', sans-serif; }
        .form-group select:focus { outline: none; border-color: #FF4788; }
        .price-box { padding: 0.75rem 1rem; background: rgba(255, 255, 255, 0.05); border-radius: 12px; font-weight: 600; color: #4CAF50; font-size: 1rem; }
        .quantity-input { display: flex; align-items: center; gap: 1rem; }
        .quantity-input input { flex: 1; text-align: center; padding: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; color: #fff; font-size: 1.2rem; font-weight: 600; }
        .quantity-input input:focus { outline: none; border-color: #FF4788; }
        .quantity-btn { width: 50px; height: 50px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; color: #fff; font-size: 1.5rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .quantity-btn:hover { background: rgba(255, 71, 136, 0.2); border-color: #FF4788; transform: scale(1.05); }
        .order-summary { background: rgba(0, 0, 0, 0.3); border-radius: 16px; padding: 1rem; margin: 1.5rem 0; }
        .summary-row { display: flex; justify-content: space-between; padding: 0.5rem 0; font-size: 1rem; }
        .summary-row.total { border-top: 1px solid rgba(255, 255, 255, 0.1); margin-top: 0.5rem; padding-top: 0.8rem; font-weight: 700; font-size: 1.2rem; color: #4CAF50; }
        .alert { padding: 1rem; border-radius: 16px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; animation: slideDown 0.3s ease-out; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .alert-success { background: rgba(76, 175, 80, 0.15); border: 1px solid rgba(76, 175, 80, 0.3); color: #4CAF50; }
        .alert-error { background: rgba(244, 67, 54, 0.15); border: 1px solid rgba(244, 67, 54, 0.3); color: #f44336; }
        .btn-submit { width: 100%; background: linear-gradient(95deg, #FF4788, #FF8C42); padding: 1rem; border-radius: 16px; color: #fff; border: none; cursor: pointer; font-weight: 700; font-size: 1rem; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s ease; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(255, 71, 136, 0.4); }
        .footer { margin-top: 4rem; text-align: center; opacity: 0.6; font-size: 0.85rem; padding: 2rem 0; border-top: 1px solid rgba(255, 255, 255, 0.1); }
        @media (max-width: 768px) { .container { padding: 1rem; } .page-title { font-size: 1.5rem; } .navbar { padding: 0.75rem 1rem; } .logo { font-size: 1.2rem; } .two-columns { grid-template-columns: 1fr; } .quantity-btn { width: 40px; height: 40px; font-size: 1.2rem; } }
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
        <a href="events.php" class="back-link" data-aos="fade-right"><i class="fas fa-arrow-left"></i> Kembali ke Daftar Event</a>
        <div class="page-header" data-aos="fade-right" data-aos-delay="100">
            <div class="page-title"><i class="fas fa-ticket-alt"></i> Pesan Tiket</div>
            <div class="page-subtitle">Lengkapi data pemesanan tiket konser</div>
        </div>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" data-aos="fade-up">
                <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
                <?php if ($message_type == 'success'): ?>
                    <span style="margin-left: auto; font-size: 0.8rem;">Mengalihkan ke pesanan saya...</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="two-columns" data-aos="fade-up" data-aos-delay="150">
            <div class="event-card">
                <div class="event-image">
                    <span class="event-category"><?php echo htmlspecialchars($event_category); ?></span>
                    <i class="fas fa-music"></i>
                </div>
                <div class="event-content">
                    <h2 class="event-title"><?php echo htmlspecialchars($event_name); ?></h2>
                    <div class="event-artist"><i class="fas fa-microphone-alt"></i> <?php echo htmlspecialchars($event_artist); ?></div>
                    <div class="event-info">
                        <div class="event-info-item"><i class="fas fa-calendar-alt"></i><span><?php echo $event_date ? date('d F Y H:i', strtotime($event_date)) : 'TBA'; ?></span></div>
                        <div class="event-info-item"><i class="fas fa-map-marker-alt"></i><span><?php echo htmlspecialchars($event_location); ?><?php echo $event_venue ? ' - ' . htmlspecialchars($event_venue) : ''; ?></span></div>
                    </div>
                </div>
            </div>
            <div class="order-card">
                <div class="order-title"><i class="fas fa-edit"></i> Detail Pemesanan</div>
                <form method="POST" action="" id="orderForm">
                    <?php if ($has_categories): ?>
                        <div class="form-group">
                            <label><i class="fas fa-ticket-alt"></i> Pilih Kategori Tiket</label>
                            <select name="category_id" id="category_id" required onchange="updatePrice()">
                                <option value="">Pilih Kategori</option>
                                <?php 
                                $categories_query->data_seek(0);
                                while($cat = $categories_query->fetch_assoc()): 
                                    $stock = $cat['quota'] - $cat['sold_count'];
                                ?>
                                    <option value="<?php echo $cat['category_id']; ?>" data-price="<?php echo $cat['price']; ?>" data-stock="<?php echo $stock; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?> - Rp <?php echo number_format($cat['price'], 0, ',', '.'); ?> (Stok: <?php echo $stock; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="form-group">
                            <label><i class="fas fa-ticket-alt"></i> Harga Tiket</label>
                            <div class="price-box">
                                Rp <?php echo number_format($event_price, 0, ',', '.'); ?> / tiket
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label><i class="fas fa-shopping-cart"></i> Jumlah Tiket</label>
                        <div class="quantity-input">
                            <button type="button" class="quantity-btn" onclick="decrementQuantity()">-</button>
                            <input type="number" name="qty" id="quantity" value="1" min="1" max="10" required>
                            <button type="button" class="quantity-btn" onclick="incrementQuantity()">+</button>
                        </div>
                        <small style="color: rgba(255,255,255,0.5); display: block; margin-top: 0.5rem;">
                            <i class="fas fa-info-circle"></i> Maksimal pembelian 10 tiket per transaksi
                        </small>
                    </div>
                    
                    <div class="order-summary">
                        <div class="summary-row"><span>Harga Tiket</span><span id="priceDisplay">Rp <?php echo number_format($has_categories ? 0 : $event_price, 0, ',', '.'); ?></span></div>
                        <div class="summary-row"><span>Jumlah Tiket</span><span id="quantityDisplay">1</span></div>
                        <div class="summary-row total"><span>Total Pembayaran</span><span id="totalDisplay">Rp <?php echo number_format($has_categories ? 0 : $event_price, 0, ',', '.'); ?></span></div>
                    </div>
                    
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-credit-card"></i> Konfirmasi Pemesanan
                    </button>
                </form>
            </div>
        </div>
        <div class="footer"><p>© 2026 ConcertHub — Experience the beat, live.</p></div>
    </div>
</div>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 120, duration: 800 });
    
    let currentPrice = <?php echo $has_categories ? 0 : $event_price; ?>;
    let currentStock = 999999;
    
    <?php if ($has_categories): ?>
    function updatePrice() {
        const select = document.getElementById('category_id');
        const selectedOption = select.options[select.selectedIndex];
        
        if (selectedOption && selectedOption.value) {
            currentPrice = parseInt(selectedOption.getAttribute('data-price')) || 0;
            currentStock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
            document.getElementById('submitBtn').disabled = false;
            
            // Update max quantity based on stock
            const quantityInput = document.getElementById('quantity');
            const maxQty = Math.min(10, currentStock);
            quantityInput.max = maxQty;
            if (parseInt(quantityInput.value) > maxQty) {
                quantityInput.value = maxQty;
            }
        } else {
            currentPrice = 0;
            document.getElementById('submitBtn').disabled = true;
        }
        updateTotal();
    }
    <?php endif; ?>
    
    function updateTotal() {
        const quantity = parseInt(document.getElementById('quantity').value) || 0;
        const total = currentPrice * quantity;
        
        document.getElementById('priceDisplay').innerHTML = 'Rp ' + (currentPrice ? currentPrice.toLocaleString('id-ID') : '0');
        document.getElementById('quantityDisplay').innerHTML = quantity;
        document.getElementById('totalDisplay').innerHTML = 'Rp ' + total.toLocaleString('id-ID');
    }
    
    function incrementQuantity() {
        const input = document.getElementById('quantity');
        let value = parseInt(input.value);
        const maxQty = Math.min(10, currentStock);
        if (value < maxQty) {
            value++;
            input.value = value;
            updateTotal();
        }
    }
    
    function decrementQuantity() {
        const input = document.getElementById('quantity');
        let value = parseInt(input.value);
        if (value > 1) {
            value--;
            input.value = value;
            updateTotal();
        }
    }
    
    document.getElementById('quantity').addEventListener('change', function() {
        let value = parseInt(this.value);
        const maxQty = Math.min(10, currentStock);
        if (isNaN(value) || value < 1) value = 1;
        if (value > maxQty) value = maxQty;
        this.value = value;
        updateTotal();
    });
    
    <?php if ($has_categories): ?>
    const categorySelect = document.getElementById('category_id');
    if (categorySelect) {
        categorySelect.addEventListener('change', updatePrice);
        updatePrice();
    }
    <?php else: ?>
    updateTotal();
    <?php endif; ?>

    // Form validation before submit
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        <?php if ($has_categories): ?>
        const category = document.getElementById('category_id').value;
        if (!category) {
            e.preventDefault();
            alert('Pilih kategori tiket terlebih dahulu!');
            return;
        }
        <?php endif; ?>
        
        const quantity = parseInt(document.getElementById('quantity').value);
        if (quantity < 1) {
            e.preventDefault();
            alert('Jumlah tiket minimal 1!');
        } else if (quantity > 10) {
            e.preventDefault();
            alert('Maksimal pembelian 10 tiket per transaksi!');
        } else if (currentPrice <= 0) {
            e.preventDefault();
            alert('Harga tiket tidak valid!');
        }
    });

    // Particle Background Effect
    const canvas = document.getElementById('particle-canvas');
    const ctx = canvas.getContext('2d');
    let width = window.innerWidth;
    let height = window.innerHeight;
    let particles = [];

    function resizeCanvas() { width = window.innerWidth; height = window.innerHeight; canvas.width = width; canvas.height = height; }
    class Particle { constructor() { this.x = Math.random() * width; this.y = Math.random() * height; this.size = Math.random() * 2.5 + 0.8; this.speedX = (Math.random() - 0.5) * 0.6; this.speedY = (Math.random() - 0.5) * 0.4; this.color = `rgba(255, ${Math.floor(100 + Math.random() * 100)}, ${Math.floor(120 + Math.random() * 80)}, ${Math.random() * 0.5 + 0.2})`; } update() { this.x += this.speedX; this.y += this.speedY; if (this.x < 0 || this.x > width) this.speedX *= -1; if (this.y < 0 || this.y > height) this.speedY *= -1; } draw() { ctx.beginPath(); ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2); ctx.fillStyle = this.color; ctx.fill(); } }
    function initParticles() { particles = []; let numberOfParticles = (width * height) / 8000; if (numberOfParticles > 180) numberOfParticles = 180; for (let i = 0; i < numberOfParticles; i++) { particles.push(new Particle()); } }
    function animateParticles() { ctx.clearRect(0, 0, width, height); for (let p of particles) { p.update(); p.draw(); } requestAnimationFrame(animateParticles); }
    window.addEventListener('resize', () => { resizeCanvas(); initParticles(); }); resizeCanvas(); initParticles(); animateParticles();
</script>
</body>
</html>