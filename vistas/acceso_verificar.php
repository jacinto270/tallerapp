<?php require __DIR__ . '/cabecera.php'; ?>

<div class="acceso">
  <section class="tarjeta acceso-caja">
    <?php if (!$valido): ?>
      <h2>Enlace no válido</h2>
      <p>El enlace no es correcto, ya se ha usado o ha caducado. Pide al taller que te lo reenvíe.</p>
    <?php else: ?>
      <h2>Hola, <?= e($nombre) ?></h2>
      <p>Confirma que <strong><?= e($email) ?></strong> es tu email.</p>
      <form method="post" action="index.php?accion=verificar_email" class="acceso-form">
        <?= csrf() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <button class="boton-ancho boton-verde">Confirmar mi email</button>
      </form>
    <?php endif; ?>
  </section>
  <?= cocheSvg('acceso-coche') ?>
</div>

<?php require __DIR__ . '/pie.php'; ?>
