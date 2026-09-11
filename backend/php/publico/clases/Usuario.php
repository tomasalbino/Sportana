<?php
require_once __DIR__ . '/../config/Conexion.php';

class Usuario
{
    public int $idUsuario;
    public string $nombreUsuario;
    public string $nombre;
    public string $correo;
    public string $rol;

    public static function registrar(string $nombre, string $apellido, string $correo, string $nombreUsuario, string $password): int
    {

        if (trim($nombre) === '' || trim($apellido) === '') {
            throw new InvalidArgumentException('Nombre y apellido son obligatorios.');
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El correo electrónico no es válido.');
        }
        if (strlen($nombreUsuario) < 3) {
            throw new InvalidArgumentException('El nombre de usuario debe tener al menos 3 caracteres.');
        }
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('La contraseña debe tener al menos 8 caracteres.');
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw new InvalidArgumentException('La contraseña debe incluir al menos una mayúscula.');
        }
        if (!preg_match('/[a-z]/', $password)) {
            throw new InvalidArgumentException('La contraseña debe incluir al menos una minúscula.');
        }
        if (!preg_match('/[0-9]/', $password)) {
            throw new InvalidArgumentException('La contraseña debe incluir al menos un número.');
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            throw new InvalidArgumentException('La contraseña debe incluir al menos un carácter especial.');
        }

        $mysqli = Conexion::obtener();

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $mysqli->begin_transaction();
        try {
            $stmt = $mysqli->prepare(
                'INSERT INTO usuario (nombre_usuario, nombre, correo, password_hash, id_rol)
                 VALUES (?, ?, ?, ?, (SELECT id_rol FROM rol WHERE nombre = ?))'
            );
            $rolParticipante = 'participante';
            $stmt->bind_param('sssss', $nombreUsuario, $nombre, $correo, $hash, $rolParticipante);
            $stmt->execute();
            $idUsuario = $mysqli->insert_id;
            $stmt->close();

            $stmt2 = $mysqli->prepare(
                'INSERT INTO participante (id_usuario, apellido) VALUES (?, ?)'
            );
            $stmt2->bind_param('is', $idUsuario, $apellido);
            $stmt2->execute();
            $stmt2->close();

            $mysqli->commit();
            return $idUsuario;
        } catch (mysqli_sql_exception $e) {
            $mysqli->rollback();
            if ($e->getCode() === 1062) { 
                throw new RuntimeException('El nombre de usuario o el correo ya están registrados.');
            }
            throw $e;
        }
    }

    public static function login(string $nombreUsuario, string $password): ?Usuario
    {
        $mysqli = Conexion::obtener();

        $stmtIntentos = $mysqli->prepare(
            'SELECT COUNT(*) AS fallidos FROM intento_login
             WHERE nombre_usuario = ? AND exitoso = 0 AND fecha_hora > (NOW() - INTERVAL 15 MINUTE)'
        );
        $stmtIntentos->bind_param('s', $nombreUsuario);
        $stmtIntentos->execute();
        $fallidos = (int) $stmtIntentos->get_result()->fetch_assoc()['fallidos'];
        $stmtIntentos->close();

        if ($fallidos >= 5) {
            throw new RuntimeException('Cuenta bloqueada temporalmente por intentos fallidos. Probá de nuevo en 15 minutos.');
        }

        $stmt = $mysqli->prepare(
            'SELECT u.id_usuario, u.nombre_usuario, u.nombre, u.correo, u.password_hash, r.nombre AS rol
             FROM usuario u
             JOIN rol r ON r.id_rol = u.id_rol
             WHERE u.nombre_usuario = ?'
        );
        $stmt->bind_param('s', $nombreUsuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();
        $stmt->close();

        $credencialesValidas = $fila !== null && password_verify($password, $fila['password_hash']);

        $stmtRegistro = $mysqli->prepare(
            'INSERT INTO intento_login (nombre_usuario, exitoso) VALUES (?, ?)'
        );
        $exitoso = $credencialesValidas ? 1 : 0;
        $stmtRegistro->bind_param('si', $nombreUsuario, $exitoso);
        $stmtRegistro->execute();
        $stmtRegistro->close();

        if (!$credencialesValidas) {
            return null;
        }

        $usuario = new self();
        $usuario->idUsuario = (int) $fila['id_usuario'];
        $usuario->nombreUsuario = $fila['nombre_usuario'];
        $usuario->nombre = $fila['nombre'];
        $usuario->correo = $fila['correo'];
        $usuario->rol = $fila['rol'];
        return $usuario;
    }

    public static function obtenerPerfil(int $idUsuario): ?array
    {
        $mysqli = Conexion::obtener();
        $stmt = $mysqli->prepare(
            'SELECT u.nombre, u.correo, u.ubicacion, u.fecha_registro, p.apellido, p.telefono
             FROM usuario u
             JOIN participante p ON p.id_usuario = u.id_usuario
             WHERE u.id_usuario = ?'
        );
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $fila ?: null;
    }

    public static function actualizarPerfil(int $idUsuario, string $nombre, string $apellido, string $correo, string $ubicacion): void
    {
        if (trim($nombre) === '' || trim($apellido) === '') {
            throw new InvalidArgumentException('Nombre y apellido son obligatorios.');
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El correo electrónico no es válido.');
        }

        $mysqli = Conexion::obtener();
        $mysqli->begin_transaction();
        try {
            $stmt = $mysqli->prepare('UPDATE usuario SET nombre = ?, correo = ?, ubicacion = ? WHERE id_usuario = ?');
            $stmt->bind_param('sssi', $nombre, $correo, $ubicacion, $idUsuario);
            $stmt->execute();
            $stmt->close();

            $stmt2 = $mysqli->prepare('UPDATE participante SET apellido = ? WHERE id_usuario = ?');
            $stmt2->bind_param('si', $apellido, $idUsuario);
            $stmt2->execute();
            $stmt2->close();

            $mysqli->commit();
        } catch (mysqli_sql_exception $e) {
            $mysqli->rollback();
            if ($e->getCode() === 1062) {
                throw new RuntimeException('Ese correo ya está en uso por otra cuenta.');
            }
            throw $e;
        }
    }
}
