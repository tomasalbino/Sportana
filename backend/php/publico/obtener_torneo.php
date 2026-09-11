<?php
require_once __DIR__ . '/clases/Torneo.php';
require_once __DIR__ . '/clases/Inscripcion.php';

header('Content-Type: application/json; charset=utf-8');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Falta indicar el id del torneo.']);
    exit;
}

$torneo = Torneo::obtenerPorId($id);
if ($torneo === null) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Torneo no encontrado.']);
    exit;
}

$torneo['inscritos'] = Inscripcion::contarPorTorneo($id);
echo json_encode(['ok' => true, 'torneo' => $torneo]);
