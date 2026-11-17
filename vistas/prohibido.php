<?php $paginaGuia = 'perdida'; require __DIR__ . '/cabecera.php'; ?>

<section class="portada">
  <p class="eslogan">Zona restringida</p>
  <p><?= e($mensaje) ?></p>
  <p><a class="boton" href="index.php">Volver al inicio</a></p>
  <?= cocheSvg('portada-coche') ?>
</section>

<?php require __DIR__ . '/pie.php'; ?>
