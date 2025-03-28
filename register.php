<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Respuesta inicial
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del POST
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Si no se recibió JSON, intentar con POST normal
    if (!$data) {
        $data = $_POST;
    }

    // Validar datos requeridos
    $requiredFields = ['name', 'email', 'password'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $response['errors'][$field] = "Este campo es requerido";
        }
    }

    // Validar formato de email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $response['errors']['email'] = "Correo electrónico inválido";
    }

    // Validar longitud de contraseña
    if (strlen($data['password']) < 6) {
        $response['errors']['password'] = "La contraseña debe tener al menos 6 caracteres";
    }

    // Si no hay errores de validación, proceder con el registro
    if (empty($response['errors'])) {
        // Configuración de la base de datos
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "lensicnc";

        try {
            // Crear conexión
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Verificar si el email ya existe
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->bindParam(':email', $data['email']);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $response['errors']['email'] = "Este correo ya está registrado";
            } else {
                // Hash de la contraseña
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

                // Insertar nuevo usuario
                $stmt = $conn->prepare("INSERT INTO users (nombre, email, password, created_at) 
                                        VALUES (:name, :email, :password, NOW())");
                $stmt->bindParam(':name', $data['name']);
                $stmt->bindParam(':email', $data['email']);
                $stmt->bindParam(':password', $hashedPassword);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = "Registro exitoso. Serás redirigido...";
                } else {
                    $response['message'] = "Error al registrar el usuario";
                }
            }
        } catch(PDOException $e) {
            $response['message'] = "Error de conexión: " . $e->getMessage();
        }
        
        $conn = null; // Cerrar conexión
    }
} else {
    $response['message'] = "Método no permitido";
}

// Establecer código de respuesta HTTP
http_response_code($response['success'] ? 200 : ($response['errors'] ? 400 : 500));

// Devolver respuesta JSON
echo json_encode($response);
?>