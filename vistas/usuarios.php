<?php
require __DIR__ . '/cabecera.php';

$eventos = [
    'alta' => 'Alta', 'baja' => 'Baja', 'reactivacion' => 'Alta de nuevo', 'activacion' => 'Cuenta activada',
    'login_paso1' => 'Contraseña correcta', 'login_ok' => 'Entrada', 'login_fallo' => 'Entrada fallida',
    'codigo_fallo' => 'Código erróneo', 'bloqueo' => 'Bloqueo', 'logout' => 'Salida',
    'recuperar' => 'Pide recuperar', 'restablecer' => 'Contraseña cambiada', 'enlace_invalido' => 'Enlace no válido',
    'reenvio_activar' => 'Reenvío de activación', 'reenvio_verificar' => 'Reenvío de verificación',
    'edicion' => 'Datos cambiados', 'email_verificado' => 'Email verificado',
    'registro' => 'Se registra', 'registro_repetido' => 'Registro repetido', 'registro_robot' => 'Registro de robot',
    'cambio_password' => 'Cambia su contraseña', 'cambio_password_fallo' => 'Contraseña actual errónea',
    'password_admin' => 'Contraseña temporal', 'enlace_password_admin' => 'Enlace de contraseña',
];
$alertas = ['login_fallo', 'codigo_fallo', 'bloqueo', 'enlace_invalido', 'registro_robot', 'cambio_password_fallo'];
?>

<div class="acciones">
  <a class="boton" href="index.php?accion=usuario_nuevo&amp;rol=admin">+ Nuevo administrador</a>
  <a class="boton" href="index.php?accion=usuario_nuevo&amp;rol=usuario">+ Nuevo cliente</a>
</div>

<?php if ($sinFicha > 0): ?>
  <p class="aviso"><?= $sinFicha === 1 ? '1 cliente registrado espera' : "$sinFicha clientes registrados esperan" ?>
    que le vincules su ficha de cliente para ver sus facturas. Están marcados con «Sin ficha»: pulsa «Editar».</p>
<?php endif; ?>

<nav class="filtros" aria-label="Qué ver">
  <?php foreach (['activos' => 'De alta', 'bajas' => 'De baja', 'accesos' => 'Registro de accesos'] as $valor => $texto): ?>
    <a href="index.php?accion=usuarios&amp;ver=<?= $valor ?>"<?= $ver === $valor ? ' class="activo" aria-current="page"' : '' ?>><?= $texto ?></a>
  <?php endforeach; ?>
</nav>

