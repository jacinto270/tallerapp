<?php require __DIR__ . '/cabecera.php'; ?>

<div class="acceso">
  <section class="tarjeta acceso-caja acceso-ancha">
    <?php if ($enviado): ?>
      <h2>Revisa tu email</h2>
      <p class="aviso">Si los datos son correctos, te hemos enviado un enlace para confirmar tu email<?= isset($email) ? ' (<strong>' . e($email) . '</strong>)' : '' ?>.
         Ábrelo para activar tu cuenta: caduca en 48 horas.</p>
      <p>Después, para ver tus facturas, el taller vinculará tu cuenta con tu ficha de cliente cuando lo confirmes en persona.</p>
      <p class="acceso-nota">¿No te llega? Mira en la carpeta de spam. Si ya tenías cuenta, entra o usa «¿Has olvidado la contraseña?».</p>
      <p><a class="boton" href="index.php?accion=login">Ir a entrar</a></p>
    <?php else: ?>
      <h2>Crea tu cuenta</h2>
      <p class="suave">Para consultar e imprimir tus facturas del taller.</p>

      <?php if ($errores): ?>
        <ul class="error" role="alert">
          <?php foreach ($errores as $error): ?>
            <li><?= e($error) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <form method="post" action="index.php?accion=registro" class="acceso-form form-usuario">
        <?= csrf() ?>
        <div class="rejilla-campos">
          <div>
            <label for="nombre">Nombre</label>
            <input type="text" name="nombre" id="nombre" required maxlength="60" autocomplete="given-name" value="<?= e($valores['nombre'] ?? '') ?>">
          </div>
          <div>
            <label for="apellidos">Apellidos</label>
            <input type="text" name="apellidos" id="apellidos" required maxlength="120" autocomplete="family-name" value="<?= e($valores['apellidos'] ?? '') ?>">
          </div>
          <div>
            <label for="dni">DNI o NIE</label>
            <input type="text" name="dni" id="dni" required maxlength="12" autocomplete="off" placeholder="12345678Z" value="<?= e($valores['dni'] ?? '') ?>">
          </div>
          <div>
            <label for="telefono">Teléfono</label>
            <input type="tel" name="telefono" id="telefono" required maxlength="16" autocomplete="tel" placeholder="600 000 000" value="<?= e($valores['telefono'] ?? '') ?>">
          </div>
          <div class="campo-ancho">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" required maxlength="190" autocomplete="email" value="<?= e($valores['email'] ?? '') ?>">
          </div>
          <div>
            <label for="password">Contraseña</label>
            <input type="password" name="password" id="password" required minlength="12" maxlength="128" autocomplete="new-password">
          </div>
          <div>
            <label for="password2">Repite la contraseña</label>
            <input type="password" name="password2" id="password2" required minlength="12" maxlength="128" autocomplete="new-password">
          </div>
        </div>
        <small class="ayuda-campo">Al menos 12 caracteres, con letras y números, y sin tu nombre, DNI, email ni teléfono.</small>

        <!-- Trampa para robots: oculto a las personas; si llega relleno, no se crea la cuenta -->
        <div class="campo-trampa" aria-hidden="true">
          <label for="web">No rellenes este campo</label>
          <input type="text" name="web" id="web" tabindex="-1" autocomplete="off">
        </div>

        <label class="consentimiento">
          <input type="checkbox" name="acepto" value="si" required>
          <span>Acepto que el Taller Genil guarde estos datos para gestionar mi cuenta, mis reparaciones y mis facturas.
                Puedo pedir que los corrijan o los borren en el taller.</span>
        </label>

        <button class="boton-ancho">Crear mi cuenta</button>
      </form>
      <p class="acceso-pie">¿Ya tienes cuenta? <a href="index.php?accion=login">Entra</a></p>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/pie.php'; ?>
