<?php
session_start();
require 'db/conexion.php'; // Ajustá el path si hace falta

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $precio = $_POST['precio'] ?? 0;
    $categoria = $_POST['categoria'] ?? '';

    $talles = $_POST['talle'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];

    if (!$nombre || !$descripcion || !$precio || !$categoria || !isset($_FILES['imagenes'])) {
        echo "<p style='color:red;'>Faltan datos obligatorios.</p>";
    } else {
        // Procesar imágenes múltiples
        $imagenesSubidas = [];
        $totalArchivos = count($_FILES['imagenes']['name']);
        for ($i = 0; $i < $totalArchivos; $i++) {
            if ($_FILES['imagenes']['error'][$i] === 0) {
                $ext = pathinfo($_FILES['imagenes']['name'][$i], PATHINFO_EXTENSION);
                $nombreImagen = uniqid('img_') . '.' . $ext;
                $destino = 'assets/img/' . $nombreImagen;

                if (move_uploaded_file($_FILES['imagenes']['tmp_name'][$i], $destino)) {
                    $imagenesSubidas[] = $nombreImagen;
                }
            }
        }

        if (count($imagenesSubidas) === 0) {
            echo "<p style='color:red;'>Error al subir las imágenes.</p>";
        } else {
            $imagenesJSON = json_encode($imagenesSubidas);

            $stmt = $conexion->prepare("INSERT INTO productos (nombre, descripcion, precio, imagenes, categoria) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                echo "<p style='color:red;'>Error en prepare productos: " . $conexion->error . "</p>";
                exit;
            }
            $stmt->bind_param("ssdss", $nombre, $descripcion, $precio, $imagenesJSON, $categoria);
            $stmt->execute();

            if ($stmt->affected_rows === 0) {
                echo "<p style='color:red;'>No se pudo insertar el producto.</p>";
                exit;
            }

            $id_producto = $conexion->insert_id;
            $stmt->close();

            $stmt_stock = $conexion->prepare("INSERT INTO stocks (producto_id, talle, cantidad) VALUES (?, ?, ?)");
            if (!$stmt_stock) {
                echo "<p style='color:red;'>Error en prepare stocks: " . $conexion->error . "</p>";
                exit;
            }

            for ($i = 0; $i < count($talles); $i++) {
                $talle = $talles[$i];
                $cantidad = (int)$cantidades[$i];
                if ($cantidad <= 0) continue;

                $stmt_stock->bind_param("isi", $id_producto, $talle, $cantidad);
                $stmt_stock->execute();
            }
            $stmt_stock->close();

            echo "<p style='color:green;'>Producto agregado correctamente.</p>";
        }
    }
}

