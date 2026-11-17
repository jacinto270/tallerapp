<?php
// La acción que se está mostrando (la decide index.php según el rol)
$accionActual = $GLOBALS['accion'] ?? 'listado';
$estadoActual = $_GET['estado'] ?? '';
$conectado = Seguridad::usuario();

// Menú según quién está conectado: [dirección, texto, ¿activo?]
if ($conectado && $conectado['rol'] === 'admin') {
    $menu = [
        ['index.php?accion=listado', 'Reparaciones',
            ($accionActual === 'listado' && $estadoActual !== 'taller') || in_array($accionActual, ['ver', 'anadir_pieza', 'quitar_pieza', 'mano_obra'])],
        ['index.php?accion=listado&estado=taller', 'En el taller',
            $accionActual === 'listado' && $estadoActual === 'taller'],
        ['index.php?accion=nueva', 'Nueva reparación', in_array($accionActual, ['nueva', 'guardar'])],
        ['index.php?accion=clientes', 'Clientes', in_array($accionActual, ['clientes', 'cliente'])],
        ['index.php?accion=facturas', 'Facturas', in_array($accionActual, ['facturas', 'factura', 'facturar'])],
        ['index.php?accion=ventas', 'Ventas', $accionActual === 'ventas'],
        ['index.php?accion=usuarios', 'Usuarios', str_starts_with($accionActual, 'usuario')],
        ['index.php?accion=sala', 'Sala de espera', $accionActual === 'sala'],
        ['index.php?accion=mi_cuenta', 'Mi cuenta', in_array($accionActual, ['mi_cuenta', 'cambiar_password'])],
    ];
} elseif ($conectado) {
    $menu = [
        ['index.php?accion=mis_facturas', 'Mis facturas', in_array($accionActual, ['mis_facturas', 'mi_factura'])],
        ['index.php?accion=mi_cuenta', 'Mi cuenta', in_array($accionActual, ['mi_cuenta', 'cambiar_password'])],
        ['index.php?accion=sala', 'Sala de espera', $accionActual === 'sala'],
    ];
} else {
    $menu = [
        ['index.php?accion=sala', 'Sala de espera', $accionActual === 'sala'],
        ['index.php?accion=login', 'Entrar', in_array($accionActual, ['login', 'codigo', 'recuperar', 'activar', 'restablecer', 'verificar_email'])],
        ['index.php?accion=registro', 'Crear cuenta', $accionActual === 'registro'],
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <meta name="color-scheme" content="dark light">
  <meta name="theme-color" content="#06060d" media="(prefers-color-scheme: dark)">
  <meta name="theme-color" content="#eef1f8" media="(prefers-color-scheme: light)">
  <script src="tema.js"></script>
  <title><?= e($titulo) ?> · Taller Genil</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Exo+2:wght@400;500;600;700&family=Oswald:wght@500&family=Orbitron:wght@500;700;900&display=swap">
  <link rel="stylesheet" href="estilos.css">
  <script src="app.js" defer></script>
</head>
<body data-pagina="<?= e($paginaGuia ?? $accionActual) ?>">
  <header class="cabecera">
    <div class="contenedor">
      <a class="marca" href="index.php">
        <?= cocheSvg('logo', '70 26 276 82') ?>
        <span>Taller Genil<small>Racing service</small></span>
      </a>
      <nav class="menu" aria-label="Menú principal">
        <?php foreach ($menu as [$url, $texto, $activo]): ?>
          <a href="<?= e($url) ?>"<?= $activo ? ' class="activo" aria-current="page"' : '' ?>><?= e($texto) ?></a>
        <?php endforeach; ?>
      </nav>
      <!-- Lo muestra app.js: sin JavaScript se usa el tema del sistema -->
      <button type="button" class="boton-tema" data-boton-tema hidden>
        <span class="boton-tema-icono" aria-hidden="true">◐</span>
        <span class="boton-tema-texto">Auto</span>
      </button>
      <?php if ($conectado): ?>
        <div class="sesion">
          <span class="sesion-nombre"><?= e($conectado['nombre']) ?>
            <small><?= $conectado['rol'] === 'admin' ? 'Admin' : 'Cliente' ?></small></span>
          <form method="post" action="index.php?accion=salir">
            <?= csrf() ?>
            <button class="boton-mini boton-secundario">Salir</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </header>
  <main class="contenedor">
    <h1><?= icono('engranaje', 'h1-icono') ?><?= e($titulo) ?></h1>
    <?php foreach (Seguridad::tomarAvisos() as [$tipoAviso, $textoAviso]): ?>
      <p class="<?= $tipoAviso === 'error' ? 'error' : 'aviso' ?>" role="<?= $tipoAviso === 'error' ? 'alert' : 'status' ?>"><?= e($textoAviso) ?></p>
    <?php endforeach; ?>
