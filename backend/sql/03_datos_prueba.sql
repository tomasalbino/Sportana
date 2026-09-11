SET NAMES utf8mb4;
USE sportana;

INSERT INTO equipo (nombre, categoria) VALUES
  ('Los Tigres', 'Mayores'),
  ('Aguilas FC', 'Mayores');

INSERT INTO torneo (
    nombre, deporte, id_modulo, rondas_totales, ubicacion,
    descripcion, fecha_inicio, fecha_fin, fecha_cierre_inscripcion,
    cupo_minimo, cupo_maximo, es_publico, requiere_aprobacion, inscripciones_abiertas,
    premio_primero, premio_segundo, premio_tercero, premio_mvp
) VALUES (
    'Copa Verificación 2026', 'Fútbol',
    (SELECT id_modulo FROM modulo_competencia WHERE nombre = 'liga'),
    5, 'Montevideo', 'Torneo cargado como dato de prueba para verificación integral.',
    '2026-11-01', '2026-11-30', '2026-10-25',
    4, 8, 1, 0, 1,
    'Copa', 'Medalla plateada', 'Medalla de bronce', 'Reconocimiento individual'
);
