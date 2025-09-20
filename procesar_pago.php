<?php
session_start();
require 'db/conexion.php';
require __DIR__ . '/vendor/autoload.php';

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Resources\Preference\Item;

// Datos del form
$email = $_POST['email'] ?? '';
$direccion = $_POST['direccion'] ?? '';
$provincia = $_POST['provincia'] ?? '';
$coordinar_envio = isset($_POST['coordinar_envio']);
$carrito = $_SESSION['carrito'] ?? [];

if (!$email || empty($carrito)) {
    die("Faltan datos o el carrito está vacío");
}

if (!$coordinar_envio && (empty($direccion) || empty($provincia))) {
    die("Debés ingresar dirección y provincia si no coordinás envío");
}

// Si el correo es admin, redirigir
if ($email === 'admin@123.com') {
    header('Location: login_admin.php');
    exit;
}

// Calcular subtotal de productos
$subtotal = 0;
foreach ($carrito as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}

// Costos de envío según provincia
$envio = 0;
if (!$coordinar_envio) {
    switch ($provincia) {
        case 'CABA': $envio = 1200; break;
        case 'Buenos Aires': $envio = 1500; break;
        case 'Córdoba':
        case 'Santa Fe': $envio = 1800; break;
        default: $envio = 2000; break;
    }
}

// Aplicar cupón si hay
$cupon_aplicado = $_SESSION['cupon'] ?? null;
$tipo_descuento = '';
$valor_descuento = 0;
$envio_gratis = false;
$descuento_total = 0;

if ($cupon_aplicado) {
    $stmt = $conexion->prepare("SELECT * FROM cupones WHERE codigo = ? AND activo = 1");
    $stmt->bind_param("s", $cupon_aplicado);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $cupon = $res->fetch_assoc();
        $hoy = date('Y-m-d');

        if ($cupon['fecha_expiracion'] >= $hoy) {
            $tipo_descuento = $cupon['tipo_descuento'];
            $valor_descuento = $cupon['descuento'];

            if ($tipo_descuento === 'envio_gratis') {
                $envio_gratis = true;
            } elseif ($tipo_descuento === 'porcentaje') {
                $descuento_total = ($valor_descuento / 100) * $subtotal;
            } elseif ($tipo_descuento === 'monto') {
                $descuento_total = min($valor_descuento, $subtotal);
            }
        } else {
            // Cupón expirado
            $cupon_aplicado = null;
            unset($_SESSION['cupon']);
        }
    }
    $stmt->close();
}

// Si el cupón es envío gratis, no se descuenta sobre productos
if ($envio_gratis) {
    $descuento_total = 0;
    $envio = 0;
}

// Total con descuento aplicado (si corresponde)
$total_con_descuento = $subtotal - $descuento_total;
$monto_final = $total_con_descuento + ($coordinar_envio ? 0 : $envio);

// Generar archivo temporal con datos
$tempDir = __DIR__ . "/temp";
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

$external_reference = uniqid('orden_', true);
$tempData = [
    "email" => $email,
    "direccion" => $coordinar_envio ? "Coordinar envío con local" : $direccion,
    "provincia" => $coordinar_envio ? "Coordinar envío con local" : $provincia,
    "envio" => $envio,
    "carrito" => $carrito,
    "total_con_descuento" => $total_con_descuento,
    "monto_final" => $monto_final,
    "coordinar_envio" => $coordinar_envio,
    "cupon" => $cupon_aplicado,
];
file_put_contents("$tempDir/$external_reference.json", json_encode($tempData));

// Crear ítems para Mercado Pago
$items = [];
foreach ($carrito as $producto) {
    $item = new Item();
    $item->title = $producto['nombre'];
    $item->quantity = (int)$producto['cantidad'];

    // Si hay descuento tipo porcentaje o monto, aplicar proporcionalmente
    $precio_unitario = $producto['precio'];
    if (!$envio_gratis && $descuento_total > 0 && $subtotal > 0) {
        $ratio = ($subtotal - $descuento_total) / $subtotal;
        $precio_unitario = $precio_unitario * $ratio;
    }

    $item->unit_price = round($precio_unitario, 2);
    $items[] = $item;
}

// Agregar ítem de envío (si corresponde)
if (!$coordinar_envio && $envio > 0) {
    $envioItem = new Item();
    $envioItem->title = "Costo de Envío";
    $envioItem->quantity = 1;
    $envioItem->unit_price = round($envio, 2);
    $items[] = $envioItem;
}

// Configurar y redirigir a Mercado Pago
MercadoPagoConfig::setAccessToken('TU_ACCESS_TOKEN');
$client = new PreferenceClient();

try {
    $preference = $client->create([
        "items" => $items,
        "external_reference" => $external_reference,
        "notification_url" => "https://tuweb.com/notificacion.php",
        "back_urls" => [
            "success" => "https://tuweb.com/pago_exitoso.php?external_reference=$external_reference",
            "failure" => "https://tuweb.com/pago_fallido.php",
            "pending" => "https://tuweb.com/pago_pendiente.php"
        ],
        "auto_return" => "approved"
    ]);

    header("Location: " . $preference->init_point);
    exit;
} catch (Exception $e) {
    echo "Error al generar el pago: " . $e->getMessage();
}
