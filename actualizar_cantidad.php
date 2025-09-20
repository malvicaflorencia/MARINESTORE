<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['accion'])) {
    $id = $_POST['id'];
    $accion = $_POST['accion'];

    if (isset($_SESSION['carrito'][$id])) {
        if ($accion === 'sumar') {
            if (!isset($_SESSION['carrito'][$id]['stock']) || $_SESSION['carrito'][$id]['cantidad'] < $_SESSION['carrito'][$id]['stock']) {
                $_SESSION['carrito'][$id]['cantidad'] += 1;
            }
        } elseif ($accion === 'restar') {
            $_SESSION['carrito'][$id]['cantidad'] -= 1;

            // Si la cantidad llega a 0 o menos, elimino el producto
            if ($_SESSION['carrito'][$id]['cantidad'] <= 0) {
                unset($_SESSION['carrito'][$id]);
            }
        }
    }
}

header('Location: carrito.php');
exit;
?>
