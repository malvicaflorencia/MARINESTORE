<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login_admin.php");
    exit;
}
include 'db/conexion.php';

$mensaje = "";
$busqueda = $_POST['buscar'] ?? "";

// Eliminar producto entero
if (isset($_POST['eliminar_producto'])) {
    $id = $_POST['producto_id'];
    $conexion->query("DELETE FROM stocks WHERE producto_id = $id");
    $conexion->query("DELETE FROM productos WHERE id = $id");
    $mensaje = "Producto eliminado correctamente.";
}

// Restar stock por talle
if (isset($_POST['restar_stock'])) {
    $id = $_POST['producto_id'];
    $talle = $_POST['talle'];
    $stmt = $conexion->prepare("UPDATE stocks SET cantidad = GREATEST(cantidad - 1, 0) WHERE producto_id = ? AND talle = ?");
    $stmt->bind_param("is", $id, $talle);
    $stmt->execute();
    $stmt->close();
}

// Buscar productos
if ($busqueda !== "") {
    $stmt = $conexion->prepare("SELECT * FROM productos WHERE nombre LIKE ?");
    $param = "%$busqueda%";
    $stmt->bind_param("s", $param);
    $stmt->execute();
    $productos = $stmt->get_result();
} else {
    $productos = $conexion->query("SELECT * FROM productos");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Producto</title>
    <link rel="icon" href="assets/img/logo.jpg" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background: #f2f2f2;
            margin: 0;
            padding: 20px;
        }

        h2 {
            text-align: center;
            color: #A68E6D;
        }

        form.buscar {
            text-align: center;
            margin-bottom: 20px;
        }

        form.buscar input {
            padding: 8px;
            width: 200px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        form.buscar button {
            padding: 8px 16px;
            background-color: #A68E6D;
            border: none;
            color: white;
            border-radius: 5px;
            margin-left: 10px;
            cursor: pointer;
        }

        .mensaje {
            text-align: center;
            color: green;
            font-weight: bold;
        }

        .grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .card {
            background: white;
            border: 1px solid #ccc;
            border-radius: 10px;
            width: 250px;
            overflow: hidden;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        /* Carrusel */
        .carrusel {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
        }

        .carrusel img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: none;
            user-select: none;
        }

        .carrusel img.activo {
            display: block;
        }

        /* Flechas */
        .flecha {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(166, 142, 109, 0.8);
            color: white;
            border: none;
            padding: 6px 10px;
            cursor: pointer;
            border-radius: 3px;
            font-weight: bold;
            font-size: 18px;
            user-select: none;
            z-index: 10;
        }

        .flecha.izquierda {
            left: 5px;
        }

        .flecha.derecha {
            right: 5px;
        }

        .info {
            padding: 15px;
        }

        .info h3 {
            margin: 0;
            font-size: 1.2rem;
            color: #333;
        }

        .info p {
            margin: 5px 0;
            font-size: 14px;
        }

        .stock-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 5px 0;
        }

        .stock-line form {
            margin: 0;
        }

        .stock-line button {
            padding: 4px 8px;
            font-size: 14px;
            background: #A68E6D;
            border: none;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }

        form.eliminar {
            padding: 10px;
            text-align: center;
        }

        form.eliminar button {
            background: #c0392b;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .volver {
            display: block;
            width: fit-content;
            margin: 30px auto 0;
            padding: 10px 20px;
            background: #A68E6D;
            color: white;
            border-radius: 5px;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>

<h2>🗑️ Eliminar Productos o Stock</h2>

<a class="volver" href="login_admin.php">← Volver</a>

<?php if ($mensaje): ?>
    <p class="mensaje"><?= $mensaje ?></p>
<?php endif; ?>

<form method="POST" class="buscar">
    <input type="text" name="buscar" placeholder="Buscar producto..." value="<?= htmlspecialchars($busqueda) ?>">
    <button type="submit">Buscar</button>
</form>

<div class="grid">
    <?php while($producto = $productos->fetch_assoc()): ?>
        <div class="card">
            <div class="carrusel" data-producto-id="<?= $producto['id'] ?>">
                <?php
                $imagenes = $producto['imagenes'];
                $imgs = json_decode($imagenes, true);

                // Si no es array, asumimos que es imagen simple (string)
                if (!is_array($imgs)) {
                    $imgs = [$imagenes];
                }

                foreach ($imgs as $i => $img): ?>
                    <img src="assets/img/<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($producto['nombre']) ?>" class="<?= $i === 0 ? 'activo' : '' ?>">
                <?php endforeach; ?>
                <?php if (count($imgs) > 1): ?>
                    <button class="flecha izquierda" aria-label="Imagen anterior">&lt;</button>
                    <button class="flecha derecha" aria-label="Imagen siguiente">&gt;</button>
                <?php endif; ?>
            </div>

            <div class="info">
                <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
                <p><strong>Precio:</strong> $<?= number_format($producto['precio'], 2) ?></p>
                <div>
                    <strong>Stock:</strong>
                    <?php
                        $id = $producto['id'];
                        $stocks = $conexion->query("SELECT * FROM stocks WHERE producto_id = $id");
                        if ($stocks->num_rows === 0) {
                            echo "<p>Sin stock</p>";
                        } else {
                            while ($s = $stocks->fetch_assoc()):
                    ?>
                    <div class="stock-line">
                        <span><?= htmlspecialchars($s['talle']) ?>: <?= $s['cantidad'] ?></span>
                        <form method="POST">
                            <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">
                            <input type="hidden" name="talle" value="<?= $s['talle'] ?>">
                            <button type="submit" name="restar_stock">-1</button>
                        </form>
                    </div>
                    <?php endwhile; } ?>
                </div>
            </div>

            <form method="POST" class="eliminar" onsubmit="return confirm('¿Eliminar el producto completo?')">
                <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">
                <button type="submit" name="eliminar_producto">Eliminar Producto</button>
            </form>
        </div>
    <?php endwhile; ?>
</div>

<script>
// Funcionalidad simple para el carrusel
document.querySelectorAll('.carrusel').forEach(carrusel => {
    const imgs = carrusel.querySelectorAll('img');
    if (imgs.length <= 1) return; // Si hay 1 o menos, nada que hacer

    let idx = 0;
    const mostrarImg = (nuevoIdx) => {
        imgs[idx].classList.remove('activo');
        idx = nuevoIdx;
        imgs[idx].classList.add('activo');
    };

    carrusel.querySelector('.flecha.izquierda').addEventListener('click', () => {
        const nuevoIdx = (idx - 1 + imgs.length) % imgs.length;
        mostrarImg(nuevoIdx);
    });

    carrusel.querySelector('.flecha.derecha').addEventListener('click', () => {
        const nuevoIdx = (idx + 1) % imgs.length;
        mostrarImg(nuevoIdx);
    });
});
</script>

</body>
</html>
