<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../env.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido. Utiliza POST.']);
    exit;
}

// Validación de CUIT argentino (formato + dígito verificador), igual a la del cotizador
function isValidCuit(string $raw): bool {
    $digits = preg_replace('/\D/', '', $raw);
    if (strlen($digits) !== 11) {
        return false;
    }
    $mult = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
    $sum = 0;
    for ($i = 0; $i < 10; $i++) {
        $sum += (int)$digits[$i] * $mult[$i];
    }
    $check = 11 - ($sum % 11);
    if ($check === 11) $check = 0;
    if ($check === 10) return false;
    return $check === (int)$digits[10];
}

try {
    // Obtener y decodificar el cuerpo de la petición JSON
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (empty($data['items']) || !is_array($data['items'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'El carrito está vacío o el formato es inválido.']);
        exit;
    }

    // Modalidad de precio: minorista (retail) o mayorista (wholesale)
    $mode = ($data['mode'] ?? 'retail') === 'wholesale' ? 'wholesale' : 'retail';

    // Tipo de comprobante: A (discrimina IVA, requiere CUIT) o B (consumidor final)
    $invoiceType = ($data['invoice_type'] ?? 'B') === 'A' ? 'A' : 'B';
    $cuit = preg_replace('/\D/', '', (string)($data['cuit'] ?? ''));
    $razonSocial = trim((string)($data['razon_social'] ?? ''));

    if ($invoiceType === 'A' && !isValidCuit($cuit)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'CUIT inválido. La Factura A requiere un CUIT válido.']);
        exit;
    }
    if ($invoiceType === 'A' && mb_strlen($razonSocial) < 3) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'La Factura A requiere la Razón Social del cliente.']);
        exit;
    }
    // CUIT con formato 20-12345678-9 para guardarlo legible
    $cuitFormateado = $invoiceType === 'A'
        ? substr($cuit, 0, 2) . '-' . substr($cuit, 2, 8) . '-' . substr($cuit, 10, 1)
        : null;
    $razonSocialParam = $invoiceType === 'A' ? mb_substr($razonSocial, 0, 150) : null;
    $modalidad = $mode === 'wholesale' ? 'mayorista' : 'minorista';

    $db = Database::getConnection();
    $db->begin_transaction();

    $orderItems = [];
    $mpItems = [];
    $montoNeto = 0.0; // suma de subtotales (precios con IVA incluido)

    $stmtProduct = $db->prepare("SELECT id, nombre, descripcion, precio, precio_mayorista, stock FROM productos WHERE id = ? FOR UPDATE");

    // Validar productos y calcular precios en servidor desde MySQLi
    foreach ($data['items'] as $item) {
        $productId = (int)($item['id'] ?? 0);
        $qty = (int)($item['quantity'] ?? 0);

        if ($productId <= 0 || $qty <= 0) {
            continue;
        }

        $stmtProduct->bind_param("i", $productId);
        $stmtProduct->execute();
        $res = $stmtProduct->get_result();
        $product = $res->fetch_assoc();

        if (!$product) {
            throw new Exception("El producto ID {$productId} no fue encontrado.");
        }

        if ((int)$product['stock'] < $qty) {
            throw new Exception("Stock insuficiente para '{$product['nombre']}'. Disponible: {$product['stock']}.");
        }

        // El precio se elige según la modalidad, siempre calculado en el servidor
        // (si un producto no tiene precio mayorista cargado, se usa el minorista)
        $precioMayorista = (float)$product['precio_mayorista'];
        $precioUnitario = ($mode === 'wholesale' && $precioMayorista > 0) ? $precioMayorista : (float)$product['precio'];
        $subtotal = $precioUnitario * $qty;
        $montoNeto += $subtotal;

        $orderItems[] = [
            'producto_id' => (int)$product['id'],
            'cantidad' => $qty,
            'precio_unitario' => $precioUnitario
        ];

        $mpItems[] = [
            'id' => (string)$product['id'],
            'title' => (string)$product['nombre'],
            'description' => substr((string)($product['descripcion'] ?? $product['nombre']), 0, 255),
            'quantity' => $qty,
            'currency_id' => 'ARS',
            'unit_price' => $precioUnitario
        ];
    }

    if (empty($mpItems)) {
        throw new Exception("No hay productos válidos en la solicitud.");
    }

    // Los precios de lista ya incluyen IVA (21%). El total a pagar es el mismo
    // para Factura A y B: en la Factura A solo se DISCRIMINA el IVA contenido.
    $montoTotal = round($montoNeto, 2);
    $netoGravado = round($montoTotal / 1.21, 2);
    $ivaMonto = $invoiceType === 'A' ? round($montoTotal - $netoGravado, 2) : 0.0;

    // Generar número de referencia externa único para la orden
    $externalReference = 'FERR-' . time() . '-' . strtoupper(bin2hex(random_bytes(3)));

    // 1. Insertar Orden principal en MySQL (Estado: 'pending')
    $stmtOrder = $db->prepare("
        INSERT INTO ordenes (external_reference, monto_total, estado, modalidad, tipo_factura, cuit_cliente, razon_social, iva_monto, created_at)
        VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, NOW())
    ");
    $stmtOrder->bind_param("sdssssd", $externalReference, $montoTotal, $modalidad, $invoiceType, $cuitFormateado, $razonSocialParam, $ivaMonto);
    $stmtOrder->execute();
    $orderId = $db->insert_id;

    // 2. Insertar Detalle de la Orden
    $stmtItem = $db->prepare("
        INSERT INTO orden_items (orden_id, producto_id, cantidad, precio_unitario)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($orderItems as $oItem) {
        $stmtItem->bind_param("iiid", $orderId, $oItem['producto_id'], $oItem['cantidad'], $oItem['precio_unitario']);
        $stmtItem->execute();
    }

    // 3. MODO DEMO: se aprueba la orden sin pasar por Mercado Pago (no se cobra nada)
    if (!empty($data['demo'])) {
        if (!DEMO_PAYMENTS) {
            throw new Exception('El pago de demostración está deshabilitado en este servidor.');
        }
        $demoPaymentId = 'DEMO-' . strtoupper(bin2hex(random_bytes(4)));
        $stmtApprove = $db->prepare("UPDATE ordenes SET estado = 'approved', mp_payment_id = ? WHERE id = ?");
        $stmtApprove->bind_param("si", $demoPaymentId, $orderId);
        $stmtApprove->execute();

        // Igual que el webhook: al aprobarse la orden se descuenta el stock
        $stmtStock = $db->prepare("UPDATE productos SET stock = GREATEST(0, stock - ?) WHERE id = ?");
        foreach ($orderItems as $oItem) {
            $stmtStock->bind_param("ii", $oItem['cantidad'], $oItem['producto_id']);
            $stmtStock->execute();
        }
        $db->commit();

        $query = http_build_query([
            'status' => 'approved',
            'payment_id' => $demoPaymentId,
            'external_reference' => $externalReference,
            'demo' => 1
        ]);
        echo json_encode([
            'status' => 'success',
            'demo' => true,
            'order_id' => $orderId,
            'external_reference' => $externalReference,
            'monto_total' => $montoTotal,
            'iva_monto' => $ivaMonto,
            'init_point' => 'public/success.php?' . $query
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $db->commit();

    // 4. Crear Preferencia de Pago usando la API REST de Mercado Pago
    $baseUrl = rtrim(trim(BASE_URL), '/');
    if (!preg_match('/^https?:\/\//i', $baseUrl)) {
        $baseUrl = 'http://' . $baseUrl;
    }

    $mpPayload = [
        'items' => $mpItems,
        'back_urls' => [
            'success' => $baseUrl . '/public/success.php',
            'pending' => $baseUrl . '/public/pending.php',
            'failure' => $baseUrl . '/public/failure.php'
        ],
        'external_reference' => $externalReference,
        'statement_descriptor' => 'FERRETERIA EQUIPO 8'
    ];

    // En dominios públicos HTTPS activamos auto_return
    $host = parse_url($baseUrl, PHP_URL_HOST) ?? '';
    $scheme = strtolower(parse_url($baseUrl, PHP_URL_SCHEME) ?? 'http');
    if ($scheme === 'https' && !preg_match('/(localhost|127\.0\.0\.1)/i', $host)) {
        $mpPayload['auto_return'] = 'approved';
    }

    // Omitir notification_url si se ejecuta en localhost
    if (!preg_match('/(localhost|127\.0\.0\.1)/i', $host)) {
        $mpPayload['notification_url'] = $baseUrl . '/api/webhook.php';
    }

    // Realizar llamada cURL a la API oficial de Mercado Pago
    $ch = curl_init('https://api.mercadopago.com/checkout/preferences');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($mpPayload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . MP_ACCESS_TOKEN
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        throw new Exception("Error al comunicarse con Mercado Pago: " . $curlErr);
    }

    $responseData = json_decode($response, true);

    if ($httpCode !== 200 && $httpCode !== 201) {
        $msg = $responseData['message'] ?? 'Error al generar la preferencia de Mercado Pago';
        $causes = [];
        if (!empty($responseData['cause']) && is_array($responseData['cause'])) {
            foreach ($responseData['cause'] as $c) {
                $causes[] = ($c['code'] ?? '') . ': ' . ($c['description'] ?? '');
            }
        }
        $causeDetail = !empty($causes) ? ' Detalle: [' . implode(', ', $causes) . ']' : '';
        throw new Exception("Mercado Pago API (Error {$httpCode}): {$msg}.{$causeDetail}");
    }

    // Retornar punto de inicio (init_point / sandbox_init_point)
    $initPoint = $responseData['init_point'] ?? '';
    $sandboxInitPoint = $responseData['sandbox_init_point'] ?? $initPoint;

    echo json_encode([
        'status' => 'success',
        'order_id' => $orderId,
        'external_reference' => $externalReference,
        'preference_id' => $responseData['id'] ?? '',
        'neto_gravado' => $netoGravado,
        'iva_monto' => $ivaMonto,
        'monto_total' => $montoTotal,
        'init_point' => $initPoint,
        'sandbox_init_point' => $sandboxInitPoint
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($db) && $db->connect_errno === 0) {
        @$db->rollback();
    }
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
