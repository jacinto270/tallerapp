<?php require __DIR__ . '/cabecera.php'; ?>

<?php if ($errores): ?>
  <ul class="error" role="alert">
    <?php foreach ($errores as $error): ?>
      <li><?= e($error) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<form method="post" action="index.php?accion=<?= $editando ? 'usuario_actualizar' : 'usuario_guardar' ?>" class="alta form-usuario">
  <?php if ($editando): ?>
    <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
    <?php if ($hayCorreo): ?>
      <p class="suave">Si cambias el email, el usuario no podrá entrar hasta que confirme el nuevo con el enlace que le enviaremos.
         También avisaremos al email antiguo.</p>
    <?php endif; ?>
  <?php elseif ($hayCorreo): ?>
    <p class="suave">Al guardar, el usuario recibe un email para verificar que la dirección es suya y elegir su contraseña.
       Nadie más la conoce, tampoco el admin.</p>
  <?php else: ?>
    <p class="suave">Ponle una contraseña temporal y dásela en persona o por teléfono. La primera vez que entre
       tendrá que cambiarla por una que solo sepa él.</p>
  <?php endif; ?>

  <div class="rejilla-campos">
    <div>
      <label for="nombre">Nombre</label>
      <input type="text" name="nombre" id="nombre" required maxlength="60" autocomplete="off" value="<?= e($valores['nombre'] ?? '') ?>">
    </div>
    <div>
      <label for="apellidos">Apellidos</label>
      <input type="text" name="apellidos" id="apellidos" required maxlength="120" autocomplete="off" value="<?= e($valores['apellidos'] ?? '') ?>">
    </div>
    <div>
      <label for="dni">DNI o NIE</label>
      <input type="text" name="dni" id="dni" required maxlength="12" autocomplete="off" placeholder="12345678Z"
             value="<?= e($valores['dni'] ?? '') ?>" data-validar="dni">
    </div>
    <div>
      <label for="telefono">Teléfono</label>
      <input type="tel" name="telefono" id="telefono" required maxlength="16" autocomplete="off" placeholder="600 000 000"
             value="<?= e($valores['telefono'] ?? '') ?>">
    </div>
    <div class="campo-ancho">
      <label for="email">Email</label>
      <input type="email" name="email" id="email" required maxlength="190" autocomplete="off" value="<?= e($valores['email'] ?? '') ?>">
    </div>
  </div>

  <fieldset class="roles">
    <legend>Rol</legend>
    <?php if ($esYo): ?>
      <p class="ayuda-campo">Eres tú: no puedes quitarte el rol de admin.</p>
    <?php endif; ?>
    <label class="rol-opcion">
      <input type="radio" name="rol" value="usuario" <?= ($valores['rol'] ?? '') === 'usuario' ? 'checked' : '' ?>>
      <span><strong>Usuario (cliente)</strong> Solo ve sus facturas y sus datos.</span>
    </label>
    <label class="rol-opcion">
      <input type="radio" name="rol" value="admin" <?= ($valores['rol'] ?? '') === 'admin' ? 'checked' : '' ?>>
      <span><strong>Admin</strong> Acceso completo: reparaciones, clientes, facturas, ventas y usuarios.</span>
    </label>
  </fieldset>

  <div data-solo-rol="usuario"<?= $puedeSinFicha ? ' data-opcional' : '' ?>>
    <label for="cliente_id">Ficha de cliente</label>
    <?php if ($puedeSinFicha): ?>
      <p class="aviso">Se registró él mismo y todavía no tiene ficha. Comprueba en persona que es quien dice ser
         (DNI y teléfono) antes de vincularla: con ella verá todas las facturas de ese cliente.</p>
    <?php endif; ?>
    <select name="cliente_id" id="cliente_id">
      <option value=""><?= $puedeSinFicha ? 'Sin ficha todavía (no ve ninguna factura)' : 'Elige el cliente…' ?></option>
      <option value="nueva" <?= ($valores['cliente_id'] ?? '') === 'nueva' ? 'selected' : '' ?>>+ Crear una ficha nueva con estos datos (cliente nuevo en el taller)</option>
      <?php foreach ($clientes as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= is_int($valores['cliente_id'] ?? null) && $valores['cliente_id'] === (int) $c['id'] ? 'selected' : '' ?>>
          <?= e($c['apellidos'] . ', ' . $c['nombre'] . ' · ' . $c['telefono']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <small class="ayuda-campo">Solo aparecen los clientes que aún no tienen cuenta.</small>
  </div>

  <?php if (!$editando && !$hayCorreo): ?>
    <div class="rejilla-campos">
      <div>
        <label for="alta_password">Contraseña temporal</label>
        <input type="password" name="password" id="alta_password" required minlength="8" maxlength="128" autocomplete="new-password">
      </div>
      <div>
        <label for="alta_password2">Repítela</label>
        <input type="password" name="password2" id="alta_password2" required minlength="8" maxlength="128" autocomplete="new-password">
      </div>
    </div>
    <small class="ayuda-campo">Al menos 8 caracteres, con letras y números. Al entrar por primera vez tendrá que cambiarla.</small>
  <?php endif; ?>

  <?= csrf() ?>
  <div class="acciones-form">
    <button><?= $editando ? 'Guardar cambios' : 'Dar de alta' ?></button>
    <a class="boton boton-secundario" href="index.php?accion=usuarios">Cancelar</a>
  </div>
</form>

<?php if ($editando): ?>
  <section class="tarjeta contrasena" id="contrasena">
    <h3><?= icono('llave') ?> Contraseña</h3>
    <?php if ($esYo): ?>
      <p>Tu propia contraseña la cambias desde <a href="index.php?accion=cambiar_password">Mi cuenta → Cambiar la contraseña</a>.</p>
    <?php elseif ($hayCorreo && ($editando['password_hash'] === null || $editando['email_verificado'] === null)): ?>
      <p class="suave">Todavía no ha activado su cuenta o no ha verificado su email. Usa «Reenviar activación» o «Reenviar verificación» en la lista de usuarios.</p>
    <?php else: ?>
      <div class="opciones-contrasena">
        <?php if ($hayCorreo): ?>
        <div>
          <h4>Opción recomendada: que la cambie él</h4>
          <p class="suave">Le llega un enlace a <strong><?= e($editando['email']) ?></strong> (caduca en 1 hora) y elige una nueva. Nadie más la conoce.</p>
          <form method="post" action="index.php?accion=usuario_enlace_password"
                data-confirmar="<?= e('¿Enviar a ' . $editando['email'] . ' un enlace para cambiar la contraseña?') ?>" data-aceptar="Enviar enlace">
            <?= csrf() ?>
            <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
            <button class="boton-secundario">Enviarle un enlace</button>
          </form>
        </div>
        <?php endif; ?>
        <div>
          <h4>Poner una contraseña temporal</h4>
          <p class="suave"><?= $hayCorreo ? 'Para cuando no puede usar su email. ' : 'Por ejemplo, si la ha olvidado. ' ?>Dásela en persona o por teléfono, nunca por escrito.
             Al entrar tendrá que cambiarla, y se cerrarán las sesiones que tenga abiertas.</p>
          <form method="post" action="index.php?accion=usuario_password" class="acceso-form"
                data-confirmar="<?= e('¿Poner una contraseña temporal a ' . $editando['nombre'] . ' ' . $editando['apellidos'] . '?') ?>" data-aceptar="Guardar">
            <?= csrf() ?>
            <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
            <input type="text" name="usuario" value="<?= e($editando['email']) ?>" autocomplete="off" hidden readonly>
            <label for="temporal">Contraseña temporal</label>
            <input type="password" name="password" id="temporal" required minlength="8" maxlength="128" autocomplete="new-password">
            <label for="temporal2">Repítela</label>
            <input type="password" name="password2" id="temporal2" required minlength="8" maxlength="128" autocomplete="new-password">
            <button class="boton-peligro">Guardar contraseña temporal</button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </section>
<?php endif; ?>

<?php require __DIR__ . '/pie.php'; ?>
