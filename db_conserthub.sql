-- DATABASE CONCERT_TICKETING
create DATABASE concert_ticketing;
USE concert_ticketing;


-- TABEL USERS
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin','user') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- TABEL EVENTS (DIPERBAIKI)
CREATE TABLE IF NOT EXISTS events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150),
    artist VARCHAR(100),
    event_date DATETIME,
    location VARCHAR(100) DEFAULT NULL,
    venue VARCHAR(100) DEFAULT NULL,
    category VARCHAR(50) DEFAULT NULL,
    price DECIMAL(12,2) DEFAULT 0,
    total_seats INT DEFAULT 0,
    description TEXT,
    image_path VARCHAR(255) DEFAULT NULL,
    status ENUM('upcoming','completed') DEFAULT 'upcoming',
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- TABEL TICKET_CATEGORIES
CREATE TABLE IF NOT EXISTS ticket_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT,
    name VARCHAR(100),
    price INT,
    quota INT,
    sold_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
);

-- TABEL ORDERS (DIPERBAIKI)
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price_per_ticket DECIMAL(12,2) NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    order_code VARCHAR(50) NULL,
    payment_method VARCHAR(50) DEFAULT NULL,
    payment_proof VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
);

-- TABEL ORDER_ITEMS
CREATE TABLE IF NOT EXISTS order_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    category_id INT,
    quantity INT,
    subtotal INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES ticket_categories(category_id) ON DELETE SET NULL
);

-- TABEL AUDIT_LOGS
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(50),
    action VARCHAR(20),
    old_data JSON,
    new_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TRIGGER UNTUK AUDIT TICKET_CATEGORIES
DROP TRIGGER IF EXISTS trg_update_ticket;
DELIMITER $$

CREATE TRIGGER trg_update_ticket
BEFORE UPDATE ON ticket_categories
FOR EACH ROW
BEGIN
    IF OLD.price <> NEW.price OR OLD.quota <> NEW.quota THEN
        INSERT INTO audit_logs(table_name, action, old_data, new_data)
        VALUES (
            'ticket_categories',
            'UPDATE',
            JSON_OBJECT('price', OLD.price, 'quota', OLD.quota, 'sold_count', OLD.sold_count),
            JSON_OBJECT('price', NEW.price, 'quota', NEW.quota, 'sold_count', NEW.sold_count)
        );
    END IF;
END$$

DELIMITER ;

-- STORED PROCEDURE: sp_create_order (DIPERBAIKI)
DROP PROCEDURE IF EXISTS sp_create_order;
DELIMITER $$

