<?php
// Desactivar visualización de errores en producción
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers para forzar respuesta JSON
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Respuesta inicial
$response = [
    'success' => false,
    'message' => 'Error desconocido',
    'errors' => []
];

try {
    // Verificar método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido', 405);
    }

    // Obtener datos del POST
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Si falla el JSON, intentar con POST normal
    if (json_last_error() !== JSON_ERROR_NONE) {
        $data = $_POST;
    }

    // Validar campos requeridos
    $requiredFields = ['email', 'password'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $response['errors'][$field] = "Este campo es requerido";
        }
    }

    // Validar formato de email
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $response['errors']['email'] = "Correo electrónico inválido";
    }

    // Si hay errores de validación
    if (!empty($response['errors'])) {
        $response['message'] = "Error de validación";
        throw new Exception('Validation error', 400);
    }

    // Configuración de la base de datos (AJUSTA ESTOS VALORES)
    $dbHost = 'localhost';
    $dbName = 'lensicnc';
    $dbUser = 'root';
    $dbPass = '';

    // Conexión a la base de datos
    $conn = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Buscar usuario por email
    $stmt = $conn->prepare("SELECT id, nombre, email, password FROM users WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $data['email']);
    $stmt->execute();

    if ($stmt->rowCount() === 1) {
        $user = $stmt->fetch();
        
        // Verificar contraseña
        if (password_verify($data['password'], $user['password'])) {
            // Iniciar sesión
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            
            $response['success'] = true;
            $response['message'] = "Inicio de sesión exitoso";
            $response['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ];
        } else {
            $response['errors']['password'] = "Contraseña incorrecta";
            $response['message'] = "Credenciales inválidas";
            throw new Exception('Invalid credentials', 401);
        }
    } else {
        $response['errors']['email'] = "No existe una cuenta con este correo";
        $response['message'] = "Credenciales inválidas";
        throw new Exception('Invalid credentials', 401);
    }
} catch (PDOException $e) {
    $response['message'] = "Error de base de datos: " . $e->getMessage();
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code($e->getCode() ?: 400);
}

// Asegurar que no haya salida antes de este punto
ob_clean();

// Establecer código de respuesta HTTP si no se ha establecido
if (!http_response_code()) {
    http_response_code($response['success'] ? 200 : 500);
}

// Devolver respuesta JSON
echo json_encode($response);
exit;
?>