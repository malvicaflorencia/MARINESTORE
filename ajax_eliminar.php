<?php
session_start();
header('Content-Type: application/json');

if (!isset($_POST['id'], $_POST['talle'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
    exit;
}

$id = $_POST['id'];
$talle = $_POST['talle'];

$clave = $id . "_" . $talle;

// Verificar que exista el carrito y el producto con talle
if (isset($_SESSION['carrito'][$clave])) {
    if ($_SESSION['carrito'][$clave]['cantidad'] > 0) {
        $_SESSION['carrito'][$clave]['cantidad']--;

        // Si la cantidad llega a 0, lo quitamos del carrito
        if ($_SESSION['carrito'][$clave]['cantidad'] == 0) {
            unset($_SESSION['carrito'][$clave]);
        }

        $cantidad_total = array_sum(array_column($_SESSION['carrito'], 'cantidad'));

        echo json_encode([
            'success' => true,
            'cantidad_total' => $cantidad_total
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Cantidad ya es cero'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Producto no está en el carrito'
    ]);
}
?>
