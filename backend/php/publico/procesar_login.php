<?php
require_once __DIR__ . '/clases/Usuario.php';

header('Content-Type: application/json; charset=utf-8');

ini_set('session.gc_maxlifetime', 900); 
session_set_cookie_params([
    'lifetime' => 900,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Strict',

]);
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

$nombreUsuario = trim($_POST['usuario'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';

try {
    $usuario = Usuario::login($nombreUsuario, $contrasena);
} catch (\mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error del servidor. Probá de nuevo en un momento.']);
    exit;
} catch (RuntimeException $e) {

    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
}

if ($usuario === null) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Usuario o contraseña incorrectos.']);
    exit;
}

session_regenerate_id(true);

$_SESSION['id_usuario'] = $usuario->idUsuario;
$_SESSION['rol'] = $usuario->rol;

echo json_encode(['ok' => true, 'rol' => $usuario->rol, 'nombre_usuario' => $usuario->nombreUsuario]);
