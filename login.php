<?php
/**
 * NourArt - login handler.
 *
 * Companion to register.php: same connection setup, prepared statements,
 * password_verify() against the stored hash.
 */

declare(strict_types=1);

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
        . '<title>Giriş Hatası - NourArt</title>'
        . '<link rel="stylesheet" href="NourArtCSS/styles.css"></head><body>'
        . '<main><section class="login-container">'
        . '<h1>Giriş Yapılamadı</h1>'
        . '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p><a href="giris.html">Geri dön</a></p>'
        . '</section></main></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: giris.html');
    exit;
}

$email    = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    fail('Lütfen e-posta ve şifrenizi girin.');
}

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['name']);

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    fail('Veritabanına bağlanılamadı. config.php ayarlarını kontrol edin.');
}

$stmt = $pdo->prepare('SELECT name, password FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Same message whether the address is unknown or the password is wrong, so the
// form does not reveal which addresses are registered.
if (!$user || !password_verify($password, $user['password'])) {
    fail('E-posta veya şifre hatalı.');
}

header('Location: welcome.html?name=' . rawurlencode($user['name']));
exit;
