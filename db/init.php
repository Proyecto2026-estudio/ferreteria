<?php
/**
 * Inicialización automática de la base (usado en Railway / Docker).
 * Si la tabla `productos` no existe, importa db/schema.sql en la base configurada.
 * Si ya existe, no toca nada (no borra datos en cada deploy).
 * Uso: php db/init.php
 */
require_once __DIR__ . '/../env.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db = null;
for ($intento = 1; $intento <= 20; $intento++) {
    try {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
        break;
    } catch (mysqli_sql_exception $e) {
        fwrite(STDERR, "[init-db] Esperando MySQL ({$intento}/20): {$e->getMessage()}\n");
        sleep(3);
    }
}
if (!$db) {
    fwrite(STDERR, "[init-db] No se pudo conectar a MySQL. La app arranca igual.\n");
    exit(0);
}
$db->set_charset('utf8mb4');

$existe = $db->query("SHOW TABLES LIKE 'productos'")->num_rows > 0;
if ($existe) {
    echo "[init-db] La base ya tiene tablas. No se importa nada.\n";
    exit(0);
}

$sql = file_get_contents(__DIR__ . '/schema.sql');
// En Railway la base ya existe (se llama "railway"), así que se quitan CREATE DATABASE y USE
$sql = preg_replace('/^\s*(CREATE DATABASE|USE)\b[^;]*;/mi', '', $sql);

try {
    $db->multi_query($sql);
    do {
        if ($r = $db->store_result()) $r->free();
    } while ($db->more_results() && $db->next_result());
    echo "[init-db] Base importada desde db/schema.sql en '" . DB_NAME . "'.\n";
} catch (mysqli_sql_exception $e) {
    fwrite(STDERR, "[init-db] Error importando schema.sql: {$e->getMessage()}\n");
}
