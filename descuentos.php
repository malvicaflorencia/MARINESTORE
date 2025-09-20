<?php
session_start();
require 'db/conexion.php';

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login_admin.php");
    exit;
}

$error = '';
$exito = '';

// Crear o actualizar descuento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['crear'])) {
        // Crear nuevo descuento
        $aplicar_a = $_POST['aplicar_a'] ?? '';
        $id_producto = isset($_POST['producto']) && $_POST['producto'] !== '' ? intval($_POST['producto']) : 0;
        $categoria = trim($_POST['categoria'] ?? '');
        $descuento = floatval($_POST['descuento'] ?? 0);
        $tipo_descuento = $_POST['tipo_descuento'] ?? '';
        
        if ($descuento <= 0) {
            $error = "Debe ingresar un descuento válido mayor a 0.";
        } elseif ($tipo_descuento !== 'porcentaje' && $tipo_descuento !== 'monto') {
            $error = "Tipo de descuento inválido.";
        } elseif ($aplicar_a !== 'producto' && $aplicar_a !== 'categoria') {
            $error = "Debe elegir aplicar a producto o categoría.";
        } elseif ($aplicar_a === 'producto' && $id_producto <= 0) {
            $error = "Debe seleccionar un producto válido.";
        } elseif ($aplicar_a === 'categoria' && empty($categoria)) {
            $error = "Debe ingresar una categoría válida.";
        }

        if (!$error) {
            $producto_db = $aplicar_a === 'producto' ? $id_producto : 0;
            $categoria_db = $aplicar_a === 'categoria' ? $categoria : "";

            $stmt = $conexion->prepare("INSERT INTO descuentos_aplicados (producto_id, categoria, descuento, tipo_descuento, activo, creado_en) VALUES (?, ?, ?, ?, 1, NOW())");
            if (!$stmt) {
                $error = "Error en la consulta: " . $conexion->error;
            } else {
                $stmt->bind_param("isds", $producto_db, $categoria_db, $descuento, $tipo_descuento);
                if ($stmt->execute()) {
                    $exito = "Descuento aplicado correctamente.";
                } else {
                    $error = "Error al aplicar el descuento.";
                }
                $stmt->close();
            }
        }
    } elseif (isset($_POST['editar'])) {
        // Editar descuento
        $id = intval($_POST['id']);
        $descuento = floatval($_POST['descuento'] ?? 0);
        $tipo_descuento = $_POST['tipo_descuento'] ?? '';
        $activo = isset($_POST['activo']) ? 1 : 0;

        if ($descuento <= 0) {
            $error = "Debe ingresar un descuento válido mayor a 0.";
        } elseif ($tipo_descuento !== 'porcentaje' && $tipo_descuento !== 'monto') {
            $error = "Tipo de descuento inválido.";
        }

        if (!$error) {
            $stmt = $conexion->prepare("UPDATE descuentos_aplicados SET descuento = ?, tipo_descuento = ?, activo = ?, actualizado_en = NOW() WHERE id = ?");
            if (!$stmt) {
                $error = "Error en la consulta: " . $conexion->error;
            } else {
                $stmt->bind_param("dsii", $descuento, $tipo_descuento, $activo, $id);
                if ($stmt->execute()) {
                    $exito = "Descuento actualizado correctamente.";
                } else {
                    $error = "Error al actualizar el descuento.";
                }
                $stmt->close();
            }
        }
    } elseif (isset($_POST['eliminar'])) {
        // Eliminar descuento
        $id = intval($_POST['eliminar']);
        $stmt = $conexion->prepare("DELETE FROM descuentos_aplicados WHERE id = ?");
        if (!$stmt) {
            $error = "Error en la consulta: " . $conexion->error;
        } else {
            if ($stmt->execute()) {
                $exito = "Descuento eliminado correctamente.";
            } else {
                $error = "Error al eliminar el descuento.";
            }
            $stmt->close();
        }
    }
}

