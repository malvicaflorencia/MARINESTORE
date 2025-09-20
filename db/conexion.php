<?php
$host = "localhost";
$usuario = "root";  // Cambié $user a $usuario para mantener coherencia
$pass = "";
$db = "marine";

$conexion = new mysqli($host, $usuario, $pass, $db);

if ($conexion->connect_errno) {
    die("Error al conectar a la base de datos: " . $conexion->connect_error);
}
?>
