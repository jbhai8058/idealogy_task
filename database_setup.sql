
CREATE DATABASE IF NOT EXISTS idealogy_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE idealogy_test;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super', 'admin') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    sku VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    category_id INT NULL,
    product_id INT NULL,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO admins (username, password, role) VALUES 
('super', '$2y$10$kXGCX7bgg9g/gtppZ9Suh.lFjfDevtsnJFwox7jP3EeUscxZjo/jq', 'super'),
('admin', '$2y$10$9IpReOwQAfo7UwiRI3e3TOYCXppTjs1R/7DqdJ7xwvkJzR84vJRS6', 'admin');

INSERT INTO categories (name, description) VALUES 
('Mobile', 'Mobile phones and accessories'),
('Cars', 'Automobiles and vehicles');

INSERT INTO products (category_id, name, sku, description, price, image) VALUES 
(1, 'Samsung', 'MOB-SAM-001', 'Samsung flagship smartphone', 999.99, 'samsung.jpg'),
(1, 'iPhone', 'MOB-IPH-001', 'Apple iPhone latest model', 1299.99, 'iphone.jpg'),
(2, 'BMW', 'CAR-BMW-001', 'BMW luxury sedan', 45000.00, 'bmw.jpg'),
(2, 'Audi', 'CAR-AUD-001', 'Audi premium vehicle', 42000.00, 'audi.jpg');

