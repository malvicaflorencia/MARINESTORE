<?php
session_start();
unset($_SESSION['carrito']);
unset($_SESSION['cupon']);
unset($_SESSION['total']);
echo "<h1>Pago fallido</h1>";
echo "<p>Tu pago no se pudo completar. Podés intentar nuevamente.</p>";
echo '<a href="index.php">Volver al inicio</a>';
?>
