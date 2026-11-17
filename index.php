<?php
// Controlador frontal: todas las peticiones de la aplicación entran por aquí.
// Aquí se decide, antes de nada, quién puede ver cada página.
require __DIR__ . '/config/conexion.php';
require __DIR__ . '/nucleo/Seguridad.php';
require __DIR__ . '/nucleo/Validar.php';
require __DIR__ . '/nucleo/Correo.php';
require __DIR__ . '/vistas/ayudas.php';
require __DIR__ . '/modelos/ReparacionesModelo.php';
require __DIR__ . '/modelos/CatalogoModelo.php';
require __DIR__ . '/modelos/ClienteModelo.php';
require __DIR__ . '/modelos/FacturaModelo.php';
require __DIR__ . '/modelos/EstadisticaModelo.php';
require __DIR__ . '/modelos/UsuarioModelo.php';
require __DIR__ . '/controladores/Controlador.php';
require __DIR__ . '/controladores/ReparacionesControlador.php';
require __DIR__ . '/controladores/ClientesControlador.php';
require __DIR__ . '/controladores/FacturasControlador.php';
require __DIR__ . '/controladores/EstadisticasControlador.php';
require __DIR__ . '/controladores/AccesoControlador.php';
require __DIR__ . '/controladores/UsuariosControlador.php';

// Cabeceras de seguridad, HTTPS y sesión
Seguridad::iniciar();

$pdo = conectar();
$usuarioModelo = new UsuarioModelo($pdo);
Seguridad::cargarUsuario($usuarioModelo);

$reparacionModelo = new ReparacionModelo($pdo);
$clienteModelo = new ClienteModelo($pdo);
$reparaciones = new ReparacionesControlador($reparacionModelo, new CatalogoModelo($pdo));
$clientes = new ClientesControlador($clienteModelo);
$facturas = new FacturasControlador(new FacturaModelo($pdo), $reparacionModelo);
$estadisticas = new EstadisticasControlador(new EstadisticaModelo($pdo));
$acceso = new AccesoControlador($usuarioModelo);
$usuarios = new UsuariosControlador($usuarioModelo, $clienteModelo, $acceso);

// Cada página: [quién puede verla, qué hace]
//   publica  → cualquiera
//   invitado → solo quien NO ha entrado (login, código…)
//   usuario  → cualquier usuario conectado (cliente o admin)
//   admin    → solo admin
$rutas = [
    // Acceso
    'login'            => ['invitado', fn() => $acceso->login()],
    'codigo'           => ['invitado', fn() => $acceso->codigo()],
    'reenviar_codigo'  => ['invitado', fn() => $acceso->reenviarCodigo()],
    'recuperar'        => ['invitado', fn() => $acceso->recuperar()],
    'registro'         => ['invitado', fn() => $acceso->registro()],
    'cambiar_password' => ['usuario',  fn() => $acceso->cambiarPassword()],
    'activar'          => ['publica',  fn() => $acceso->activar()],
    'restablecer'      => ['publica',  fn() => $acceso->restablecer()],
    'verificar_email'  => ['publica',  fn() => $acceso->verificarEmail()],
    'correos_dev'      => ['publica',  fn() => $acceso->correosDesarrollo()],
    'salir'            => ['usuario',  fn() => $acceso->salir()],
    'sala'             => ['publica',  fn() => $clientes->salaEspera()],
    // Área del cliente: solo lo suyo
    'mis_facturas'     => ['usuario',  fn() => $facturas->misFacturas()],
    'mi_factura'       => ['usuario',  fn() => $facturas->miFactura()],
    'mi_cuenta'        => ['usuario',  fn() => $usuarios->miCuenta()],
    // Gestión del taller: solo admin
    'listado'          => ['admin',    fn() => $reparaciones->listado()],
    'ver'              => ['admin',    fn() => $reparaciones->ver()],
    'nueva'            => ['admin',    fn() => $reparaciones->nueva()],
    'guardar'          => ['admin',    fn() => $reparaciones->guardar()],
    'anadir_pieza'     => ['admin',    fn() => $reparaciones->anadirPieza()],
    'quitar_pieza'     => ['admin',    fn() => $reparaciones->quitarPieza()],
    'mano_obra'        => ['admin',    fn() => $reparaciones->manoObra()],
    'facturas'         => ['admin',    fn() => $facturas->listado()],
    'factura'          => ['admin',    fn() => $facturas->ver()],
    'facturar'         => ['admin',    fn() => $facturas->facturar()],
    'ventas'           => ['admin',    fn() => $estadisticas->ver()],
    'clientes'         => ['admin',    fn() => $clientes->listado()],
    'cliente'          => ['admin',    fn() => $clientes->ver()],
    'usuarios'         => ['admin',    fn() => $usuarios->listado()],
    'usuario_nuevo'    => ['admin',    fn() => $usuarios->nuevo()],
    'usuario_guardar'  => ['admin',    fn() => $usuarios->guardar()],
    'usuario_editar'   => ['admin',    fn() => $usuarios->editar()],
    'usuario_actualizar' => ['admin',  fn() => $usuarios->actualizar()],
    'usuario_baja'     => ['admin',    fn() => $usuarios->baja()],
    'usuario_alta'     => ['admin',    fn() => $usuarios->reactivar()],
    'usuario_reenviar' => ['admin',    fn() => $usuarios->reenviar()],
    'usuario_password' => ['admin',    fn() => $usuarios->passwordTemporal()],
    'usuario_enlace_password' => ['admin', fn() => $usuarios->enlacePassword()],
];

$yo = Seguridad::usuario();
$inicio = !$yo ? 'login' : ($yo['rol'] === 'admin' ? 'listado' : 'mis_facturas');
$accion = is_string($_GET['accion'] ?? null) ? $_GET['accion'] : $inicio;

if (!isset($rutas[$accion])) {
    $reparaciones->noEncontrada();
    exit;
}
[$permiso, $ejecutar] = $rutas[$accion];

// 1. Todo formulario debe traer el token CSRF de esta sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Seguridad::peticionValida()) {
    $reparaciones->prohibido('El formulario ha caducado o no viene de esta aplicación. Vuelve atrás, recarga la página e inténtalo otra vez.');
    exit;
}

// 2. Páginas solo para quien no ha entrado: si ya está dentro, a su inicio
if ($permiso === 'invitado' && $yo) {
    header('Location: index.php?accion=' . $inicio);
    exit;
}

// 3. Páginas privadas: hay que entrar primero (y luego se vuelve aquí)
if (($permiso === 'usuario' || $permiso === 'admin') && !$yo) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion'])) {
        $_SESSION['volver'] = http_build_query(array_intersect_key($_GET, array_flip(['accion', 'id', 'estado', 'q', 'periodo', 'ver'])));
    }
    header('Location: index.php?accion=login');
    exit;
}

// 4. Contraseña temporal puesta por el admin: hay que cambiarla antes de nada más
if ($yo && $yo['debe_cambiar_password'] && !in_array($accion, ['cambiar_password', 'salir'], true)) {
    header('Location: index.php?accion=cambiar_password');
    exit;
}

// 5. Páginas de admin: un usuario normal no pasa
if ($permiso === 'admin' && $yo['rol'] !== 'admin') {
    $reparaciones->prohibido('Esta parte es solo para el personal del taller.');
    exit;
}

$ejecutar();
