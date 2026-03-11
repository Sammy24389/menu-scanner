-- PHP Menu Scanner System - SQLite Schema
-- For Render free tier deployment

-- Tables
CREATE TABLE IF NOT EXISTS tables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_number VARCHAR(20) NOT NULL,
    qr_code_uuid VARCHAR(36) NOT NULL UNIQUE,
    status VARCHAR(20) DEFAULT 'active' CHECK(status IN ('active', 'inactive', 'maintenance')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_qr_code ON tables(qr_code_uuid);
CREATE INDEX IF NOT EXISTS idx_status ON tables(status);

-- Categories
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    sort_order INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_sort ON categories(sort_order);
CREATE INDEX IF NOT EXISTS idx_active ON categories(is_active);

-- Admin Users
CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'admin',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Waitstaff
CREATE TABLE IF NOT EXISTS waitstaff (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    pin_code VARCHAR(6) NOT NULL,
    is_active INTEGER DEFAULT 1,
    current_table_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (current_table_id) REFERENCES tables(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_pin ON waitstaff(pin_code);
CREATE INDEX IF NOT EXISTS idx_active ON waitstaff(is_active);

-- Menu Items
CREATE TABLE IF NOT EXISTS menu_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    image VARCHAR(255),
    is_available INTEGER DEFAULT 1,
    is_featured INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_category ON menu_items(category_id);
CREATE INDEX IF NOT EXISTS idx_available ON menu_items(is_available);
CREATE INDEX IF NOT EXISTS idx_featured ON menu_items(is_featured);

-- Item Variations
CREATE TABLE IF NOT EXISTS item_variations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    item_id INTEGER NOT NULL,
    name VARCHAR(100) NOT NULL,
    price_modifier DECIMAL(10, 2) DEFAULT 0.00,
    is_default INTEGER DEFAULT 0,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_item ON item_variations(item_id);
CREATE INDEX IF NOT EXISTS idx_default ON item_variations(is_default);

-- Service Calls
CREATE TABLE IF NOT EXISTS service_calls (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_id INTEGER NOT NULL,
    waitstaff_id INTEGER,
    status VARCHAR(20) DEFAULT 'pending' CHECK(status IN ('pending', 'assigned', 'completed')),
    call_type VARCHAR(20) DEFAULT 'waiter' CHECK(call_type IN ('waiter', 'bill', 'complaint', 'other')),
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    assigned_at DATETIME,
    resolved_at DATETIME,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE,
    FOREIGN KEY (waitstaff_id) REFERENCES waitstaff(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_table ON service_calls(table_id);
CREATE INDEX IF NOT EXISTS idx_status ON service_calls(status);
CREATE INDEX IF NOT EXISTS idx_created ON service_calls(created_at);

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

-- Menu items
INSERT INTO menu_items (category_id, name, description, price, is_featured) VALUES
(1, 'Spring Rolls', 'Crispy vegetable spring rolls with dipping sauce', 8.99, 1),
(1, 'Garlic Bread', 'Toasted bread with garlic butter', 5.99, 0),
(2, 'Grilled Salmon', 'Fresh salmon with lemon butter sauce', 24.99, 1),
(2, 'Chicken Pasta', 'Creamy pasta with grilled chicken', 18.99, 0),
(3, 'Fresh Juice', 'Freshly squeezed orange juice', 4.99, 0),
(3, 'Iced Coffee', 'Cold brewed coffee with ice', 3.99, 0),
(4, 'Chocolate Cake', 'Rich chocolate layer cake', 7.99, 1),
(4, 'Ice Cream', 'Vanilla ice cream with toppings', 5.99, 0);

-- Variations
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
