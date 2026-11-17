<?php require __DIR__ . '/cabecera.php'; ?>

<div class="acceso">
  <section class="tarjeta acceso-caja">
    <?php if (!$valido): ?>
      <h2>Enlace no válido</h2>
      <p>El enlace no es correcto, ya se ha usado o ha caducado.</p>
      <p>
        <?php if ($tipo === 'restablecer'): ?>
          <a class="boton" href="index.php?accion=recuperar">Pedir otro enlace</a>
        <?php else: ?>
          Pide al taller que te reenvíe el email de activación.
        <?php endif; ?>
      </p>
    <?php else: ?>
      <h2><?= $tipo === 'activar' ? 'Hola, ' . e($nombre) : 'Elige una contraseña nueva' ?></h2>
      <p>
        <?= $tipo === 'activar'
          ? 'Tu email <strong>' . e($email) . '</strong> queda verificado al guardar la contraseña.'
          : 'Cuenta: <strong>' . e($email) . '</strong>' ?>
      </p>

      <?php if ($errores): ?>
        <ul class="error" role="alert">
          <?php foreach ($errores as $error): ?>
            <li><?= e($error) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <form method="post" action="index.php?accion=<?= e($tipo) ?>" class="acceso-form">
        <?= csrf() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <!-- Para que el gestor de contraseñas sepa de qué cuenta es -->
        <input type="email" name="usuario" value="<?= e($email) ?>" autocomplete="username" hidden readonly>

        <label for="password">Contraseña</label>
        <input type="password" name="password" id="password" required minlength="8" maxlength="128" autocomplete="new-password" autofocus>
        <small class="ayuda-campo">Al menos 8 caracteres, con letras y números. Mejor una frase que solo tú recuerdes.</small>

        <label for="password2">Repite la contraseña</label>
        <input type="password" name="password2" id="password2" required minlength="8" maxlength="128" autocomplete="new-password">

        <button class="boton-ancho">Guardar contraseña</button>
      </form>
    <?php endif; ?>
  </section>
  <?= cocheSvg('acceso-coche') ?>
</div>

<?php require __DIR__ . '/pie.php'; ?>
