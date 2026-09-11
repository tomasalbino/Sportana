<?php
require_once __DIR__ . '/clases/Usuario.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Tenés que iniciar sesión para editar tu perfil.']);
    exit;
}
if ($_SESSION['rol'] !== 'participante') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Este perfil es solo para participantes.']);
    exit;
}

$nombre = trim($_POST['nombre-perfil'] ?? '');
$apellido = trim($_POST['apellido-perfil'] ?? '');
$correo = trim($_POST['correo-perfil'] ?? '');
$ubicacion = trim($_POST['ubicacion-perfil'] ?? '');

try {
    Usuario::actualizarPerfil((int) $_SESSION['id_usuario'], $nombre, $apellido, $correo, $ubicacion);
} catch (\mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error del servidor. Probá de nuevo en un momento.']);
    exit;
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
} catch (RuntimeException $e) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
}

echo json_encode(['ok' => true]);