// Obtener lista de descuentos aplicados
$descuentos = $conexion->query("
    SELECT d.*, p.nombre AS nombre_producto
    FROM descuentos_aplicados d
    LEFT JOIN productos p ON d.producto_id = p.id
    ORDER BY d.creado_en DESC
");

// Obtener lista de productos para el formulario
$productos = $conexion->query("SELECT id, nombre FROM productos ORDER BY nombre ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Administración de Descuentos</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Montserrat', sans-serif; padding: 40px; background: #f7f7f7; }
  h2 { color: #A68E6D; }
  table { border-collapse: collapse; width: 100%; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
  th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
  th { background: #A68E6D; color: white; }
  input[type="text"], input[type="number"], select { padding: 6px; width: 100%; box-sizing: border-box; margin-bottom: 6px; border-radius: 5px; border: 1px solid #aaa; }
  button { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
  button.crear { background-color: #A68E6D; color: white; margin-bottom: 20px; }
  button.guardar { background-color: #4CAF50; color: white; }
  button.eliminar { background-color: #f44336; color: white; }
  button.cancelar { background-color: #555; color: white; }
  form.inline { display: inline-block; margin: 0 4px; }
  .success { color: green; font-weight: bold; margin-bottom: 15px; }
  .error { color: red; font-weight: bold; margin-bottom: 15px; }
  #form-nuevo-descuento { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.1); margin-bottom: 40px; }
  #form-nuevo-descuento label { font-weight: bold; }
  .switch { position: relative; display: inline-block; width: 40px; height: 22px; }
  .switch input { display:none; }
  .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 22px; }
  .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 2px; bottom: 2px; background-color: white; transition: .4s; border-radius: 50%; }
  input:checked + .slider { background-color: #4CAF50; }
  input:checked + .slider:before { transform: translateX(18px); }
</style>
</head>
<body>

<h2>🛍️ Administración de Descuentos</h2>

<?php if ($error): ?>
  <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($exito): ?>
  <p class="success"><?= htmlspecialchars($exito) ?></p>
<?php endif; ?>

<div id="form-nuevo-descuento">
  <h3>Crear nuevo descuento</h3>
  <form method="POST">
    <input type="hidden" name="crear" value="1">

    <label>Aplicar a:</label>
    <select name="aplicar_a" id="aplicar_a" onchange="toggleCampos()">
      <option value="">-- Seleccionar --</option>
      <option value="producto">Producto</option>
      <option value="categoria">Categoría</option>
    </select>

    <div id="campo-producto" style="display:none;">
      <label>Producto:</label>
      <select name="producto">
        <option value="">-- Seleccionar producto --</option>
        <?php while ($p = $productos->fetch_assoc()): ?>
          <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div id="campo-categoria" style="display:none;">
      <label>Categoría:</label>
      <input type="text" name="categoria" placeholder="Ej: BIKINIS, CALZADO, ETC">
    </div>

    <label>Descuento:</label>
    <input type="number" step="0.01" name="descuento" min="0.01" required>

    <label>Tipo de descuento:</label>
    <select name="tipo_descuento" required>
      <option value="porcentaje">Porcentaje (%)</option>
      <option value="monto">Monto fijo ($)</option>
    </select>

    <br>
    <button type="submit" class="crear">Aplicar descuento</button>
  </form>
</div>

<h3>Descuentos aplicados</h3>

<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Producto</th>
      <th>Categoría</th>
      <th>Descuento</th>
      <th>Tipo</th>
      <th>Activo</th>
      <th>Creado</th>
      <th>Actualizado</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($d = $descuentos->fetch_assoc()): ?>
      <tr>
        <form method="POST" class="inline">
          <td><?= $d['id'] ?><input type="hidden" name="id" value="<?= $d['id'] ?>"></td>
          <td><?= htmlspecialchars($d['nombre_producto'] ?: '-') ?></td>
          <td><?= htmlspecialchars($d['categoria'] ?: '-') ?></td>
          <td><input type="number" step="0.01" name="descuento" value="<?= $d['descuento'] ?>" min="0.01" required></td>
          <td>
            <select name="tipo_descuento" required>
              <option value="porcentaje" <?= $d['tipo_descuento'] === 'porcentaje' ? 'selected' : '' ?>>Porcentaje (%)</option>
              <option value="monto" <?= $d['tipo_descuento'] === 'monto' ? 'selected' : '' ?>>Monto fijo ($)</option>
            </select>
          </td>
          <td>
            <label class="switch">
              <input type="checkbox" name="activo" <?= $d['activo'] ? 'checked' : '' ?>>
              <span class="slider"></span>
            </label>
          </td>
          <td><?= $d['creado_en'] ?></td>
          <td><?= $d['actualizado_en'] ?></td>
          <td>
            <button type="submit" name="editar" class="guardar">Guardar</button>
        </form>
            <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar este descuento?');">
              <input type="hidden" name="eliminar" value="<?= $d['id'] ?>">
              <button type="submit" class="eliminar">Eliminar</button>
            </form>
          </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<script>
function toggleCampos() {
  var aplicar = document.getElementById('aplicar_a').value;
  document.getElementById('campo-producto').style.display = (aplicar === 'producto') ? 'block' : 'none';
  document.getElementById('campo-categoria').style.display = (aplicar === 'categoria') ? 'block' : 'none';
}
</script>

</body>
</html>
