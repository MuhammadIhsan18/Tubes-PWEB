<?php
include "../includes/auth.php";
include "../config/db.php";

if ($_SESSION['user']['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Get event ID from URL
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($event_id == 0) {
    header("Location: events.php");
    exit;
}

// Fetch event data langsung dengan query sederhana
$event_query = $conn->query("SELECT * FROM events WHERE event_id = $event_id");
if (!$event_query || $event_query->num_rows == 0) {
    header("Location: events.php");
    exit;
}
$event = $event_query->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $artist = mysqli_real_escape_string($conn, $_POST['artist']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $venue = mysqli_real_escape_string($conn, $_POST['venue']);
    $price = (float)$_POST['price'];
    $total_seats = (int)$_POST['total_seats'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Gabungkan tanggal dan waktu
    $datetime = $event_date . ' ' . $event_time;
    
    // Handle image upload
    $image_value = $event['image_path'] ?? null;
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $file_size = $_FILES['image']['size'];
        
        if (in_array($ext, $allowed) && $file_size <= 2097152) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = "../uploads/events/" . $new_filename;
            
            if (!file_exists("../uploads/events/")) {
                mkdir("../uploads/events/", 0777, true);
            }
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                if ($image_value && file_exists("../" . $image_value)) {
                    unlink("../" . $image_value);
                }
                $image_value = "uploads/events/" . $new_filename;
            }
        }
    }
    
    // Query UPDATE sederhana - tanpa foreign key constraint masalah
    $update_sql = "UPDATE events SET 
                    title = '$title',
                    artist = '$artist',
                    event_date = '$datetime',
                    category = '$category',
                    location = '$location',
                    venue = '$venue',
                    price = $price,
                    total_seats = $total_seats,
                    description = '$description',
                    image_path = " . ($image_value ? "'$image_value'" : "NULL") . ",
                    is_active = $is_active
                  WHERE event_id = $event_id";
    
    if ($conn->query($update_sql)) {
        $success_message = "Event berhasil diupdate!";
        // Refresh data
        $event_query = $conn->query("SELECT * FROM events WHERE event_id = $event_id");
        $event = $event_query->fetch_assoc();
    } else {
        $error_message = "Gagal mengupdate event: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Edit Event | ConcertHub Admin</title>
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

        /* Form Card */
        .form-card {
            background: rgba(20, 20, 30, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
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

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #FF8C42;
        }

        .form-group label i {
            margin-right: 8px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FF4788;
            background: rgba(255, 71, 136, 0.1);
        }

        .form-group input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        /* Image Upload */
        .image-upload {
            margin-bottom: 1.5rem;
        }

        .current-image {
            margin-bottom: 1rem;
        }

        .current-image img {
            max-width: 200px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .image-preview {
            margin-top: 1rem;
            display: none;
        }

        .image-preview img {
            max-width: 200px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-submit {
            background: linear-gradient(95deg, #FF4788, #FF8C42);
            padding: 0.75rem 2rem;
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

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(255, 71, 136, 0.4);
        }

        .btn-cancel {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.75rem 2rem;
            border-radius: 12px;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-group.full-width {
                grid-column: span 1;
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
            .form-actions {
                flex-direction: column;
            }
            .btn-submit,
            .btn-cancel {
                justify-content: center;
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
            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user']['username'] ?? $_SESSION['user']['name'] ?? 'Admin'); ?>
        </span>
        <a href="../auth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<div class="container">
    <div class="main-content">
        <a href="events.php" class="back-link" data-aos="fade-right">
            <i class="fas fa-arrow-left"></i> Kembali ke Kelola Event
        </a>

        <div class="page-header" data-aos="fade-right" data-aos-delay="100">
            <div class="page-title">
                <i class="fas fa-edit"></i> Edit Event
            </div>
            <div class="page-subtitle">
                Perbaharui informasi event konser
            </div>
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

        <div class="form-card" data-aos="fade-up" data-aos-delay="200">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="fas fa-music"></i> Nama Event</label>
                        <input type="text" name="title" required 
                               value="<?php echo htmlspecialchars($event['title'] ?? ''); ?>"
                               placeholder="Contoh: Rock Symphony 2026">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-microphone-alt"></i> Artist / Band</label>
                        <input type="text" name="artist" required 
                               value="<?php echo htmlspecialchars($event['artist'] ?? ''); ?>"
                               placeholder="Contoh: Noah, Dewa 19, Sheila On 7">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Kategori</label>
                        <select name="category" required>
                            <option value="Rock" <?php echo (($event['category'] ?? '') == 'Rock') ? 'selected' : ''; ?>>Rock</option>
                            <option value="Pop" <?php echo (($event['category'] ?? '') == 'Pop') ? 'selected' : ''; ?>>Pop</option>
                            <option value="EDM" <?php echo (($event['category'] ?? '') == 'EDM') ? 'selected' : ''; ?>>EDM</option>
                            <option value="Jazz" <?php echo (($event['category'] ?? '') == 'Jazz') ? 'selected' : ''; ?>>Jazz</option>
                            <option value="Classical" <?php echo (($event['category'] ?? '') == 'Classical') ? 'selected' : ''; ?>>Classical</option>
                            <option value="Dangdut" <?php echo (($event['category'] ?? '') == 'Dangdut') ? 'selected' : ''; ?>>Dangdut</option>
                            <option value="Indie" <?php echo (($event['category'] ?? '') == 'Indie') ? 'selected' : ''; ?>>Indie</option>
                            <option value="Metal" <?php echo (($event['category'] ?? '') == 'Metal') ? 'selected' : ''; ?>>Metal</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Tanggal Event</label>
                        <?php 
                        $event_datetime = $event['event_date'] ?? '';
                        $event_date_only = $event_datetime ? date('Y-m-d', strtotime($event_datetime)) : '';
                        ?>
                        <input type="date" name="event_date" required 
                               value="<?php echo $event_date_only; ?>">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Waktu Event</label>
                        <?php 
                        $event_time_only = $event_datetime ? date('H:i', strtotime($event_datetime)) : '19:00';
                        ?>
                        <input type="time" name="event_time" required 
                               value="<?php echo $event_time_only; ?>">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Kota / Lokasi</label>
                        <input type="text" name="location" required 
                               value="<?php echo htmlspecialchars($event['location'] ?? ''); ?>"
                               placeholder="Contoh: Jakarta, Surabaya, Bali">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-building"></i> Venue / Tempat</label>
                        <input type="text" name="venue" required 
                               value="<?php echo htmlspecialchars($event['venue'] ?? ''); ?>"
                               placeholder="Contoh: GBK, ICE BSD, Stadion Utama">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-ticket-alt"></i> Harga Tiket (Rp)</label>
                        <input type="number" name="price" required 
                               value="<?php echo $event['price'] ?? 0; ?>"
                               placeholder="Contoh: 350000" step="1000">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-chair"></i> Total Kursi</label>
                        <input type="number" name="total_seats" required 
                               value="<?php echo $event['total_seats'] ?? 1000; ?>"
                               placeholder="Kapasitas penonton">
                    </div>

                    <div class="form-group full-width">
                        <label><i class="fas fa-align-left"></i> Deskripsi Event</label>
                        <textarea name="description" rows="5" 
                                  placeholder="Deskripsikan event konser ini..."><?php echo htmlspecialchars($event['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label><i class="fas fa-image"></i> Gambar Event</label>
                        <div class="image-upload">
                            <?php if (!empty($event['image_path'])): ?>
                                <div class="current-image">
                                    <p style="font-size: 0.8rem; margin-bottom: 0.5rem;">Gambar Saat Ini:</p>
                                    <img src="../<?php echo $event['image_path']; ?>" alt="Current event image">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="image" accept="image/*" onchange="previewImage(this)">
                            <div class="image-preview" id="imagePreview">
                                <p style="font-size: 0.8rem; margin-bottom: 0.5rem;">Preview Gambar Baru:</p>
                                <img id="previewImg" src="" alt="Image preview">
                            </div>
                            <small style="color: rgba(255,255,255,0.5);">Format: JPG, PNG, GIF, WEBP. Maksimal 2MB</small>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_active" value="1" 
                                   <?php echo (($event['is_active'] ?? 1) == 1) ? 'checked' : ''; ?>>
                            <i class="fas fa-check-circle" style="color: #4CAF50;"></i>
                            Event Aktif (ditampilkan di website)
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="events.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 120, duration: 800 });

    // Image preview function
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            }
            
            reader.readAsDataURL(input.files[0]);
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