-- PHP Menu Scanner System Database Schema
-- MySQL 8.x compatible

CREATE DATABASE IF NOT EXISTS menu_scanner CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE menu_scanner;

-- 1. Tables table (no foreign keys)
CREATE TABLE IF NOT EXISTS tables (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    table_number VARCHAR(20) NOT NULL,
    qr_code_uuid CHAR(36) NOT NULL UNIQUE,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_qr_code (qr_code_uuid),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Categories table (no foreign keys)
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    sort_order INT UNSIGNED DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sort (sort_order),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Admin users table (no foreign keys)
CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Waitstaff table (foreign key to tables)
CREATE TABLE IF NOT EXISTS waitstaff (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    pin_code VARCHAR(6) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    current_table_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (current_table_id) REFERENCES tables(id) ON DELETE SET NULL,
    INDEX idx_pin (pin_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Menu items table (foreign key to categories)
CREATE TABLE IF NOT EXISTS menu_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    image VARCHAR(255),
    is_available TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id),
    INDEX idx_available (is_available),
    INDEX idx_featured (is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Item variations table (foreign key to menu_items)
CREATE TABLE IF NOT EXISTS item_variations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    price_modifier DECIMAL(10, 2) DEFAULT 0.00,
    is_default TINYINT(1) DEFAULT 0,
    sort_order INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    INDEX idx_item (item_id),
    INDEX idx_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Service calls table (foreign keys to tables and waitstaff)
CREATE TABLE IF NOT EXISTS service_calls (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    table_id INT UNSIGNED NOT NULL,
    waitstaff_id INT UNSIGNED NULL,
    status ENUM('pending', 'assigned', 'completed') DEFAULT 'pending',
    call_type ENUM('waiter', 'bill', 'complaint', 'other') DEFAULT 'waiter',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE,
    FOREIGN KEY (waitstaff_id) REFERENCES waitstaff(id) ON DELETE SET NULL,
    INDEX idx_table (table_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============ SAMPLE DATA ============

-- Default admin (password: admin123)
INSERT INTO admin_users (username, password_hash, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Tables
INSERT INTO tables (table_number, qr_code_uuid, status) VALUES
('T1', '550e8400-e29b-41d4-a716-446655440001', 'active'),
('T2', '550e8400-e29b-41d4-a716-446655440002', 'active'),
('T3', '550e8400-e29b-41d4-a716-446655440003', 'active'),
('T4', '550e8400-e29b-41d4-a716-446655440004', 'active');

-- Categories
INSERT INTO categories (name, description, sort_order) VALUES
('Appetizers', 'Start your meal right', 1),
('Main Course', 'Hearty main dishes', 2),
('Beverages', 'Drinks and refreshments', 3),
('Desserts', 'Sweet endings', 4);

-- Menu items (category_id matches categories above)
INSERT INTO menu_items (category_id, name, description, price, is_featured) VALUES
(1, 'Spring Rolls', 'Crispy vegetable spring rolls with dipping sauce', 8.99, 1),
(1, 'Garlic Bread', 'Toasted bread with garlic butter', 5.99, 0),
(2, 'Grilled Salmon', 'Fresh salmon with lemon butter sauce', 24.99, 1),
(2, 'Chicken Pasta', 'Creamy pasta with grilled chicken', 18.99, 0),
(3, 'Fresh Juice', 'Freshly squeezed orange juice', 4.99, 0),
(3, 'Iced Coffee', 'Cold brewed coffee with ice', 3.99, 0),
(4, 'Chocolate Cake', 'Rich chocolate layer cake', 7.99, 1),
(4, 'Ice Cream', 'Vanilla ice cream with toppings', 5.99, 0);

-- Variations (item_id: 3=Grilled Salmon, 5=Fresh Juice, 6=Iced Coffee)
INSERT INTO item_variations (item_id, name, price_modifier, is_default) VALUES
(3, 'Regular', 0.00, 1),
(3, 'Large', 5.00, 0),
(5, 'Small', 0.00, 1),
(5, 'Medium', 1.50, 0),
(5, 'Large', 3.00, 0),
(6, 'Regular', 0.00, 1),
(6, 'With Cream', 1.00, 0);

-- Waitstaff
INSERT INTO waitstaff (name, pin_code, is_active) VALUES
('John Doe', '1001', 1),
('Jane Smith', '1002', 1),
('Mike Johnson', '1003', 1);
