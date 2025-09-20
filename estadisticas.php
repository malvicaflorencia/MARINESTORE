<?php
session_start();
include 'db/conexion.php';

// Para almacenar resultados
$stats = [];

// --- PRODUCTOS ---

// Más clickeado
$sql = "SELECT nombre, clicks FROM productos ORDER BY clicks DESC LIMIT 1";
$res = mysqli_query($conexion, $sql);
$stats['producto_mas_clickeado'] = $res && mysqli_num_rows($res) ? mysqli_fetch_assoc($res) : ['nombre' => '-', 'clicks' => 0];

// Menos clickeado (considerando que clicks puede ser 0)
$sql = "SELECT nombre, clicks FROM productos ORDER BY clicks ASC LIMIT 1";
$res = mysqli_query($conexion, $sql);
$stats['producto_menos_clickeado'] = $res && mysqli_num_rows($res) ? mysqli_fetch_assoc($res) : ['nombre' => '-', 'clicks' => 0];

// Más vendido (sumo cantidades en detalle_ventas)
$sql = "SELECT p.nombre, SUM(dv.cantidad) as total_vendido 
        FROM detalle_ventas dv
        JOIN productos p ON dv.producto_id = p.id
        GROUP BY dv.producto_id
        ORDER BY total_vendido DESC
        LIMIT 1";
$res = mysqli_query($conexion, $sql);
$stats['producto_mas_vendido'] = $res && mysqli_num_rows($res) ? mysqli_fetch_assoc($res) : ['nombre' => '-', 'total_vendido' => 0];

// Menos vendido (pero vendido al menos 1)
$sql = "SELECT p.nombre, SUM(dv.cantidad) as total_vendido 
        FROM detalle_ventas dv
        JOIN productos p ON dv.producto_id = p.id
        GROUP BY dv.producto_id
        HAVING total_vendido > 0
        ORDER BY total_vendido ASC
        LIMIT 1";
$res = mysqli_query($conexion, $sql);
$stats['producto_menos_vendido'] = $res && mysqli_num_rows($res) ? mysqli_fetch_assoc($res) : ['nombre' => '-', 'total_vendido' => 0];

// Producto con más stock (sumo cantidades en stocks)
$sql = "SELECT p.nombre, SUM(s.cantidad) as stock_total
        FROM stocks s
        JOIN productos p ON s.producto_id = p.id
        GROUP BY s.producto_id
        ORDER BY stock_total DESC
        LIMIT 1";
$res = mysqli_query($conexion, $sql);
$stats['producto_mas_stock'] = $res && mysqli_num_rows($res) ? mysqli_fetch_assoc($res) : ['nombre' => '-', 'stock_total' => 0];

// Producto con menos stock (pero > 0)
$sql = "SELECT p.nombre, SUM(s.cantidad) as stock_total
        FROM stocks s
        JOIN productos p ON s.producto_id = p.id
        GROUP BY s.producto_id
        HAVING stock_total > 0
        ORDER BY stock_total ASC
        LIMIT 1";
$res = mysqli_query($conexion, $sql);
$stats['producto_menos_stock'] = $res && mysqli_num_rows($res) ? mysqli_fetch_assoc($res) : ['nombre' => '-', 'stock_total' => 0];

// Producto más caro
$sql = "SELECT nombre, precio FROM productos ORDER BY precio DESC LIMIT 1";
$res = mysqli_query($conexion, $sql);
$stats['producto_mas_caro'] = $res && mysqli_num_rows($res) ? mysqli_fetch_assoc($res) : ['nombre' => '-', 'precio' => 0];

// Producto más barato
$sql = "SELECT nombre, precio FROM productos ORDER BY precio ASC LIMIT 1";
$res = mysqli_query($conexion, $sql);
$stats['producto_mas_barato'] = $res && mysqli_num_rows($res) ? mysqli_fetch_assoc($res) : ['nombre' => '-', 'precio' => 0];

// --- CLIENTES ---

// Cliente que más compró (sumo monto_final)
$sql = "SELECT u.email, SUM(v.monto_final) as total_comprado
        FROM ventas v
        JOIN usuarios u ON v.usuario_id = u.id
        GROUP BY v.usuario_id
        ORDER BY total_comprado DESC
        LIMIT 1";
$res = mysqli_query($conexion, $sql);
$stats['cliente_mas_compro'] = $res && mysqli_num_rows($res) ? mysqli_fetch_assoc($res) : ['email' => '-', 'total_comprado' => 0];

