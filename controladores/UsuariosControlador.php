<?php
// Controlador de los usuarios: registro de altas y bajas (solo admin)
// y «Mi cuenta» (cada usuario sus propios datos).
// No contiene SQL ni HTML.
class UsuariosControlador extends Controlador
{
    public function __construct(
        private UsuarioModelo $usuarios,
        private ClienteModelo $clientes,
        private AccesoControlador $acceso,
    ) {}

    // index.php?accion=usuarios  (&ver=activos|bajas|accesos)
    public function listado(): void
    {
        $ver = $_GET['ver'] ?? 'activos';
        if (!in_array($ver, ['activos', 'bajas', 'accesos'], true)) {
            $ver = 'activos';
        }
        $this->mostrar('usuarios', [
            'titulo' => 'Usuarios',
            'ver' => $ver,
            'usuarios' => $ver === 'accesos' ? [] : $this->usuarios->todos($ver === 'activos'),
            'accesos' => $ver === 'accesos' ? $this->usuarios->ultimosAccesos() : [],
            'yo' => Seguridad::usuario()['id'],
            'sinFicha' => $this->usuarios->sinFicha(),
        ]);
    }

    // index.php?accion=usuario_nuevo
    // &rol=admin para «Nuevo administrador»; si no, cliente
    public function nuevo(): void
    {
        $this->formulario([], ['rol' => ($_GET['rol'] ?? '') === 'admin' ? 'admin' : 'usuario']);
    }

