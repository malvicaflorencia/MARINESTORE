<?php
session_start();
include 'db/conexion.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // evita inyección
    
    // Consulta para obtener producto
    $query = "SELECT * FROM productos WHERE id = $id";
    $resultado = mysqli_query($conexion, $query);

    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $producto = mysqli_fetch_object($resultado);

        // Sumar 1 click
        $updateClicks = "UPDATE productos SET clicks = clicks + 1 WHERE id = $id";
        mysqli_query($conexion, $updateClicks);

        // Consultar descuento activo para el producto o su categoría
        $descuento = 0; // porcentaje

        // Buscamos descuento activo para el producto o la categoría
        $queryDescuento = "
          SELECT descuento, tipo_descuento 
          FROM descuentos_aplicados 
          WHERE activo = 1 
            AND (producto_id = {$producto->id} OR categoria = '{$producto->categoria}')
          ORDER BY producto_id DESC
          LIMIT 1";

        $resultDescuento = mysqli_query($conexion, $queryDescuento);

        if ($resultDescuento && mysqli_num_rows($resultDescuento) > 0) {
            $filaDescuento = mysqli_fetch_assoc($resultDescuento);

            if ($filaDescuento['tipo_descuento'] == 'porcentaje') {
                $descuento = floatval($filaDescuento['descuento']);
            }
            // Si tienes tipo_descuento = 'monto', acá podrías agregar lógica para monto fijo
        }

        $precioOriginal = $producto->precio;
        $precioConDescuento = $precioOriginal;

        if ($descuento > 0) {
            $precioConDescuento = $precioOriginal * (1 - $descuento / 100);
        }
    } else {
        echo "Producto no encontrado.";
        exit;
    }
} else {
    echo "ID no especificado.";
    exit;
}

// Consultar talles con stock > 0 para este producto
$queryTalles = "SELECT talle, cantidad FROM stocks WHERE producto_id = $id AND cantidad > 0";
$resultTalles = mysqli_query($conexion, $queryTalles);
$tallesDisponibles = [];
if ($resultTalles) {
    while ($fila = mysqli_fetch_assoc($resultTalles)) {
        $tallesDisponibles[] = $fila;
    }
}

// Si el admin está logueado y entra a una página pública, cerrar sesión admin
if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    unset($_SESSION['admin']);
    session_write_close(); // opcional, pero asegura que se guarde el cambio
}

// -- Manejo seguro del campo imagenes --