// Clientes frecuentes (más de 1 compra)
$sql = "SELECT COUNT(*) as compras, u.email
        FROM ventas v
        JOIN usuarios u ON v.usuario_id = u.id
        GROUP BY v.usuario_id
        HAVING compras > 1
        ORDER BY compras DESC";
$res = mysqli_query($conexion, $sql);
$clientesFrecuentes = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $clientesFrecuentes[] = $row;
    }
}
$stats['clientes_frecuentes'] = $clientesFrecuentes;

// Clientes nuevos (registrados en últimos 30 días)
$sql = "SELECT COUNT(*) as nuevos
        FROM usuarios
        WHERE fecha_registro >= CURDATE() - INTERVAL 30 DAY";
$res = mysqli_query($conexion, $sql);
$stats['clientes_nuevos'] = $res && mysqli_num_rows($res) ? mysqli_fetch_assoc($res)['nuevos'] : 0;

// Promedio compras por usuario
$sql = "SELECT AVG(compras) as promedio_compras FROM (
        SELECT COUNT(*) as compras FROM ventas GROUP BY usuario_id
    ) as subquery";
$res = mysqli_query($conexion, $sql);
$stats['promedio_compras_usuario'] = $res && mysqli_num_rows($res) ? round(mysqli_fetch_assoc($res)['promedio_compras'], 2) : 0;

// --- VENTAS ---

// Total ventas realizadas (cantidad de ventas)
$sql = "SELECT COUNT(*) as total_ventas FROM ventas";
$res = mysqli_query($conexion, $sql);
$stats['total_ventas'] = $res && mysqli_num_rows($res) ? mysqli_fetch_assoc($res)['total_ventas'] : 0;

// Ventas de hoy
$sql = "SELECT COUNT(*) as ventas_hoy FROM ventas WHERE DATE(fecha) = CURDATE()";
$res = mysqli_query($conexion, $sql);
$stats['ventas_hoy'] = $res && mysqli_num_rows($res) ? mysqli_fetch_assoc($res)['ventas_hoy'] : 0;

// Ventas del mes
$sql = "SELECT COUNT(*) as ventas_mes FROM ventas WHERE MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())";
$res = mysqli_query($conexion, $sql);
$stats['ventas_mes'] = $res && mysqli_num_rows($res) ? mysqli_fetch_assoc($res)['ventas_mes'] : 0;

// Ingresos totales
$sql = "SELECT SUM(monto_final) as ingresos_totales FROM ventas";
$res = mysqli_query($conexion, $sql);
$stats['ingresos_totales'] = $res && mysqli_num_rows($res) ? mysqli_fetch_assoc($res)['ingresos_totales'] : 0;

// Ticket promedio (promedio monto_final)
$sql = "SELECT AVG(monto_final) as ticket_promedio FROM ventas";
$res = mysqli_query($conexion, $sql);
$stats['ticket_promedio'] = $res && mysqli_num_rows($res) ? round(mysqli_fetch_assoc($res)['ticket_promedio'], 2) : 0;

// --- TENDENCIAS TEMPORALES ---

// Comparativa mensual (ventas por mes últimos 6 meses)
$sql = "SELECT DATE_FORMAT(fecha, '%Y-%m') as mes, COUNT(*) as ventas
        FROM ventas
        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY mes
        ORDER BY mes ASC";
$res = mysqli_query($conexion, $sql);
$comparativa_mensual = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $comparativa_mensual[] = $row;
    }
}
$stats['comparativa_mensual'] = $comparativa_mensual;

// Hora con más ventas (según fecha hora en ventas)
$sql = "SELECT HOUR(fecha) as hora, COUNT(*) as ventas
        FROM ventas
        GROUP BY hora
        ORDER BY ventas DESC
        LIMIT 1";
$res = mysqli_query($conexion, $sql);
$stats['hora_mas_ventas'] = $res && mysqli_num_rows($res) ? mysqli_fetch_assoc($res)['hora'] : '-';

// Estacionalidad detectada (por simplicidad, ventas promedio por mes)
// Esto se puede ampliar con análisis más complejos.
$sql = "SELECT MONTH(fecha) as mes, AVG(monto_final) as promedio_mes
        FROM ventas
        GROUP BY mes
        ORDER BY mes";
