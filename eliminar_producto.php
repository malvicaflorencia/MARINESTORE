<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $talle = $_POST['talle'] ?? null;

    if ($id && $talle && isset($_SESSION['carrito'])) {
        $clave = $id . '_' . $talle;
        if (isset($_SESSION['carrito'][$clave])) {
            unset($_SESSION['carrito'][$clave]);
        }
    }
}

header('Location: carrito.php');
exit;
?>
