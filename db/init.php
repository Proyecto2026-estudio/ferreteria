<?php
/**
 * Inicialización automática de la base (usado en Railway / Docker).
 * Si la tabla `productos` no existe, importa db/schema.sql en la base configurada.
 * Si ya existe, no toca nada (no borra datos en cada deploy).
 * Uso: php db/init.php
 */
require_once __DIR__ . '/../env.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/**
 * Bases creadas con la versión anterior usaban fotos de loremflickr (poco confiables).
 * Se reemplazan por las fotos locales de public/img/ (y los dibujos .svg por las fotos .jpg).
 */
function actualizarImagenes(mysqli $db): void {
    $imagenes = [
    'Cemento Portland' => 'cemento',
    'Arena gruesa' => 'arena',
    'Cal hidratada' => 'cal',
    'Hierro Ø8mm x 12m' => 'hierro',
    'Hierro Ø10mm x 12m' => 'hierro',
    'Malla soldada 15x15' => 'malla',
    'Ladrillo hueco 8x18x33' => 'ladrillo',
    'Bloque de hormigón 20x20x40' => 'bloque',
    'Látex interior premium' => 'latex',
    'Antióxido convertidor' => 'antioxido',
    'Tornillo autoperforante' => 'tornillo',
    'Taco fischer S8' => 'taco',
    'Caño PVC 110mm x4m' => 'cano',
    'Membrana asfáltica' => 'membrana',
    ];
    // Solo reemplaza imágenes viejas (loremflickr o el dibujo .svg); lo cargado desde el admin no se toca
    $stmt = $db->prepare("UPDATE productos SET imagen_url = ? WHERE nombre = ? AND (imagen_url LIKE '%loremflickr%' OR imagen_url = ?)");
    $total = 0;
    foreach ($imagenes as $nombre => $img) {
        $url = "public/img/{$img}.jpg"; // fotos reales
        $dibujoViejo = "public/img/{$img}.svg";
        $stmt->bind_param("sss", $url, $nombre, $dibujoViejo);
        $stmt->execute();
        $total += $stmt->affected_rows;
    }
    if ($total > 0) echo "[init-db] Imágenes actualizadas: {$total} productos.\n";
}

/**
 * Actualiza los precios del catálogo original a valores de mercado (septiembre 2026).
 * Solo cambia productos que todavía tienen el precio viejo, para no pisar lo editado en el admin.
 */
function actualizarPrecios(mysqli $db): void {
    $precios = [
        // [nombre, minorista viejo, mayorista viejo, minorista nuevo, mayorista nuevo, unidad nueva]
        ['Cemento Portland', 9800, 8200, 9800, 8500, null],
        ['Arena gruesa', 45000, 38000, 45000, 39000, null],
        ['Cal hidratada', 4200, 3500, 7900, 6800, null],
        ['Hierro Ø8mm x 12m', 12500, 10800, 12300, 10900, null],
        ['Hierro Ø10mm x 12m', 18200, 15600, 19000, 16800, null],
        ['Malla soldada 15x15', 31000, 27000, 72700, 64000, 'panel 6x2.15m'],
        ['Ladrillo hueco 8x18x33', 350, 280, 760, 620, null],
        ['Bloque de hormigón 20x20x40', 890, 740, 1300, 1100, null],
        ['Látex interior premium', 68000, 57000, 46000, 40000, null],
        ['Antióxido convertidor', 8900, 7400, 24000, 21000, null],
        ['Tornillo autoperforante', 4200, 3400, 9200, 8000, null],
        ['Taco fischer S8', 3100, 2500, 5950, 5100, 'caja x100'],
        ['Caño PVC 110mm x4m', 15800, 13200, 25400, 22000, null],
        ['Membrana asfáltica', 42000, 36500, 74500, 65000, null],
    ];
    $stmt = $db->prepare("UPDATE productos SET precio = ?, precio_mayorista = ?, unidad = COALESCE(?, unidad) WHERE nombre = ? AND precio = ? AND precio_mayorista = ?");
    $total = 0;
    foreach ($precios as [$nombre, $viejoMin, $viejoMay, $nuevoMin, $nuevoMay, $unidad]) {
        $stmt->bind_param("ddssdd", $nuevoMin, $nuevoMay, $unidad, $nombre, $viejoMin, $viejoMay);
        $stmt->execute();
        $total += $stmt->affected_rows;
    }
    if ($total > 0) echo "[init-db] Precios actualizados: {$total} productos.\n";
}

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
    actualizarImagenes($db);
    actualizarPrecios($db);
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