// Para mostrar los productos con carrusel
$productos = mysqli_query($conexion, "SELECT * FROM productos");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Agregar Producto con Carrusel de Imágenes</title>
            <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            margin: 20px;
            background: #f9f9f9;
        }
        label { font-weight: bold; display: block; margin-top: 10px; }
        input[type="text"], input[type="number"], textarea, select {
            width: 100%; padding: 6px; margin-top: 5px;
            box-sizing: border-box;
        }
        .stock-row { display: flex; gap: 10px; margin-top: 10px; }
        .stock-row input { flex: 1; }
        button { background-color: #A68E6D; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #8b7a53; }
        .volver { text-decoration: none; display: inline-block; margin-bottom: 20px; }

        /* Carrusel */
        .carrusel {
            position: relative;
            width: 300px;
            margin: 10px auto;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .carrusel img {
            width: 100%;
            display: none;
        }
        .carrusel img.active {
            display: block;
        }
        .prev, .next {
            cursor: pointer;
            position: absolute;
            top: 50%;
            padding: 8px;
            margin-top: -20px;
            color: white;
            font-weight: bold;
            font-size: 24px;
            background-color: rgba(0,0,0,0.5);
            border-radius: 50%;
            user-select: none;
        }
        .prev { left: 10px; }
        .next { right: 10px; }
        .producto {
            margin-bottom: 30px;
            padding: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 6px rgba(0,0,0,0.1);
            max-width: 320px;
        }
        .producto h3 {
            margin: 5px 0;
            font-size: 1.2rem;
            color: #A68E6D;
        }
        .producto p {
            font-size: 0.9rem;
            color: #444;
        }
        .productos-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
    </style>
</head>
<body>

<h2>Agregar Producto con Carrusel de Imágenes</h2>
<a href="login_admin.php" class="volver">← Volver</a>

<form method="POST" enctype="multipart/form-data">
    <label>Nombre:</label>
    <input type="text" name="nombre" required>

    <label>Descripción:</label>
    <textarea name="descripcion" required></textarea>

    <label>Precio:</label>
    <input type="number" step="0.01" name="precio" required>

    <label>Categoría:</label>
    <input type="text" name="categoria" required placeholder="Ejemplo: BIKINIS, ABRIGOS, CALZADO...">

    <label>Imágenes (puedes seleccionar varias):</label>
    <input type="file" name="imagenes[]" accept="image/*" multiple required>

    <h3>Stock por talle</h3>
    <div id="stocks-container">
        <div class="stock-row">
            <input type="text" name="talle[]" placeholder="Talle" required>
            <input type="number" name="cantidad[]" min="0" placeholder="Cantidad" required>
            <button type="button" onclick="this.parentNode.remove()">Eliminar</button>
        </div>
    </div>
    <button type="button" onclick="agregarStock()">Añadir talle</button><br><br>

    <button type="submit">Agregar Producto</button>
</form>

<h2>Listado de Productos</h2>
<div class="productos-container">
    <?php while ($prod = mysqli_fetch_assoc($productos)): ?>
        <?php
        $imagenes = json_decode($prod['imagenes'], true) ?: [];
        ?>
        <div class="producto">
            <h3><?= htmlspecialchars($prod['nombre']) ?></h3>
            <p><strong>Categoría:</strong> <?= htmlspecialchars($prod['categoria']) ?></p>
            <p><?= htmlspecialchars($prod['descripcion']) ?></p>
            <p><strong>Precio:</strong> $<?= number_format($prod['precio'], 2) ?></p>

            <?php if (count($imagenes) > 0): ?>
                <div class="carrusel" data-id="<?= $prod['id'] ?>">
                    <button class="prev" onclick="cambiarImagen(<?= $prod['id'] ?>, -1)">&#10094;</button>
                    <?php foreach ($imagenes as $i => $img): ?>
                        <img src="assets/img/<?= htmlspecialchars($img) ?>" class="<?= $i === 0 ? 'active' : '' ?>">
                    <?php endforeach; ?>
                    <button class="next" onclick="cambiarImagen(<?= $prod['id'] ?>, 1)">&#10095;</button>
                </div>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
</div>

<script>
    const carruseles = {};

    document.querySelectorAll('.carrusel').forEach(carrusel => {
        const id = carrusel.getAttribute('data-id');
        carruseles[id] = {
            index: 0,
            slides: carrusel.querySelectorAll('img')
        };
    });

    function cambiarImagen(id, n) {
        const c = carruseles[id];
        c.slides[c.index].classList.remove('active');
        c.index += n;
        if (c.index < 0) c.index = c.slides.length - 1;
        if (c.index >= c.slides.length) c.index = 0;
        c.slides[c.index].classList.add('active');
    }

    function agregarStock() {
        const container = document.getElementById('stocks-container');
        const div = document.createElement('div');
        div.className = 'stock-row';
        div.innerHTML = `
            <input type="text" name="talle[]" placeholder="Talle" required>
            <input type="number" name="cantidad[]" min="0" placeholder="Cantidad" required>
            <button type="button" onclick="this.parentNode.remove()">Eliminar</button>
        `;
        container.appendChild(div);
    }
</script>

</body>
</html>
