<?php
require_once __DIR__ . '/clases/Usuario.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$usuario = trim($_POST['usuario'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';
$confirmar = $_POST['confirmar_contrasena'] ?? '';

if ($contrasena !== $confirmar) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Las contraseñas no coinciden.']);
    exit;
}

try {
    $id = Usuario::registrar($nombre, $apellido, $correo, $usuario, $contrasena);
    echo json_encode(['ok' => true, 'id_usuario' => $id]);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
} catch (RuntimeException $e) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {

    error_log('Error inesperado en procesar_registro.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Ocurrió un error. Intentá nuevamente más tarde.']);
}
