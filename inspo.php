<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" href="assets/img/logo.jpg" type="image/png">
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/inspo.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
        <title>MARINE STORE - INSPO</title>


    <div class="breadcrumb" style="margin: 20px;">
    </div> 

<?php include 'includes/header.php'?>
<?php

// Si el admin está logueado y entra a una página pública, cerrar sesión admin
if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    unset($_SESSION['admin']);
    session_write_close(); // opcional, pero asegura que se guarde el cambio
}
?>

<section class="seccion-inspo">
  <div class="inspo-grid">
    <img src="assets/img/inspo1.jpg" alt="Inspiración 1" />
    <img src="assets/img/inspo2.jpg" alt="Inspiración 2" />
    <img src="assets/img/inspo3.jpg" alt="Inspiración 3" />
    <img src="assets/img/inspo4.jpg" alt="Inspiración 4" />
    <img src="assets/img/inspo5.jpg" alt="Inspiración 5" />
    <img src="assets/img/inspo6.jpg" alt="Inspiración 6" />
    <img src="assets/img/inspo7.jpg" alt="Inspiración 7" />
    <img src="assets/img/inspo8.jpg" alt="Inspiración 8" />
    <img src="assets/img/inspo9.jpg" alt="Inspiración 9" />
    <img src="assets/img/inspo10.jpg" alt="Inspiración 10" />
    <img src="assets/img/inspo11.jpg" alt="Inspiración 11" />
  </div>
</section>
<?php include 'includes/footer.php'?>