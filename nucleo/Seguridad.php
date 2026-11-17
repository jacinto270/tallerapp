<?php
// Seguridad común a todas las peticiones: cabeceras HTTP, sesión,
// protección CSRF y usuario conectado.
final class Seguridad
{
    private const INACTIVIDAD = 1800;      // 30 min sin actividad: se cierra la sesión
    private const DURACION_MAXIMA = 43200; // 12 h como máximo, aunque haya actividad
    private const RENOVAR_ID = 900;        // el identificador de sesión cambia cada 15 min

    private static ?array $usuario = null;

    // ---------- Configuración ----------

    public static function config(string $clave, mixed $defecto = null): mixed
    {
        return $GLOBALS[$clave] ?? $defecto;
    }

    // Si falta el ajuste, se trata como producción: lo más seguro
    public static function enProduccion(): bool
    {
        return self::config('entorno', 'produccion') !== 'desarrollo';
    }

    public static function esHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    public static function ip(): string
    {
        // Solo REMOTE_ADDR: X-Forwarded-For lo puede inventar cualquiera
        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
    }

    public static function esLocal(): bool
    {
        return in_array(self::ip(), ['127.0.0.1', '::1'], true);
    }

    // ---------- Arranque de cada petición ----------

    public static function iniciar(): void
    {
        if (self::enProduccion()) {
            // Los errores nunca se enseñan (revelarían rutas, SQL…): solo se registran
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');

            if (strlen((string) self::config('claveSecreta', '')) < 32) {
                http_response_code(500);
                exit('Falta configurar $claveSecreta en config/config.php.');
            }
            // En producción todo va por HTTPS
            if (!self::esHttps()) {
                $base = parse_url((string) self::config('urlBase', ''));
                $host = $base['host'] ?? '';
                if ($host !== '') {
                    header('Location: https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
                    exit;
                }
            }
        }

        self::cabeceras();
        self::sesion();
    }

    private static function cabeceras(): void
    {
        header_remove('X-Powered-By');
        // Solo se cargan scripts, estilos y fuentes propios (y las fuentes de Google):
        // un script inyectado no se ejecutaría
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; "
             . "style-src 'self' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; "
             . "img-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'self'; "
             . "form-action 'self'; frame-ancestors 'none'");
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: same-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        // Páginas con datos privados: que no se guarden en cachés
        header('Cache-Control: no-store');
        if (self::esHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    private static function sesion(): void
    {
        ini_set('session.use_strict_mode', '1');   // no acepta identificadores inventados
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');

        $ruta = parse_url((string) self::config('urlBase', ''), PHP_URL_PATH) ?: '/';
        session_name('taller_sesion');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $ruta,
            'secure' => self::esHttps(),
            'httponly' => true,      // JavaScript no puede leer la cookie
            'samesite' => 'Lax',     // otras webs no envían la cookie en sus formularios
        ]);
        session_start();

        $ahora = time();
        if (isset($_SESSION['usuario'])) {
            $inactivo = $ahora - ($_SESSION['ultima_actividad'] ?? 0) > self::INACTIVIDAD;
            $demasiadoLarga = $ahora - ($_SESSION['inicio'] ?? 0) > self::DURACION_MAXIMA;
            if ($inactivo || $demasiadoLarga) {
                self::cerrarSesion();
                self::aviso('Tu sesión ha caducado. Vuelve a entrar.');
            }
        }
        $_SESSION['ultima_actividad'] = $ahora;

        if ($ahora - ($_SESSION['renovada'] ?? 0) > self::RENOVAR_ID) {
            session_regenerate_id(true);
            $_SESSION['renovada'] = $ahora;
        }
    }

    // ---------- CSRF: cada formulario lleva un token secreto de la sesión ----------

    public static function tokenCsrf(): string
    {
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function campoCsrf(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::tokenCsrf() . '">';
    }

    public static function peticionValida(): bool
    {
        $token = $_POST['_csrf'] ?? '';
        $tokenValido = is_string($token) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);

        // Además, si el navegador dice de dónde viene el formulario, tiene que ser de aquí
        $origen = $_SERVER['HTTP_ORIGIN'] ?? '';
        $origenValido = $origen === ''
            || parse_url($origen, PHP_URL_HOST) === parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);

        return $tokenValido && $origenValido;
    }

    // ---------- Usuario conectado ----------

    // Carga el usuario de la sesión desde la base de datos en cada petición:
    // si le han dado de baja o le han cambiado el rol, se nota al momento
    public static function cargarUsuario(UsuarioModelo $usuarios): void
    {
        $id = $_SESSION['usuario'] ?? null;
        if (!$id) {
            return;
        }
        $usuario = $usuarios->buscar((int) $id);
        if (!$usuario || !$usuario['activo']) {
            self::cerrarSesion();
            self::aviso('Tu cuenta ya no está activa.');
            return;
        }
        // Si la contraseña ha cambiado (la ha cambiado el admin o desde otro sitio),
        // las sesiones abiertas con la anterior dejan de valer
        if (!hash_equals($_SESSION['huella_password'] ?? '', self::huellaPassword($usuario))) {
            self::cerrarSesion();
            self::aviso('Tu contraseña ha cambiado. Vuelve a entrar.');
            return;
        }
        self::$usuario = $usuario;
    }

    // Resumen de la contraseña guardada (no permite recuperarla): cambia si cambia la contraseña
    private static function huellaPassword(array $usuario): string
    {
        return self::huella('sesion:' . ($usuario['password_hash'] ?? ''));
    }

    // Tras cambiar uno mismo la contraseña: esta sesión sigue valiendo, las demás no
    public static function renovarHuella(array $usuario): void
    {
        session_regenerate_id(true);
        $_SESSION['huella_password'] = self::huellaPassword($usuario);
        self::$usuario = $usuario;
    }

    public static function usuario(): ?array
    {
        return self::$usuario;
    }

    public static function esAdmin(): bool
    {
        return (self::$usuario['rol'] ?? '') === 'admin';
    }

    // Tras superar los dos pasos: nueva sesión (evita la fijación de sesión)
    public static function entrar(array $usuario): void
    {
        session_regenerate_id(true);
        $volver = $_SESSION['volver'] ?? '';
        $_SESSION = [
            'usuario' => (int) $usuario['id'],
            'inicio' => time(),
            'ultima_actividad' => time(),
            'renovada' => time(),
            'csrf' => bin2hex(random_bytes(32)),
            'volver' => $volver,
            'huella_password' => self::huellaPassword($usuario),
        ];
        self::$usuario = $usuario;
    }

    public static function cerrarSesion(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        self::$usuario = null;
    }

    // ---------- Avisos de una sola vez (se muestran en la siguiente página) ----------

    public static function aviso(string $texto, string $tipo = 'aviso'): void
    {
        $_SESSION['avisos'][] = [$tipo, $texto];
    }

    public static function tomarAvisos(): array
    {
        $avisos = $_SESSION['avisos'] ?? [];
        unset($_SESSION['avisos']);
        return $avisos;
    }

    // ---------- Códigos y tokens ----------

    // Solo se guarda este HMAC en la base de datos, nunca el código
    public static function huella(string $valor): string
    {
        return hash_hmac('sha256', $valor, (string) self::config('claveSecreta', ''));
    }

    public static function tokenAleatorio(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function codigoAleatorio(): string
    {
        return sprintf('%06d', random_int(0, 999999));
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
}
