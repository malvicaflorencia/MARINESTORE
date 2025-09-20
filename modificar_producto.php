<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login_admin.php");
    exit;
}
include 'db/conexion.php';

$mensaje = '';
$productoActual = null;
$stocks = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modificar'])) {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $categoria = $_POST['categoria'];
    $imagenesExistentes = json_decode($_POST['imagenes_actuales'], true) ?? [];

    // Eliminar imágenes seleccionadas
    $imagenesAEliminar = $_POST['eliminar_imagen'] ?? [];
    $imagenesFiltradas = array_filter($imagenesExistentes, function ($img) use ($imagenesAEliminar) {
        return !in_array($img, $imagenesAEliminar);
    });

    // Subir nuevas imágenes
    $imagenesNuevas = [];
    if (!empty($_FILES['imagenes']['name'][0])) {
        foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['imagenes']['error'][$key] === 0) {
                $ext = pathinfo($_FILES['imagenes']['name'][$key], PATHINFO_EXTENSION);
                $nombreImagen = uniqid('img_') . '.' . $ext;
                move_uploaded_file($tmp_name, 'assets/img/' . $nombreImagen);
                $imagenesNuevas[] = $nombreImagen;
            }
        }
    }

    // Merge final
    $imagenesFinales = array_values(array_merge($imagenesFiltradas, $imagenesNuevas));
    $imagenesJson = json_encode($imagenesFinales);

    // Guardar producto
    $sql = "UPDATE productos SET nombre=?, precio=?, imagenes=?, categoria=? WHERE id=?";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        die("Error en prepare UPDATE productos: " . $conexion->error);
    }
    $stmt->bind_param("sdssi", $nombre, $precio, $imagenesJson, $categoria, $id);
    $resultadoProd = $stmt->execute();
    if (!$resultadoProd) {
        die("Error en execute UPDATE productos: " . $stmt->error);
    }
    $stmt->close();

    // Depuración: ver qué datos llegan para stocks
    // var_dump($_POST['stock_id'], $_POST['talle'], $_POST['cantidad']);

    // Actualizar talles existentes
    if (isset($_POST['stock_id'])) {
        foreach ($_POST['stock_id'] as $index => $stock_id) {
            $talle = $_POST['talle'][$index];
            $cantidad = $_POST['cantidad'][$index];

            $stmtStock = $conexion->prepare("UPDATE stocks SET talle=?, cantidad=? WHERE id=?");
            if (!$stmtStock) {
                die("Error en prepare UPDATE stocks: " . $conexion->error);
            }
            $stmtStock->bind_param("sii", $talle, $cantidad, $stock_id);
            $resultadoStock = $stmtStock->execute();
            if (!$resultadoStock) {
                die("Error en execute UPDATE stocks: " . $stmtStock->error);
            }
            $stmtStock->close();
        }
    }

    // Agregar nuevos talles
    if (!empty($_POST['nuevo_talle'])) {
        foreach ($_POST['nuevo_talle'] as $index => $nuevoTalle) {
            $nuevaCantidad = $_POST['nueva_cantidad'][$index];
            if ($nuevoTalle !== '' && $nuevaCantidad !== '') {
                $stmtNew = $conexion->prepare("INSERT INTO stocks (producto_id, talle, cantidad) VALUES (?, ?, ?)");
                if (!$stmtNew) {
                    die("Error en prepare INSERT stocks: " . $conexion->error);
                }
                $stmtNew->bind_param("isi", $id, $nuevoTalle, $nuevaCantidad);
                $resultadoNew = $stmtNew->execute();
                if (!$resultadoNew) {
                    die("Error en execute INSERT stocks: " . $stmtNew->error);
                }
                $stmtNew->close();
            }
        }
    }

    $mensaje = "✅ Producto actualizado con éxito.";
}

