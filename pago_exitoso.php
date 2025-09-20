<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" href="assets/img/logo.jpg" type="image/png">
    <meta charset="UTF-8" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <title>MARINE STORE - PAGO EXITOSO</title>
</head>

<body>

<?php
session_start();
unset($_SESSION['carrito']);
unset($_SESSION['cupon']);
unset($_SESSION['total']);

require 'db/conexion.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Recibir referencia de pago
$external_reference = $_GET['external_reference'] ?? null;

if (!$external_reference) {
    die("<p>No se encontró la referencia de pago.</p>");
}

// 2. Leer archivo temporal
$tempFile = __DIR__ . "/temp/{$external_reference}.json";
if (!file_exists($tempFile)) {
    die("<p>No se encontró información del pedido.</p>");
}

$tempData = json_decode(file_get_contents($tempFile), true);
$email = $tempData['email'] ?? null;
$carrito = $tempData['carrito'] ?? [];
$total_pago = $tempData['total_con_descuento'] ?? 0;
$cupon = $tempData['cupon_aplicado'] ?? null;

if (!$email || empty($carrito)) {
    die("<p>Información incompleta del pedido.</p>");
}

// 3. Verificar o insertar usuario
$stmt = $conexion->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    $stmtInsert = $conexion->prepare("INSERT INTO usuarios (email, fecha_registro) VALUES (?, NOW())");
    $stmtInsert->bind_param("s", $email);
    $stmtInsert->execute();
    $usuario_id = $stmtInsert->insert_id;
    $stmtInsert->close();
} else {
    $stmt->bind_result($usuario_id);
    $stmt->fetch();
}
$stmt->close();

// 4. Calcular subtotal real (sin descuento)
$subtotal = 0;
foreach ($carrito as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}

// 5. Insertar venta, con cupón si existe
$stmtVenta = $conexion->prepare("INSERT INTO ventas (usuario_id, fecha, subtotal, cupon, total) VALUES (?, NOW(), ?, ?, ?)");
$stmtVenta->bind_param("idss", $usuario_id, $subtotal, $cupon, $total_pago);
$stmtVenta->execute();
$id_venta = $stmtVenta->insert_id;
$stmtVenta->close();

// 6. Insertar detalle y actualizar stock por talle
$stmtDetalle = $conexion->prepare("INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
$stmtStock = $conexion->prepare("UPDATE stocks SET cantidad = GREATEST(cantidad - ?, 0) WHERE producto_id = ? AND talle = ?");

foreach ($carrito as $producto) {
    $idProducto = $producto['id'];
    $cantidad = $producto['cantidad'];
    $precio = $producto['precio'];
    $subtotalProducto = $cantidad * $precio;
    $talle = $producto['talle'] ?? 'UNICO'; // si no hay talle

    // Insertar detalle de venta
    $stmtDetalle->bind_param("iiidd", $id_venta, $idProducto, $cantidad, $precio, $subtotalProducto);
    $stmtDetalle->execute();

    // Descontar stock según producto y talle
    $stmtStock->bind_param("iis", $cantidad, $idProducto, $talle);
    $stmtStock->execute();
}

$stmtDetalle->close();
$stmtStock->close();

// 7. Eliminar archivo temporal para evitar repetición
unlink($tempFile);
?>

<div class="venta-container">
    <h2>¡Gracias por tu compra!</h2>
    <p class="venta-info">Email: <?php echo htmlspecialchars($email); ?></p>
    <p class="venta-info">Fecha: <?php echo date('Y-m-d H:i:s'); ?></p>
    <p class="venta-info">Cupón aplicado: <?php echo $cupon ? htmlspecialchars($cupon) : 'Ninguno'; ?></p>
    <p class="venta-info">Descuento aplicado: <?php echo $cupon ? '15%' : '0%'; ?></p>

    <h3>Detalle de productos:</h3>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Talle</th>
                <th>Cantidad</th>
                <th>Precio unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($carrito as $producto): ?>
            <tr>
                <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                <td><?php echo htmlspecialchars($producto['talle'] ?? 'UNICO'); ?></td>
                <td><?php echo (int)$producto['cantidad']; ?></td>
                <td>$ <?php echo number_format($producto['precio'], 2, ',', '.'); ?></td>
                <td>$ <?php echo number_format($producto['precio'] * $producto['cantidad'], 2, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p><strong>Total pagado: $ <?php echo number_format($total_pago, 2, ',', '.'); ?></strong></p>
</div>

<a href="index.php" class="volver">Volver al inicio</a>

<style>
body {
  font-family: 'Montserrat', sans-serif;
  background-color: #f0f0f0;
  margin: 0;
  padding: 20px 0;
  min-height: 100vh;
}

.venta-container {
  background-color: rgba(255, 255, 255, 0.95);
  max-width: 900px;
  margin: 30px auto;
  padding: 20px 30px;
  border-radius: 12px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.1);
  position: relative;
  overflow: hidden;
}

.venta-info {
  font-weight: 600;
  font-size: 1.1rem;
  color: #5a4a2a;
  margin-bottom: 15px;
  border-bottom: 2px solid #A68E6D;
  padding-bottom: 8px;
  word-wrap: break-word;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
  font-size: 0.95rem;
  color: #333;
}

th, td {
  padding: 12px 15px;
  border: 1px solid #c4bfa6;
  text-align: center;
}

th {
  background-color: #A68E6D;
  color: white;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
}

tbody tr:nth-child(even) {
  background-color: #f9f5f0;
}

tfoot tr {
  font-weight: 700;
  background-color: #ddd6c9;
  color: #4b3a1a;
}

.volver {
  display: block;
  max-width: 900px;
  margin: 40px auto 20px;
  background-color: #A68E6D;
  color: white;
  text-align: center;
  padding: 14px 0;
  border-radius: 8px;
  font-weight: 700;
  font-size: 1.1rem;
  text-decoration: none;
  transition: background-color 0.3s ease;
  box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.volver:hover {
  background-color: #8b7952;
}

p {
  font-size: 1.1rem;
  color: #a17c4b;
  text-align: center;
  margin: 50px 0;
}
</style>

</body>
</html>
