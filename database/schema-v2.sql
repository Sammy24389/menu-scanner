-- Menu Scanner System - Enhanced Schema v2.0
-- All New Features Database Structure
-- Run this AFTER the original schema

-- ============================================
-- ROLE-BASED ACCESS CONTROL
-- ============================================

-- Roles table
CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update admin_users table for roles
ALTER TABLE admin_users 
    ADD COLUMN role_id INT UNSIGNED AFTER role,
    ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER role,
    ADD COLUMN last_login TIMESTAMP NULL AFTER is_active,
    ADD FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL;

-- Create default roles
INSERT INTO roles (name, description, permissions) VALUES
('owner', 'Full access to everything', '{"all": true}'),
('manager', 'Manage daily operations', '{"dashboard": true, "tables": true, "categories": true, "items": true, "orders": true, "reservations": true, "reports": true, "staff": false, "settings": false}'),
('head_chef', 'Kitchen management', '{"kitchen": true, "inventory": true, "menu": true, "orders": true}'),
('waitstaff', 'Service staff', '{"orders": true, "tables": true, "calls": true}'),
('cashier', 'Payment processing', '{"orders": true, "payments": true}');

-- Assign owner role to existing admin
UPDATE admin_users SET role_id = 1 WHERE username = 'admin';

-- ============================================
-- MULTI-LANGUAGE SUPPORT
-- ============================================

-- Languages table
CREATE TABLE IF NOT EXISTS languages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(5) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Translations table
CREATE TABLE IF NOT EXISTS translations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    language_id INT UNSIGNED NOT NULL,
    translation_key VARCHAR(100) NOT NULL,
    translation_text TEXT NOT NULL,
    category VARCHAR(50) DEFAULT 'general',
    FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE,
    UNIQUE KEY unique_lang_key (language_id, translation_key),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default languages
INSERT INTO languages (code, name, is_default) VALUES
('en', 'English', 1),
('es', 'Spanish', 0),
('fr', 'French', 0),
('de', 'German', 0),
('zh', 'Chinese', 0),
('ar', 'Arabic', 0);

-- ============================================
-- MENU ENHANCEMENTS
-- ============================================

-- Add fields to menu_items
ALTER TABLE menu_items
    ADD COLUMN sku VARCHAR(50) AFTER name,
    ADD COLUMN preparation_time INT DEFAULT 15 AFTER description,
    ADD COLUMN calories INT NULL AFTER price,
    ADD COLUMN protein DECIMAL(5,1) NULL AFTER calories,
    ADD COLUMN carbs DECIMAL(5,1) NULL AFTER protein,
    ADD COLUMN fat DECIMAL(5,1) NULL AFTER carbs,
    ADD COLUMN is_vegetarian TINYINT(1) DEFAULT 0 AFTER is_featured,
    ADD COLUMN is_vegan TINYINT(1) DEFAULT 0 AFTER is_vegetarian,
    ADD COLUMN is_gluten_free TINYINT(1) DEFAULT 0 AFTER is_vegan,
    ADD COLUMN is_halal TINYINT(1) DEFAULT 0 AFTER is_gluten_free,
    ADD COLUMN is_kosher TINYINT(1) DEFAULT 0 AFTER is_halal,
    ADD COLUMN spice_level TINYINT(1) DEFAULT 0 AFTER is_kosher,
    ADD COLUMN allergens JSON NULL AFTER spice_level,
    ADD COLUMN video_url VARCHAR(255) NULL AFTER image,
    ADD COLUMN gallery JSON NULL AFTER video_url,
    ADD COLUMN daily_special TINYINT(1) DEFAULT 0 AFTER is_featured,
    ADD COLUMN daily_special_price DECIMAL(10,2) NULL AFTER daily_special,
    ADD COLUMN daily_special_start DATE NULL AFTER daily_special_price,
    ADD COLUMN daily_special_end DATE NULL AFTER daily_special_start,
    ADD COLUMN stock_quantity INT DEFAULT -1 AFTER daily_special_end,
    ADD COLUMN low_stock_threshold INT DEFAULT 5 AFTER stock_quantity,
    ADD COLUMN average_rating DECIMAL(3,2) DEFAULT 0 AFTER stock_quantity,
    ADD COLUMN rating_count INT DEFAULT 0 AFTER average_rating;

