<?php
// Controlador de acceso: entrar en dos pasos (contraseña + código por email),
// salir, activar la cuenta y recuperar la contraseña.
// No contiene SQL ni HTML.
class AccesoControlador extends Controlador
{
    // Hash de relleno: si el email no existe se comprueba contra él igualmente,
    // así la respuesta tarda lo mismo y no revela qué emails están dados de alta
    private const HASH_RELLENO = '$argon2id$v=19$m=65536,t=4,p=1$czVTSkhoZEhvV1hrdi55aQ$IHfq34y2XIlOBmqGLwgha5m6xmPwZAfHIozWqhYw7No';

    private const MAX_FALLOS_EMAIL = 5;     // en 15 minutos, por cuenta
    private const MAX_FALLOS_IP = 20;       // en 15 minutos, por IP
    private const MINUTOS_BLOQUEO = 15;
    private const MINUTOS_CODIGO = 10;
    private const INTENTOS_CODIGO = 5;
    private const HORAS_ACTIVAR = 48;
    private const MINUTOS_RESTABLECER = 60;

    public function __construct(private UsuarioModelo $usuarios) {}

    // ---------- Paso 1: email y contraseña ----------

    // index.php?accion=login
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->mostrar('acceso_login', ['titulo' => 'Entrar', 'errores' => [], 'email' => '']);
            return;
        }

        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if ($this->bloqueado($email)) {
            $this->usuarios->apuntar('bloqueo', null, $email, 'Demasiados intentos fallidos');
            $this->mostrar('acceso_login', [
                'titulo' => 'Entrar',
                'errores' => ['Demasiados intentos fallidos. Espera ' . self::MINUTOS_BLOQUEO . ' minutos y vuelve a probar.'],
                'email' => $email,
            ]);
            return;
        }

        $usuario = $email !== '' && strlen($email) <= 190 ? $this->usuarios->buscarPorEmail($email) : null;
        $correcta = password_verify($password, $usuario['password_hash'] ?? self::HASH_RELLENO);
        $puedeEntrar = $correcta && $usuario && $usuario['activo'] && $usuario['email_verificado'] !== null;

        if (!$puedeEntrar) {
            $this->usuarios->apuntar('login_fallo', $usuario['id'] ?? null, $email,
                $correcta ? 'Cuenta de baja o email sin verificar' : 'Contraseña incorrecta o email desconocido');
            // Con la contraseña buena y el email nuevo sin confirmar, se le dice qué hacer
            // (no revela nada: solo lo ve quien ya sabe la contraseña)
            $pendienteVerificar = $correcta && $usuario && $usuario['activo'] && $usuario['email_verificado'] === null;
            $this->mostrar('acceso_login', [
                'titulo' => 'Entrar',
                // En los demás casos, el mismo mensaje: no se revela si el email existe
                'errores' => [$pendienteVerificar
                    ? 'Antes de entrar tienes que confirmar tu email: abre el enlace que te hemos enviado.'
                    : 'El email o la contraseña no son correctos.'],
                'email' => $email,
            ]);
            return;
        }

        if (password_needs_rehash($usuario['password_hash'], PASSWORD_ARGON2ID)) {
            $this->usuarios->actualizarHash($usuario['id'], Seguridad::hashPassword($password));
        }

        // Contraseña correcta: aún no entra. Se manda el código del segundo paso
        session_regenerate_id(true);
        $_SESSION['dos_pasos'] = ['id' => (int) $usuario['id'], 'desde' => time()];
        $this->usuarios->apuntar('login_paso1', $usuario['id'], $email, 'Contraseña correcta, código enviado');

        if (!$this->enviarCodigo($usuario)) {
            unset($_SESSION['dos_pasos']);
            $this->mostrar('acceso_login', [
                'titulo' => 'Entrar',
                'errores' => ['No se ha podido enviar el código de verificación. Inténtalo dentro de un rato.'],
                'email' => $email,
            ]);
            return;
        }
        $this->redirigir('index.php?accion=codigo');
    }

    // ---------- Paso 2: código enviado por email ----------

    // index.php?accion=codigo
    public function codigo(): void
    {
        $usuario = $this->usuarioEnDosPasos();
        if (!$usuario) {
            Seguridad::aviso('Vuelve a escribir tu email y tu contraseña.', 'error');
            $this->redirigir('index.php?accion=login');
        }

        $datos = ['titulo' => 'Código de verificación', 'errores' => [], 'emailOculto' => $this->ocultarEmail($usuario['email'])];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->mostrar('acceso_codigo', $datos);
            return;
        }

        $codigo = preg_replace('/\D/', '', (string) ($_POST['codigo'] ?? ''));
        $vigente = $this->usuarios->codigoVigente($usuario['id'], 'login');

        if (!$vigente || $vigente['intentos'] >= self::INTENTOS_CODIGO) {
            if ($vigente) {
                $this->usuarios->marcarUsado($vigente['id']);
            }
            unset($_SESSION['dos_pasos']);
            Seguridad::aviso('El código ha caducado o se ha equivocado demasiadas veces. Vuelve a entrar.', 'error');
            $this->redirigir('index.php?accion=login');
        }

        if (strlen($codigo) === 6 && hash_equals($vigente['hash'], Seguridad::huella($usuario['id'] . ':' . $codigo))) {
            $this->usuarios->marcarUsado($vigente['id']);
            $this->usuarios->anotarAcceso($usuario['id']);
            $this->usuarios->apuntar('login_ok', $usuario['id'], $usuario['email']);
            Seguridad::entrar($usuario);

            // Vuelve a la página que quería ver (solo direcciones de esta aplicación)
            $volver = $_SESSION['volver'] ?? '';
            unset($_SESSION['volver']);
            $this->redirigir('index.php' . ($volver !== '' ? '?' . $volver : ''));
        }

        $this->usuarios->sumarIntento($vigente['id']);
        $this->usuarios->apuntar('codigo_fallo', $usuario['id'], $usuario['email']);
        $quedan = self::INTENTOS_CODIGO - $vigente['intentos'] - 1;
        $datos['errores'][] = $quedan > 0
            ? "El código no es correcto. Te quedan $quedan " . ($quedan === 1 ? 'intento.' : 'intentos.')
            : 'El código no es correcto y no te quedan intentos. Vuelve a entrar.';
        $this->mostrar('acceso_codigo', $datos);
    }

    // index.php?accion=reenviar_codigo (POST)
    public function reenviarCodigo(): void
    {
        $usuario = $this->usuarioEnDosPasos();
        if (!$usuario || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('index.php?accion=login');
        }
        $vigente = $this->usuarios->codigoVigente($usuario['id'], 'login');
        if ($vigente && time() - strtotime($vigente['creado']) < 60) {
            Seguridad::aviso('Espera un minuto antes de pedir otro código.', 'error');
        } elseif ($this->enviarCodigo($usuario)) {
            Seguridad::aviso('Te hemos enviado un código nuevo.');
        } else {
            Seguridad::aviso('No se ha podido enviar el código. Inténtalo dentro de un rato.', 'error');
        }
        $this->redirigir('index.php?accion=codigo');
    }

    // index.php?accion=salir (POST)
    public function salir(): void
    {
        // Solo por POST (con token CSRF): un enlace o una imagen de otra web no te puede sacar
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('index.php');
        }
        $usuario = Seguridad::usuario();
        if ($usuario) {
            $this->usuarios->apuntar('logout', $usuario['id'], $usuario['email']);
        }
        Seguridad::cerrarSesion();
        Seguridad::aviso('Has salido. ¡Hasta la próxima vuelta!');
        $this->redirigir('index.php?accion=login');
    }

    // ---------- Activar la cuenta y recuperar la contraseña ----------

    // index.php?accion=activar&token=…  (enlace del email de alta)
    public function activar(): void
    {
        $this->elegirPassword('activar', 'Activa tu cuenta',
            'Cuenta activada y email verificado. Ya puedes entrar.');
    }

    // index.php?accion=restablecer&token=…  (enlace del email de recuperación)
    public function restablecer(): void
    {
        $this->elegirPassword('restablecer', 'Nueva contraseña',
            'Contraseña cambiada. Ya puedes entrar con la nueva.');
    }

    // index.php?accion=verificar_email&token=…  (enlace enviado al email nuevo)
    // GET muestra un botón y POST verifica: así, si un antivirus del correo abre
    // el enlace por su cuenta, no lo da por confirmado
    public function verificarEmail(): void
    {
        $token = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
        $usuario = preg_match('/^[a-f0-9]{64}$/', $token)
            ? $this->usuarios->usuarioDelEnlace('verificar', Seguridad::huella($token))
            : null;

        if (!$usuario) {
            $this->usuarios->apuntar('enlace_invalido', null, null, 'verificar');
            $this->mostrar('acceso_verificar', ['titulo' => 'Verificar el email', 'valido' => false]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->usuarios->marcarEmailVerificado($usuario['id']);
            $this->usuarios->marcarUsado($usuario['codigo_id']);
            $this->usuarios->apuntar('email_verificado', $usuario['id'], $usuario['email']);
            Seguridad::aviso('Email verificado. Ya puedes entrar con él.');
            $this->redirigir(Seguridad::usuario() ? 'index.php' : 'index.php?accion=login');
        }

        $this->mostrar('acceso_verificar', [
            'titulo' => 'Verificar el email', 'valido' => true, 'token' => $token,
            'nombre' => $usuario['nombre'], 'email' => $usuario['email'],
        ]);
    }

    // index.php?accion=registro  — cualquiera puede crearse una cuenta de cliente.
    // Entra sin ficha de cliente: no ve ninguna factura hasta que el admin la vincula
    public function registro(): void
    {
        $datos = ['titulo' => 'Crear cuenta', 'errores' => [], 'valores' => [], 'enviado' => false];
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->mostrar('acceso_registro', $datos);
            return;
        }

        $entrada = fn(string $campo) => is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
        $datos['valores'] = [
            'nombre' => $entrada('nombre'), 'apellidos' => $entrada('apellidos'), 'dni' => $entrada('dni'),
            'email' => $entrada('email'), 'telefono' => $entrada('telefono'),
        ];

        // Trampa para robots: un campo oculto que una persona nunca rellena
        if ($entrada('web') !== '') {
            $this->usuarios->apuntar('registro_robot', null, mb_substr($entrada('email'), 0, 190));
            $datos['enviado'] = true;
            $this->mostrar('acceso_registro', $datos);
            return;
        }
        if ($this->usuarios->contarRecientes(['registro', 'registro_repetido', 'registro_robot'], 'ip', Seguridad::ip(), 60) >= 5) {
            $datos['errores'][] = 'Demasiados registros desde esta conexión. Inténtalo dentro de una hora.';
            $this->mostrar('acceso_registro', $datos);
            return;
        }

        $limpio = [
            'nombre' => Validar::nombre($entrada('nombre'), 60),
            'apellidos' => Validar::nombre($entrada('apellidos'), 120),
            'dni' => Validar::dni($entrada('dni')),
            'email' => Validar::email($entrada('email')),
            'telefono' => Validar::telefono($entrada('telefono')),
        ];
        $errores = [];
        if (!$limpio['nombre']) $errores[] = 'Escribe tu nombre (solo letras).';
        if (!$limpio['apellidos']) $errores[] = 'Escribe tus apellidos (solo letras).';
        if (!$limpio['dni']) $errores[] = 'El DNI o NIE no es válido: revisa los números y la letra.';
        if (!$limpio['email']) $errores[] = 'El email no es válido.';
        if (!$limpio['telefono']) $errores[] = 'El teléfono debe ser español: 9 cifras que empiezan por 6, 7, 8 o 9.';
        $password = $entrada('password');
        $errores = array_merge($errores, Validar::password($password, $entrada('password2'), $datos['valores']));
        if (($_POST['acepto'] ?? '') !== 'si') {
            $errores[] = 'Para crear la cuenta tienes que aceptar que el taller guarde tus datos.';
        }
        if ($errores) {
            $datos['errores'] = $errores;
            $this->mostrar('acceso_registro', $datos);
            return;
        }

        // El hash se calcula siempre: así tarda lo mismo si el email ya existe o no
        $hash = Seguridad::hashPassword($password);

        // Si el email o el DNI ya están dados de alta, la respuesta es la misma
        // (no se revela quién tiene cuenta); al dueño del email se le avisa
        $existente = $this->usuarios->buscarPorEmail($limpio['email']);
        if ($existente) {
            $this->usuarios->apuntar('registro_repetido', $existente['id'], $limpio['email'], 'Email ya registrado');
            if ($existente['activo']) {
                Correo::enviar($limpio['email'], 'Ya tienes una cuenta en Taller Genil',
                    "Hola, {$existente['nombre']}:\n\n"
                    . "Alguien (quizá tú) ha intentado crear otra cuenta con este email, pero ya tienes una.\n"
                    . "Puedes entrar con tu contraseña o, si no la recuerdas, usar «¿Has olvidado la contraseña?».\n"
                    . "Si no has sido tú, no tienes que hacer nada.");
            }
        } elseif ($this->usuarios->existe('dni', $limpio['dni'])) {
            $this->usuarios->apuntar('registro_repetido', null, $limpio['email'], 'DNI ya registrado');
        } else {
            try {
                $id = $this->usuarios->registrar($limpio, $hash);
                $this->usuarios->apuntar('registro', $id, $limpio['email'], 'Registro propio (acepta el tratamiento de sus datos)');
                $this->enviarEnlace($this->usuarios->buscar($id), 'verificar', true);
            } catch (PDOException $e) {
                // Otro registro con el mismo email o DNI a la vez: misma respuesta
                $this->usuarios->apuntar('registro_repetido', null, $limpio['email'], 'Registro simultáneo');
            }
        }

        $datos['enviado'] = true;
        $datos['email'] = $limpio['email'];
        $this->mostrar('acceso_registro', $datos);
    }

    // index.php?accion=cambiar_password  — cualquier usuario, su propia contraseña.
    // También es la página obligatoria cuando el admin le ha puesto una temporal
    public function cambiarPassword(): void
    {
        $yo = Seguridad::usuario();
        $datos = ['titulo' => 'Cambiar la contraseña', 'errores' => [], 'obligatorio' => (bool) $yo['debe_cambiar_password']];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $actual = (string) ($_POST['actual'] ?? '');
            $nueva = (string) ($_POST['password'] ?? '');
            $errores = [];

            if ($this->usuarios->contarRecientes(['cambio_password_fallo'], 'email', $yo['email'], self::MINUTOS_BLOQUEO) >= self::MAX_FALLOS_EMAIL) {
                $errores[] = 'Demasiados intentos con una contraseña actual incorrecta. Espera ' . self::MINUTOS_BLOQUEO . ' minutos.';
            } elseif (!password_verify($actual, (string) $yo['password_hash'])) {
                $errores[] = 'La contraseña actual no es correcta.';
                $this->usuarios->apuntar('cambio_password_fallo', $yo['id'], $yo['email']);
            } else {
                $errores = Validar::password($nueva, (string) ($_POST['password2'] ?? ''), $yo);
                if (!$errores && hash_equals($actual, $nueva)) {
                    $errores[] = 'La contraseña nueva tiene que ser distinta de la actual.';
                }
            }

            if (!$errores) {
                $this->usuarios->cambiarPassword($yo['id'], Seguridad::hashPassword($nueva), false);
                $this->usuarios->anularCodigos($yo['id']);   // los enlaces de recuperación pendientes dejan de valer
                $this->usuarios->apuntar('cambio_password', $yo['id'], $yo['email']);
                Seguridad::renovarHuella($this->usuarios->buscar($yo['id']));   // esta sesión sigue; las demás se cierran
                Correo::enviar($yo['email'], 'Has cambiado tu contraseña de Taller Genil',
                    "Hola, {$yo['nombre']}:\n\nLa contraseña de tu cuenta acaba de cambiar.\n"
                    . "Si no has sido tú, usa «¿Has olvidado la contraseña?» cuanto antes y avisa al taller.");
                Seguridad::aviso('Contraseña cambiada. Si tenías la sesión abierta en otro sitio, se ha cerrado.');
                $this->redirigir('index.php?accion=mi_cuenta');
            }
            $datos['errores'] = $errores;
        }
        $this->mostrar('acceso_cambiar_password', $datos);
    }

    // index.php?accion=recuperar
    public function recuperar(): void
    {
        $datos = ['titulo' => 'Recuperar la contraseña', 'errores' => [], 'enviado' => false];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));

            if ($this->usuarios->contarRecientes(['recuperar'], 'ip', Seguridad::ip(), self::MINUTOS_BLOQUEO) >= 5) {
                $datos['errores'][] = 'Demasiadas peticiones. Espera unos minutos.';
            } else {
                $usuario = filter_var($email, FILTER_VALIDATE_EMAIL) ? $this->usuarios->buscarPorEmail($email) : null;
                $this->usuarios->apuntar('recuperar', $usuario['id'] ?? null, $email);
                if ($usuario && $usuario['activo']) {
                    $this->enviarEnlace($usuario, 'restablecer');
                }
                // Siempre la misma respuesta: no se revela si el email está dado de alta
                $datos['enviado'] = true;
            }
        }
        $this->mostrar('acceso_recuperar', $datos);
    }

    // ---------- Bandeja de correos (solo desarrollo y solo desde este ordenador) ----------

    // index.php?accion=correos_dev
    public function correosDesarrollo(): void
    {
        if (Seguridad::enProduccion() || !Seguridad::esLocal()) {
            $this->noEncontrada();
            return;
        }
        $archivos = glob(Correo::carpeta() . '/*.eml') ?: [];
        rsort($archivos);
        $correos = array_map(fn($ruta) => file_get_contents($ruta), array_slice($archivos, 0, 20));
        $this->mostrar('correos_dev', ['titulo' => 'Correos enviados (desarrollo)', 'correos' => $correos]);
    }

    // ---------- Para el controlador de usuarios ----------

    // Envía el enlace para activar la cuenta o para cambiar la contraseña
    // $nuevaCuenta: el enlace de «verificar» es de un registro, no de un cambio de email
    public function enviarEnlace(array $usuario, string $tipo, bool $nuevaCuenta = false): bool
    {
        $token = Seguridad::tokenAleatorio();
        $minutos = $tipo === 'restablecer' ? self::MINUTOS_RESTABLECER : self::HORAS_ACTIVAR * 60;
        $this->usuarios->crearCodigo($usuario['id'], $tipo, Seguridad::huella($token), $minutos);

        $accion = $tipo === 'verificar' ? 'verificar_email' : $tipo;
        $enlace = Seguridad::config('urlBase', '') . 'index.php?accion=' . $accion . '&token=' . $token;
        if ($tipo === 'verificar' && $nuevaCuenta) {
            $asunto = 'Confirma tu email para activar tu cuenta de Taller Genil';
            $texto = "Hola, {$usuario['nombre']}:\n\n"
                   . "Gracias por registrarte en el Taller Genil. Para confirmar que este email es tuyo,\n"
                   . "abre este enlace y pulsa «Confirmar»:\n\n"
                   . "$enlace\n\n"
                   . "Hasta que lo confirmes no podrás entrar. El enlace caduca en " . self::HORAS_ACTIVAR . " horas.\n"
                   . "Para ver tus facturas, el taller vinculará tu cuenta con tu ficha de cliente cuando lo confirmes en persona.\n"
                   . "Si no te has registrado tú, ignora este correo.";
        } elseif ($tipo === 'verificar') {
            $asunto = 'Confirma tu nuevo email en Taller Genil';
            $texto = "Hola, {$usuario['nombre']}:\n\n"
                   . "El email de tu cuenta del Taller Genil ha cambiado a esta dirección.\n"
                   . "Para confirmar que es tuya, abre este enlace y pulsa «Confirmar»:\n\n"
                   . "$enlace\n\n"
                   . "Hasta que lo confirmes no podrás entrar. El enlace caduca en " . self::HORAS_ACTIVAR . " horas.\n"
                   . "Si no esperabas este correo, ignóralo.";
        } elseif ($tipo === 'activar') {
            $asunto = 'Activa tu cuenta en Taller Genil';
            $texto = "Hola, {$usuario['nombre']}:\n\n"
                   . "Te han dado de alta en la aplicación del Taller Genil.\n"
                   . "Para confirmar que este email es tuyo y elegir tu contraseña, abre este enlace:\n\n"
                   . "$enlace\n\n"
                   . "El enlace caduca en " . self::HORAS_ACTIVAR . " horas y solo se puede usar una vez.\n"
                   . "Si no esperabas este correo, ignóralo.";
        } else {
            $asunto = 'Cambia tu contraseña de Taller Genil';
            $texto = "Hola, {$usuario['nombre']}:\n\n"
                   . "Alguien ha pedido cambiar la contraseña de tu cuenta. Si has sido tú, abre este enlace:\n\n"
                   . "$enlace\n\n"
                   . "El enlace caduca en " . self::MINUTOS_RESTABLECER . " minutos y solo se puede usar una vez.\n"
                   . "Si no lo has pedido tú, ignora este correo: tu contraseña no cambia.";
        }
        return Correo::enviar($usuario['email'], $asunto, $texto);
    }

    // ---------- Ayudantes ----------

    private function elegirPassword(string $tipo, string $titulo, string $exito): void
    {
        $token = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
        $usuario = preg_match('/^[a-f0-9]{64}$/', $token)
            ? $this->usuarios->usuarioDelEnlace($tipo, Seguridad::huella($token))
            : null;

        if (!$usuario) {
            $this->usuarios->apuntar('enlace_invalido', null, null, $tipo);
            $this->mostrar('acceso_password', ['titulo' => $titulo, 'valido' => false, 'errores' => [], 'tipo' => $tipo]);
            return;
        }

        $datos = ['titulo' => $titulo, 'valido' => true, 'errores' => [], 'tipo' => $tipo,
                  'token' => $token, 'nombre' => $usuario['nombre'], 'email' => $usuario['email']];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = (string) ($_POST['password'] ?? '');
            $errores = Validar::password($password, (string) ($_POST['password2'] ?? ''), $usuario);
            if (!$errores) {
                $this->usuarios->guardarPassword($usuario['id'], Seguridad::hashPassword($password));
                $this->usuarios->anularCodigos($usuario['id']);   // el enlace y cualquier otro código dejan de valer
                $this->usuarios->apuntar($tipo === 'activar' ? 'activacion' : 'restablecer', $usuario['id'], $usuario['email']);
                Seguridad::cerrarSesion();
                Seguridad::aviso($exito);
                $this->redirigir('index.php?accion=login');
            }
            $datos['errores'] = $errores;
        }
        $this->mostrar('acceso_password', $datos);
    }

    private function enviarCodigo(array $usuario): bool
    {
        $codigo = Seguridad::codigoAleatorio();
        $this->usuarios->crearCodigo($usuario['id'], 'login', Seguridad::huella($usuario['id'] . ':' . $codigo), self::MINUTOS_CODIGO);
        return Correo::enviar($usuario['email'], "Tu código de acceso: $codigo",
            "Hola, {$usuario['nombre']}:\n\n"
            . "Tu código para entrar en Taller Genil es:\n\n    $codigo\n\n"
            . "Caduca en " . self::MINUTOS_CODIGO . " minutos. No se lo des a nadie: nadie del taller te lo pedirá.\n"
            . "Si no estabas intentando entrar, cambia tu contraseña cuanto antes.");
    }

    // El usuario que ha superado el paso 1 y aún no el 2
    private function usuarioEnDosPasos(): ?array
    {
        $pendiente = $_SESSION['dos_pasos'] ?? null;
        if (!$pendiente || time() - $pendiente['desde'] > self::MINUTOS_CODIGO * 60) {
            unset($_SESSION['dos_pasos']);
            return null;
        }
        $usuario = $this->usuarios->buscar($pendiente['id']);
        return $usuario && $usuario['activo'] ? $usuario : null;
    }

    private function bloqueado(string $email): bool
    {
        $fallos = ['login_fallo', 'codigo_fallo'];
        return $this->usuarios->contarRecientes($fallos, 'ip', Seguridad::ip(), self::MINUTOS_BLOQUEO) >= self::MAX_FALLOS_IP
            || ($email !== '' && $this->usuarios->contarRecientes($fallos, 'email', $email, self::MINUTOS_BLOQUEO) >= self::MAX_FALLOS_EMAIL);
    }

    // jacinto@gmail.com  →  j*****o@gmail.com
    private function ocultarEmail(string $email): string
    {
        [$nombre, $dominio] = explode('@', $email, 2);
        $largo = mb_strlen($nombre);
        $visible = $largo <= 2 ? mb_substr($nombre, 0, 1) . '*' : mb_substr($nombre, 0, 1) . str_repeat('*', $largo - 2) . mb_substr($nombre, -1);
        return $visible . '@' . $dominio;
    }
}
