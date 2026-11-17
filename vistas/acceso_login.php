<?php require __DIR__ . '/cabecera.php'; ?>

<div class="acceso">
  <section class="tarjeta acceso-caja">
    <p class="acceso-paso"><span class="activo">1</span><span>2</span> Paso 1 de 2</p>
    <h2>Entra en tu box</h2>

    <?php if ($errores): ?>
      <ul class="error" role="alert">
        <?php foreach ($errores as $error): ?>
          <li><?= e($error) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form method="post" action="index.php?accion=login" class="acceso-form">
      <?= csrf() ?>
      <label for="email">Email</label>
      <input type="email" name="email" id="email" required maxlength="190" autocomplete="username"
             value="<?= e($email) ?>" autofocus>

      <label for="password">Contraseña</label>
      <input type="password" name="password" id="password" required maxlength="128" autocomplete="current-password">

      <button class="boton-ancho">Continuar</button>
    </form>

    <p class="acceso-pie">
      <a href="index.php?accion=recuperar">¿Has olvidado la contraseña?</a>
      <a href="index.php?accion=registro">¿No tienes cuenta? Créala</a>
    </p>
    <p class="acceso-nota">Después te enviaremos un código a tu email para comprobar que eres tú.</p>
  </section>
  <?= cocheSvg('acceso-coche') ?>
</div>

<?php require __DIR__ . '/pie.php'; ?>
