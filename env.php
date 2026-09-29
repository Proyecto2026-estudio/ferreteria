<?php
/**
 * Configuración de entorno y credenciales para la Ferretería (Equipo 8).
 * Carga automática de variables desde el archivo .env mediante vlucas/phpdotenv (Composer).
 */
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('Dotenv\Dotenv')) {
        // Carga segura sin lanzar excepciones si no existe el archivo .env
        $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
        $dotenv->safeLoad();
    }
}
// Función helper para obtener variables de entorno
function getEnvVal(string $key, string $default = ''): string {
    $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return ($val !== false && $val !== null && $val !== '') ? (string)$val : $default;
}
// Base de Datos MySQL
// - XAMPP: usa DB_HOST, DB_USER... (por defecto host=127.0.0.1, user=root, pass="")
// - Railway: si no hay DB_*, toma las variables del plugin MySQL (MYSQLHOST, MYSQLUSER...)
define('DB_HOST', getEnvVal('DB_HOST', getEnvVal('MYSQLHOST', '127.0.0.1')));
define('DB_PORT', getEnvVal('DB_PORT', getEnvVal('MYSQLPORT', '3306')));
define('DB_NAME', getEnvVal('DB_NAME', getEnvVal('MYSQLDATABASE', 'kiosco_online')));
define('DB_USER', getEnvVal('DB_USER', getEnvVal('MYSQLUSER', 'root')));
define('DB_PASS', getEnvVal('DB_PASS', getEnvVal('MYSQLPASSWORD', '')));
// Mercado Pago Credentials (credenciales de PRUEBA — Equipo 8, Ferretería)
define('MP_ACCESS_TOKEN', getEnvVal('MP_ACCESS_TOKEN', 'TEST-970333950949076-081815-5cc9bd5ca9af7fa2858c687052fa3f69-2177710951'));
define('MP_PUBLIC_KEY', getEnvVal('MP_PUBLIC_KEY', 'TEST-1601638d-e62e-4eb6-9008-7239992d8df8'));
// Modo demo: permite "pagar" sin Mercado Pago para presentaciones.
// Solo se habilita con credenciales de PRUEBA (TEST-...) o si DEMO_PAYMENTS=1.
define('DEMO_PAYMENTS', getEnvVal('DEMO_PAYMENTS', str_starts_with(MP_ACCESS_TOKEN, 'TEST-') ? '1' : '0') === '1');
// URL Base del proyecto en XAMPP (sanitizada sin comillas ni barras al final)
$rawBaseUrl = getEnvVal('BASE_URL');
// En Railway, si no se definió BASE_URL, se usa el dominio público que asigna Railway (siempre HTTPS)
if (!$rawBaseUrl && getEnvVal('RAILWAY_PUBLIC_DOMAIN')) {
    $rawBaseUrl = 'https://' . getEnvVal('RAILWAY_PUBLIC_DOMAIN');
}
if (!$rawBaseUrl) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $rawBaseUrl = $protocol . "://" . $host . "/2026/Antigravity";
}
$cleanBaseUrl = trim($rawBaseUrl, "\"' \t\n\r\0\x0B/");
if (!preg_match('/^https?:\/\//i', $cleanBaseUrl)) {
    $cleanBaseUrl = 'http://' . $cleanBaseUrl;
}
define('BASE_URL', $cleanBaseUrl);

