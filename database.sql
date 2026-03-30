-- ============================================
-- LUXE STORE - Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS luxe_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE luxe_store;

-- ============================================
-- TABLE: users
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    avatar VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB;

-- ============================================
-- TABLE: categories
-- ============================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABLE: products (related to categories via FK)
-- ============================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image VARCHAR(255) DEFAULT NULL,
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- TABLE: orders (related to users via FK)
-- ============================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    shipping_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- TABLE: order_items (related to orders and products)
-- ============================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- SEED DATA
-- ============================================

-- Admin user (password: Admin@123)
INSERT INTO users (username, email, password_hash, full_name, role) VALUES
('admin', 'admin@luxestore.com', '$2y$12$KGByDzHvh0/JhYhDIEf4NeqMQA/MRMWl.M5RKWijBbDIeG/YDVeNq', 'Store Administrator', 'admin');

-- Demo customer (password: User@123)
INSERT INTO users (username, email, password_hash, full_name, role) VALUES
('johndoe', 'john@example.com', '$2y$12$OvVv/A7Mw8ZxzWfUj8a6.u5rATc5JFbEJ3mKvqMfBvv7NUhIK3Mz6', 'John Doe', 'customer');

-- Categories
INSERT INTO categories (name, slug, description) VALUES
('Electronics', 'electronics', 'Cutting-edge gadgets and devices'),
('Fashion', 'fashion', 'Premium clothing and accessories'),
('Home & Living', 'home-living', 'Elegant home decor and furniture'),
('Beauty', 'beauty', 'Luxury skincare and cosmetics');

-- Products
-- (No sample products - add via admin panel)

-- Sample orders
-- (No sample orders)

-- ============================================
-- HELPFUL VIEWS
-- ============================================
CREATE OR REPLACE VIEW v_product_details AS
SELECT 
    p.id, p.name, p.slug, p.description, p.price, p.stock,
    p.is_featured, p.is_active, p.created_at,
    c.name AS category_name, c.slug AS category_slug
FROM products p
JOIN categories c ON p.category_id = c.id;

CREATE OR REPLACE VIEW v_order_summary AS
SELECT 
    o.id AS order_id, o.total_amount, o.status, o.created_at,
    u.username, u.email, u.full_name,
    COUNT(oi.id) AS item_count
FROM orders o
JOIN users u ON o.user_id = u.id
JOIN order_items oi ON o.id = oi.order_id
GROUP BY o.id;
