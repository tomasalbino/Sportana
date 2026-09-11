CREATE USER IF NOT EXISTS 'sportana_app'@'localhost' IDENTIFIED BY 'CAMBIAR_ESTA_CONTRASENA';
GRANT SELECT, INSERT, UPDATE, DELETE ON sportana.* TO 'sportana_app'@'localhost';

CREATE USER IF NOT EXISTS 'sportana_publico'@'localhost' IDENTIFIED BY 'CAMBIAR_ESTA_CONTRASENA';
GRANT SELECT ON sportana.torneo TO 'sportana_publico'@'localhost';
GRANT SELECT ON sportana.modulo_competencia TO 'sportana_publico'@'localhost';
GRANT SELECT ON sportana.ronda TO 'sportana_publico'@'localhost';
GRANT SELECT ON sportana.enfrentamiento TO 'sportana_publico'@'localhost';
GRANT SELECT ON sportana.resultado TO 'sportana_publico'@'localhost';
GRANT SELECT ON sportana.tabla_posiciones TO 'sportana_publico'@'localhost';
GRANT SELECT ON sportana.equipo TO 'sportana_publico'@'localhost';
GRANT SELECT ON sportana.inscripcion TO 'sportana_publico'@'localhost';
GRANT SELECT ON sportana.vista_organizador TO 'sportana_publico'@'localhost';

CREATE USER IF NOT EXISTS 'sportana_participante'@'localhost' IDENTIFIED BY 'CAMBIAR_ESTA_CONTRASENA';
GRANT SELECT ON sportana.torneo TO 'sportana_participante'@'localhost';
GRANT SELECT ON sportana.modulo_competencia TO 'sportana_participante'@'localhost';
GRANT SELECT ON sportana.ronda TO 'sportana_participante'@'localhost';
GRANT SELECT ON sportana.enfrentamiento TO 'sportana_participante'@'localhost';
GRANT SELECT ON sportana.resultado TO 'sportana_participante'@'localhost';
GRANT SELECT ON sportana.tabla_posiciones TO 'sportana_participante'@'localhost';
GRANT SELECT ON sportana.equipo TO 'sportana_participante'@'localhost';
GRANT SELECT, INSERT ON sportana.equipo_participante TO 'sportana_participante'@'localhost';
GRANT SELECT, UPDATE ON sportana.participante TO 'sportana_participante'@'localhost';
GRANT SELECT ON sportana.inscripcion TO 'sportana_participante'@'localhost';

CREATE USER IF NOT EXISTS 'sportana_organizador'@'localhost' IDENTIFIED BY 'CAMBIAR_ESTA_CONTRASENA';
GRANT SELECT, INSERT, UPDATE ON sportana.torneo TO 'sportana_organizador'@'localhost';
GRANT SELECT, INSERT, UPDATE ON sportana.configuracion_torneo TO 'sportana_organizador'@'localhost';
GRANT SELECT, INSERT, UPDATE ON sportana.ronda TO 'sportana_organizador'@'localhost';
GRANT SELECT, INSERT, UPDATE ON sportana.enfrentamiento TO 'sportana_organizador'@'localhost';
GRANT SELECT, INSERT, UPDATE ON sportana.resultado TO 'sportana_organizador'@'localhost';
GRANT SELECT, INSERT, UPDATE ON sportana.equipo TO 'sportana_organizador'@'localhost';
GRANT SELECT, INSERT, UPDATE ON sportana.equipo_participante TO 'sportana_organizador'@'localhost';
GRANT SELECT, INSERT, UPDATE ON sportana.tabla_posiciones TO 'sportana_organizador'@'localhost';
GRANT SELECT ON sportana.modulo_competencia TO 'sportana_organizador'@'localhost';
GRANT SELECT, INSERT, UPDATE ON sportana.inscripcion TO 'sportana_organizador'@'localhost';

CREATE USER IF NOT EXISTS 'sportana_admin'@'localhost' IDENTIFIED BY 'CAMBIAR_ESTA_CONTRASENA';
GRANT SELECT, INSERT, UPDATE, DELETE ON sportana.* TO 'sportana_admin'@'localhost';

FLUSH PRIVILEGES;
