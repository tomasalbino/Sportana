<?php
require_once __DIR__ . '/../config/Conexion.php';

class Federacion
{
    public int $idUsuario;
    public string $nombre;
    public string $rol;

    public static function registrar(
        string $nombre, string $deporte, string $rut, string $representante,
        string $correo, string $telefono, string $password
    ): int {
        if (trim($nombre) === '' || trim($deporte) === '' || trim($representante) === '') {
            throw new InvalidArgumentException('Nombre, deporte y representante legal son obligatorios.');
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El correo institucional no es válido.');
        }
        if (trim($rut) === '') {
            throw new InvalidArgumentException('El RUT es obligatorio.');
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

            $rolOrganizador = 'organizador';
            $stmt->bind_param('sssss', $correo, $representante, $correo, $hash, $rolOrganizador);
            $stmt->execute();
            $idUsuario = $mysqli->insert_id;
            $stmt->close();

            $stmt2 = $mysqli->prepare(
                'INSERT INTO federacion (id_usuario, nombre, deporte, rut, representante_legal, telefono)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt2->bind_param('isssss', $idUsuario, $nombre, $deporte, $rut, $representante, $telefono);
            $stmt2->execute();
            $stmt2->close();

            $mysqli->commit();
            return $idUsuario;
        } catch (mysqli_sql_exception $e) {
            $mysqli->rollback();
            if ($e->getCode() === 1062) {
                throw new RuntimeException('El correo o el RUT ya están registrados.');
            }
            throw $e;
        }
    }

    public static function login(string $identificador, string $password): ?Federacion
    {
        $mysqli = Conexion::obtener();

        $stmtIntentos = $mysqli->prepare(
            'SELECT COUNT(*) AS fallidos FROM intento_login
             WHERE nombre_usuario = ? AND exitoso = 0 AND fecha_hora > (NOW() - INTERVAL 15 MINUTE)'
        );
        $stmtIntentos->bind_param('s', $identificador);
        $stmtIntentos->execute();
        $fallidos = (int) $stmtIntentos->get_result()->fetch_assoc()['fallidos'];
        $stmtIntentos->close();

        if ($fallidos >= 5) {
            throw new RuntimeException('Cuenta bloqueada temporalmente por intentos fallidos. Probá de nuevo en 15 minutos.');
        }

        $stmt = $mysqli->prepare(
            'SELECT u.id_usuario, u.password_hash, r.nombre AS rol, f.nombre AS nombre_federacion
             FROM usuario u
             JOIN rol r ON r.id_rol = u.id_rol
             JOIN federacion f ON f.id_usuario = u.id_usuario
             WHERE u.correo = ? OR f.rut = ?'
        );
        $stmt->bind_param('ss', $identificador, $identificador);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $credencialesValidas = $fila !== null && password_verify($password, $fila['password_hash']);

        $stmtRegistro = $mysqli->prepare('INSERT INTO intento_login (nombre_usuario, exitoso) VALUES (?, ?)');
        $exitoso = $credencialesValidas ? 1 : 0;
        $stmtRegistro->bind_param('si', $identificador, $exitoso);
        $stmtRegistro->execute();
        $stmtRegistro->close();

        if (!$credencialesValidas) {
            return null;
        }

        $federacion = new self();
        $federacion->idUsuario = (int) $fila['id_usuario'];
        $federacion->nombre = $fila['nombre_federacion'];
        $federacion->rol = $fila['rol'];
        return $federacion;
    }
}