CREATE PROCEDURE sp_create_order(
    IN p_user_id INT,
    IN p_category_id INT,
    IN p_qty INT
)
BEGIN
    DECLARE v_price INT;
    DECLARE v_stock INT;
    DECLARE v_event_id INT;
    DECLARE v_order_id INT;

    -- Cek stok dan ambil harga
    SELECT price, (quota - sold_count), event_id
    INTO v_price, v_stock, v_event_id
    FROM ticket_categories
    WHERE category_id = p_category_id;

    IF v_stock IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Kategori tiket tidak ditemukan';
    END IF;

    IF v_stock < p_qty THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stok tiket tidak mencukupi';
    END IF;

    -- Insert ke orders
    INSERT INTO orders(user_id, event_id, quantity, price_per_ticket, total_amount, status, order_code, created_at)
    VALUES (p_user_id, v_event_id, p_qty, v_price, v_price * p_qty, 'pending', CONCAT('ORD-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(FLOOR(RAND() * 10000), 4, '0')), NOW());

    SET v_order_id = LAST_INSERT_ID();

    -- Insert ke order_items
    INSERT INTO order_items(order_id, category_id, quantity, subtotal)
    VALUES (v_order_id, p_category_id, p_qty, v_price * p_qty);

    -- Update sold_count
    UPDATE ticket_categories
    SET sold_count = sold_count + p_qty
    WHERE category_id = p_category_id;

    -- Return order_id
    SELECT v_order_id AS order_id;
END$$

DELIMITER ;

-- STORED PROCEDURE: sp_report (DIPERBAIKI)
DROP PROCEDURE IF EXISTS sp_report;
DELIMITER $$

CREATE PROCEDURE sp_report()
BEGIN
    SELECT 
        e.event_id,
        e.title,
        e.artist,
        e.event_date,
        COUNT(DISTINCT o.order_id) AS total_orders,
        SUM(oi.quantity) AS total_tickets_sold,
        COALESCE(SUM(o.total_amount), 0) AS total_revenue,
        COALESCE(AVG(o.total_amount), 0) AS avg_order
    FROM events e
    LEFT JOIN orders o ON o.event_id = e.event_id AND o.status = 'paid'
    LEFT JOIN order_items oi ON oi.order_id = o.order_id
    WHERE e.status = 'upcoming'
    GROUP BY e.event_id
    ORDER BY e.event_date ASC;
END$$

DELIMITER ;

-- STORED PROCEDURE: sp_admin_dashboard
DROP PROCEDURE IF EXISTS sp_admin_dashboard;
DELIMITER $$

CREATE PROCEDURE sp_admin_dashboard()
BEGIN
    DECLARE v_total_users INT;
    DECLARE v_total_events INT;
    DECLARE v_total_tickets_sold INT;
    DECLARE v_total_revenue DECIMAL(12,2);
    
    -- Total Users
    SELECT COUNT(*) INTO v_total_users FROM users WHERE is_active = 1;
    
    -- Total Events
    SELECT COUNT(*) INTO v_total_events FROM events WHERE status = 'upcoming';
    
    -- Total Tickets Sold
    SELECT COALESCE(SUM(oi.quantity), 0) INTO v_total_tickets_sold
    FROM order_items oi
    JOIN orders o ON o.order_id = oi.order_id
    WHERE o.status = 'paid';
    
    -- Total Revenue
    SELECT COALESCE(SUM(o.total_amount), 0) INTO v_total_revenue
    FROM orders o
    WHERE o.status = 'paid';
    
    -- Return result
    SELECT 
        v_total_users AS total_users,
        v_total_events AS total_events,
        v_total_tickets_sold AS total_tickets_sold,
        v_total_revenue AS total_revenue;
END$$

DELIMITER ;

-- DATA SAMPLE

-- Insert Users
INSERT INTO users (username, email, password, role, is_active) VALUES
('admin', 'admin@mail.com', '123456', 'admin', 1),
('user', 'user@mail.com', '123456', 'user', 1);

-- Insert Events (dengan kolom lengkap)
INSERT INTO events (title, artist, event_date, location, venue, category, price, total_seats, description, status, is_active, created_by) VALUES
('Rock Symphony 2026', 'Noah', '2026-05-20 19:00:00', 'Jakarta', 'GBK', 'Rock', 350000, 5000, 'Konser rock terbesar tahun ini dengan penampilan spesial!', 'upcoming', 1, 1),
('EDM Festival', 'DJ W&W', '2026-06-05 20:00:00', 'Surabaya', 'ICE BSD', 'EDM', 500000, 10000, 'Festival EDM paling seru dengan light show spektakuler!', 'upcoming', 1, 1),
('Pop Grandeur', 'Sheila On 7', '2026-07-18 18:30:00', 'Bandung', 'Stadion Utama', 'Pop', 275000, 8000, 'Konser pop dengan bintang ternama Indonesia!', 'upcoming', 1, 1),
('Jazz Night', 'Fariz RM', '2026-08-10 19:30:00', 'Jakarta', 'Taman Ismail Marzuki', 'Jazz', 400000, 3000, 'Malam jazz yang romantis dengan musisi terbaik!', 'upcoming', 1, 1),
('Classical Orchestra', 'Twilite Orchestra', '2026-09-15 19:00:00', 'Jakarta', 'Aula Simfonia', 'Classical', 600000, 2000, 'Penampilan orkestra klasik yang megah!', 'upcoming', 1, 1);

-- Insert Ticket Categories
INSERT INTO ticket_categories (event_id, name, price, quota, sold_count) VALUES
(1, 'VIP', 750000, 500, 0),
(1, 'Festival', 350000, 3000, 0),
(1, 'Bronze', 150000, 1500, 0),
(2, 'VIP', 1000000, 500, 0),
(2, 'Festival', 500000, 5000, 0),
(2, 'Regular', 250000, 4500, 0),
(3, 'VIP', 550000, 500, 0),
(3, 'Festival', 275000, 5000, 0),
(3, 'Bronze', 125000, 2500, 0),
(4, 'VIP', 800000, 300, 0),
(4, 'Regular', 400000, 2000, 0),
(5, 'VIP', 1200000, 200, 0),
(5, 'Regular', 600000, 1500, 0);

-- QUERY UNTUK VERIFIKASI

-- Cek semua tabel
SHOW TABLES;

-- Cek data events
SELECT * FROM events;

-- Cek data ticket_categories
SELECT * FROM ticket_categories;

-- Cek stored procedures
SHOW PROCEDURE STATUS WHERE Db = 'concert_ticketing';

-- Test dashboard procedure
CALL sp_admin_dashboard();

-- Test report procedure
CALL sp_report();