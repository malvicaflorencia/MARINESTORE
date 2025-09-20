<?php
require 'db/conexion.php';
require _DIR_ . '/vendor/autoload.php';

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Payment;

MercadoPagoConfig::setAccessToken('APP_USR-2466252085506128-070100-1cf527a612eab5ad44ba0915a3cb89f3-572888122');

// Leer la notificación JSON que MercadoPago envía
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['type']) || $data['type'] !== 'payment') {
    http_response_code(400);
    exit;
}

$payment_id = $data['data']['id'] ?? null;
if (!$payment_id) {
    http_response_code(400);
    exit;
}

// Obtener info del pago de MercadoPago
$payment = Payment::find_by_id($payment_id);

// Solo procesar si el pago está aprobado
if ($payment->status !== 'approved') {
    http_response_code(200); // 200 para evitar reintentos
    exit;
}

$external_reference = $payment->external_reference;
if (!$external_reference) {
    http_response_code(400);
    exit;
}

$tempFile = _DIR_ . "/temp/{$external_reference}.json";
if (!file_exists($tempFile)) {
    http_response_code(404);
    exit;
}

$tempData = json_decode(file_get_contents($tempFile), true);
$email = $tempData['email'] ?? '';
$carrito = $tempData['carrito'] ?? [];

if (!$email || empty($carrito)) {
    http_response_code(400);
    exit;
}

// Verificar si usuario ya existe o insertarlo
$stmt = $conexion->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    // Insertar nuevo usuario
    $stmtInsertUser = $conexion->prepare("INSERT INTO usuarios (email, fecha_registro) VALUES (?, NOW())");
    $stmtInsertUser->bind_param("s", $email);
    if (!$stmtInsertUser->execute()) {
        http_response_code(500);
        exit("Error al insertar usuario: " . $stmtInsertUser->error);
    }
    $usuario_id = $stmtInsertUser->insert_id;
    $stmtInsertUser->close();
} else {
    $stmt->bind_result($usuario_id);
    $stmt->fetch();
}
$stmt->close();

// Calcular subtotal
$subtotal = 0;
foreach ($carrito as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}

$total_pago = $payment->transaction_amount ?? $subtotal;

// Insertar la venta (ojo con el nombre de la columna, usa usuario_id)
$stmtVenta = $conexion->prepare("INSERT INTO ventas (usuario_id, fecha, subtotal, cupon, total, monto_final) VALUES (?, NOW(), ?, NULL, ?, NULL)");

if (!$stmtVenta) {
    http_response_code(500);
    exit("Error en prepare ventas: " . $conexion->error);
}

$stmtVenta->bind_param("idd", $usuario_id, $subtotal, $total_pago);

if (!$stmtVenta->execute()) {
    http_response_code(500);
    exit("Error al insertar en ventas: " . $stmtVenta->error);
}

$id_venta = $stmtVenta->insert_id;
$stmtVenta->close();

// Insertar detalle y descontar stock
$stmtDetalle = $conexion->prepare("INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
$stmtStock = $conexion->prepare("UPDATE productos SET stock = GREATEST(stock - ?, 0) WHERE id = ?");

foreach ($carrito as $producto) {
    $idProducto = $producto['producto_id'];
    $cantidad = $producto['cantidad'];
    $precio = $producto['precio_unitario'];
    $subtotalProducto = $cantidad * $precio;

    $stmtDetalle->bind_param("iiidd", $id_venta, $idProducto, $cantidad, $precio, $subtotalProducto);
    if (!$stmtDetalle->execute()) {
        http_response_code(500);
        exit("Error en detalle_ventas: " . $stmtDetalle->error);
    }

    $stmtStock->bind_param("ii", $cantidad, $idProducto);
    if (!$stmtStock->execute()) {
        http_response_code(500);
        exit("Error al descontar stock: " . $stmtStock->error);
    }
}

$stmtDetalle->close();
$stmtStock->close();

// NO borrar el archivo temporal acá para que pago_exitoso.php lo pueda leer
// unlink($tempFile);

// Responder 200 para indicar que se procesó correctamente
http_response_code(200);
exit;