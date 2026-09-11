<?php
require_once __DIR__ . '/../config/Conexion.php';

class Torneo
{

    private const MAPA_FORMATO = [
        'liga' => 'liga',
        'eliminacion' => 'eliminacion_directa',
        'suizo' => 'sistema_suizo',
    ];

    public static function crear(array $datos, int $idUsuarioCreador): int
    {
        $nombre = trim($datos['nombre'] ?? '');
        $deporte = trim($datos['deporte'] ?? '');
        $formato = trim($datos['formato'] ?? '');
        $fechaInicio = trim($datos['fecha-inicio'] ?? '');
        $fechaFin = trim($datos['fecha-fin'] ?? '');

        if ($nombre === '' || $deporte === '') {
            throw new InvalidArgumentException('Nombre y deporte son obligatorios.');
        }
        if (!isset(self::MAPA_FORMATO[$formato])) {
            throw new InvalidArgumentException('El módulo de competencia seleccionado no es válido.');
        }
        if ($fechaInicio === '' || $fechaFin === '') {
            throw new InvalidArgumentException('Las fechas de inicio y fin son obligatorias.');
        }
        if ($fechaFin < $fechaInicio) {
            throw new InvalidArgumentException('La fecha de fin no puede ser anterior a la de inicio.');
        }

        $mysqli = Conexion::obtener('organizador');

        $stmtModulo = $mysqli->prepare('SELECT id_modulo FROM modulo_competencia WHERE nombre = ?');
        $nombreModulo = self::MAPA_FORMATO[$formato];
        $stmtModulo->bind_param('s', $nombreModulo);
        $stmtModulo->execute();
        $filaModulo = $stmtModulo->get_result()->fetch_assoc();
        $stmtModulo->close();
        if ($filaModulo === null) {
            throw new RuntimeException('No se encontró el módulo de competencia en la base de datos.');
        }
        $idModulo = (int) $filaModulo['id_modulo'];

        $campos = [
            'nombre'                    => ['s', $nombre],
            'deporte'                   => ['s', $deporte],
            'id_modulo'                 => ['i', $idModulo],
            'rondas_totales'            => ['i', ($datos['rondas'] ?? '') !== '' ? (int) $datos['rondas'] : null],
            'ubicacion'                 => ['s', trim($datos['ubicacion'] ?? '') ?: null],
            'descripcion'               => ['s', trim($datos['descripcion'] ?? '') ?: null],
            'fecha_inicio'              => ['s', $fechaInicio],
            'fecha_fin'                 => ['s', $fechaFin],
            'fecha_cierre_inscripcion'  => ['s', trim($datos['fecha-cierre'] ?? '') ?: null],
            'cupo_minimo'               => ['i', ($datos['cupo-min'] ?? '') !== '' ? (int) $datos['cupo-min'] : null],
            'cupo_maximo'               => ['i', ($datos['cupo-max'] ?? '') !== '' ? (int) $datos['cupo-max'] : null],
            'es_publico'                => ['i', ($datos['es_publico'] ?? '1') === '1' ? 1 : 0],
            'requiere_aprobacion'       => ['i', ($datos['requiere_aprobacion'] ?? '0') === '1' ? 1 : 0],
            'inscripciones_abiertas'    => ['i', ($datos['inscripciones_abiertas'] ?? '1') === '1' ? 1 : 0],
            'premio_primero'            => ['s', trim($datos['premio-primero'] ?? '') ?: null],
            'premio_segundo'            => ['s', trim($datos['premio-segundo'] ?? '') ?: null],
            'premio_tercero'            => ['s', trim($datos['premio-tercero'] ?? '') ?: null],
            'premio_mvp'                => ['s', trim($datos['premio-mvp'] ?? '') ?: null],
            'id_organizador_usuario'    => ['i', $idUsuarioCreador],
        ];

        $columnas = implode(', ', array_keys($campos));
        $placeholders = implode(', ', array_fill(0, count($campos), '?'));
        $tipos = implode('', array_map(fn($c) => $c[0], $campos));
        $valores = array_values(array_map(fn($c) => $c[1], $campos));

        $stmt = $mysqli->prepare("INSERT INTO torneo ($columnas) VALUES ($placeholders)");
        $stmt->bind_param($tipos, ...$valores);
        $stmt->execute();
        $idTorneo = $mysqli->insert_id;
        $stmt->close();

        $camposConfig = [
            'id_torneo'              => ['i', $idTorneo],
            'criterio_puntuacion'    => ['s', trim($datos['criterio-puntuacion'] ?? '') ?: null],
            'criterio_desempate'     => ['s', trim($datos['criterio-desempate'] ?? '') ?: null],
            'duracion_partido'       => ['s', trim($datos['duracion-partido'] ?? '') ?: null],
            'politica_walkover'      => ['s', trim($datos['politica-walkover'] ?? '') ?: null],
            'reglas_disciplinarias'  => ['s', trim($datos['reglas-disciplinarias'] ?? '') ?: null],
            'reglas_adicionales'     => ['s', trim($datos['reglas-adicionales'] ?? '') ?: null],
        ];
        $columnasConfig = implode(', ', array_keys($camposConfig));
        $placeholdersConfig = implode(', ', array_fill(0, count($camposConfig), '?'));
        $tiposConfig = implode('', array_map(fn($c) => $c[0], $camposConfig));
        $valoresConfig = array_values(array_map(fn($c) => $c[1], $camposConfig));

        $stmtConfig = $mysqli->prepare("INSERT INTO configuracion_torneo ($columnasConfig) VALUES ($placeholdersConfig)");
        $stmtConfig->bind_param($tiposConfig, ...$valoresConfig);
        $stmtConfig->execute();
        $stmtConfig->close();

        return $idTorneo;
    }

    public static function obtenerPorId(int $idTorneo): ?array
    {
        $mysqli = Conexion::obtener('publico');
        $stmt = $mysqli->prepare(
            'SELECT t.*, m.nombre AS modulo_nombre, v.nombre_organizador AS organizador
             FROM torneo t
             JOIN modulo_competencia m ON m.id_modulo = t.id_modulo
             LEFT JOIN vista_organizador v ON v.id_usuario = t.id_organizador_usuario
             WHERE t.id_torneo = ? AND t.es_publico = 1'
        );
        $stmt->bind_param('i', $idTorneo);
        $stmt->execute();
        $torneo = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $torneo ?: null;
    }
}