// Intentamos decodificar JSON
$imagenes = json_decode($producto->imagenes, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    // No es JSON válido, puede ser una cadena simple (ejemplo: bikini.png)

    // Limpiamos espacios y corchetes si los tiene
    $limpio = trim($producto->imagenes);

    // Si comienza con [ y termina con ], intentamos quitar corchetes para evitar error
    if (str_starts_with($limpio, '[') && str_ends_with($limpio, ']')) {
        $limpio = trim($limpio, "[]\"");
    }

    if ($limpio === '') {
        // Vacío: array vacío
        $imagenes = [];
    } else {
        // Si tiene comas, se puede intentar separar en array, si no, único elemento
        if (strpos($limpio, ',') !== false) {
            // Separar por coma y limpiar espacios
            $arr = array_map('trim', explode(',', $limpio));
            $imagenes = $arr;
        } else {
            $imagenes = [$limpio];
        }
    }
} elseif (!is_array($imagenes)) {
    // Si por alguna razón es un tipo distinto, aseguramos array
    $imagenes = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" href="assets/img/logo.jpg" type="image/png">
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/detailstyle.css">
    <title>MARINE STORE - DETAILS</title>
    <script src="assets/js/myscript.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'includes/header.php'; ?>

<h1 class="nombreProducto"><?= str_replace(' ', '<br>', htmlspecialchars($producto->nombre)) ?></h1>

<div class="detalle-img carrusel" data-id="<?= $producto->id ?>">
    <button class="prev" onclick="cambiarImagenes(<?= $producto->id ?>, -1)">&#10094;</button>
    
    <?php if(count($imagenes) === 0): ?>
        <img src="assets/img/no-image.png" alt="Sin imagen">
    <?php else: ?>
        <?php foreach ($imagenes as $i => $img): ?>
            <img src="assets/img/<?= htmlspecialchars(trim($img)) ?>" alt="Imagen <?= $i + 1 ?>" style="<?= $i === 0 ? 'display:block;' : 'display:none;' ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <button class="next" onclick="cambiarImagenes(<?= $producto->id ?>, 1)">&#10095;</button>
</div>

<div class="card" data-precio="<?= $producto->precio ?>">
  <p class="descripcion"><?= htmlspecialchars($producto->descripcion) ?></p>
  <br><br><br>

  <p class="precio-producto">
    Precio: 
    <?php if ($descuento > 0): ?>
        <span style="text-decoration: line-through; color: #888;">
            $<?= number_format($precioOriginal, 0, '', '.') ?>
        </span>
        &nbsp;
        <span style="color: #a68e6d; font-weight: 700;">
            $<?= number_format($precioConDescuento, 0, '', '.') ?>
        </span>
        <br>
        <small style="color: #a68e6d;">Descuento aplicado: <?= $descuento ?>%</small>
    <?php else: ?>
        $<?= number_format($precioOriginal, 0, '', '.') ?>
    <?php endif; ?>
  </p>

  <div class="selector-talle">
    <label for="selectTalle">Talle:</label>
    <select id="selectTalle" name="talle" required <?= count($tallesDisponibles) === 0 ? 'disabled' : '' ?>>
      <?php if (count($tallesDisponibles) === 0): ?>
          <option value="" disabled selected>No hay talles disponibles</option>
      <?php else: ?>
          <?php foreach ($tallesDisponibles as $talle): ?>
              <option value="<?= htmlspecialchars($talle['talle']) ?>">
                  <?= htmlspecialchars($talle['talle']) ?>
              </option>
          <?php endforeach; ?>
      <?php endif; ?>
    </select>
  </div>

  <div class="botones-carrito" style="margin-top: 15px;">
    <button id="agregarItem" 
      data-id="<?= $producto->id ?>" 
      data-nombre="<?= htmlspecialchars($producto->nombre) ?>" 
      data-precio="<?= $producto->precio ?>" 
      data-imagenes="<?= htmlspecialchars($producto->imagenes) ?>"
      <?= count($tallesDisponibles) === 0 ? 'disabled' : '' ?>>
      Agregar al carrito
    </button>

    <button id="sacarItem" <?= count($tallesDisponibles) === 0 ? 'disabled' : '' ?>>
      Eliminar del carrito
    </button>

    <a href="productos.php" style="margin-left: 10px;">Volver al listado</a>
  </div>
</div>

<div id="toast" style="
    position: fixed;
    top: 100px;
    left: 50%;
    transform: translateX(-50%);
    background-color: rgba(166, 142, 109, 0.53);
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    display: none;
    font-family: 'Montserrat', sans-serif;
    font-weight: 500;
    font-size: 15px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    z-index: 9999;
    max-width: 90%;
    text-align: center;
"></div>

<script>
function mostrarToast(mensaje) {
    const toast = document.getElementById('toast');
    toast.textContent = mensaje;
    toast.style.display = 'block';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}

const selectTalle = document.getElementById('selectTalle');
const agregarBtn = document.getElementById('agregarItem');
const sacarBtn = document.getElementById('sacarItem');

function actualizarBotones() {
    const talle = selectTalle.value;
    if (!talle) {
        agregarBtn.disabled = true;
        sacarBtn.disabled = true;
    } else {
        agregarBtn.disabled = false;
        sacarBtn.disabled = false;
    }
}

selectTalle.addEventListener('change', actualizarBotones);
actualizarBotones();

agregarBtn.addEventListener('click', function () {
    const talleSeleccionado = selectTalle.value;

    if (!talleSeleccionado) {
        mostrarToast("⚠ Por favor seleccioná un talle disponible");
        return;
    }

    const data = {
        id: this.dataset.id,
        nombre: this.dataset.nombre,
        precio: this.dataset.precio,
        imagenes: this.dataset.imagenes,
        talle: talleSeleccionado
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
            mostrarToast("✔ Se agregó '" + result.nombre + "' (Talle: " + result.talle + ")");
            document.getElementById('contador-carrito').textContent = result.cantidad_total;
        } else {
            mostrarToast("⚠ " + result.mensaje);
        }
    })
    .catch(error => {
        mostrarToast("❌ Error al agregar al carrito: " + error.message);
        console.error('Error en fetch:', error);
    });
});

sacarBtn.addEventListener('click', function () {
    const id = agregarBtn.dataset.id;
    const talleSeleccionado = selectTalle.value;

    if (!talleSeleccionado) {
        mostrarToast("⚠ Por favor seleccioná un talle disponible");
        return;
    }

    fetch('ajax_eliminar.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ id: id, talle: talleSeleccionado })
    })
    .then(response => {
        if (!response.ok) throw new Error('Error en la respuesta HTTP');
        return response.json();
    })
    .then(result => {
        if (result.success) {
            mostrarToast("🗑 Producto eliminado del carrito");
            document.getElementById('contador-carrito').textContent = result.cantidad_total;
        } else {
            mostrarToast("⚠ " + result.mensaje);
        }
    })
    .catch(error => {
        mostrarToast("❌ Error al eliminar del carrito: " + error.message);
        console.error('Error en fetch:', error);
    });
});

// CARRUSEL

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