$res = mysqli_query($conexion, $sql);
$estacionalidad = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $estacionalidad[] = $row;
    }
}
$stats['estacionalidad'] = $estacionalidad;

// --- INVENTARIO ---

// Productos sin stock
$sql = "SELECT COUNT(DISTINCT producto_id) as sin_stock
        FROM stocks
        WHERE cantidad = 0";
$res = mysqli_query($conexion, $sql);
$stats['productos_sin_stock'] = $res && mysqli_num_rows($res) ? mysqli_fetch_assoc($res)['sin_stock'] : 0;

// Productos con rotación alta (más vendidos top 5)
$sql = "SELECT p.nombre, SUM(dv.cantidad) as vendidos
        FROM detalle_ventas dv
        JOIN productos p ON dv.producto_id = p.id
        GROUP BY dv.producto_id
        ORDER BY vendidos DESC
        LIMIT 5";
$res = mysqli_query($conexion, $sql);
$rotacion_alta = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $rotacion_alta[] = $row;
    }
}
$stats['productos_rotacion_alta'] = $rotacion_alta;

// Productos sin ventas (productos que no están en detalle_ventas)
$sql = "SELECT nombre FROM productos WHERE id NOT IN (SELECT DISTINCT producto_id FROM detalle_ventas)";
$res = mysqli_query($conexion, $sql);
$sin_ventas = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $sin_ventas[] = $row['nombre'];
    }
}
$stats['productos_sin_ventas'] = $sin_ventas;

// Productos para reponer (stock total < 5)
$sql = "SELECT p.nombre, SUM(s.cantidad) as stock_total
        FROM stocks s
        JOIN productos p ON s.producto_id = p.id
        GROUP BY s.producto_id
        HAVING stock_total < 5
        ORDER BY stock_total ASC";
$res = mysqli_query($conexion, $sql);
$productos_reponer = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $productos_reponer[] = $row;
    }
}
$stats['productos_para_reponer'] = $productos_reponer;

