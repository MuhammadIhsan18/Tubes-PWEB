<?php
include "../includes/auth.php";
include "../config/db.php";

if ($_SESSION['user']['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Get table structure to detect column names
$columns = [];
$col_query = $conn->query("SHOW COLUMNS FROM users");
if ($col_query) {
    while ($col = $col_query->fetch_assoc()) {
        $columns[] = $col['Field'];
    }
}

// Detect user name column
$user_name_column = 'name';
$possible_names = ['name', 'username', 'nama', 'fullname', 'full_name'];
foreach ($possible_names as $name) {
    if (in_array($name, $columns)) {
        $user_name_column = $name;
        break;
    }
}

// Detect user email column
$user_email_column = 'email';
$possible_emails = ['email', 'email_address', 'mail'];
foreach ($possible_emails as $email) {
    if (in_array($email, $columns)) {
        $user_email_column = $email;
        break;
    }
}

// Detect user role column
$user_role_column = 'role';
$possible_roles = ['role', 'roles', 'user_role', 'level'];
foreach ($possible_roles as $role) {
    if (in_array($role, $columns)) {
        $user_role_column = $role;
        break;
    }
}

// Detect user status column
$user_status_column = 'is_active';
$possible_status = ['is_active', 'status', 'active', 'aktif'];
foreach ($possible_status as $status) {
    if (in_array($status, $columns)) {
        $user_status_column = $status;
        break;
    }
}

// Detect date column for sorting
$date_column = 'created_at';
$possible_dates = ['created_at', 'createdAt', 'register_date', 'registered_at', 'tanggal_daftar'];
foreach ($possible_dates as $date) {
    if (in_array($date, $columns)) {
        $date_column = $date;
        break;
    }
}

// Detect primary key
$primary_key = 'id';
$possible_keys = ['id', 'user_id', 'userId', 'ID'];
foreach ($possible_keys as $key) {
    if (in_array($key, $columns)) {
        $primary_key = $key;
        break;
    }
}

/* =======================
   HANDLE DELETE USER
======================= */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = (int) $_GET['delete'];
    
    // Don't allow admin to delete themselves
    if ($user_id == $_SESSION['user'][$primary_key]) {
        $error_message = "Anda tidak dapat menghapus akun sendiri!";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE $primary_key = ?");
        if ($stmt === false) {
            $error_message = "Error preparing delete: " . $conn->error;
        } else {
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $success_message = "User berhasil dihapus!";
            } else {
                $error_message = "Gagal menghapus user: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

/* =======================
   HANDLE TOGGLE STATUS
======================= */
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $user_id = (int) $_GET['toggle'];
    
    $stmt = $conn->prepare("UPDATE users SET $user_status_column = NOT $user_status_column WHERE $primary_key = ?");
    if ($stmt === false) {
        $error_message = "Error preparing toggle: " . $conn->error;
    } else {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $success_message = "Status user berhasil diubah!";
        } else {
            $error_message = "Gagal mengubah status user: " . $stmt->error;
        }
        $stmt->close();
    }
}

/* =======================
   HANDLE UPDATE ROLE
======================= */
if (isset($_GET['update_role']) && is_numeric($_GET['update_role'])) {
    $user_id = (int) $_GET['update_role'];
    $new_role = isset($_GET['role_value']) ? $_GET['role_value'] : 'user';
    
    $allowed_roles = ['admin', 'user', 'member'];
    if (in_array($new_role, $allowed_roles)) {
        $stmt = $conn->prepare("UPDATE users SET $user_role_column = ? WHERE $primary_key = ?");
        if ($stmt === false) {
            $error_message = "Error preparing update: " . $conn->error;
        } else {
            $stmt->bind_param("si", $new_role, $user_id);
            if ($stmt->execute()) {
                $success_message = "Role user berhasil diupdate!";
            } else {
                $error_message = "Gagal mengupdate role: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

/* =======================
   FETCH DATA - Fix ORDER BY clause
======================= */
// Use the detected date column or default to id if no date column exists
$order_by = "";
if (in_array($date_column, $columns)) {
    $order_by = "ORDER BY $date_column DESC";
} else if (in_array($primary_key, $columns)) {
    $order_by = "ORDER BY $primary_key DESC";
} else {
    $order_by = "";
}

$sql = "SELECT * FROM users $order_by";
$result = $conn->query($sql);

// If query fails, show error
if ($result === false) {
    $error_message = "Error fetching data: " . $conn->error;
    $result = null;
}

// Get statistics with safe column names
$total_users = 0;
$total_admins = 0;
$active_users = 0;

// Build stats query safely
$stats_sql = "SELECT COUNT(*) as total FROM users";
$stats_result = $conn->query($stats_sql);
if ($stats_result && $stats_result->num_rows > 0) {
    $total_users = $stats_result->fetch_assoc()['total'];
}

// Count admins if role column exists
if (in_array($user_role_column, $columns)) {
    $admin_sql = "SELECT COUNT(*) as admins FROM users WHERE $user_role_column = 'admin'";
    $admin_result = $conn->query($admin_sql);
    if ($admin_result && $admin_result->num_rows > 0) {
        $total_admins = $admin_result->fetch_assoc()['admins'];
    }
}

// Count active users if status column exists
if (in_array($user_status_column, $columns)) {
    $active_sql = "SELECT COUNT(*) as active FROM users WHERE $user_status_column = 1";
    $active_result = $conn->query($active_sql);
    if ($active_result && $active_result->num_rows > 0) {
        $active_users = $active_result->fetch_assoc()['active'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Kelola User | ConcertHub Admin</title>
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(20, 20, 30, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 1.2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            border-color: #FF4788;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #FFB347, #FF4788);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .stat-label {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-top: 0.3rem;
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

        /* Role Badge */
        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .role-admin {
            background: rgba(255, 71, 136, 0.2);
            color: #FF4788;
            border: 1px solid rgba(255, 71, 136, 0.3);
        }

        .role-user {
            background: rgba(33, 150, 243, 0.2);
            color: #2196F3;
            border: 1px solid rgba(33, 150, 243, 0.3);
        }

        .role-member {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-icon {
            padding: 0.5rem 0.8rem;
            border-radius: 10px;
            color: #fff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.75rem;
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

        .role-select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #fff;
            padding: 0.3rem 0.5rem;
            font-size: 0.75rem;
            cursor: pointer;
        }

        .role-select:focus {
            outline: none;
            border-color: #FF4788;
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
                padding: 0.3rem 0.6rem;
                font-size: 0.7rem;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user'][$user_name_column] ?? 'Admin'); ?>
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
                    <i class="fas fa-users"></i> Kelola User
                </div>
                <p style="color: rgba(255,255,255,0.6); margin-top: 0.5rem;">Kelola semua pengguna platform ConcertHub</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid" data-aos="fade-up" data-aos-delay="150">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Pengguna</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_admins; ?></div>
                <div class="stat-label">Admin</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $active_users; ?></div>
                <div class="stat-label">Aktif</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_users - $active_users; ?></div>
                <div class="stat-label">Nonaktif</div>
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

        <div class="table-card" data-aos="fade-up" data-aos-delay="200">
            <?php if ($result && $result->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Bergabung</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = $result->fetch_assoc()): 
                            $user_id = $user[$primary_key] ?? '-';
                            $is_current_user = ($user_id == ($_SESSION['user'][$primary_key] ?? null));
                            $status = $user[$user_status_column] ?? 1;
                            $role = $user[$user_role_column] ?? 'user';
                            
                            // Get date value safely
                            $date_value = '-';
                            if (isset($user[$date_column]) && !empty($user[$date_column])) {
                                $date_value = date('d/m/Y', strtotime($user[$date_column]));
                            }
                        ?>
                            <tr>
                                <td><?php echo $user_id; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user[$user_name_column] ?? '-'); ?></strong>
                                    <?php if($is_current_user): ?>
                                        <br><small style="color: #FF4788;">(Anda)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user[$user_email_column] ?? '-'); ?></td>
                                <td>
                                    <?php if (!$is_current_user): ?>
                                        <select class="role-select" onchange="updateRole(<?php echo $user_id; ?>, this.value)">
                                            <option value="user" <?php echo $role == 'user' ? 'selected' : ''; ?>>User</option>
                                            <option value="member" <?php echo $role == 'member' ? 'selected' : ''; ?>>Member</option>
                                            <option value="admin" <?php echo $role == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                    <?php else: ?>
                                        <span class="role-badge role-<?php echo $role; ?>">
                                            <i class="fas <?php echo $role == 'admin' ? 'fa-crown' : 'fa-user'; ?>"></i>
                                            <?php echo ucfirst($role); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $status ? 'status-active' : 'status-inactive'; ?>">
                                        <i class="fas <?php echo $status ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                        <?php echo $status ? 'Aktif' : 'Nonaktif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <i class="far fa-calendar-alt" style="color: #FF8C42;"></i>
                                    <?php echo $date_value; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if (!$is_current_user): ?>
                                            <a href="?toggle=<?php echo $user_id; ?>" class="btn-icon btn-toggle" title="Toggle Status" onclick="return confirm('Ubah status user ini?')">
                                                <i class="fas fa-power-off"></i>
                                                <span>Toggle</span>
                                            </a>
                                            <a href="?delete=<?php echo $user_id; ?>" class="btn-icon btn-delete" title="Hapus" onclick="return confirm('Yakin ingin menghapus user ini? Data tidak dapat dikembalikan!')">
                                                <i class="fas fa-trash"></i>
                                                <span>Hapus</span>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: rgba(255,255,255,0.4); font-size: 0.7rem;">Tidak dapat mengubah akun sendiri</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <p>Belum ada pengguna yang terdaftar</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ once: true, offset: 120, duration: 800 });

    // Update role function
    function updateRole(userId, newRole) {
        if (confirm('Ubah role user ini menjadi ' + newRole.toUpperCase() + '?')) {
            window.location.href = '?update_role=' + userId + '&role_value=' + newRole;
        }
    }

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