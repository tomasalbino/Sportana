<?php
require_once __DIR__ . '/clases/Inscripcion.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Tenés que iniciar sesión para inscribir participantes.']);
    exit;
}
if (!in_array($_SESSION['rol'], ['organizador', 'administrador'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Solo organizadores o administradores pueden inscribir participantes.']);
    exit;
}

$idTorneo = isset($_POST['id_torneo']) ? (int) $_POST['id_torneo'] : 0;
$correoParticipante = trim($_POST['correo_participante'] ?? '');
if ($idTorneo <= 0 || $correoParticipante === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Faltan datos: torneo y correo del participante.']);
    exit;
}

try {

    $mysqli = Conexion::obtener('app');
    $stmt = $mysqli->prepare(
        'SELECT p.id_participante FROM participante p JOIN usuario u ON u.id_usuario = p.id_usuario WHERE u.correo = ?'
    );
    $stmt->bind_param('s', $correoParticipante);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($fila === null) {
        throw new InvalidArgumentException('No existe ningún participante registrado con ese correo.');
    }

    $resultado = Inscripcion::crear($idTorneo, (int) $fila['id_participante']);
    echo json_encode(['ok' => true] + $resultado);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
} catch (RuntimeException $e) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('Error inesperado en procesar_inscripcion.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Ocurrió un error. Intentá nuevamente más tarde.']);
}
