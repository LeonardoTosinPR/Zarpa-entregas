SET foreign_key_checks = 0;

DROP TABLE IF EXISTS order_tags;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    encrypted_password VARCHAR(255) NOT NULL,
    birth_date DATE,
    cpf VARCHAR(14),
    avatar_name VARCHAR(65),
    user_type ENUM('client', 'deliverer') NOT NULL DEFAULT 'client',
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_cpf (cpf)
) ENGINE=InnoDB;

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    courier_id INT NULL,
    pickup_address TEXT NOT NULL,
    delivery_address TEXT NOT NULL,
    package_size ENUM('pequeno', 'medio', 'grande') NOT NULL DEFAULT 'pequeno',
    is_fragile TINYINT(1) NOT NULL DEFAULT 0,
    distance_km DECIMAL(8, 2) NOT NULL DEFAULT 0.00,
    status ENUM('pendente', 'aceito', 'cancelado', 'em rota', 'entregue') NOT NULL DEFAULT 'pendente',
    payment_method ENUM('dinheiro', 'pix', 'cartao') NOT NULL DEFAULT 'pix',
    shipping_fee DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    confirmation_code VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_client FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_orders_courier FOREIGN KEY (courier_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE order_delivery_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    file_name VARCHAR(120) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(30) NOT NULL,
    size_bytes INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_delivery_photos_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(40) NOT NULL,
    color ENUM('secondary', 'primary', 'success', 'danger', 'warning', 'info', 'dark') NOT NULL DEFAULT 'secondary',
    UNIQUE KEY uq_tags_name (name)
) ENGINE=InnoDB;

CREATE TABLE order_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    tag_id INT NOT NULL,
    UNIQUE KEY uq_order_tags (order_id, tag_id),
    CONSTRAINT fk_order_tags_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_tags_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

SET foreign_key_checks = 1;
