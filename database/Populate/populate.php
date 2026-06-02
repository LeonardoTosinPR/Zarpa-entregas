<?php

require __DIR__ . '/../../vendor/autoload.php';

use Core\Database\Database;
use Core\Env\EnvLoader;

EnvLoader::init();

$pdo = Database::getDatabaseConn();
$senha = password_hash('password123', PASSWORD_DEFAULT);
$adminSenha = password_hash('admin123', PASSWORD_DEFAULT);

if ($pdo->query("SHOW TABLES LIKE 'orders'")->fetchColumn()) {
    if ($pdo->query("SHOW TABLES LIKE 'order_delivery_photos'")->fetchColumn()) {
        $pdo->exec("DELETE FROM order_delivery_photos");
    }
    $pdo->exec("DELETE FROM orders");
}
if ($pdo->query("SHOW TABLES LIKE 'notifications'")->fetchColumn()) {
    $pdo->exec("DELETE FROM notifications");
}
$pdo->exec("DELETE FROM users");

$pdo->exec("INSERT INTO users (name, email, encrypted_password, birth_date, cpf, user_type, is_admin) VALUES
    ('Administrador', 'admin@zarpa.com', '$adminSenha', null, '39053344705', 'client', 1),
    ('Joao Paulo', 'joao@zarpa.com', '$senha', '1998-05-15', '52998224725', 'client', 0),
    ('Leonardo Tosin', 'leonardo@zarpa.com', '$senha', '2000-03-22', '15350946056', 'deliverer', 0),
    ('William Veiga', 'william@zarpa.com', '$senha', '1995-11-08', '11144477735', 'deliverer', 0)
");

$adminId = $pdo->query("SELECT id FROM users WHERE email = 'admin@zarpa.com'")->fetchColumn();
$joaoId = $pdo->query("SELECT id FROM users WHERE email = 'joao@zarpa.com'")->fetchColumn();
$leonardoId = $pdo->query("SELECT id FROM users WHERE email = 'leonardo@zarpa.com'")->fetchColumn();
$williamId = $pdo->query("SELECT id FROM users WHERE email = 'william@zarpa.com'")->fetchColumn();

$pdo->exec("INSERT INTO orders (client_id, courier_id, pickup_address, delivery_address, package_size, is_fragile, distance_km, status, payment_method, shipping_fee, confirmation_code) VALUES
    ($joaoId, null, 'Rua das Palmeiras, 120 - Centro', 'Avenida Brasil, 900 - Vila Nova', 'pequeno', 0, 12.00, 'pendente', 'pix', 11.20, 'ZP1001'),
    ($joaoId, $leonardoId, 'Mercado Municipal - Box 14', 'Rua Sete de Setembro, 45 - Centro', 'medio', 1, 8.00, 'aceito', 'cartao', 20.80, 'ZP1002'),
    ($adminId, $williamId, 'Rua Projetada, 30 - Jardim Sul', 'Condominio Primavera, Bloco B', 'grande', 0, 15.00, 'em rota', 'dinheiro', 21.50, 'ZP1003')
");

echo "Banco populado!\n";
