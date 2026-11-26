<?php
/**
 * NourArt - registration handler.
 *
 * The static site works on its own; this file is the server side that the
 * original XAMPP version used. Drop the repository into htdocs\NourArt, create
 * the database with the SQL below, and the register form works.
 *
 * Design notes:
 *   - passwords are hashed with password_hash(), never stored in plain text
 *   - all queries are prepared statements, so form input cannot become SQL
 *   - the output is escaped before it goes back into the page
 *   - the database credentials live in config.php, which is gitignored
 *
 * CREATE DATABASE IF NOT EXISTS nourart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
 * USE nourart;
 * CREATE TABLE IF NOT EXISTS users (
 *     id          INT AUTO_INCREMENT PRIMARY KEY,
 *     name        VARCHAR(100) NOT NULL,
 *     email       VARCHAR(190) NOT NULL UNIQUE,
 *     password    VARCHAR(255) NOT NULL,
 *     created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 */

declare(strict_types=1);

// Keep the credentials out of the repository.
$configPath = __DIR__ . '/config.php';
$config = is_file($configPath) ? require $configPath : [
    'host' => 'localhost',
    'name' => 'nourart',
    'user' => 'root',
    'pass' => '',
];

function fail(string $message): void
{
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8">'
        . '<title>Kayıt Hatası - NourArt</title>'
        . '<link rel="stylesheet" href="NourArtCSS/styles.css"></head><body>'
        . '<main><section class="login-container">'
        . '<h1>Kayıt Yapılamadı</h1>'
        . '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p><a href="kayit.html">Geri dön</a></p>'
        . '</section></main></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: kayit.html');
    exit;
}

$name     = trim((string) ($_POST['name'] ?? ''));
$email    = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$confirm  = (string) ($_POST['confirm-password'] ?? '');

if ($name === '' || $email === '' || $password === '') {
    fail('Lütfen tüm alanları doldurun.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('Geçerli bir e-posta adresi girin.');
}

if (strlen($password) < 8) {
    fail('Şifre en az 8 karakter olmalı.');
}

if ($password !== $confirm) {
    fail('Şifreler birbiriyle eşleşmiyor.');
}

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['name']);

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    fail('Veritabanına bağlanılamadı. config.php ayarlarını kontrol edin.');
}

// Is the address already taken?
$check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$check->execute([$email]);

if ($check->fetch()) {
    fail('Bu e-posta adresi zaten kayıtlı.');
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$insert = $pdo->prepare(
    'INSERT INTO users (name, email, password) VALUES (?, ?, ?)'
);

try {
    $insert->execute([$name, $email, $hash]);
} catch (PDOException $e) {
    // 23000 is the integrity constraint code, which here means the unique
    // email index lost a race with a concurrent request.
    if ($e->getCode() === '23000') {
        fail('Bu e-posta adresi zaten kayıtlı.');
    }
    fail('Kayıt sırasında bir hata oluştu.');
}

header('Location: welcome.html?name=' . rawurlencode($name));
exit;
