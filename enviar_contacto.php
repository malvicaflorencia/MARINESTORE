<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = htmlspecialchars($_POST['nombre']);
    $email = htmlspecialchars($_POST['email']);
    $mensaje = htmlspecialchars($_POST['mensaje']);

    // CONFIGURA TU CORREO AQUÍ
    $destinatario = "flormalvica02@gmail.com";  // ← poné tu mail real
    $asunto = "Nuevo mensaje desde el formulario de contacto";

    $contenido = "
    Has recibido un nuevo mensaje desde tu sitio web:\n\n
    Nombre: $nombre\n
    Email: $email\n
    Mensaje:\n$mensaje
    ";

    $headers = "From: contacto@tuweb.com\r\n";  // Este remitente debe ser válido en algunos hostings
    $headers .= "Reply-To: $email\r\n";

    if (mail($destinatario, $asunto, $contenido, $headers)) {
        echo "OK";
    } else {
        echo "Error al enviar el mensaje.";
    }
} else {
    echo "Acceso no permitido.";
}
