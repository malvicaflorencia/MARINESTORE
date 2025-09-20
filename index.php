<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" href="assets/img/logo.jpg" type="image/png">
    <meta charset="UTF-8" />
    <link rel="stylesheet" href="assets/css/styleindex.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
        <title>MARINE STORE - HOME</title>

</head>
<body>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<div class="marquesina">
  <div class="marquesina-contenedor">
    <div class="marquesina-contenido" id="marquesinaContenido">
      <span>✨ Envíos gratis desde $250.000 | 3 cuotas sin interés ✨</span>
      <span>✨ Envíos gratis desde $250.000 | 3 cuotas sin interés ✨</span>
      <span>✨ Envíos gratis desde $250.000 | 3 cuotas sin interés ✨</span>
      <span>✨ Envíos gratis desde $250.000 | 3 cuotas sin interés ✨</span>
    </div>
  </div>
</div>

<style>
.marquesina {
  width: 100%;
  overflow: hidden;
  background-color: #a68e6d;
  color: white;
  font-family: 'Montserrat', sans-serif;
  font-weight: 500;
  font-size: 15px;
  padding: 10px 0;
  position: relative;
}

.marquesina-contenedor {
  display: flex;
  width: max-content;
  animation: scrollMarquesina 15s linear infinite;
}

.marquesina-contenido {
  display: flex;
  gap: 2px;
  white-space: nowrap;
}
</style>

<script>
window.addEventListener("DOMContentLoaded", () => {
  const contenido = document.getElementById("marquesinaContenido");
  const contenedor = contenido.parentElement;

  // Clonamos el contenido para que parezca un carrusel infinito
  const clone = contenido.cloneNode(true);
  contenedor.appendChild(clone);
});
</script>

<style>
@keyframes scrollMarquesina {
  0% {
    transform: translateX(0%);
  }
  100% {
    transform: translateX(-50%);
  }
}
</style>


<?php include 'includes/header.php'; ?>
<?php
// Si el admin está logueado y entra a una página pública, cerrar sesión admin
if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    unset($_SESSION['admin']);
    session_write_close(); // opcional, pero asegura que se guarde el cambio
}
?>

<!-- Banner principal -->
  <section class="fondorepetido" >
    <div class="overlay"></div>
    <div class="contenido-texto">
      <h1>MARINE STORE</h1>
      <a href="productos.php" class="btn-explore">Explore now</a>
    </div>
  </section>
<br>  
<!-- Sección categorías destacadas -->
<!-- Sección categorías destacadas -->
<section class="seccion-categorias">
    <a href="productos.php?categoria=Denim" class="categoria">
        <img src="assets/img/denim.jpg" alt="Denim">
        <h2>DENIM</h2>
    </a>
    <a href="productos.php?categoria=Sweaters" class="categoria">
        <img src="assets/img/sweaters.jpg" alt="Sweaters">
        <h2>SWEATERS</h2>
    </a>
    <a href="productos.php?categoria=Abrigos" class="categoria">
        <img src="assets/img/abrigos.jpg" alt="Abrigos">
        <h2>ABRIGOS</h2>
    </a>
    <a href="productos.php?categoria=Tops%20y%20Bodys" class="categoria">
        <img src="assets/img/tops.jpg" alt="Tops y Bodys">
        <h2>TOPS Y BODYS</h2>
    </a>
</section>

<?php include 'includes/footer.php'; ?>
</body>