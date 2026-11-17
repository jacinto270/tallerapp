<?php require __DIR__ . '/cabecera.php'; ?>

<div class="acceso">
  <section class="tarjeta acceso-caja">
    <?php if ($obligatorio): ?>
      <p class="aviso">El taller te ha puesto una contraseña temporal. Para seguir, elige una nueva que solo sepas tú.</p>
    <?php endif; ?>
    <h2>Cambiar la contraseña</h2>

    <?php if ($errores): ?>
      <ul class="error" role="alert">
        <?php foreach ($errores as $error): ?>
          <li><?= e($error) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form method="post" action="index.php?accion=cambiar_password" class="acceso-form">
      <?= csrf() ?>
      <input type="email" name="usuario" value="<?= e(Seguridad::usuario()['email']) ?>" autocomplete="username" hidden readonly>

      <label for="actual"><?= $obligatorio ? 'Contraseña temporal' : 'Contraseña actual' ?></label>
      <input type="password" name="actual" id="actual" required maxlength="128" autocomplete="current-password" autofocus>

      <label for="password">Contraseña nueva</label>
      <input type="password" name="password" id="password" required minlength="12" maxlength="128" autocomplete="new-password">
      <small class="ayuda-campo">Al menos 12 caracteres, con letras y números. Mejor una frase que solo tú recuerdes.</small>

      <label for="password2">Repite la contraseña nueva</label>
      <input type="password" name="password2" id="password2" required minlength="12" maxlength="128" autocomplete="new-password">

      <button class="boton-ancho">Guardar contraseña</button>
    </form>
    <p class="acceso-nota">Al cambiarla se cerrarán las sesiones que tengas abiertas en otros dispositivos.</p>
    <?php if (!$obligatorio): ?>
      <p class="acceso-pie"><a href="index.php?accion=mi_cuenta">Volver a mi cuenta</a></p>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/pie.php'; ?>
