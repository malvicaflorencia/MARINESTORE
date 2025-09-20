<?php
session_start();

if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    unset($_SESSION['admin']);
    session_write_close();
}

$carrito = $_SESSION['carrito'] ?? [];

$subtotal = 0;
foreach ($carrito as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}

$codigo_cupon = '';
$descuento = 0;
$total = $subtotal;
$cupon_aplicado = null;
$error_cupon = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo_cupon'])) {
    $codigo_cupon = trim($_POST['codigo_cupon']);

    $conexion = new mysqli("localhost", "root", "", "marine");
    if ($conexion->connect_errno) {
        die("Error al conectar a la base de datos: " . $conexion->connect_error);
    }

    $stmt = $conexion->prepare("SELECT * FROM cupones WHERE codigo = ?");
    $stmt->bind_param("s", $codigo_cupon);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $cupon_aplicado = $resultado->fetch_assoc();
        $hoy = date('Y-m-d');

        if ($cupon_aplicado['activo'] && $cupon_aplicado['fecha_expiracion'] >= $hoy) {

            $categoria_cupon = trim($cupon_aplicado['categoria']); // puede estar vacío o NULL

            if ($cupon_aplicado['tipo_descuento'] === 'porcentaje') {
                $porcentaje_descuento = floatval($cupon_aplicado['descuento']) / 100;
                $descuento = $subtotal * $porcentaje_descuento;
                $total = $subtotal - $descuento;

            } elseif ($cupon_aplicado['tipo_descuento'] === 'monto') {
                $descuento = floatval($cupon_aplicado['descuento']);
                $total = $subtotal - $descuento;
                if ($total < 0) $total = 0;

            } elseif ($cupon_aplicado['tipo_descuento'] === '2x1' || $cupon_aplicado['tipo_descuento'] === '3x2') {
                // Lógica 2x1 y 3x2 con filtro por categoría
                $tipo_promo = $cupon_aplicado['tipo_descuento'];
                $factor = ($tipo_promo === '2x1') ? 2 : 3; // 2x1 o 3x2
                $pagar = ($tipo_promo === '2x1') ? 1 : 2;

                $descuento_promo = 0;

                // Agrupar productos iguales (id + talle) para la promo
                $productos_agrupados = [];

                foreach ($carrito as $item) {
                    // Si el cupón tiene categoría y el producto no pertenece a esa categoría, saltar
                    // NOTA: el campo 'categoria' debe estar definido en cada item del carrito
                    if ($categoria_cupon !== '' && strcasecmp($categoria_cupon, $item['categoria'] ?? '') !== 0) {
                        continue; // producto fuera de la categoría del cupón
                    }

                    $key = $item['id'] . '-' . ($item['talle'] ?? '');
                    if (!isset($productos_agrupados[$key])) {
                        $productos_agrupados[$key] = [
                            'cantidad' => 0,
                            'precio' => $item['precio'],
                        ];
                    }
                    $productos_agrupados[$key]['cantidad'] += $item['cantidad'];
                }

                foreach ($productos_agrupados as $prod) {
                    $cantidad = $prod['cantidad'];
                    $precio = $prod['precio'];

                    // Cantidad completa de promociones que aplican
                    $grupos = intdiv($cantidad, $factor);

                    // Descuento = cantidad de productos gratis * precio unitario
                    $gratis = $grupos * ($factor - $pagar);

                    $descuento_promo += $gratis * $precio;
                }

                $descuento = $descuento_promo;
                $total = $subtotal - $descuento;

            } elseif ($cupon_aplicado['tipo_descuento'] === 'envio_gratis') {
                // Solo envío gratis, no descuento en precio
                $descuento = 0;
                $total = $subtotal;

            } else {
                // Otros tipos no contemplados
                $descuento = 0;
                $total = $subtotal;
            }

            $_SESSION['cupon'] = $codigo_cupon;

        } else {
            $error_cupon = "Este cupón ya expiró o está inactivo.";
            $total = $subtotal;
            unset($_SESSION['cupon']);
        }
    } else {
        $error_cupon = "El código de cupón no es válido.";
        $total = $subtotal;
        unset($_SESSION['cupon']);
    }

    $stmt->close();
    $conexion->close();
}

