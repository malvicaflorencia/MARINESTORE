<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login_admin.php");
    exit;
}

include 'db/conexion.php';

$sql = "SELECT v.id AS venta_id, v.fecha, u.email, v.cupon, v.subtotal, v.total, v.monto_final
        FROM ventas v
        JOIN usuarios u ON v.usuario_id = u.id
        ORDER BY v.fecha DESC";

$result = mysqli_query($conexion, $sql);
if (!$result) {
    die("Error en la consulta: " . mysqli_error($conexion));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Ventas - Marine Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .venta-container {
            background: #fff;
            margin: 30px auto;
            max-width: 900px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            position: relative;
        }

        .venta-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #A68E6D;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .venta-header img {
            height: 60px;
        }

        .venta-info {
            font-size: 1rem;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 0.95rem;
        }

        th, td {
            padding: 10px 15px;
            border: 1px solid #ddd0bc;
            text-align: center;
        }

        th {
            background-color: #A68E6D;
            color: white;
            text-transform: uppercase;
            font-weight: 600;
        }

        tfoot td {
            font-weight: bold;
            background: #f0e9de;
        }

        .totales {
            margin-top: 20px;
            text-align: right;
            font-size: 1rem;
        }

        .btn-descargar {
            background-color: #2c3e50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
        }

        .volver {
            display: block;
            text-align: center;
            background: #A68E6D;
            color: white;
            font-weight: bold;
            text-decoration: none;
            margin: 40px auto 0;
            padding: 12px 25px;
            border-radius: 6px;
            width: fit-content;
        }

        .volver:hover {
            background-color: #8b7952;
        }
    </style>
</head>
<body>
<a href="productos.php" class="volver">← VOLVER A PRODUCTOS</a>
<a href="login_admin.php" class="volver">← VOLVER AL PANEL DE ADMINISTRACION</a>
<?php if (mysqli_num_rows($result) > 0): ?>
    <?php while ($venta = mysqli_fetch_assoc($result)): ?>
        <div class="venta-container">
            <div class="venta-header">
                <img src="assets/img/logo.jpg" alt="Logo">
                <div class="venta-info">
                    <strong>Venta ID:</strong> <?= $venta['venta_id'] ?><br>
                    <strong>Fecha:</strong> <?= $venta['fecha'] ?><br>
                    <strong>Email:</strong> <?= htmlspecialchars($venta['email']) ?>
                </div>
            </div>

            <div><strong>Cupón aplicado:</strong> <?= $venta['cupon'] ?? 'Ninguno' ?></div>

            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio unitario</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $venta_id = $venta['venta_id'];
                $sql_detalle = "SELECT p.nombre, dv.cantidad, dv.precio_unitario, dv.subtotal
                                FROM detalle_ventas dv
                                JOIN productos p ON dv.producto_id = p.id
                                WHERE dv.venta_id = $venta_id";
                $res_detalle = mysqli_query($conexion, $sql_detalle);
                $cantidad_total = 0;
                $subtotal_total = 0;

                while ($prod = mysqli_fetch_assoc($res_detalle)) {
                    $cantidad_total += $prod['cantidad'];
                    $subtotal_total += $prod['subtotal'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($prod['nombre']) ?></td>
                        <td><?= $prod['cantidad'] ?></td>
                        <td>$ <?= number_format($prod['precio_unitario'], 2, ',', '.') ?></td>
                        <td>$ <?= number_format($prod['subtotal'], 2, ',', '.') ?></td>
                    </tr>
                <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>Total</strong></td>
                        <td><?= $cantidad_total ?></td>
                        <td></td>
                        <td>$ <?= number_format($subtotal_total, 2, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>

            <div class="totales">
                <p><strong>Subtotal:</strong> $ <?= number_format($venta['subtotal'], 2, ',', '.') ?></p>
                <p><strong>Descuento:</strong> $ <?= number_format($venta['subtotal'] - $venta['total'], 2, ',', '.') ?></p>
                <p><strong>Total (con cupón):</strong> $ <?= number_format($venta['total'], 2, ',', '.') ?></p>
            </div>

            <button class="btn-descargar" onclick="descargarPDF(this)">🧾 Convertir a PDF</button>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p style="text-align:center; margin:40px;">No se encontraron ventas.</p>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>
<script>
function descargarPDF(boton) {
    const contenedor = boton.closest('.venta-container');
    const nombre = 'factura_venta_' + new Date().getTime() + '.pdf';

    const opciones = {
        margin: 0.5,
        filename: nombre,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
    };

    html2pdf().set(opciones).from(contenedor).save();
}
</script>
</body>
</html>
