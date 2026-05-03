<?php

require __DIR__ . '/../../vendor/autoload.php';

use Core\Database\Database;
use Core\Env\EnvLoader;

EnvLoader::init();

$pdo = Database::getDatabaseConn();
$senha = password_hash('password123', PASSWORD_DEFAULT);
$adminSenha = password_hash('admin123', PASSWORD_DEFAULT);

$pdo->exec("DELETE FROM users");

$pdo->exec("INSERT INTO users (name, email, encrypted_password, birth_date, user_type, is_admin) VALUES
    ('Administrador', 'admin@zarpa.com', '$adminSenha', null, 'client', 1),
    ('Joao Paulo', 'joao@zarpa.com', '$senha', '1998-05-15', 'client', 0),
    ('Leonardo Tosin', 'leonardo@zarpa.com', '$senha', '2000-03-22', 'deliverer', 0),
    ('William Veiga', 'william@zarpa.com', '$senha', '1995-11-08', 'deliverer', 0)
");

echo "Banco populado!\n";
