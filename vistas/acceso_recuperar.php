<?php require __DIR__ . '/cabecera.php'; ?>

<div class="acceso">
  <section class="tarjeta acceso-caja">
    <h2>Recuperar la contraseña</h2>

    <?php if ($errores): ?>
      <ul class="error" role="alert">
        <?php foreach ($errores as $error): ?>
          <li><?= e($error) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if ($enviado): ?>
      <p class="aviso">Si ese email está dado de alta, en unos minutos recibirás un enlace para elegir una contraseña nueva. Caduca en 1 hora.</p>
      <p><a href="index.php?accion=login">Volver a entrar</a></p>
    <?php else: ?>
      <p>Escribe el email de tu cuenta y te enviaremos un enlace para cambiar la contraseña.</p>
      <form method="post" action="index.php?accion=recuperar" class="acceso-form">
        <?= csrf() ?>
        <label for="email">Email</label>
        <input type="email" name="email" id="email" required maxlength="190" autocomplete="email" autofocus>
        <button class="boton-ancho">Enviarme el enlace</button>
      </form>
      <p class="acceso-pie"><a href="index.php?accion=login">Volver a entrar</a></p>
    <?php endif; ?>
  </section>
  <?= cocheSvg('acceso-coche') ?>
</div>

<?php require __DIR__ . '/pie.php'; ?>
