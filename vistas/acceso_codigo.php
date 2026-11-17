<?php require __DIR__ . '/cabecera.php'; ?>

<div class="acceso">
  <section class="tarjeta acceso-caja">
    <p class="acceso-paso"><span class="hecho">✓</span><span class="activo">2</span> Paso 2 de 2</p>
    <h2>Código de verificación</h2>
    <p>Te hemos enviado un código de 6 cifras a <strong><?= e($emailOculto) ?></strong>. Caduca en 10 minutos.</p>

    <?php if ($errores): ?>
      <ul class="error" role="alert">
        <?php foreach ($errores as $error): ?>
          <li><?= e($error) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form method="post" action="index.php?accion=codigo" class="acceso-form">
      <?= csrf() ?>
      <label for="codigo">Código</label>
      <input type="text" name="codigo" id="codigo" class="campo-codigo" required
             inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" autofocus
             placeholder="000000">
      <button class="boton-ancho">Entrar</button>
    </form>

    <form method="post" action="index.php?accion=reenviar_codigo" class="acceso-pie">
      <?= csrf() ?>
      <button class="boton-mini boton-secundario">Enviarme otro código</button>
      <a href="index.php?accion=login">Volver al paso 1</a>
    </form>

    <?php if (!Seguridad::enProduccion() && Seguridad::esLocal()): ?>
      <p class="acceso-nota">Modo desarrollo: los correos no se envían, se guardan.
        <a href="index.php?accion=correos_dev" target="_blank" rel="noopener">Ver los correos</a></p>
    <?php endif; ?>
  </section>
  <?= cocheSvg('acceso-coche') ?>
</div>

<?php require __DIR__ . '/pie.php'; ?>
