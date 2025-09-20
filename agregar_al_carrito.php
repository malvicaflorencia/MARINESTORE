<?php
session_start();

// Verificamos si se recibieron los datos necesarios por GET
if (isset($_GET['id'], $_GET['nombre'], $_GET['precio'], $_GET['imagen'], $_GET['stock'])) {
    $id = $_GET['id'];
    $nombre = $_GET['nombre'];
    $precio = $_GET['precio'];
    $imagen = $_GET['imagen'];
    $stock = (int) $_GET['stock'];

    // Inicializamos el carrito si no existe
    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }

    // Si el producto ya está en el carrito
    if (isset($_SESSION['carrito'][$id])) {
        // Aumentar cantidad si no supera el stock
        if ($_SESSION['carrito'][$id]['cantidad'] < $stock) {
            $_SESSION['carrito'][$id]['cantidad'] += 1;
        }
    } else {
        // Agregar nuevo producto
        $_SESSION['carrito'][$id] = [
            'id' => $id,
            'nombre' => $nombre,
            'precio' => $precio,
            'imagen' => $imagen,
            'cantidad' => 1,
            'stock' => $stock,
            'categoria' => $producto['categoria']
        ];
    }

    // Redirigir con mensaje de éxito
    header("Location: carrito.php?mensaje=agregado");
    exit;
} else {
    // Si faltan datos, redirigir sin mensaje
    header("Location: carrito.php");
    exit;
}
