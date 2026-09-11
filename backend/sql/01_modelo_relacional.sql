CREATE DATABASE IF NOT EXISTS sportana CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sportana;

CREATE TABLE rol (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    nivel_acceso INT NOT NULL DEFAULT 0
);

CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    id_rol INT NOT NULL,
    ubicacion VARCHAR(150),
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rol) REFERENCES rol(id_rol)
) ENGINE=InnoDB;

CREATE TABLE participante (
    id_participante INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL UNIQUE,
    apellido VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE federacion (
    id_federacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    deporte VARCHAR(100) NOT NULL,
    rut VARCHAR(20) NOT NULL UNIQUE,
    representante_legal VARCHAR(150) NOT NULL,
    telefono VARCHAR(20),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE equipo (
    id_equipo INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    categoria VARCHAR(50)
) ENGINE=InnoDB;

CREATE TABLE equipo_participante (
    id_equipo INT NOT NULL,
    id_participante INT NOT NULL,
    rol_equipo VARCHAR(50),
    PRIMARY KEY (id_equipo, id_participante),
    FOREIGN KEY (id_equipo) REFERENCES equipo(id_equipo) ON DELETE CASCADE,
    FOREIGN KEY (id_participante) REFERENCES participante(id_participante) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE modulo_competencia (
    id_modulo INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    activo BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE torneo (
    id_torneo INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    deporte VARCHAR(100) NOT NULL,
    id_modulo INT NOT NULL,
    rondas_totales INT,
    ubicacion VARCHAR(200),
    descripcion TEXT,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    fecha_cierre_inscripcion DATE,
    cupo_minimo INT,
    cupo_maximo INT,
    estado VARCHAR(30) NOT NULL DEFAULT 'planificado',
    es_publico BOOLEAN NOT NULL DEFAULT TRUE,
    requiere_aprobacion BOOLEAN NOT NULL DEFAULT FALSE,
    inscripciones_abiertas BOOLEAN NOT NULL DEFAULT TRUE,
    premio_primero VARCHAR(150),
    premio_segundo VARCHAR(150),
    premio_tercero VARCHAR(150),
    premio_mvp VARCHAR(150),
    id_organizador_usuario INT,
    FOREIGN KEY (id_modulo) REFERENCES modulo_competencia(id_modulo),
    FOREIGN KEY (id_organizador_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;

CREATE TABLE configuracion_torneo (
    id_torneo INT PRIMARY KEY,
    criterio_puntuacion VARCHAR(200),
    criterio_desempate VARCHAR(200),
    duracion_partido VARCHAR(100),
    politica_walkover VARCHAR(200),
    reglas_disciplinarias TEXT,
    reglas_adicionales TEXT,
    FOREIGN KEY (id_torneo) REFERENCES torneo(id_torneo) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ronda (
    id_ronda INT AUTO_INCREMENT PRIMARY KEY,
    id_torneo INT NOT NULL,
    numero INT NOT NULL,
    fecha DATE,
    cerrada BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (id_torneo) REFERENCES torneo(id_torneo) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE enfrentamiento (
    id_enfrentamiento INT AUTO_INCREMENT PRIMARY KEY,
    id_ronda INT NOT NULL,
    id_equipo_local INT NOT NULL,
    id_equipo_visitante INT,
    fecha_hora DATETIME,
    estado VARCHAR(30) NOT NULL DEFAULT 'pendiente',
    FOREIGN KEY (id_ronda) REFERENCES ronda(id_ronda) ON DELETE CASCADE,
    FOREIGN KEY (id_equipo_local) REFERENCES equipo(id_equipo),
    FOREIGN KEY (id_equipo_visitante) REFERENCES equipo(id_equipo)
) ENGINE=InnoDB;

CREATE TABLE resultado (
    id_enfrentamiento INT PRIMARY KEY,
    marcador_local INT NOT NULL,
    marcador_visitante INT NOT NULL,
    validado BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (id_enfrentamiento) REFERENCES enfrentamiento(id_enfrentamiento) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tabla_posiciones (
    id_tabla INT AUTO_INCREMENT PRIMARY KEY,
    id_torneo INT NOT NULL,
    id_equipo INT NOT NULL,
    puntos INT NOT NULL DEFAULT 0,
    partidos_jugados INT NOT NULL DEFAULT 0,
    diferencia_gol INT NOT NULL DEFAULT 0,
    ultima_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_torneo_equipo (id_torneo, id_equipo),
    FOREIGN KEY (id_torneo) REFERENCES torneo(id_torneo) ON DELETE CASCADE,
    FOREIGN KEY (id_equipo) REFERENCES equipo(id_equipo) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE inscripcion (
    id_inscripcion INT AUTO_INCREMENT PRIMARY KEY,
    id_torneo INT NOT NULL,
    id_participante INT NOT NULL,
    fecha_inscripcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    UNIQUE KEY uq_torneo_participante (id_torneo, id_participante),
    FOREIGN KEY (id_torneo) REFERENCES torneo(id_torneo) ON DELETE CASCADE,
    FOREIGN KEY (id_participante) REFERENCES participante(id_participante) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE auditoria (
    id_auditoria INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    accion VARCHAR(200) NOT NULL,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;

CREATE TABLE intento_login (
    id_intento INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) NOT NULL,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    exitoso BOOLEAN NOT NULL,
    INDEX idx_usuario_fecha (nombre_usuario, fecha_hora)
) ENGINE=InnoDB;

CREATE VIEW vista_organizador AS
SELECT u.id_usuario, COALESCE(f.nombre, u.nombre) AS nombre_organizador
FROM usuario u
LEFT JOIN federacion f ON f.id_usuario = u.id_usuario;

INSERT INTO rol (nombre, nivel_acceso) VALUES
    ('participante', 1),
    ('organizador', 2),
    ('administrador', 3);

INSERT INTO modulo_competencia (nombre) VALUES
    ('liga'), ('eliminacion_directa'), ('sistema_suizo');