    // index.php?accion=usuario_guardar (POST)
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('index.php?accion=usuario_nuevo');
        }

        [$datos, $rol, $clienteId, $errores, $valores, $nuevaFicha] = $this->leerFormulario(null);

        if (!$errores) {
            try {
                $yo = Seguridad::usuario();
                if ($nuevaFicha) {
                    $clienteId = $this->clientes->crear($datos['nombre'], $datos['apellidos'], $datos['telefono']);
                }
                $id = $this->usuarios->crear($datos, $rol, $clienteId, $yo['id']);
                $this->usuarios->apuntar('alta', $id, $datos['email'], "Alta como $rol por {$yo['nombre']} {$yo['apellidos']}");
                $nuevo = $this->usuarios->buscar($id);
                if ($this->acceso->enviarEnlace($nuevo, 'activar')) {
                    Seguridad::aviso("Usuario dado de alta. Le hemos enviado a {$datos['email']} el enlace para verificar su email y elegir su contraseña.");
                } else {
                    Seguridad::aviso('Usuario dado de alta, pero no se ha podido enviar el email de activación. Usa «Reenviar activación».', 'error');
                }
                $this->redirigir('index.php?accion=usuarios');
            } catch (PDOException $e) {
                $errores[] = 'No se ha podido dar de alta al usuario.';
            }
        }

        $this->formulario($errores, $valores);
    }

    // index.php?accion=usuario_editar&id=3
    public function editar(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $usuario = $id ? $this->usuarios->buscar($id) : null;
        if (!$usuario || !$usuario['activo']) {
            $this->noEncontrada();
            return;
        }
        $this->formulario([], $usuario, $usuario);
    }

    // index.php?accion=usuario_actualizar (POST con id)
    public function actualizar(): void
    {
        $usuario = $this->usuarioDelFormulario();
        if (!$usuario || !$usuario['activo']) {
            Seguridad::aviso('Ese usuario no existe o está de baja.', 'error');
            $this->redirigir('index.php?accion=usuarios');
        }

        $yo = Seguridad::usuario();
        [$datos, $rol, $clienteId, $errores, $valores, $nuevaFicha] = $this->leerFormulario($usuario);

        // Quitar el rol de admin: nunca a uno mismo, y nunca al último admin
        if ($usuario['rol'] === 'admin' && $rol === 'usuario') {
            if ($usuario['id'] === $yo['id']) {
                $errores[] = 'No puedes quitarte el rol de admin a ti mismo.';
            } elseif ($this->usuarios->adminsActivos() <= 1) {
                $errores[] = 'Es el único admin: no puede pasar a ser usuario.';
            }
        }

        if (!$errores) {
            $emailCambiado = $datos['email'] !== $usuario['email'];
            try {
                if ($nuevaFicha) {
                    $clienteId = $this->clientes->crear($datos['nombre'], $datos['apellidos'], $datos['telefono']);
                }
                $this->usuarios->actualizar($usuario['id'], $datos, $rol, $clienteId, $emailCambiado);
            } catch (PDOException $e) {
                $this->formulario(['No se han podido guardar los cambios.'], $valores, $usuario);
                return;
            }

            $cambios = [];
            foreach (['nombre', 'apellidos', 'dni', 'email', 'telefono'] as $campo) {
                if ($datos[$campo] !== $usuario[$campo]) $cambios[] = $campo;
            }
            if ($rol !== $usuario['rol']) $cambios[] = "rol ($usuario[rol] → $rol)";
            if ($clienteId !== ($usuario['cliente_id'] !== null ? (int) $usuario['cliente_id'] : null)) $cambios[] = 'cliente';
            $this->usuarios->apuntar('edicion', $usuario['id'], $datos['email'],
                'Cambios por ' . $yo['nombre'] . ' ' . $yo['apellidos'] . ': ' . ($cambios ? implode(', ', $cambios) : 'ninguno'));

            $mensaje = "Datos de {$datos['nombre']} {$datos['apellidos']} guardados.";
            if ($emailCambiado) {
                // Aviso al email antiguo: si no lo ha pedido, que llame al taller
                Correo::enviar($usuario['email'], 'Han cambiado el email de tu cuenta de Taller Genil',
                    "Hola, {$usuario['nombre']}:\n\nEl email de tu cuenta ha cambiado a {$datos['email']}.\n"
                    . "Si no lo has pedido tú, llama al taller cuanto antes.");
                $actualizado = $this->usuarios->buscar($usuario['id']);
                if ($this->acceso->enviarEnlace($actualizado, 'verificar')) {
                    $mensaje .= " Hasta que confirme el email nuevo con el enlace que le hemos enviado, no podrá entrar.";
                } else {
                    $mensaje .= " No se ha podido enviar el enlace para verificar el email nuevo: usa «Reenviar verificación».";
                }
            }
            Seguridad::aviso($mensaje);
            $this->redirigir('index.php?accion=usuarios');
        }

        $this->formulario($errores, $valores, $usuario);
    }

    // Lee y comprueba el formulario de alta o edición ($usuarioActual: el que se edita).
    // Devuelve [datos limpios, rol, cliente, errores, valores para volver a mostrarlos, ¿crear ficha nueva?]
    private function leerFormulario(?array $usuarioActual): array
    {
        $usuarioId = $usuarioActual['id'] ?? null;
        $entrada = fn(string $campo) => is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
        $datos = [
            'nombre' => Validar::nombre($entrada('nombre'), 60),
            'apellidos' => Validar::nombre($entrada('apellidos'), 120),
            'dni' => Validar::dni($entrada('dni')),
            'email' => Validar::email($entrada('email')),
            'telefono' => Validar::telefono($entrada('telefono')),
        ];
        $rol = $entrada('rol');
        $nuevaFicha = $entrada('cliente_id') === 'nueva';
        $clienteId = $nuevaFicha ? null : (filter_var($entrada('cliente_id'), FILTER_VALIDATE_INT) ?: null);
        // Quien se registró solo puede seguir sin ficha hasta que el admin la vincule
        $puedeSeguirSinFicha = $usuarioActual && $usuarioActual['rol'] === 'usuario' && $usuarioActual['cliente_id'] === null;

        $errores = [];
        if (!$datos['nombre']) $errores[] = 'Escribe un nombre válido (solo letras, de 2 a 60).';
        if (!$datos['apellidos']) $errores[] = 'Escribe unos apellidos válidos (solo letras, de 2 a 120).';
        if (!$datos['dni']) $errores[] = 'El DNI o NIE no es válido: revisa los números y la letra.';
        elseif ($this->usuarios->existe('dni', $datos['dni'], $usuarioId)) $errores[] = 'Ya hay otro usuario con ese DNI (puede estar dado de baja).';
        if (!$datos['email']) $errores[] = 'El email no es válido.';
        elseif ($this->usuarios->existe('email', $datos['email'], $usuarioId)) $errores[] = 'Ya hay otro usuario con ese email (puede estar dado de baja).';
        if (!$datos['telefono']) $errores[] = 'El teléfono debe ser español: 9 cifras que empiezan por 6, 7, 8 o 9.';
        if (!in_array($rol, ['admin', 'usuario'], true)) $errores[] = 'Elige el rol.';

        // Un usuario normal es un cliente: hay que decir cuál (y que no tenga ya otra cuenta)
        if ($rol === 'usuario' && !$nuevaFicha) {
            $libres = array_column($this->usuarios->clientesSinCuenta($usuarioId), 'id');
            $sinFichaPermitido = !$clienteId && $puedeSeguirSinFicha;
            if (!$sinFichaPermitido && (!$clienteId || !in_array($clienteId, $libres))) {
                $errores[] = 'Elige la ficha de cliente de este usuario, o «Crear una ficha nueva» (solo verá sus facturas y sus datos).';
            }
        } elseif ($rol !== 'usuario') {
            $clienteId = null;
            $nuevaFicha = false;
        }

        $valores = [
            'nombre' => $entrada('nombre'), 'apellidos' => $entrada('apellidos'), 'dni' => $entrada('dni'),
            'email' => $entrada('email'), 'telefono' => $entrada('telefono'), 'rol' => $rol,
            'cliente_id' => $nuevaFicha ? 'nueva' : $clienteId,
        ];
        return [$datos, $rol, $clienteId, $errores, $valores, $nuevaFicha];
    }

    // index.php?accion=usuario_baja (POST con id y motivo)
    public function baja(): void
    {
        $usuario = $this->usuarioDelFormulario();
        $motivo = trim((string) ($_POST['motivo'] ?? ''));
        $yo = Seguridad::usuario();

        if (!$usuario || !$usuario['activo']) {
            Seguridad::aviso('Ese usuario no existe o ya está de baja.', 'error');
        } elseif ($usuario['id'] === $yo['id']) {
            Seguridad::aviso('No puedes darte de baja a ti mismo.', 'error');
        } elseif ($usuario['rol'] === 'admin' && $this->usuarios->adminsActivos() <= 1) {
            Seguridad::aviso('Es el único admin: no se puede dar de baja.', 'error');
        } elseif ($motivo === '' || mb_strlen($motivo) > 255) {
            Seguridad::aviso('Escribe el motivo de la baja (255 caracteres como máximo).', 'error');
        } else {
            $this->usuarios->darDeBaja($usuario['id'], $motivo);
            $this->usuarios->apuntar('baja', $usuario['id'], $usuario['email'], "Baja por {$yo['nombre']} {$yo['apellidos']}: $motivo");
            Seguridad::aviso("{$usuario['nombre']} {$usuario['apellidos']} está de baja. Ya no puede entrar y, si estaba dentro, su sesión se ha cerrado.");
        }
        $this->redirigir('index.php?accion=usuarios');
    }

    // index.php?accion=usuario_alta (POST con id): vuelve a dar de alta a alguien de baja
    public function reactivar(): void
    {
        $usuario = $this->usuarioDelFormulario();
        $yo = Seguridad::usuario();
        if (!$usuario || $usuario['activo']) {
            Seguridad::aviso('Ese usuario no existe o ya está de alta.', 'error');
            $this->redirigir('index.php?accion=usuarios&ver=bajas');
        }
        $this->usuarios->darDeAlta($usuario['id']);
        $this->usuarios->apuntar('reactivacion', $usuario['id'], $usuario['email'], "Alta de nuevo por {$yo['nombre']} {$yo['apellidos']}");
        Seguridad::aviso("{$usuario['nombre']} {$usuario['apellidos']} vuelve a estar de alta.");
        $this->redirigir('index.php?accion=usuarios');
    }

    // index.php?accion=usuario_reenviar (POST con id)
    // Sin contraseña todavía → enlace de activación; con email sin verificar → enlace de verificación
    public function reenviar(): void
    {
        $usuario = $this->usuarioDelFormulario();
        $tipo = match (true) {
            !$usuario || !$usuario['activo'] => null,
            $usuario['password_hash'] === null => 'activar',
            $usuario['email_verificado'] === null => 'verificar',
            default => null,
        };
        if (!$tipo) {
            Seguridad::aviso('Este usuario no tiene nada pendiente de activar ni de verificar.', 'error');
        } elseif ($this->acceso->enviarEnlace($usuario, $tipo)) {
            $this->usuarios->apuntar('reenvio_' . $tipo, $usuario['id'], $usuario['email']);
            Seguridad::aviso("Enlace reenviado a {$usuario['email']}.");
        } else {
            Seguridad::aviso('No se ha podido enviar el email.', 'error');
        }
        $this->redirigir('index.php?accion=usuarios');
    }

    // index.php?accion=usuario_password (POST con id, password y password2)
    // El admin pone una contraseña TEMPORAL: el usuario tendrá que cambiarla al entrar,
    // y las sesiones que tuviera abiertas se cierran
    public function passwordTemporal(): void
    {
        $usuario = $this->usuarioDelFormulario();
        $yo = Seguridad::usuario();
        if (!$usuario || !$usuario['activo']) {
            Seguridad::aviso('Ese usuario no existe o está de baja.', 'error');
            $this->redirigir('index.php?accion=usuarios');
        }
        $volver = 'index.php?accion=usuario_editar&id=' . $usuario['id'];
        if ($usuario['id'] === $yo['id']) {
            Seguridad::aviso('Tu propia contraseña se cambia en «Mi cuenta».', 'error');
            $this->redirigir('index.php?accion=cambiar_password');
        }
        if ($usuario['password_hash'] === null || $usuario['email_verificado'] === null) {
            Seguridad::aviso('Este usuario aún no ha activado su cuenta ni verificado su email: usa «Reenviar activación».', 'error');
            $this->redirigir($volver);
        }

        $password = (string) ($_POST['password'] ?? '');
        $errores = Validar::password($password, (string) ($_POST['password2'] ?? ''), $usuario);
        if ($errores) {
            foreach ($errores as $error) {
                Seguridad::aviso($error, 'error');
            }
            $this->redirigir($volver . '#contrasena');
        }

        $this->usuarios->cambiarPassword($usuario['id'], Seguridad::hashPassword($password), true);
        $this->usuarios->anularCodigos($usuario['id']);
        $this->usuarios->apuntar('password_admin', $usuario['id'], $usuario['email'],
            "Contraseña temporal puesta por {$yo['nombre']} {$yo['apellidos']}");
        Correo::enviar($usuario['email'], 'El taller ha cambiado tu contraseña',
            "Hola, {$usuario['nombre']}:\n\n"
            . "El taller ha puesto una contraseña temporal en tu cuenta. Te la darán en persona o por teléfono:\n"
            . "nunca por email. Al entrar tendrás que elegir una nueva.\n"
            . "Si no lo has pedido tú, llama al taller.");
        Seguridad::aviso("Contraseña temporal guardada para {$usuario['nombre']} {$usuario['apellidos']}. "
            . 'Dásela en persona o por teléfono: al entrar tendrá que cambiarla. Sus sesiones abiertas se han cerrado.');
        $this->redirigir('index.php?accion=usuarios');
    }

    // index.php?accion=usuario_enlace_password (POST con id)
    // Lo más seguro: le llega un enlace y la elige él; nadie más la conoce
    public function enlacePassword(): void
    {
        $usuario = $this->usuarioDelFormulario();
        $yo = Seguridad::usuario();
        if (!$usuario || !$usuario['activo'] || $usuario['password_hash'] === null) {
            Seguridad::aviso('Solo para usuarios de alta que ya activaron su cuenta.', 'error');
        } elseif ($this->acceso->enviarEnlace($usuario, 'restablecer')) {
            $this->usuarios->apuntar('enlace_password_admin', $usuario['id'], $usuario['email'],
                "Enlace de cambio de contraseña enviado por {$yo['nombre']} {$yo['apellidos']}");
            Seguridad::aviso("Enlace para cambiar la contraseña enviado a {$usuario['email']} (caduca en 1 hora).");
        } else {
            Seguridad::aviso('No se ha podido enviar el email.', 'error');
        }
        $this->redirigir('index.php?accion=usuarios');
    }

    // index.php?accion=mi_cuenta  — cualquier usuario: solo sus propios datos
    public function miCuenta(): void
    {
        $yo = Seguridad::usuario();
        $this->mostrar('mi_cuenta', [
            'titulo' => 'Mi cuenta',
            'yo' => $yo,
            'vehiculos' => $yo['cliente_id'] ? $this->clientes->vehiculos((int) $yo['cliente_id']) : [],
        ]);
    }

    private function usuarioDelFormulario(): ?array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('index.php?accion=usuarios');
        }
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        return $id ? $this->usuarios->buscar($id) : null;
    }

    // $editando: el usuario que se edita (null = alta nueva)
    private function formulario(array $errores, array $valores, ?array $editando = null): void
    {
        $this->mostrar('usuario_form', [
            'titulo' => $editando ? 'Editar usuario' : (($valores['rol'] ?? '') === 'admin' ? 'Nuevo administrador' : 'Nuevo cliente'),
            'puedeSinFicha' => $editando && $editando['rol'] === 'usuario' && $editando['cliente_id'] === null,
            'errores' => $errores,
            'valores' => $valores,
            'editando' => $editando,
            'esYo' => $editando && $editando['id'] === Seguridad::usuario()['id'],
            'clientes' => $this->usuarios->clientesSinCuenta($editando['id'] ?? null),
        ]);
    }
}
