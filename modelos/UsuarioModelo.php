<?php
// Modelo: todo el SQL de los usuarios, sus códigos y el registro de accesos
class UsuarioModelo
{
    public function __construct(private PDO $pdo) {}

    // ---------- Usuarios ----------

    public function buscar(int $id): ?array
    {
        $consulta = $this->pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $consulta->execute([$id]);
        return $consulta->fetch() ?: null;
    }

    public function buscarPorEmail(string $email): ?array
    {
        $consulta = $this->pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $consulta->execute([$email]);
        return $consulta->fetch() ?: null;
    }

    // Usuarios dados de alta ($activos = true) o dados de baja (false)
    public function todos(bool $activos): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT u.id, u.nombre, u.apellidos, u.dni, u.email, u.telefono, u.rol, u.cliente_id,
                    u.password_hash IS NOT NULL AS cuenta_activada, u.email_verificado,
                    u.fecha_alta, u.fecha_baja, u.motivo_baja, u.ultimo_acceso,
                    CONCAT(c.nombre, ' ', c.apellidos) AS creado_por,
                    CONCAT(cl.nombre, ' ', cl.apellidos) AS cliente
             FROM usuarios u
             LEFT JOIN usuarios c ON c.id = u.creado_por
             LEFT JOIN cliente cl ON cl.id = u.cliente_id
             WHERE u.activo = ?
             ORDER BY " . ($activos ? "u.apellidos, u.nombre" : "u.fecha_baja DESC"));
        $consulta->execute([$activos ? 1 : 0]);
        return $consulta->fetchAll();
    }

    // ¿Hay ya alguien (de alta o de baja) con ese DNI o ese email?
    // $excepto: al editar, el propio usuario no cuenta
    public function existe(string $campo, string $valor, ?int $excepto = null): bool
    {
        if (!in_array($campo, ['dni', 'email'], true)) {
            throw new InvalidArgumentException('Campo no válido');
        }
        $consulta = $this->pdo->prepare("SELECT 1 FROM usuarios WHERE $campo = ? AND id <> ?");
        $consulta->execute([$valor, $excepto ?? 0]);
        return (bool) $consulta->fetchColumn();
    }

    // Cambia los datos, el rol y la ficha de cliente de un usuario.
    // Si cambia el email, deja de estar verificado hasta que confirme el nuevo
    public function actualizar(int $id, array $datos, string $rol, ?int $clienteId, bool $emailCambiado): void
    {
        $this->pdo->prepare(
            "UPDATE usuarios
             SET nombre = ?, apellidos = ?, dni = ?, email = ?, telefono = ?, rol = ?, cliente_id = ?,
                 email_verificado = " . ($emailCambiado ? "NULL" : "email_verificado") . "
             WHERE id = ?"
        )->execute([$datos['nombre'], $datos['apellidos'], $datos['dni'], $datos['email'],
                    $datos['telefono'], $rol, $clienteId, $id]);
        if ($emailCambiado) {
            // Los códigos enviados al email antiguo dejan de valer
            $this->anularCodigos($id);
        }
    }

    public function marcarEmailVerificado(int $id): void
    {
        $this->pdo->prepare("UPDATE usuarios SET email_verificado = NOW() WHERE id = ?")->execute([$id]);
    }

    // $clienteId: la ficha de cliente de un usuario normal (null para un admin)
    public function crear(array $datos, string $rol, ?int $clienteId, ?int $creadoPor): int
    {
        $insertar = $this->pdo->prepare(
            "INSERT INTO usuarios (nombre, apellidos, dni, email, telefono, rol, cliente_id, creado_por)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insertar->execute([$datos['nombre'], $datos['apellidos'], $datos['dni'], $datos['email'],
                            $datos['telefono'], $rol, $clienteId, $creadoPor]);
        return (int) $this->pdo->lastInsertId();
    }

    // Registro público: siempre rol «usuario», sin ficha de cliente (la vincula el admin)
    // y con el email sin verificar hasta que abra el enlace
    public function registrar(array $datos, string $hash): int
    {
        $insertar = $this->pdo->prepare(
            "INSERT INTO usuarios (nombre, apellidos, dni, email, telefono, rol, password_hash)
             VALUES (?, ?, ?, ?, ?, 'usuario', ?)");
        $insertar->execute([$datos['nombre'], $datos['apellidos'], $datos['dni'], $datos['email'],
                            $datos['telefono'], $hash]);
        return (int) $this->pdo->lastInsertId();
    }

    // Clientes de alta que todavía no tienen su ficha de cliente vinculada
    public function sinFicha(): int
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM usuarios WHERE rol = 'usuario' AND cliente_id IS NULL AND activo = 1"
        )->fetchColumn();
    }

    // Clientes del taller que todavía no tienen cuenta (para el desplegable).
    // $usuarioId: al editar, también sale el cliente que ya tiene ese usuario
    public function clientesSinCuenta(?int $usuarioId = null): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT c.id, c.nombre, c.apellidos, c.telefono
             FROM cliente c
             LEFT JOIN usuarios u ON u.cliente_id = c.id
             WHERE u.id IS NULL OR u.id = ?
             ORDER BY c.apellidos, c.nombre");
        $consulta->execute([$usuarioId ?? 0]);
        return $consulta->fetchAll();
    }

    // Guarda la contraseña; quien la guarda con un enlace recibido por email
    // ha demostrado que el email es suyo
    public function guardarPassword(int $id, string $hash): void
    {
        $actualizar = $this->pdo->prepare(
            "UPDATE usuarios SET password_hash = ?, debe_cambiar_password = 0,
                                 email_verificado = COALESCE(email_verificado, NOW())
             WHERE id = ?");
        $actualizar->execute([$hash, $id]);
    }

    // Cambia la contraseña sin tocar la verificación del email.
    // $temporal: la ha puesto el admin, así que el usuario tendrá que cambiarla al entrar
    public function cambiarPassword(int $id, string $hash, bool $temporal): void
    {
        $this->pdo->prepare("UPDATE usuarios SET password_hash = ?, debe_cambiar_password = ? WHERE id = ?")
                  ->execute([$hash, $temporal ? 1 : 0, $id]);
    }

    public function actualizarHash(int $id, string $hash): void
    {
        $this->pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?")->execute([$hash, $id]);
    }

    public function anotarAcceso(int $id): void
    {
        $this->pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?")->execute([$id]);
    }

    public function darDeBaja(int $id, string $motivo): void
    {
        $this->pdo->prepare(
            "UPDATE usuarios SET activo = 0, fecha_baja = NOW(), motivo_baja = ? WHERE id = ? AND activo = 1"
        )->execute([$motivo, $id]);
        $this->anularCodigos($id);
    }

    public function darDeAlta(int $id): void
    {
        $this->pdo->prepare(
            "UPDATE usuarios SET activo = 1, fecha_baja = NULL, motivo_baja = NULL WHERE id = ? AND activo = 0"
        )->execute([$id]);
    }

    public function adminsActivos(): int
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM usuarios WHERE rol = 'admin' AND activo = 1"
        )->fetchColumn();
    }

    // ---------- Códigos de un solo uso ----------

    // Crea un código nuevo y anula los anteriores del mismo tipo
    public function crearCodigo(int $usuarioId, string $tipo, string $huella, int $minutos): void
    {
        $this->pdo->prepare(
            "UPDATE codigos_usuario SET usado = NOW() WHERE usuario_id = ? AND tipo = ? AND usado IS NULL"
        )->execute([$usuarioId, $tipo]);
        $this->pdo->prepare(
            "INSERT INTO codigos_usuario (usuario_id, tipo, hash, expira)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))"
        )->execute([$usuarioId, $tipo, $huella, $minutos]);
    }

    // El código vigente (sin usar y sin caducar) de un usuario
    public function codigoVigente(int $usuarioId, string $tipo): ?array
    {
        $consulta = $this->pdo->prepare(
            "SELECT id, hash, intentos, creado FROM codigos_usuario
             WHERE usuario_id = ? AND tipo = ? AND usado IS NULL AND expira > NOW()
             ORDER BY id DESC LIMIT 1");
        $consulta->execute([$usuarioId, $tipo]);
        return $consulta->fetch() ?: null;
    }

    // El usuario dueño de un enlace vigente (activar o restablecer)
    public function usuarioDelEnlace(string $tipo, string $huella): ?array
    {
        $consulta = $this->pdo->prepare(
            "SELECT u.*, c.id AS codigo_id
             FROM codigos_usuario c
             JOIN usuarios u ON u.id = c.usuario_id
             WHERE c.tipo = ? AND c.hash = ? AND c.usado IS NULL AND c.expira > NOW() AND u.activo = 1");
        $consulta->execute([$tipo, $huella]);
        return $consulta->fetch() ?: null;
    }

    public function sumarIntento(int $codigoId): void
    {
        $this->pdo->prepare("UPDATE codigos_usuario SET intentos = intentos + 1 WHERE id = ?")->execute([$codigoId]);
    }

    public function marcarUsado(int $codigoId): void
    {
        $this->pdo->prepare("UPDATE codigos_usuario SET usado = NOW() WHERE id = ?")->execute([$codigoId]);
    }

    public function anularCodigos(int $usuarioId): void
    {
        $this->pdo->prepare(
            "UPDATE codigos_usuario SET usado = NOW() WHERE usuario_id = ? AND usado IS NULL"
        )->execute([$usuarioId]);
    }

    // ---------- Registro de accesos ----------

    public function apuntar(string $evento, ?int $usuarioId, ?string $email, string $detalle = ''): void
    {
        $this->pdo->prepare(
            "INSERT INTO accesos (usuario_id, email, ip, evento, detalle) VALUES (?, ?, ?, ?, ?)"
        )->execute([$usuarioId, $email !== null ? mb_substr($email, 0, 190) : null, Seguridad::ip(), $evento, mb_substr($detalle, 0, 255)]);
    }

    // Cuántos eventos de esos tipos ha habido en los últimos minutos para una IP o un email
    public function contarRecientes(array $eventos, string $campo, string $valor, int $minutos): int
    {
        if (!in_array($campo, ['ip', 'email'], true) || !$eventos) {
            throw new InvalidArgumentException('Parámetros no válidos');
        }
        $huecos = implode(',', array_fill(0, count($eventos), '?'));
        $consulta = $this->pdo->prepare(
            "SELECT COUNT(*) FROM accesos
             WHERE $campo = ? AND evento IN ($huecos) AND fecha > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
        $consulta->execute([$valor, ...$eventos, $minutos]);
        return (int) $consulta->fetchColumn();
    }

    public function ultimosAccesos(int $limite = 150): array
    {
        return $this->pdo->query(
            "SELECT a.fecha, a.evento, a.email, a.ip, a.detalle,
                    CONCAT(u.nombre, ' ', u.apellidos) AS usuario
             FROM accesos a
             LEFT JOIN usuarios u ON u.id = a.usuario_id
             ORDER BY a.id DESC
             LIMIT " . (int) $limite
        )->fetchAll();
    }
}
