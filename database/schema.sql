SET foreign_key_checks = 0;

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

SET foreign_key_checks = 1;