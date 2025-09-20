<?php
// PREVENIR salida antes de JSON
ob_start(); // Captura cualquier salida inesperada

session_start();
header('Content-Type: application/json');
include 'db/conexion.php';

// Validar campos requeridos
if (!isset($_POST['id'], $_POST['nombre'], $_POST['precio'], $_POST['imagenes'], $_POST['talle'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'mensaje' => '❌ Datos incompletos']);
    exit;
}

$id = intval($_POST['id']);
$nombre = trim($_POST['nombre']);
$precio = floatval($_POST['precio']);
$imagenes = trim($_POST['imagenes']);
$talle = trim($_POST['talle']);

// Verificar conexión
if (!$conexion) {
    ob_end_clean();
    echo json_encode(['success' => false, 'mensaje' => '❌ Error de conexión a la base de datos']);
    exit;
}

// Consultar stock real
$stmt = $conexion->prepare("SELECT cantidad FROM stocks WHERE producto_id = ? AND talle = ?");
$stmt->bind_param("is", $id, $talle);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    ob_end_clean();
    echo json_encode(['success' => false, 'mensaje' => "❌ El talle '$talle' no existe para este producto"]);
    exit;
}

$stockDisponible = intval($result->fetch_assoc()['cantidad']);

// Iniciar carrito
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$clave = "{$id}_{$talle}";

// Agregar si hay stock
if (!isset($_SESSION['carrito'][$clave])) {
    $_SESSION['carrito'][$clave] = [
        'id' => $id,
        'nombre' => $nombre,
        'precio' => $precio,
        'imagenes' => $imagenes,
        'talle' => $talle,
        'cantidad' => 0
    ];
}

if ($_SESSION['carrito'][$clave]['cantidad'] < $stockDisponible) {
    $_SESSION['carrito'][$clave]['cantidad']++;

    $cantidad_total = array_sum(array_column($_SESSION['carrito'], 'cantidad'));

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'nombre' => $nombre,
        'talle' => $talle,
        'cantidad_total' => $cantidad_total
    ]);
} else {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'nombre' => $nombre,
        'talle' => $talle,
        'mensaje' => "⚠ No hay más stock del talle '$talle' para '$nombre'"
    ]);
}
