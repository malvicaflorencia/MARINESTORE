<?php
session_start();
include 'db/conexion.php';

$condiciones = [];
$productos = [];

// Filtros
if (!empty($_GET['buscar'])) {
    $buscar = mysqli_real_escape_string($conexion, $_GET['buscar']);
    $condiciones[] = "(nombre LIKE '%$buscar%' OR categoria LIKE '%$buscar%')";
}

if (!empty($_GET['categoria'])) {
    $categoria = mysqli_real_escape_string($conexion, $_GET['categoria']);
    $condiciones[] = "categoria = '$categoria'";
}

$where = count($condiciones) > 0 ? "WHERE " . implode(' AND ', $condiciones) : "";

$sql = "SELECT id, nombre, precio, imagenes, categoria FROM productos $where ORDER BY nombre ASC";
$result = mysqli_query($conexion, $sql);

if ($result) {
    while ($fila = mysqli_fetch_assoc($result)) {
        $productos[] = $fila;
    }
}

// Función para obtener descuento activo (producto o categoría)
function obtenerDescuento($conexion, $producto_id, $categoria) {
    $producto_id = intval($producto_id);
    $categoria_esc = $conexion->real_escape_string($categoria);

    // Primero, busca descuento activo directo para producto
    $sql_producto = "SELECT descuento, tipo_descuento FROM descuentos_aplicados WHERE producto_id = $producto_id AND activo = 1 LIMIT 1";
    $res_producto = $conexion->query($sql_producto);
    if ($res_producto && $res_producto->num_rows > 0) {
        return $res_producto->fetch_assoc();
    }

    // Luego, busca descuento activo por categoría
    $sql_categoria = "SELECT descuento, tipo_descuento FROM descuentos_aplicados WHERE categoria = '$categoria_esc' AND activo = 1 LIMIT 1";
    $res_categoria = $conexion->query($sql_categoria);
    if ($res_categoria && $res_categoria->num_rows > 0) {
        return $res_categoria->fetch_assoc();
    }

    // No hay descuento
    return null;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MARINE STORE - SHOP</title>
    <link rel="icon" href="assets/img/logo.jpg" type="image/png">
    <link rel="stylesheet" href="assets/css/styleproductos.css" />
    <style>
        .carrusel {
            position: relative;
            max-width: 250px;
            margin: 0 auto 15px;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .carrusel img {
            width: 100%;
            display: none;
            user-select: none;
        }
        .carrusel img:first-child {
            display: block;
        }
        .carrusel button.prev,
        .carrusel button.next {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            background: rgba(0,0,0,0.5);
            color: #fff;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            user-select: none;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .carrusel button.prev { left: 5px; }
        .carrusel button.next { right: 5px; }
        .precio-original {
            text-decoration: line-through;
            color: #999;
            font-size: 0.9em;
            margin-right: 6px;
        }
        .precio-descuento {
            color: #a68e6d;
            font-weight: bold;
            margin-right: 6px;
        }
        .precio-final {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>

<div class="breadcrumb" style="margin: 20px;">
    <a href="index.php">home</a> / <span>shop</span>
</div> 

<?php
$pagina_actual = "SHOP";
include 'includes/header.php';
?>

<section class="productos">
    <div class="grid-productos">
        <?php if (empty($productos)): ?>
            <p>No se encontraron productos con esos filtros.</p>
        <?php else: ?>
            <?php foreach ($productos as $producto): ?>
                <?php
                $idProducto = $producto['id'];
                $queryTalles = "SELECT talle, cantidad FROM stocks WHERE producto_id = $idProducto AND cantidad > 0";
                $resTalles = mysqli_query($conexion, $queryTalles);
                $talles = [];
                while ($filaTalle = mysqli_fetch_assoc($resTalles)) {
                    $talles[] = $filaTalle;
                }
                $imagenes = json_decode($producto['imagenes'], true);
                if (!is_array($imagenes)) {
                    $imagenes = [$producto['imagenes']];
                }

                // Obtener descuento activo para este producto
                $descuento_info = obtenerDescuento($conexion, $producto['id'], $producto['categoria']);

                $precio_original = floatval($producto['precio']);
                $precio_final = $precio_original;
                $descuento_str = '';

                if ($descuento_info) {
                    if ($descuento_info['tipo_descuento'] === 'porcentaje') {
                        $desc_val = floatval($descuento_info['descuento']);
                        $precio_final = $precio_original * (1 - $desc_val / 100);
                        $descuento_str = "-$desc_val%";
                    } elseif ($descuento_info['tipo_descuento'] === 'monto') {
                        $desc_val = floatval($descuento_info['descuento']);
                        $precio_final = max(0, $precio_original - $desc_val);
                        $descuento_str = "-$" . number_format($desc_val, 0, ',', '.');
                    }
                }
                ?>
                <article class="producto">
                    <a href="detalle.php?id=<?= $producto['id'] ?>" class="imagen-link">
                        <div class="carrusel" data-id="<?= $producto['id'] ?>">
                            <button class="prev" onclick="cambiarImagenes(<?= $producto['id'] ?>, -1)">&#10094;</button>
                            <?php foreach ($imagenes as $i => $img): ?>
                                <img src="assets/img/<?= htmlspecialchars($img) ?>" alt="Imagen <?= $i + 1 ?>" style="<?= $i === 0 ? 'display:block;' : 'display:none;' ?>">
                            <?php endforeach; ?>
                            <button class="next" onclick="cambiarImagenes(<?= $producto['id'] ?>, 1)">&#10095;</button>
                        </div>
                    </a>
                    <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
                    <p class="precio">
                        <?php if ($descuento_info): ?>
                            <span class="precio-original">$ <?= number_format($precio_original, 0, ',', '.') ?></span>
                            <span class="precio-descuento"><?= $descuento_str ?></span>
                            <br>
                            <span class="precio-final">$ <?= number_format($precio_final, 0, ',', '.') ?></span>
                        <?php else: ?>
                            $ <?= number_format($precio_original, 0, ',', '.') ?>
                        <?php endif; ?>
                    </p>

                    <?php if (!empty($talles)): ?>
                        <div>
                            <label class="talle" for="talle-<?= $producto['id'] ?>" style="white-space: nowrap;">Talle:</label>
                            <select id="talle-<?= $producto['id'] ?>" class="select-talle" style="padding: 4px 8px;">
                                <?php foreach ($talles as $t): ?>
                                    <option value="<?= htmlspecialchars($t['talle']) ?>"><?= htmlspecialchars($t['talle']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <p class="sinstock">Sin stock disponible</p>
                    <?php endif; ?>

                    <button class="agregar-carrito" 
                        data-id="<?= $producto['id'] ?>" 
                        data-nombre="<?= htmlspecialchars($producto['nombre']) ?>" 
                        data-precio="<?= $precio_final ?>" 
                        data-imagenes="<?= htmlspecialchars($producto['imagenes']) ?>"
                        <?= empty($talles) ? 'disabled' : '' ?>>
                        ADD TO CART
                    </button>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<div id="toast" style="position:fixed;top:100px;left:50%;transform:translateX(-50%);background-color:rgba(166,142,109,0.9);color:white;padding:12px 20px;border-radius:8px;display:none;font-family:'Montserrat',sans-serif;font-weight:500;font-size:15px;box-shadow:0 4px 10px rgba(0,0,0,0.2);z-index:9999;max-width:90%;text-align:center;"></div>

<script>
function mostrarToast(mensaje) {
    const toast = document.getElementById('toast');
    toast.textContent = mensaje;
    toast.style.display = 'block';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}

document.querySelectorAll('.agregar-carrito').forEach(btn => {
    btn.addEventListener('click', function () {
        const idProducto = this.dataset.id;
        const selectTalle = document.querySelector(`#talle-${idProducto}`);
        const talle = selectTalle ? selectTalle.value : '';

        if (!talle) {
            mostrarToast("⚠ Por favor seleccioná un talle");
            return;
        }

        const data = {
            id: this.dataset.id,
            nombre: this.dataset.nombre,
            precio: this.dataset.precio,
            imagenes: this.dataset.imagenes,
            talle: talle
        };

        fetch('ajax_agregar.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams(data)
        })
        .then(response => {
            if (!response.ok) throw new Error('Error en la respuesta HTTP');
            return response.json();
        })
        .then(result => {
            if (result.success) {
                mostrarToast(`✔ Se agregó '${result.nombre}' talle ${result.talle}`);
                const contador = document.getElementById('contador-carrito');
                if (contador) {
                    contador.textContent = result.cantidad_total;
                }
            } else {
                mostrarToast(result.mensaje);
            }
        })
        .catch(error => {
            mostrarToast("❌ Error al agregar al carrito: " + error.message);
            console.error('Error en fetch:', error);
        });
    });
});

const carruseles = {};
document.querySelectorAll('.carrusel').forEach(carrusel => {
    const id = carrusel.getAttribute('data-id');
    carruseles[id] = {
        index: 0,
        slides: carrusel.querySelectorAll('img')
    };
});

function cambiarImagenes(id, n) {
    const c = carruseles[id];
    if (!c) return;
    c.slides[c.index].style.display = 'none';
    c.index += n;
    if (c.index < 0) c.index = c.slides.length - 1;
    if (c.index >= c.slides.length) c.index = 0;
    c.slides[c.index].style.display = 'block';
}
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>