// Si se seleccionó producto para cargar datos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && !isset($_POST['modificar'])) {
    $idSeleccionado = $_POST['id'];

    $resProd = mysqli_query($conexion, "SELECT * FROM productos WHERE id = $idSeleccionado");
    $productoActual = mysqli_fetch_assoc($resProd);

    $resStocks = mysqli_query($conexion, "SELECT * FROM stocks WHERE producto_id = $idSeleccionado");
    while ($fila = mysqli_fetch_assoc($resStocks)) {
        $stocks[] = $fila;
    }
}

$productos = mysqli_query($conexion, "SELECT * FROM productos");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Producto</title>
    <link rel="icon" href="assets/img/logo.jpg" type="image/png">
    <link rel="stylesheet" href="assets/css/modifprod.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        .mini-img { width: 60px; border-radius: 6px; vertical-align: middle; }
        .stock-row { display: flex; gap: 10px; margin-top: 10px; }
        .stock-row input { flex: 1; }
        .btn { background: #A68E6D; color: white; padding: 10px; border-radius: 5px; text-decoration: none; }
        label { display: block; margin-top: 10px; font-weight: bold; }
    </style>
</head>
<body>
<h2>Modificar Producto</h2>
<?php if ($mensaje) echo "<p>$mensaje</p>"; ?>

<form method="POST">
    <label>Seleccionar producto a modificar:</label>
    <select name="id" onchange="this.form.submit()">
        <option value="">-- Elegí uno --</option>
        <?php while($prod = mysqli_fetch_assoc($productos)): ?>
            <option value="<?= $prod['id'] ?>" <?= (isset($productoActual['id']) && $productoActual['id'] == $prod['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($prod['nombre']) ?>
            </option>
        <?php endwhile; ?>
    </select>
</form>

<?php if ($productoActual): ?>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $productoActual['id'] ?>">
    <input type="hidden" name="modificar" value="1">
    <input type="hidden" name="imagenes_actuales" value='<?= htmlspecialchars($productoActual['imagenes']) ?>'>

    <label>Nombre:</label>
    <input type="text" name="nombre" value="<?= htmlspecialchars($productoActual['nombre']) ?>" required>

    <label>Precio:</label>
    <input type="number" step="0.01" name="precio" value="<?= $productoActual['precio'] ?>" required>

    <label>Categoría:</label>
    <input type="text" name="categoria" value="<?= htmlspecialchars($productoActual['categoria']) ?>" required>

    <label>Imágenes actuales:</label><br>
    <?php
    $imagenes = json_decode($productoActual['imagenes'], true);
    if (is_array($imagenes)) {
        foreach ($imagenes as $img) {
            echo "<label><img src='assets/img/$img' class='mini-img'> ";
            echo "<input type='checkbox' name='eliminar_imagen[]' value='$img'> Eliminar</label><br>";
        }
    } else {
        echo "No hay imágenes.";
    }
    ?>

    <label>Agregar nuevas imágenes:</label>
    <input type="file" name="imagenes[]" multiple>

    <h3>Stock existente</h3>
    <?php foreach ($stocks as $s): ?>
        <div class="stock-row">
            <input type="hidden" name="stock_id[]" value="<?= $s['id'] ?>">
            <input type="text" name="talle[]" value="<?= htmlspecialchars($s['talle']) ?>" required>
            <input type="number" name="cantidad[]" min="0" value="<?= $s['cantidad'] ?>" required>
        </div>
    <?php endforeach; ?>

    <h3>Agregar nuevos talles</h3>
    <div id="nuevos-talles"></div>
    <button type="button" onclick="agregarNuevoTalle()">➕ Añadir talle</button><br><br>

    <button type="submit" class="btn">Guardar Cambios</button>
</form>
<?php endif; ?>

<a href="login_admin.php" class="btn">← Volver</a>

<script>
function agregarNuevoTalle() {
    const div = document.createElement('div');
    div.className = 'stock-row';
    div.innerHTML = `
        <input type="text" name="nuevo_talle[]" placeholder="Nuevo talle" required>
        <input type="number" name="nueva_cantidad[]" placeholder="Cantidad" min="0" required>
    `;
    document.getElementById('nuevos-talles').appendChild(div);
}
</script>
</body>
</html>
