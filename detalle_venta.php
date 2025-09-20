<?php
session_start();
include 'db/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$venta_id = intval($_GET['id'] ?? 0);

if ($venta_id <= 0) {
    die("ID de venta inválido.");
}

// Validar que la venta pertenece al usuario usando consulta preparada
$stmt_check = $conexion->prepare("SELECT id FROM ventas WHERE id = ? AND usuario_id = ?");
$stmt_check->bind_param("ii", $venta_id, $usuario_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    echo "Venta no encontrada o no autorizada.";
    exit;
}
$stmt_check->close();

// Obtener detalle, incluyendo talle (agregado)
$stmt = $conexion->prepare("SELECT dv.*, p.nombre FROM detalle_ventas dv JOIN productos p ON dv.producto_id = p.id WHERE dv.venta_id = ?");
$stmt->bind_param("i", $venta_id);
$stmt->execute();
$res = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Detalle Pedido #<?= htmlspecialchars($venta_id) ?></title>
<style>
    body {
        font-family: 'Montserrat', sans-serif;
        background-color: #f7f7f7;
        margin: 20px;
        color: #333;
    }
    h1 {
        color: #5a4a2a;
        margin-bottom: 20px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    th, td {
        padding: 12px 15px;
        border: 1px solid #c4bfa6;
        text-align: center;
    }
    th {
        background-color: #A68E6D;
        color: white;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    tbody tr:nth-child(even) {
        background-color: #f9f5f0;
    }
</style>
</head>
<body>
<h1>Detalle Pedido #<?= htmlspecialchars($venta_id) ?></h1>

<?php if ($res->num_rows > 0): ?>
<table>
<thead>
<tr>
    <th>Producto</th>
    <th>Talle</th>
    <th>Cantidad</th>
    <th>Precio Unitario</th>
    <th>Subtotal</th>
</tr>
</thead>
<tbody>
<?php while ($item = $res->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($item['nombre']) ?></td>
    <td><?= htmlspecialchars($item['talle'] ?? 'UNICO') ?></td>
    <td><?= (int)$item['cantidad'] ?></td>
    <td>$ <?= number_format($item['precio_unitario'], 2, ',', '.') ?></td>
    <td>$ <?= number_format($item['subtotal'], 2, ',', '.') ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php else: ?>
<p>No se encontraron detalles para esta venta.</p>
<?php endif; ?>

</body>
</html>

<?php
$stmt->close();
$conexion->close();
?>
