<?php require __DIR__ . '/cabecera.php'; ?>

<div class="ficha-rejilla">
  <section class="tarjeta">
    <h3><?= icono('cliente') ?> Mis datos</h3>
    <dl>
      <dt>Nombre</dt><dd><?= e($yo['nombre'] . ' ' . $yo['apellidos']) ?></dd>
      <dt>DNI</dt><dd><?= e($yo['dni']) ?></dd>
      <dt>Email</dt><dd><?= e($yo['email']) ?> <?= $yo['email_verificado'] ? '<span class="verificado">✓ Verificado</span>' : '' ?></dd>
      <dt>Teléfono</dt><dd><?= e($yo['telefono']) ?></dd>
      <dt>Rol</dt><dd><?= $yo['rol'] === 'admin' ? 'Admin' : 'Cliente' ?></dd>
      <dt>Alta</dt><dd><?= fecha($yo['fecha_alta']) ?></dd>
    </dl>
  </section>

  <section class="tarjeta">
    <h3><?= icono('llave') ?> Seguridad</h3>
    <p>Para entrar usas tu contraseña y un código que te enviamos por email cada vez.</p>
    <p><a class="boton boton-secundario" href="index.php?accion=cambiar_password">Cambiar la contraseña</a></p>
    <p class="suave">Si ves algo raro en tus datos, llama al taller.</p>
    <?php if ($yo['rol'] === 'usuario' && !$yo['cliente_id']): ?>
      <p class="aviso">Tu cuenta aún no está vinculada a tu ficha de cliente: enseña tu DNI en el taller para ver tus facturas.</p>
    <?php endif; ?>
  </section>
</div>

<?php if ($vehiculos): ?>
  <h2>Mis vehículos</h2>
  <div class="rejilla">
    <?php foreach ($vehiculos as $v): ?>
      <article class="tarjeta">
        <?= matricula($v['matricula']) ?>
        <h3 class="vehiculo-nombre"><?= e($v['marca'] . ' ' . $v['modelo']) ?></h3>
        <p class="suave">Año <?= e((string) $v['anio_fabricacion']) ?></p>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/pie.php'; ?>
