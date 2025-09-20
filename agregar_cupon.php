<?php
session_start();
require 'db/conexion.php';

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login_admin.php");
    exit;
}

$error = '';
$creado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear'])) {
    $codigo = strtoupper(trim($_POST['codigo']));
    $envio_gratis = isset($_POST['envio_gratis']) ? intval($_POST['envio_gratis']) : 0;
    $minimo_compra = isset($_POST['minimo_compra']) ? floatval($_POST['minimo_compra']) : 0;
    $fecha_expiracion = $_POST['fecha_expiracion'];
    $fecha_activacion = $_POST['fecha_activacion'] ?? null; // agregado fecha activacion
    $categoria = $_POST['categoria'] ?? null;

    $tipo = $_POST['tipo_descuento'] ?? '';
    $descuento = 0;

    // Validar tipo
    if (!in_array($tipo, ['porcentaje', 'monto', '2x1', '3x2', 'envio_gratis'])) {  // agregué 3x2
        $error = "Debe seleccionar un tipo de descuento válido.";
    }

    // Validar fechas
    if (!$fecha_activacion || !$fecha_expiracion) {
        $error = "Debe ingresar fecha de activación y expiración.";
    } elseif ($fecha_activacion > $fecha_expiracion) {
        $error = "La fecha de activación no puede ser posterior a la de expiración.";
    }

    // Si no es envío gratis ni 2x1 ni 3x2, validar el descuento numérico
    if (!$error && $tipo !== 'envio_gratis' && $tipo !== '2x1' && $tipo !== '3x2') {
        $descuento = isset($_POST['descuento']) ? floatval($_POST['descuento']) : 0;
        if ($descuento <= 0) {
            $error = "Debe ingresar un descuento válido.";
        }
    }

    // Si tipo es 2x1 o 3x2 o envio_gratis → dejar descuento en 0
    if ($tipo === '2x1' || $tipo === '3x2' || $envio_gratis === 1) {
        $descuento = 0;
    }

    if (!$error) {
        $stmt = $conexion->prepare("INSERT INTO cupones (codigo, descuento, tipo_descuento, fecha_expiracion, fecha_activacion, envio_gratis, minimo_compra, categoria, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("sdsssids", $codigo, $descuento, $tipo, $fecha_expiracion, $fecha_activacion, $envio_gratis, $minimo_compra, $categoria);
        $stmt->execute();
        $stmt->close();
        $creado = true;
    }
}

// Activar/desactivar cupón
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['accion'])) {
    $id = intval($_POST['id']);
    $accion = $_POST['accion'] === 'activar' ? 1 : 0;
    $stmt = $conexion->prepare("UPDATE cupones SET activo = ? WHERE id = ?");
    $stmt->bind_param("ii", $accion, $id);
    $stmt->execute();
    $stmt->close();
}

