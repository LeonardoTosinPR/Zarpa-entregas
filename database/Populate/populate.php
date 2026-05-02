<?php

require __DIR__ . '/../../config/bootstrap.php';

use Core\Database\Database;

$pdo = Database::getDatabaseConn();
$senha = password_hash('password123', PASSWORD_DEFAULT);

$pdo->exec("DELETE FROM users");

$pdo->exec("INSERT INTO users (name, email, encrypted_password, birth_date) VALUES
    ('João Paulo', 'joao@zarpa.com', '$senha', '1998-05-15'),
    ('Leonardo Tosin', 'leonardo@zarpa.com', '$senha', '2000-03-22'),
    ('William Veiga', 'william@zarpa.com', '$senha', '1995-11-08')
");

echo "Banco populado!\n";
