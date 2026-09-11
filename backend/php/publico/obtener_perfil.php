<?php
require_once __DIR__ . '/clases/Usuario.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Tenés que iniciar sesión para ver tu perfil.']);
    exit;
}
if ($_SESSION['rol'] !== 'participante') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Este perfil es solo para participantes.']);
    exit;
}

$perfil = Usuario::obtenerPerfil((int) $_SESSION['id_usuario']);
if ($perfil === null) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Perfil no encontrado.']);
    exit;
}

echo json_encode(['ok' => true, 'perfil' => $perfil]);
