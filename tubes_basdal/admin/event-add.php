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

// Detect column names for various fields
$name_column = 'title';
$possible_names = ['title', 'name', 'event_name', 'event_title', 'nama_event'];
foreach ($possible_names as $name) {
    if (in_array($name, $columns)) {
        $name_column = $name;
        break;
    }
}

// Detect artist column
$artist_column = 'artist';
$possible_artist = ['artist', 'artis', 'performer'];
foreach ($possible_artist as $artist) {
    if (in_array($artist, $columns)) {
        $artist_column = $artist;
        break;
    }
}

$category_column = in_array('category', $columns) ? 'category' : (in_array('kategori', $columns) ? 'kategori' : 'category');
$date_column = in_array('event_date', $columns) ? 'event_date' : (in_array('tanggal', $columns) ? 'tanggal' : 'event_date');
$time_column = in_array('event_time', $columns) ? 'event_time' : (in_array('waktu', $columns) ? 'waktu' : 'event_time');
$location_column = in_array('location', $columns) ? 'location' : (in_array('lokasi', $columns) ? 'lokasi' : 'location');
$venue_column = in_array('venue', $columns) ? 'venue' : (in_array('tempat', $columns) ? 'tempat' : 'venue');
$price_column = in_array('price', $columns) ? 'price' : (in_array('harga', $columns) ? 'harga' : 'price');
$seats_column = in_array('total_seats', $columns) ? 'total_seats' : (in_array('kapasitas', $columns) ? 'kapasitas' : 'total_seats');
$desc_column = in_array('description', $columns) ? 'description' : (in_array('deskripsi', $columns) ? 'deskripsi' : 'description');
$image_column = in_array('image_path', $columns) ? 'image_path' : (in_array('image', $columns) ? 'image' : (in_array('gambar', $columns) ? 'gambar' : 'image_path'));
$active_column = in_array('is_active', $columns) ? 'is_active' : (in_array('status', $columns) ? 'status' : 'is_active');

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name_value = mysqli_real_escape_string($conn, $_POST['name']);
    $artist_value = mysqli_real_escape_string($conn, $_POST['artist']);
    $category_value = mysqli_real_escape_string($conn, $_POST['category']);
    $event_date_value = $_POST['event_date'];
    $event_time_value = $_POST['event_time'];
    $location_value = mysqli_real_escape_string($conn, $_POST['location']);
    $venue_value = mysqli_real_escape_string($conn, $_POST['venue']);
    
    // Perbaikan: Pastikan harga tersimpan dengan benar
    $price_value = 0;
    if (isset($_POST['price']) && $_POST['price'] != '') {
        // Hapus titik dan koma, lalu konversi ke float
        $price_clean = str_replace('.', '', $_POST['price']);
        $price_clean = str_replace(',', '.', $price_clean);
        $price_value = (float)$price_clean;
    }
    
    $total_seats_value = isset($_POST['total_seats']) ? (int)$_POST['total_seats'] : 1000;
    $description_value = mysqli_real_escape_string($conn, $_POST['description']);
    $is_active_value = isset($_POST['is_active']) ? 1 : 0;
    
    // Handle image upload
    $image_value = null;
    
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
                $image_value = "uploads/events/" . $new_filename;
            }
        }
    }
    
    // Build dynamic insert query based on existing columns
    $insert_fields = [];
    $insert_placeholders = [];
    $insert_types = "";
    $insert_values = [];
    
    // Add each field if column exists
    if (in_array($name_column, $columns)) {
        $insert_fields[] = $name_column;
        $insert_placeholders[] = "?";
        $insert_types .= "s";
        $insert_values[] = $name_value;
    }
    
    if (in_array($artist_column, $columns)) {
        $insert_fields[] = $artist_column;
        $insert_placeholders[] = "?";
        $insert_types .= "s";
        $insert_values[] = $artist_value;
    }
    
    if (in_array($category_column, $columns)) {
        $insert_fields[] = $category_column;
        $insert_placeholders[] = "?";
        $insert_types .= "s";
        $insert_values[] = $category_value;
    }
    
    if (in_array($date_column, $columns)) {
        $insert_fields[] = $date_column;
        $insert_placeholders[] = "?";
        $insert_types .= "s";
        // Gabungkan tanggal dan waktu jika diperlukan
        $insert_values[] = $event_date_value . ' ' . $event_time_value;
    }
    
    if (in_array($time_column, $columns) && !in_array($date_column, $columns)) {
        $insert_fields[] = $time_column;
        $insert_placeholders[] = "?";
        $insert_types .= "s";
        $insert_values[] = $event_time_value;
    }
    
    if (in_array($location_column, $columns)) {
        $insert_fields[] = $location_column;
        $insert_placeholders[] = "?";
        $insert_types .= "s";
        $insert_values[] = $location_value;
    }
    
    if (in_array($venue_column, $columns)) {
        $insert_fields[] = $venue_column;
        $insert_placeholders[] = "?";
        $insert_types .= "s";
        $insert_values[] = $venue_value;
    }
    
    // Pastikan kolom harga dimasukkan dengan benar
    if (in_array($price_column, $columns)) {
        $insert_fields[] = $price_column;
        $insert_placeholders[] = "?";
        $insert_types .= "d";
        $insert_values[] = $price_value;
    }
    
    if (in_array($seats_column, $columns)) {
        $insert_fields[] = $seats_column;
        $insert_placeholders[] = "?";
        $insert_types .= "i";
        $insert_values[] = $total_seats_value;
    }
    
    if (in_array($desc_column, $columns)) {
        $insert_fields[] = $desc_column;
        $insert_placeholders[] = "?";
        $insert_types .= "s";
        $insert_values[] = $description_value;
    }
    
    if (in_array($image_column, $columns) && $image_value) {
        $insert_fields[] = $image_column;
        $insert_placeholders[] = "?";
        $insert_types .= "s";
        $insert_values[] = $image_value;
    }
    
    if (in_array($active_column, $columns)) {
        $insert_fields[] = $active_column;
        $insert_placeholders[] = "?";
        $insert_types .= "i";
        $insert_values[] = $is_active_value;
    }
    
    // Build and execute insert query
    if (!empty($insert_fields)) {
        $insert_sql = "INSERT INTO events (" . implode(", ", $insert_fields) . ") VALUES (" . implode(", ", $insert_placeholders) . ")";
        $insert_stmt = $conn->prepare($insert_sql);
        
        if ($insert_stmt === false) {
            $error_message = "Error preparing insert: " . $conn->error;
        } else {
            // Dynamically bind parameters
            $insert_stmt->bind_param($insert_types, ...$insert_values);
            
            if ($insert_stmt->execute()) {
                $success_message = "Event berhasil ditambahkan!";
                $new_id = $conn->insert_id;
                
                // Redirect to edit page after 2 seconds
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'event-edit.php?id=" . $new_id . "';
                    }, 2000);
                </script>";
            } else {
                $error_message = "Gagal menambahkan event: " . $insert_stmt->error;
            }
            $insert_stmt->close();
        }
    } else {
        $error_message = "Tidak ada field yang dapat diinsert!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Tambah Event | ConcertHub Admin</title>
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

        .btn-reset {
            background: rgba(255, 255, 255, 0.1);
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

        .btn-reset:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .btn-cancel {
            background: rgba(244, 67, 54, 0.2);
            padding: 0.75rem 2rem;
            border-radius: 12px;
            color: #f44336;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: rgba(244, 67, 54, 0.4);
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
            .btn-reset,
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
                <i class="fas fa-plus-circle"></i> Tambah Event Baru
            </div>
            <div class="page-subtitle">
                Buat event konser baru untuk ditampilkan di platform ConcertHub
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" data-aos="fade-up">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
                <span style="margin-left: auto; font-size: 0.8rem;">Redirecting...</span>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error" data-aos="fade-up">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <div class="form-card" data-aos="fade-up" data-aos-delay="200">
            <form method="POST" action="" enctype="multipart/form-data" id="eventForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="fas fa-music"></i> Nama Event <span style="color: #FF4788;">*</span></label>
                        <input type="text" name="name" required 
                               placeholder="Contoh: Rock Symphony 2026">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-microphone-alt"></i> Nama Artist/Band <span style="color: #FF4788;">*</span></label>
                        <input type="text" name="artist" required 
                               placeholder="Contoh: Noah, Dewa 19, Sheila On 7">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Kategori <span style="color: #FF4788;">*</span></label>
                        <select name="category" required>
                            <option value="">Pilih Kategori</option>
                            <option value="Rock">🎸 Rock</option>
                            <option value="Pop">🎤 Pop</option>
                            <option value="EDM">🎧 EDM</option>
                            <option value="Jazz">🎷 Jazz</option>
                            <option value="Classical">🎻 Classical</option>
                            <option value="Dangdut">🪘 Dangdut</option>
                            <option value="Indie">🎸 Indie</option>
                            <option value="Metal">🤘 Metal</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Tanggal Event <span style="color: #FF4788;">*</span></label>
                        <input type="date" name="event_date" required 
                               value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Waktu Event <span style="color: #FF4788;">*</span></label>
                        <input type="time" name="event_time" required value="19:00">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Kota / Lokasi <span style="color: #FF4788;">*</span></label>
                        <input type="text" name="location" required 
                               placeholder="Contoh: Jakarta, Surabaya, Bali">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-building"></i> Venue / Tempat <span style="color: #FF4788;">*</span></label>
                        <input type="text" name="venue" required 
                               placeholder="Contoh: GBK, ICE BSD, Stadion Utama">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-ticket-alt"></i> Harga Tiket (Rp) <span style="color: #FF4788;">*</span></label>
                        <input type="number" name="price" required 
                               placeholder="Contoh: 350000" step="1000" min="0" value="0">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-chair"></i> Total Kursi <span style="color: #FF4788;">*</span></label>
                        <input type="number" name="total_seats" required 
                               placeholder="Kapasitas penonton" min="1" value="1000">
                    </div>

                    <div class="form-group full-width">
                        <label><i class="fas fa-align-left"></i> Deskripsi Event</label>
                        <textarea name="description" rows="5" 
                                  placeholder="Deskripsikan event konser ini... Contoh: 
- Lineup artis yang akan tampil
- Fasilitas yang tersedia
- Hal-hal yang perlu dibawa
- Aturan dan ketentuan"></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label><i class="fas fa-image"></i> Gambar Event</label>
                        <div class="image-upload">
                            <input type="file" name="image" accept="image/*" onchange="previewImage(this)">
                            <div class="image-preview" id="imagePreview">
                                <p style="font-size: 0.8rem; margin-bottom: 0.5rem;">Preview Gambar:</p>
                                <img id="previewImg" src="" alt="Image preview">
                            </div>
                            <small style="color: rgba(255,255,255,0.5); display: block; margin-top: 0.5rem;">
                                <i class="fas fa-info-circle"></i> Format: JPG, JPEG, PNG, GIF, WEBP. Maksimal 2MB
                            </small>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_active" value="1" checked>
                            <i class="fas fa-check-circle" style="color: #4CAF50;"></i>
                            Aktifkan Event (langsung ditampilkan di website)
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Simpan Event
                    </button>
                    <button type="reset" class="btn-reset" onclick="resetForm()">
                        <i class="fas fa-undo"></i> Reset Form
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
        } else {
            preview.style.display = 'none';
            previewImg.src = '';
        }
    }

    // Reset form function
    function resetForm() {
        document.getElementById('eventForm').reset();
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('previewImg').src = '';
        document.querySelector('input[name="total_seats"]').value = 1000;
        document.querySelector('input[name="price"]').value = 0;
    }

    // Form validation before submit
    document.getElementById('eventForm').addEventListener('submit', function(e) {
        const name = document.querySelector('input[name="name"]').value;
        const artist = document.querySelector('input[name="artist"]').value;
        const date = document.querySelector('input[name="event_date"]').value;
        const price = document.querySelector('input[name="price"]').value;
        
        if (!name || !artist || !date || !price) {
            e.preventDefault();
            alert('Harap isi semua field yang wajib diisi (Nama Event, Artist, Tanggal, dan Harga)!');
        }
        
        if (price && parseFloat(price) <= 0) {
            e.preventDefault();
            alert('Harga tiket harus lebih dari 0!');
        }
    });

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