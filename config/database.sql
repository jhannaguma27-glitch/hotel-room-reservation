-- Database initialization
CREATE DATABASE hotel_reservation_system;
USE hotel_reservation_system;

-- ==============================
--  Table: admins
-- ==============================
CREATE TABLE admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) UNIQUE NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('superadmin', 'manager', 'staff') DEFAULT 'staff',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL
);

-- ==============================
--  Table: users
-- ==============================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    created_by_admin INT NULL,
    FOREIGN KEY (created_by_admin) REFERENCES admins(admin_id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- ==============================
--  Table: room_types

-- ==============================
CREATE TABLE room_types (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT NULL,
    base_price DECIMAL(10,2) NOT NULL,
    created_by_admin INT NULL,
    FOREIGN KEY (created_by_admin) REFERENCES admins(admin_id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- ==============================
--  Table: rooms
-- ==============================
CREATE TABLE rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) UNIQUE NOT NULL,
    type_id INT NOT NULL,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    max_guests INT NOT NULL,
    amenities TEXT NULL,
    created_by_admin INT NULL,
    FOREIGN KEY (type_id) REFERENCES room_types(type_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (created_by_admin) REFERENCES admins(admin_id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- ==============================
--  Table: reservations
-- ==============================
CREATE TABLE reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'checked_in', 'completed', 'cancelled') DEFAULT 'pending',
    created_by_admin INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (created_by_admin) REFERENCES admins(admin_id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- ==============================
--  Table: payments
-- ==============================
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('credit_card', 'paypal', 'bank_transfer', 'cash') NOT NULL,
    payment_status ENUM('pending', 'successful', 'failed', 'refunded') DEFAULT 'pending',
    processed_by_admin INT NULL,
    paid_at DATETIME NULL,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (processed_by_admin) REFERENCES admins(admin_id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- ==============================
--  Table: reviews
-- ==============================
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NULL,
    approved_by_admin INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (approved_by_admin) REFERENCES admins(admin_id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- ==============================
--  Table: notifications
-- ==============================
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type ENUM('system', 'promo', 'alert') DEFAULT 'system',
    is_read BOOLEAN DEFAULT FALSE,
    sent_by_admin INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (sent_by_admin) REFERENCES admins(admin_id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- ==============================
--  Table: admin_logs
-- ==============================
CREATE TABLE admin_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    entity_affected VARCHAR(100) NOT NULL,
    entity_id INT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    details TEXT NULL,
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id)
        ON DELETE CASCADE ON UPDATE CASCADE
);


INSERT INTO admins (admin_id, username, email, password_hash, role, created_at, last_login)
VALUES (1,'admin','admin@hotel.com',
        '$2y$10$hFd8t8njShdEnOzt0qMVwuIgtdyID0IUmLQyj0Cket2tG766IflFK',
        'superadmin','2025-11-25 21:50:09','2025-11-26 10:43:13')
ON DUPLICATE KEY UPDATE
    username = VALUES(username),
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    role = VALUES(role),
    last_login = VALUES(last_login);

INSERT INTO users (user_id, full_name, email, password_hash, phone, created_at, is_active, created_by_admin)
VALUES (1,'Trisha Gamido','trisha@gmail.com',
        '$2y$10$3xgfHuta5uH/s4SZd3LOXenuCet.BdqZYK.mGkUcY6eqRRmXbyv7y',
        '09088184444','2025-11-25 21:52:14',0,NULL)
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    phone = VALUES(phone),
    is_active = VALUES(is_active);

INSERT INTO room_types (type_id, type_name, description, base_price, created_by_admin)
VALUES 
(1,'Standard Room','Comfortable room with basic amenities',99.99,1),
(2,'Deluxe Room','Spacious room with premium amenities',149.99,1),
(3,'Suite','Luxury suite with separate living area',299.99,1),
(4,'Presidential Suite','Ultimate luxury with panoramic views',599.99,1),
(5,'Family Room','Spacious room perfect for families with connecting beds',199.99,1),
(6,'Executive Suite','Business-class suite with work area and premium services',399.99,1)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    base_price = VALUES(base_price),
    created_by_admin = VALUES(created_by_admin);

INSERT INTO rooms (room_id, room_number, type_id, status, max_guests, amenities, created_by_admin)
VALUES
(1,'101',1,'available',2,'WiFi, TV, Air Conditioning',1),
(2,'102',1,'available',2,'WiFi, TV, Air Conditioning',1),
(3,'201',2,'available',3,'WiFi, TV, Air Conditioning, Mini Bar',1),
(4,'202',2,'available',3,'WiFi, TV, Air Conditioning, Mini Bar',1),
(5,'301',3,'available',4,'WiFi, TV, Air Conditioning, Mini Bar, Balcony',1),
(6,'401',4,'available',6,'WiFi, TV, Air Conditioning, Mini Bar, Balcony, Jacuzzi',1),
(7,'501',5,'available',5,'WiFi, TV, Air Conditioning, Mini Bar, Connecting Beds',1),
(8,'601',6,'available',4,'WiFi, TV, Air Conditioning, Mini Bar, Work Desk, Business Center Access',1)
ON DUPLICATE KEY UPDATE
    type_id = VALUES(type_id),
    status = VALUES(status),
    max_guests = VALUES(max_guests),
    amenities = VALUES(amenities);

INSERT INTO reservations (reservation_id, user_id, room_id, check_in_date, check_out_date,
                          total_price, status, created_by_admin, created_at)
VALUES
(1,1,1,'2025-11-25','2025-11-26',99.99,'cancelled',NULL,'2025-11-25 23:04:56')
ON DUPLICATE KEY UPDATE
    status = VALUES(status),
    total_price = VALUES(total_price),
    check_in_date = VALUES(check_in_date),
    check_out_date = VALUES(check_out_date);