// Eliminar cupón
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar'])) {
    $id = intval($_POST['eliminar']);
    $stmt = $conexion->prepare("DELETE FROM cupones WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

$cupones = $conexion->query("SELECT * FROM cupones ORDER BY creado_en DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Cupones</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat&display=swap" rel="stylesheet" />
  <style>
    body { font-family: 'Montserrat', sans-serif; background: #f7f7f7; padding: 40px; }
    h2 { color: #A68E6D; }
    .btn { padding: 8px 16px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
    .crear { background: #A68E6D; color: white; margin-bottom: 20px; }
    .activar { background: #4CAF50; color: white; }
    .desactivar { background: #f44336; color: white; }
    .eliminar { background: #555; color: white; }
    table {
      background: white; border-collapse: collapse; width: 100%;
      margin-top: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    th, td { padding: 12px; border: 1px solid #ccc; text-align: center; }
    th { background: #A68E6D; color: white; }
    #formulario-cupon {
      display: none; margin-top: 20px; background: white; padding: 20px;
      border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.1);
    }
    input, select {
      padding: 8px; margin: 5px; border-radius: 5px; border: 1px solid #aaa;
    }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
  </style>
</head>
<body>

<h2>🧾 Administración de Cupones</h2>

<?php if ($error): ?>
  <p class="error">⚠️ <?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($creado): ?>
  <p class="success">✅ Cupón creado correctamente.</p>
<?php endif; ?>

<button class="btn crear" onclick="toggleFormulario()">➕ Crear nuevo cupón</button>

<div id="formulario-cupon">
  <form method="POST" id="form-cupon">
    <input type="hidden" name="crear" value="1">

    <label>Código:</label>
    <input type="text" name="codigo" required maxlength="20">

    <label>¿Otorga envío gratis?</label>
    <select name="envio_gratis" id="envio_gratis" onchange="toggleDescuento()">
      <option value="0" selected>No</option>
      <option value="1">Sí</option>
    </select>

    <div id="descuento-fields">
      <label>Descuento:</label>
      <input type="number" step="0.01" name="descuento" min="0" required>

      <label>Tipo:</label>
      <select name="tipo_descuento" required>
        <option value="porcentaje">%</option>
        <option value="monto">$</option>
        <option value="2x1">2x1</option>
        <option value="3x2">3x2</option> <!-- agregado 3x2 -->
      </select>
    </div>

    <label>Categoría (opcional):</label>
    <input type="text" name="categoria" placeholder="Ej: BIKINIS, CALZADO, etc.">

    <label>Monto mínimo de compra ($):</label>
    <input type="number" name="minimo_compra" step="0.01" min="0" placeholder="0.00">

    <label>Fecha de activación:</label> <!-- agregado fecha activacion -->
    <input type="date" name="fecha_activacion" required>

    <label>Fecha de expiración:</label>
    <input type="date" name="fecha_expiracion" required>

    <br><br>
    <button class="btn crear" type="submit">Crear cupón</button>
  </form>
</div>

<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Código</th>
      <th>Descuento</th>
      <th>Tipo</th>
      <th>Envío Gratis</th>
      <th>Categoría</th>
      <th>Mínimo</th>
      <th>Activación</th> <!-- agregado -->
      <th>Expira</th>
      <th>Activo</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
  <?php while ($c = $cupones->fetch_assoc()): ?>
    <tr>
      <td><?= $c['id'] ?></td>
      <td><?= htmlspecialchars($c['codigo']) ?></td>
      <td>
        <?php
        if ($c['tipo_descuento'] === 'envio_gratis') {
            echo '-';
        } elseif ($c['tipo_descuento'] === 'porcentaje') {
            echo $c['descuento'] . '%';
        } elseif ($c['tipo_descuento'] === 'monto') {
            echo '$' . $c['descuento'];
        } elseif ($c['tipo_descuento'] === '2x1') {
            echo '2x1';
        } elseif ($c['tipo_descuento'] === '3x2') {
            echo '3x2'; // agregado 3x2
        }
        ?>
      </td>
      <td><?= htmlspecialchars($c['tipo_descuento']) ?></td>
      <td><?= $c['envio_gratis'] ? '✅' : '❌' ?></td>
      <td><?= htmlspecialchars($c['categoria'] ?: 'Todas') ?></td>
      <td><?= '$' . number_format($c['minimo_compra'], 2, ',', '.') ?></td>
      <td><?= htmlspecialchars($c['fecha_activacion'] ?? '-') ?></td> <!-- agregado -->
      <td><?= htmlspecialchars($c['fecha_expiracion']) ?></td>
      <td><?= $c['activo'] ? '✅' : '❌' ?></td>
      <td>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="id" value="<?= $c['id'] ?>">
          <input type="hidden" name="accion" value="<?= $c['activo'] ? 'desactivar' : 'activar' ?>">
          <button class="btn <?= $c['activo'] ? 'desactivar' : 'activar' ?>">
            <?= $c['activo'] ? 'Desactivar' : 'Activar' ?>
          </button>
        </form>
        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este cupón?');">
          <input type="hidden" name="eliminar" value="<?= $c['id'] ?>">
          <button class="btn eliminar">Eliminar</button>
        </form>
      </td>
    </tr>
  <?php endwhile; ?>
  </tbody>
</table>

<script>
function toggleFormulario() {
  const form = document.getElementById('formulario-cupon');
  form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
}

function toggleDescuento() {
  const envioSelect = document.getElementById('envio_gratis');
  const descuentoFields = document.getElementById('descuento-fields');
  if (envioSelect.value === '1') {
    descuentoFields.style.display = 'none';
    descuentoFields.querySelectorAll('input, select').forEach(el => el.removeAttribute('required'));
  } else {
    descuentoFields.style.display = 'block';
    descuentoFields.querySelectorAll('input, select').forEach(el => el.setAttribute('required', 'required'));
  }
}

document.addEventListener('DOMContentLoaded', () => {
  toggleDescuento();
});
</script>

</body>
</html>
