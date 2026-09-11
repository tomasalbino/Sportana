<?php
require_once __DIR__ . '/../config/Conexion.php';

class Inscripcion
{
    public static function crear(int $idTorneo, int $idParticipante): array
    {
        $mysqli = Conexion::obtener('organizador');

        $stmtTorneo = $mysqli->prepare(
            'SELECT inscripciones_abiertas, requiere_aprobacion, fecha_cierre_inscripcion, cupo_maximo
             FROM torneo WHERE id_torneo = ?'
        );
        $stmtTorneo->bind_param('i', $idTorneo);
        $stmtTorneo->execute();
        $torneo = $stmtTorneo->get_result()->fetch_assoc();
        $stmtTorneo->close();

        if ($torneo === null) {
            throw new InvalidArgumentException('El torneo indicado no existe.');
        }
        if ((int) $torneo['inscripciones_abiertas'] !== 1) {
            throw new RuntimeException('Las inscripciones para este torneo no están abiertas.');
        }
        if ($torneo['fecha_cierre_inscripcion'] !== null && $torneo['fecha_cierre_inscripcion'] < date('Y-m-d')) {
            throw new RuntimeException('El plazo de inscripción para este torneo ya cerró.');
        }
        if ($torneo['cupo_maximo'] !== null) {
            $stmtCupo = $mysqli->prepare('SELECT COUNT(*) AS cantidad FROM inscripcion WHERE id_torneo = ?');
            $stmtCupo->bind_param('i', $idTorneo);
            $stmtCupo->execute();
            $cantidad = (int) $stmtCupo->get_result()->fetch_assoc()['cantidad'];
            $stmtCupo->close();
            if ($cantidad >= (int) $torneo['cupo_maximo']) {
                throw new RuntimeException('El torneo ya alcanzó su cupo máximo de inscriptos.');
            }
        }

        $estado = (int) $torneo['requiere_aprobacion'] === 1 ? 'pendiente' : 'aprobada';

        try {
            $stmt = $mysqli->prepare(
                'INSERT INTO inscripcion (id_torneo, id_participante, estado) VALUES (?, ?, ?)'
            );
            $stmt->bind_param('iis', $idTorneo, $idParticipante, $estado);
            $stmt->execute();
            $idInscripcion = $mysqli->insert_id;
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                throw new RuntimeException('Ya estás inscripto en este torneo.');
            }
            throw $e;
        }

        return ['id_inscripcion' => $idInscripcion, 'estado' => $estado];
    }

    public static function contarPorTorneo(int $idTorneo): int
    {
        $mysqli = Conexion::obtener('publico');
        $stmt = $mysqli->prepare('SELECT COUNT(*) AS cantidad FROM inscripcion WHERE id_torneo = ? AND estado != "rechazada"');
        $stmt->bind_param('i', $idTorneo);
        $stmt->execute();
        $cantidad = (int) $stmt->get_result()->fetch_assoc()['cantidad'];
        $stmt->close();
        return $cantidad;
    }
}
