<?php
session_start();

if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    // Mostrar panel admin
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>PANEL ADMIN - MARINE STORE</title>
        <link rel="stylesheet" href="assets/css/admin_login.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Montserrat', sans-serif;
                background-color: #f7f5f0;
                margin: 0;
                padding: 20px;
                text-align: center;
            }
            h2 {
                color: #ffffffae;
                margin-bottom: 25px;
            }
            .admin-opciones {
                margin-top: 30px;
                display: flex;
                justify-content: center;
                gap: 15px;
                flex-wrap: wrap;
            }
            .admin-opciones a {
                background-color: #A68E6D;
                color: white;
                padding: 12px 25px;
                text-decoration: none;
                font-weight: bold;
                border-radius: 5px;
                display: inline-block;
                transition: background-color 0.3s;
            }
            .admin-opciones a:hover {
                background-color: #8b7a53;
            }
            .btn-logout {
                margin-top: 30px;
                display: inline-block;
                background-color: #8b7a53;
                color: white;
                padding: 10px 20px;
                border-radius: 5px;
                text-decoration: none;
                font-weight: bold;
            }
            .btn-logout:hover {
                background-color: #6b603f;
            }
        </style>
    </head>
    <body>
        <h2>Panel de Administración</h2>
        <div class="admin-opciones">
            <a href="agregar_producto.php">➕ Añadir producto</a>
            <a href="modificar_producto.php">✏️ Modificar producto</a>
            <a href="eliminaproductobd.php">🗑️ Eliminar producto</a>
            <a href="registro_ventas.php">📋 Ver registro de ventas</a>
            <a href="estadisticas.php">📊 Ver estadísticas</a>
            <a href="agregar_cupon.php">🏷️ Administrar cupones</a>
            <a href="descuentos.php">← Crear descuentos</a>
            <a href="productos.php">← Volver a productos</a>

        </div>
    </body>
    </html>
    <?php
    exit;
}

// Si no está logueado y viene POST, procesar login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['admin_nombre'] ?? '';
    $dni = $_POST['admin_dni'] ?? '';
    $email = $_POST['admin_email'] ?? '';
    $password = $_POST['admin_pass'] ?? '';

    if (
        $email === 'admin@123.com' &&
        $password === '123' &&
        $nombre === "Florencia Malvica" &&
        $dni === "44335609"
    ) {
        $_SESSION['admin'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "Datos incorrectos";
    }
}

// Mostrar formulario login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/admin_login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f7f5f0;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        h2 {
            color: #e4dfdfba;
            margin-bottom: 25px;
        }
        form {
            background-color: #fff;
            padding: 25px;
            margin: 0 auto 30px auto;
            border-radius: 8px;
            max-width: 350px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        label {
            font-weight: 600;
            color: #5b4a2f;
            display: block;
            margin-bottom: 6px;
            text-align: left;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"] {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 18px;
            border: 1px solid #c9c9c9;
            border-radius: 4px;
            font-size: 15px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="number"]:focus {
            border-color: #a68e6d;
            outline: none;
        }
        button[type="submit"] {
            background-color: #A68E6D;
            color: white;
            padding: 12px 25px;
            font-weight: bold;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        button[type="submit"]:hover {
            background-color: #8b7a53;
        }
        .error {
            color: red;
            margin-bottom: 20px;
            font-weight: 600;
        }
    </style>
</head>
<body>
<h2>Verificación de Administrador</h2>

<?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST" autocomplete="off" novalidate>
    <label for="admin_nombre">Nombre completo:</label>
    <input type="text" id="admin_nombre" name="admin_nombre" required>

    <label for="admin_dni">DNI:</label>
    <input type="text" id="admin_dni" name="admin_dni" required>

    <label for="admin_email">Email:</label>
    <input type="email" id="admin_email" name="admin_email" required>

    <label for="admin_pass">Contraseña:</label>
    <input type="password" id="admin_pass" name="admin_pass" required autocomplete="new-password">

    <button type="submit">Acceder al Registro</button>
</form>
</body>
</html>
