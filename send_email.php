<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtén los datos del formulario
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $message = isset($_POST['message']) ? $_POST['message'] : '';

    // Configurar el correo
    $to = "jimenardzza@gmail.com"; // Cambia esto por tu correo
    $subject = "Nuevo mensaje desde el formulario de contacto";
    $message_body = "Nombre: $name\nEmail: $email\nTeléfono: $phone\nMensaje: $message";

    // Cabeceras
    $headers = "From: no-jimenardzza@gmail.com\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Enviar el correo
    if (mail($to, $subject, $message_body, $headers)) {
        // Respuesta exitosa
        echo json_encode(['status' => 'success', 'message' => 'Correo enviado correctamente.']);
    } else {
        // Error al enviar el correo
        echo json_encode(['status' => 'error', 'message' => 'Hubo un error al enviar el correo.']);
    }
} else {
    // Si no es una solicitud POST
    echo json_encode(['status' => 'error', 'message' => 'Solicitud no válida.']);
}
?>
