<?php
// Crea el primer admin. Solo se puede ejecutar desde la línea de comandos:
//
//   php herramientas/crear_admin.php --nombre="Ana" --apellidos="García López" \
//       --dni=12345678Z --email=ana@ejemplo.es --telefono=600000000
//
// El admin recibe por email (o en almacen/correos en desarrollo) el enlace para
// verificar su email y elegir su contraseña. El enlace también se muestra aquí.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../config/conexion.php';
require __DIR__ . '/../nucleo/Seguridad.php';
require __DIR__ . '/../nucleo/Validar.php';
require __DIR__ . '/../nucleo/Correo.php';
require __DIR__ . '/../modelos/UsuarioModelo.php';
require __DIR__ . '/../modelos/ClienteModelo.php';
require __DIR__ . '/../controladores/Controlador.php';
require __DIR__ . '/../controladores/AccesoControlador.php';

$opciones = getopt('', ['nombre:', 'apellidos:', 'dni:', 'email:', 'telefono:']);
$datos = [
    'nombre' => Validar::nombre($opciones['nombre'] ?? '', 60),
    'apellidos' => Validar::nombre($opciones['apellidos'] ?? '', 120),
    'dni' => Validar::dni($opciones['dni'] ?? ''),
    'email' => Validar::email($opciones['email'] ?? ''),
    'telefono' => Validar::telefono($opciones['telefono'] ?? ''),
];
foreach ($datos as $campo => $valor) {
    if (!$valor) {
        fwrite(STDERR, "El campo --$campo falta o no es válido.\n");
        exit(1);
    }
}

$usuarios = new UsuarioModelo(conectar());
foreach (['dni', 'email'] as $campo) {
    if ($usuarios->existe($campo, $datos[$campo])) {
        fwrite(STDERR, "Ya hay un usuario con ese $campo.\n");
        exit(1);
    }
}

$id = $usuarios->crear($datos, 'admin', null, null);
$usuarios->apuntar('alta', $id, $datos['email'], 'Alta del admin desde la línea de comandos');

// Se crea el enlace aquí para poder mostrarlo también en pantalla
$token = Seguridad::tokenAleatorio();
$usuarios->crearCodigo($id, 'activar', Seguridad::huella($token), 48 * 60);
$enlace = Seguridad::config('urlBase', '') . 'index.php?accion=activar&token=' . $token;
$enviado = Correo::enviar($datos['email'], 'Activa tu cuenta de admin en Taller Genil',
    "Hola, {$datos['nombre']}:\n\nAbre este enlace para verificar tu email y elegir tu contraseña:\n\n$enlace\n\nCaduca en 48 horas.");

echo "Admin creado (id $id).\n";
echo $enviado ? "Email de activación enviado a {$datos['email']}.\n" : "No se ha podido enviar el email.\n";
echo "Enlace de activación (caduca en 48 h):\n$enlace\n";