$_SESSION['total'] = $total;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>MARINE STORE - CARRITO</title>
    <link rel="icon" href="assets/img/logo.jpg" type="image/png">
    <link rel="stylesheet" href="assets/css/stylecarrito.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat&display=swap" rel="stylesheet" />
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="fondo-contenido">
  <div class="contenido">

    <div class="breadcrumb" style="margin: 20px;">
      <a href="index.php">home</a> / <span>cart</span>
    </div>

    <?php if (count($carrito) > 0): ?>
        <form action="vaciar_carrito.php" method="post" style="text-align: right; margin: 10px 20px; font-family:Montserrat;">
            <button type="submit" class="btn-vaciar">🗑 Vaciar carrito</button>
        </form>

        <table border="1" cellpadding="10" cellspacing="0" style="width: 90%; margin: auto; border-collapse: collapse;">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                    <th>Eliminar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($carrito as $producto): ?>
                <tr>
                    <td>
                        <?php if (!empty($producto['imagen'])): ?>
                            <img src="assets/img/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" style="width:50px; height:auto; vertical-align:middle; margin-right:10px;">
                        <?php endif; ?>
                        <a href="detalle.php?id=<?= htmlspecialchars($producto['id']) ?>" style="text-decoration:none; color:#000; ">
                            <?= htmlspecialchars($producto['nombre']) ?>
                        </a>
                        <?php if (!empty($producto['talle'])): ?>
                            <br><small style="font-weight: normal; font-size: 0.9em; color: #555;">Talle: <?= htmlspecialchars($producto['talle']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center; font-size: 1.1em;">
                        <?= $producto['cantidad'] ?>
                    </td>
                    <td>$ <?php echo number_format($producto['precio'] * $producto['cantidad'], 2, ',', '.'); ?></td>
                    <td>
                        <form action="eliminar_producto.php" method="post">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($producto['id']) ?>">
                            <input type="hidden" name="talle" value="<?= htmlspecialchars($producto['talle']) ?>">
                            <button type="submit" class="btn-eliminar" style="background:#c0392b; color:#fff; border:none; padding:5px 10px; cursor:pointer;font-family:Montserrat;">Eliminar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Formulario cupón -->
        <div style="width: 90%; margin: 20px auto; text-align: center;">
            <form action="carrito.php" method="post" class="form-cupon" style="display: inline-flex; gap: 10px; align-items: center;">
                <label for="codigo_cupon" style="display:none;">Código de cupón</label>
                <input type="text" id="codigo_cupon" name="codigo_cupon" value="<?= htmlspecialchars($codigo_cupon) ?>" placeholder="Código de cupón" />
                <button type="submit">Aplicar</button>
            </form>
            <?php if (!empty($error_cupon)): ?>
                <p style="color: red; margin-top: 5px;"><?= htmlspecialchars($error_cupon) ?></p>
            <?php endif; ?>
        </div>

        <div style="width: 90%; margin: 20px auto; text-align: center;">
            <p>Subtotal: $ <?php echo number_format($subtotal, 2, ',', '.'); ?></p>
            <?php if ($descuento > 0): ?>
                <p>Descuento por cupón: - $ <?php echo number_format($descuento, 2, ',', '.'); ?></p>
            <?php endif; ?>
            <p>Total: $ <span id="total-sin-envio"><?= number_format($total, 2, ',', '.') ?></span></p>
        </div>

        <form id="form-pago" action="procesar_pago.php" method="POST" class="form-pago" novalidate style="max-width: 450px; margin: 0 auto; display: flex; flex-direction: column; gap: 15px; align-items: center;">
            <label for="email" style="width: 100%; text-align: center;">Ingresá tu email para pagar:</label>
            <input type="email" name="email" id="email" required placeholder="tucorreo@example.com" style="width: 100%; max-width: 400px;">

            <div class="coordinar-envio-container" style="width: 100%; text-align: center;">
                <input type="checkbox" id="coordinar_envio" name="coordinar_envio" style="margin-right: 5px;">
                <label for="coordinar_envio" style="cursor: pointer;">Coordinar envío con el local</label>
            </div>

            <label for="direccion" style="width: 100%; text-align: center;">Dirección de envío:</label>
            <input type="text" name="direccion" id="direccion" placeholder="Ej: Calle 123, CABA" style="width: 100%; max-width: 400px;">

            <label for="provincia" style="width: 100%; text-align: center;">Provincia:</label>
            <select name="provincia" id="provincia" style="width: 100%; max-width: 400px;">
                <option value="">Seleccioná</option>
                <option value="CABA">CABA</option>
                <option value="Buenos Aires">Buenos Aires</option>
                <option value="Córdoba">Córdoba</option>
                <option value="Santa Fe">Santa Fe</option>
                <option value="Mendoza">Mendoza</option>
                <option value="Tucumán">Tucumán</option>
                <!-- Más provincias -->
            </select>

            <!-- Total con descuento sin envío -->
            <input type="hidden" name="total_con_descuento" id="total_con_descuento" value="<?= $total ?>">

            <button type="button" id="btn-calcular-envio" style="width: 100%; max-width: 400px;">Aplicar envío</button>

            <div id="resultado-envio" style=" margin-top: 10px; color: #5a4a2a; text-align: center;"></div>

            <!-- Total + envío oculto para enviar -->
            <input type="hidden" name="total_con_envio" id="total_con_envio" value="<?= $total ?>">

            <div class="btns-finalizar" style="display: flex; justify-content: center; gap: 20px; margin-top: 15px; width: 100%; max-width: 450px;">
                <a href="productos.php" style="background-color: #A68E6D; color: white; padding: 12px 25px; border-radius: 5px; text-decoration: none; min-width: 180px; text-align: center; display: inline-block;">← Seguir comprando</a>
                <button type="submit" id="btn-pagar" style="font-family: Montserrat; background-color: #A68E6D; color: white; padding: 12px 25px; border-radius: 5px; min-width: 180px; cursor: pointer;">Pagar→</button>
            </div>
        </form>

    <?php else: ?>
        <p style="text-align:center; margin:40px;">Tu carrito está vacío.</p>
    <?php endif; ?>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    const coordinarEnvioCheckbox = document.getElementById('coordinar_envio');
    const direccionInput = document.getElementById('direccion');
    const provinciaSelect = document.getElementById('provincia');
    const btnCalcularEnvio = document.getElementById('btn-calcular-envio');
    const resultadoEnvioDiv = document.getElementById('resultado-envio');
    const totalSinEnvioSpan = document.getElementById('total-sin-envio');
    const totalConEnvioInput = document.getElementById('total_con_envio');
    const totalConDescuentoInput = document.getElementById('total_con_descuento');
    const formPago = document.getElementById('form-pago');

    function calcularEnvioSimulado(provincia) {
        // Simula precios diferentes por provincia:
        const preciosEnvio = {
            'CABA': 500,
            'Buenos Aires': 700,
            'Córdoba': 600,
            'Santa Fe': 650,
            'Mendoza': 750,
            'Tucumán': 700
        };
        return preciosEnvio[provincia] || 800;
    }

    function actualizarEstadoCampos() {
        if(coordinarEnvioCheckbox.checked) {
            direccionInput.required = false;
            provinciaSelect.required = false;
            direccionInput.disabled = true;
            provinciaSelect.disabled = true;
            resultadoEnvioDiv.textContent = "Coordinarás el envío con el local luego de efectuar el pago.";
            totalConEnvioInput.value = totalConDescuentoInput.value;
            totalSinEnvioSpan.textContent = parseFloat(totalConDescuentoInput.value).toFixed(2).replace('.', ',');
        } else {
            direccionInput.required = true;
            provinciaSelect.required = true;
            direccionInput.disabled = false;
            provinciaSelect.disabled = false;
            resultadoEnvioDiv.textContent = "";
            totalConEnvioInput.value = totalConDescuentoInput.value;
            totalSinEnvioSpan.textContent = parseFloat(totalConDescuentoInput.value).toFixed(2).replace('.', ',');
        }
    }

    coordinarEnvioCheckbox.addEventListener('change', actualizarEstadoCampos);

    btnCalcularEnvio.addEventListener('click', function() {
        if(coordinarEnvioCheckbox.checked) {
            resultadoEnvioDiv.textContent = "Envío coordinado con el local, no se calcula costo.";
            totalConEnvioInput.value = totalConDescuentoInput.value;
            totalSinEnvioSpan.textContent = parseFloat(totalConDescuentoInput.value).toFixed(2).replace('.', ',');
            return;
        }

        if (!direccionInput.value.trim()) {
            alert('Por favor ingresá la dirección.');
            direccionInput.focus();
            return;
        }

        if (!provinciaSelect.value) {
            alert('Por favor seleccioná la provincia.');
            provinciaSelect.focus();
            return;
        }

        const costoEnvio = calcularEnvioSimulado(provinciaSelect.value);
        const totalFinal = parseFloat(totalConDescuentoInput.value) + costoEnvio;

        resultadoEnvioDiv.textContent = `Costo de envío: $ ${costoEnvio.toFixed(2).replace('.', ',')}`;
        totalSinEnvioSpan.textContent = totalFinal.toFixed(2).replace('.', ',');
        totalConEnvioInput.value = totalFinal;
    });

    formPago.addEventListener('submit', function(event) {
        const email = document.getElementById('email').value.trim();
        const coordinar = coordinarEnvioCheckbox.checked;
        const direccion = direccionInput.value.trim();
        const provincia = provinciaSelect.value;

        // Si falta email y además no hay opción de envío válida
        if (email === '' || (!coordinar && (direccion === '' || provincia === ''))) {
            alert('Completá el mail o elegí opción de envío');
            event.preventDefault();
            return;
        }
    });

    // Inicializamos el estado de los campos según checkbox
    actualizarEstadoCampos();
</script>

</body>
</html>