-- ============================================
-- RATINGS AND REVIEWS
-- ============================================

CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    customer_name VARCHAR(100),
    customer_email VARCHAR(100),
    rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    title VARCHAR(200),
    comment TEXT,
    is_approved TINYINT(1) DEFAULT 0,
    is_verified TINYINT(1) DEFAULT 0,
    helpful_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    INDEX idx_item (item_id),
    INDEX idx_approved (is_approved),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ONLINE ORDERING SYSTEM
-- ============================================

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    table_id INT UNSIGNED NULL,
    customer_name VARCHAR(100),
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    order_type ENUM('dine_in', 'takeout', 'delivery') DEFAULT 'dine_in',
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'served', 'cancelled', 'completed') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'refunded', 'failed') DEFAULT 'pending',
    payment_method ENUM('cash', 'card', 'stripe', 'paypal', 'online') NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) DEFAULT 0,
    tip DECIMAL(10,2) DEFAULT 0,
    discount DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    notes TEXT,
    special_requests TEXT,
    delivery_address TEXT,
    assigned_waiter_id INT UNSIGNED NULL,
    assigned_chef_id INT UNSIGNED NULL,
    estimated_time INT DEFAULT 30,
    actual_time INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    prepared_at TIMESTAMP NULL,
    served_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_waiter_id) REFERENCES waitstaff(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_table (table_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    variation_id INT UNSIGNED NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    modifiers JSON NULL,
    special_instructions TEXT,
    status ENUM('pending', 'preparing', 'ready', 'served', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE RESTRICT,
    FOREIGN KEY (variation_id) REFERENCES item_variations(id) ON DELETE SET NULL,
    INDEX idx_order (order_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- LOYALTY PROGRAM
-- ============================================

CREATE TABLE IF NOT EXISTS loyalty_programs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    points_per_dollar DECIMAL(5,2) DEFAULT 1.00,
    reward_threshold INT DEFAULT 100,
    reward_value DECIMAL(10,2) DEFAULT 10.00,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    loyalty_points INT DEFAULT 0,
    total_orders INT DEFAULT 0,
    total_spent DECIMAL(10,2) DEFAULT 0,
    birth_date DATE NULL,
    preferences JSON NULL,
    is_vip TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_order_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_vip (is_vip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS loyalty_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    order_id INT UNSIGNED NULL,
    type ENUM('earned', 'redeemed', 'expired', 'adjusted') NOT NULL,
    points INT NOT NULL,
    balance_after INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- RESERVATION SYSTEM
-- ============================================

CREATE TABLE IF NOT EXISTS reservations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_number VARCHAR(20) NOT NULL UNIQUE,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20) NOT NULL,
    table_id INT UNSIGNED NULL,
    party_size INT NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    duration INT DEFAULT 90,
    status ENUM('pending', 'confirmed', 'seated', 'completed', 'cancelled', 'no_show') DEFAULT 'pending',
    special_requests TEXT,
    is_vip TINYINT(1) DEFAULT 0,
    assigned_waiter_id INT UNSIGNED NULL,
    notes TEXT,
    reminder_sent TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    seated_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_waiter_id) REFERENCES waitstaff(id) ON DELETE SET NULL,
    INDEX idx_date (reservation_date),
    INDEX idx_status (status),
    INDEX idx_table (table_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- INVENTORY MANAGEMENT
-- ============================================

CREATE TABLE IF NOT EXISTS ingredients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    sku VARCHAR(50),
    unit VARCHAR(20) DEFAULT 'piece',
    current_stock DECIMAL(10,2) DEFAULT 0,
    min_stock DECIMAL(10,2) DEFAULT 10,
    cost_per_unit DECIMAL(10,2) DEFAULT 0,
    supplier VARCHAR(100),
    category VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    last_restocked_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_stock (current_stock),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS recipe_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_item_id INT UNSIGNED NOT NULL,
    ingredient_id INT UNSIGNED NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20),
    is_optional TINYINT(1) DEFAULT 0,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE,
    UNIQUE KEY unique_recipe (menu_item_id, ingredient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inventory_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ingredient_id INT UNSIGNED NOT NULL,
    type ENUM('restock', 'used', 'waste', 'adjustment') NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    previous_stock DECIMAL(10,2),
    new_stock DECIMAL(10,2),
    reason TEXT,
    user_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_ingredient (ingredient_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- NOTIFICATIONS
-- ============================================

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    type ENUM('order', 'reservation', 'low_stock', 'review', 'system', 'payment') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    data JSON NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notification_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    email_orders TINYINT(1) DEFAULT 1,
    email_reservations TINYINT(1) DEFAULT 1,
    email_reviews TINYINT(1) DEFAULT 1,
    sms_orders TINYINT(1) DEFAULT 0,
    sms_reservations TINYINT(1) DEFAULT 0,
    push_orders TINYINT(1) DEFAULT 1,
    push_reservations TINYINT(1) DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ANALYTICS & REPORTS
-- ============================================

CREATE TABLE IF NOT EXISTS analytics_daily (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL UNIQUE,
    total_orders INT DEFAULT 0,
    total_revenue DECIMAL(10,2) DEFAULT 0,
    total_customers INT DEFAULT 0,
    average_order_value DECIMAL(10,2) DEFAULT 0,
    top_item_id INT UNSIGNED NULL,
    top_table_id INT UNSIGNED NULL,
    peak_hour INT DEFAULT 12,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- THEME SETTINGS
-- ============================================

CREATE TABLE IF NOT EXISTS theme_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT,
    category VARCHAR(50) DEFAULT 'general',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default theme settings
INSERT INTO theme_settings (setting_key, setting_value, category) VALUES
('primary_color', '#667eea', 'colors'),
('secondary_color', '#764ba2', 'colors'),
('dark_mode_enabled', '1', 'display'),
('restaurant_name', 'My Restaurant', 'general'),
('restaurant_logo', '', 'general'),
('currency_symbol', '$', 'general'),
('timezone', 'UTC', 'general');

-- ============================================
-- SOCIAL MEDIA & INTEGRATIONS
-- ============================================

CREATE TABLE IF NOT EXISTS social_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    platform VARCHAR(50) NOT NULL UNIQUE,
    is_enabled TINYINT(1) DEFAULT 0,
    api_key VARCHAR(255),
    api_secret VARCHAR(255),
    settings JSON,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO social_settings (platform) VALUES
('facebook'), ('instagram'), ('twitter'), ('tiktok');

CREATE TABLE IF NOT EXISTS quickbooks_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    is_configured TINYINT(1) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- KITCHEN DISPLAY
-- ============================================

CREATE TABLE IF NOT EXISTS kitchen_stations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    assigned_chef_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- UPDATE WAITSTAFF TABLE
-- ============================================

ALTER TABLE waitstaff
    ADD COLUMN email VARCHAR(100) AFTER name,
    ADD COLUMN role_id INT UNSIGNED AFTER email,
    ADD COLUMN can_take_orders TINYINT(1) DEFAULT 1 AFTER is_active,
    ADD COLUMN can_manage_reservations TINYINT(1) DEFAULT 0 AFTER can_take_orders,
    ADD FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL;

-- ============================================
-- ADD INDEXES FOR PERFORMANCE
-- ============================================

CREATE INDEX IF NOT EXISTS idx_menu_available ON menu_items(is_available);
CREATE INDEX IF NOT EXISTS idx_menu_featured ON menu_items(is_featured);
CREATE INDEX IF NOT EXISTS idx_menu_daily_special ON menu_items(daily_special);