<?php if ($ver === 'accesos'): ?>
  <p class="suave">Los últimos 150 movimientos: entradas, fallos, bloqueos, altas y bajas.</p>
  <div class="tabla-scroll">
    <table>
      <thead><tr><th>Fecha</th><th>Evento</th><th>Usuario</th><th>IP</th><th>Detalle</th></tr></thead>
      <tbody>
        <?php foreach ($accesos as $a): ?>
          <tr>
            <td class="num"><?= e(date('d/m/Y H:i:s', strtotime($a['fecha']))) ?></td>
            <td><span class="chapa <?= in_array($a['evento'], $alertas, true) ? 'chapa-alerta' : 'chapa-facturada' ?>"><?= e($eventos[$a['evento']] ?? $a['evento']) ?></span></td>
            <td><?= e($a['usuario'] ?? '') ?><br><span class="suave"><?= e($a['email'] ?? '') ?></span></td>
            <td class="suave"><?= e($a['ip']) ?></td>
            <td class="suave"><?= e($a['detalle'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php elseif (!$usuarios): ?>
  <p class="vacio"><?= $ver === 'activos' ? 'No hay usuarios de alta.' : 'No hay nadie de baja.' ?></p>

<?php elseif ($ver === 'activos'): ?>
  <div class="tabla-scroll">
    <table class="tabla-usuarios">
      <thead><tr><th>Usuario</th><th>DNI</th><th>Email</th><th>Teléfono</th><th>Rol</th><th>Alta</th><th>Último acceso</th><th class="num">Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><strong><?= e($u['nombre'] . ' ' . $u['apellidos']) ?></strong>
              <?php if ($u['cliente']): ?><br><span class="suave">Cliente: <?= e($u['cliente']) ?></span>
              <?php elseif ($u['rol'] === 'usuario'): ?><br><span class="chapa chapa-alerta">Sin ficha</span><?php endif; ?></td>
            <td><?= e($u['dni']) ?></td>
            <td><?= e($u['email']) ?><br>
              <?php if (!$u['cuenta_activada']): ?>
                <span class="pendiente"><?= Seguridad::hayCorreo() ? 'Pendiente de activar' : 'Sin contraseña: ponle una temporal' ?></span>
              <?php elseif ($u['email_verificado']): ?>
                <span class="verificado">✓ Verificado</span>
              <?php elseif (Seguridad::hayCorreo()): ?>
                <span class="pendiente">Email nuevo sin verificar</span>
              <?php endif; ?></td>
            <td><?= e($u['telefono']) ?></td>
            <td><span class="chapa <?= $u['rol'] === 'admin' ? 'chapa-admin' : 'chapa-facturada' ?>"><?= $u['rol'] === 'admin' ? 'Admin' : 'Usuario' ?></span></td>
            <td><?= fecha($u['fecha_alta']) ?><br><span class="suave"><?= $u['creado_por'] ? e('por ' . $u['creado_por']) : ($u['rol'] === 'usuario' ? 'Registro propio' : '') ?></span></td>
            <td><?= $u['ultimo_acceso'] ? e(date('d/m/Y H:i', strtotime($u['ultimo_acceso']))) : '<span class="suave">Nunca</span>' ?></td>
            <td class="num">
              <a class="boton boton-mini boton-secundario en-linea" href="index.php?accion=usuario_editar&amp;id=<?= (int) $u['id'] ?>">Editar</a>
              <?php if (Seguridad::hayCorreo() && (!$u['cuenta_activada'] || !$u['email_verificado'])): ?>
                <form method="post" action="index.php?accion=usuario_reenviar" class="en-linea">
                  <?= csrf() ?>
                  <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <button class="boton-mini boton-secundario"><?= $u['cuenta_activada'] ? 'Reenviar verificación' : 'Reenviar activación' ?></button>
                </form>
              <?php endif; ?>
              <?php if ($u['id'] !== $yo): ?>
                <form method="post" action="index.php?accion=usuario_baja" class="form-baja"
                      data-confirmar="<?= e('¿Dar de baja a ' . $u['nombre'] . ' ' . $u['apellidos'] . '? No podrá entrar, pero sus datos se conservan en el registro.') ?>"
                      data-aceptar="Dar de baja">
                  <?= csrf() ?>
                  <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <input type="text" name="motivo" required maxlength="255" placeholder="Motivo de la baja" aria-label="Motivo de la baja de <?= e($u['nombre']) ?>">
                  <button class="boton-mini boton-peligro">Dar de baja</button>
                </form>
              <?php else: ?>
                <span class="suave">Eres tú</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php else: ?>
  <div class="tabla-scroll">
    <table>
      <thead><tr><th>Usuario</th><th>DNI</th><th>Email</th><th>Rol</th><th>Alta</th><th>Baja</th><th>Motivo</th><th class="num">Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><strong><?= e($u['nombre'] . ' ' . $u['apellidos']) ?></strong></td>
            <td><?= e($u['dni']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= $u['rol'] === 'admin' ? 'Admin' : 'Usuario' ?></td>
            <td><?= fecha($u['fecha_alta']) ?></td>
            <td><?= fecha($u['fecha_baja']) ?></td>
            <td class="suave"><?= e($u['motivo_baja'] ?? '') ?></td>
            <td class="num">
              <form method="post" action="index.php?accion=usuario_alta"
                    data-confirmar="<?= e('¿Dar de alta de nuevo a ' . $u['nombre'] . ' ' . $u['apellidos'] . '?') ?>" data-aceptar="Dar de alta">
                <?= csrf() ?>
                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                <button class="boton-mini boton-verde">Dar de alta</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/pie.php'; ?>