// Ya podés usar $stats para mostrar en el HTML
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Panel de Estadísticas - Marine Store</title>
    <link rel="stylesheet" href="assets/css/admin_login.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; background: #f7f5f0; margin: 20px; }
        h1 { color: #A68E6D; margin-bottom: 15px; }
        section { background: white; padding: 15px; border-radius: 8px; margin-bottom: 25px; box-shadow: 0 0 10px rgba(0,0,0,0.1);}
        h2 { color: #6b603f; margin-bottom: 12px; }
        ul { list-style: none; padding-left: 0; }
        li { padding: 4px 0; }
        .highlight { font-weight: 700; color: #A68E6D; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #A68E6D; color: white; }
    </style>
</head>
<body>

<h1>📊 Panel Completo de Estadísticas - Marine Store</h1>
    <a href="login_admin.php" class="btn">← Volver</a>
<section>
    <h2>🔥 Productos</h2>
    <ul>
        <li>🔝 Producto más clickeado: <span class="highlight"><?= htmlspecialchars($stats['producto_mas_clickeado']['nombre']) ?> (<?= $stats['producto_mas_clickeado']['clicks'] ?> clicks)</span></li>
        <li>🔽 Producto menos clickeado: <span class="highlight"><?= htmlspecialchars($stats['producto_menos_clickeado']['nombre']) ?> (<?= $stats['producto_menos_clickeado']['clicks'] ?> clicks)</span></li>
        <li>💰 Producto más vendido: <span class="highlight"><?= htmlspecialchars($stats['producto_mas_vendido']['nombre']) ?> (<?= $stats['producto_mas_vendido']['total_vendido'] ?> unidades)</span></li>
        <li>📉 Producto menos vendido: <span class="highlight"><?= htmlspecialchars($stats['producto_menos_vendido']['nombre']) ?> (<?= $stats['producto_menos_vendido']['total_vendido'] ?> unidades)</span></li>
        <li>📦 Producto con más stock: <span class="highlight"><?= htmlspecialchars($stats['producto_mas_stock']['nombre']) ?> (<?= $stats['producto_mas_stock']['stock_total'] ?> unidades)</span></li>
        <li>⚠️ Producto con menos stock: <span class="highlight"><?= htmlspecialchars($stats['producto_menos_stock']['nombre']) ?> (<?= $stats['producto_menos_stock']['stock_total'] ?> unidades)</span></li>
        <li>🏷️ Producto más caro: <span class="highlight"><?= htmlspecialchars($stats['producto_mas_caro']['nombre']) ?> ($<?= number_format($stats['producto_mas_caro']['precio'], 2, ',', '.') ?>)</span></li>
        <li>💸 Producto más barato: <span class="highlight"><?= htmlspecialchars($stats['producto_mas_barato']['nombre']) ?> ($<?= number_format($stats['producto_mas_barato']['precio'], 2, ',', '.') ?>)</span></li>
    </ul>
</section>

<section>
    <h2>👥 Clientes</h2>
    <ul>
        <li>👑 Cliente que más compró: <span class="highlight"><?= htmlspecialchars($stats['cliente_mas_compro']['email']) ?> ($<?= number_format($stats['cliente_mas_compro']['total_comprado'], 2, ',', '.') ?>)</span></li>
        <li>🔁 Clientes frecuentes (más de 1 compra): <span class="highlight"><?= count($stats['clientes_frecuentes']) ?></span></li>
        <li>🆕 Clientes nuevos (últimos 30 días): <span class="highlight"><?= $stats['clientes_nuevos'] ?></span></li>
        <li>📊 Promedio de compras por usuario: <span class="highlight"><?= $stats['promedio_compras_usuario'] ?></span></li>
    </ul>
</section>

<section>
    <h2>💸 Ventas</h2>
    <ul>
        <li>🛒 Total de ventas realizadas: <span class="highlight"><?= $stats['total_ventas'] ?></span></li>
        <li>📆 Ventas de hoy: <span class="highlight"><?= $stats['ventas_hoy'] ?></span></li>
        <li>📈 Ventas del mes: <span class="highlight"><?= $stats['ventas_mes'] ?></span></li>
        <li>💰 Ingresos totales: <span class="highlight">$<?= number_format($stats['ingresos_totales'], 2, ',', '.') ?></span></li>
        <li>🎫 Ticket promedio: <span class="highlight">$<?= number_format($stats['ticket_promedio'], 2, ',', '.') ?></span></li>
    </ul>
</section>

<section>
    <h2>📆 Tendencias temporales</h2>
    <ul>
        <li>📅 Comparativa mensual (últimos 6 meses):</li>
        <table>
            <thead>
                <tr><th>Mes (YYYY-MM)</th><th>Ventas</th></tr>
            </thead>
            <tbody>
                <?php foreach ($stats['comparativa_mensual'] as $mes): ?>
                    <tr>
                        <td><?= htmlspecialchars($mes['mes']) ?></td>
                        <td><?= $mes['ventas'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <li>🕒 Hora con más ventas: <span class="highlight"><?= $stats['hora_mas_ventas'] ?>:00</span></li>
        <li>🌤️ Estacionalidad detectada (promedio ventas por mes):</li>
        <table>
            <thead>
                <tr><th>Mes</th><th>Promedio Ventas ($)</th></tr>
            </thead>
            <tbody>
                <?php foreach ($stats['estacionalidad'] as $est): ?>
                    <tr>
                        <td><?= htmlspecialchars($est['mes']) ?></td>
                        <td>$<?= number_format($est['promedio_mes'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </ul>
</section>

<section>
    <h2>📦 Inventario</h2>
    <ul>
        <li>🚫 Productos sin stock: <span class="highlight"><?= $stats['productos_sin_stock'] ?></span></li>
        <li>⚡ Productos con rotación alta (top 5 más vendidos):</li>
        <table>
            <thead>
                <tr><th>Producto</th><th>Unidades vendidas</th></tr>
            </thead>
            <tbody>
                <?php foreach ($stats['productos_rotacion_alta'] as $prod): ?>
                    <tr>
                        <td><?= htmlspecialchars($prod['nombre']) ?></td>
                        <td><?= $prod['vendidos'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <li>🪫 Productos sin ventas:</li>
        <ul>
            <?php foreach ($stats['productos_sin_ventas'] as $prod): ?>
                <li><?= htmlspecialchars($prod) ?></li>
            <?php endforeach; ?>
        </ul>
        <li>📋 Productos para reponer (stock < 5):</li>
        <table>
            <thead>
                <tr><th>Producto</th><th>Stock</th></tr>
            </thead>
            <tbody>
                <?php foreach ($stats['productos_para_reponer'] as $prod): ?>
                    <tr>
                        <td><?= htmlspecialchars($prod['nombre']) ?></td>
                        <td><?= $prod['stock_total'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </ul>
</section>

</body>
</html>

