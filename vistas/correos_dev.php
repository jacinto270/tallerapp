<?php require __DIR__ . '/cabecera.php'; ?>

<p class="aviso">Solo en desarrollo y solo desde este ordenador. En producción los correos se envían por SMTP y esta página no existe.</p>

<?php if (!$correos): ?>
  <p class="vacio">Todavía no se ha enviado ningún correo.</p>
<?php endif; ?>

<?php foreach ($correos as $correo):
  [$cabeceras, $cuerpo] = array_pad(explode("\r\n\r\n", $correo, 2), 2, '');
  preg_match('/^Subject: =\?UTF-8\?B\?(.+)\?=$/m', $cabeceras, $asunto);
  preg_match('/^To: <(.+)>$/m', $cabeceras, $para);
  preg_match('/^Date: (.+)$/m', $cabeceras, $fecha);
?>
  <section class="tarjeta correo-dev">
    <h3><?= e(base64_decode($asunto[1] ?? '')) ?></h3>
    <p class="suave">Para <?= e($para[1] ?? '') ?> · <?= e(trim($fecha[1] ?? '')) ?></p>
    <pre><?= e($cuerpo) ?></pre>
  </section>
<?php endforeach; ?>

<?php require __DIR__ . '/pie.php'; ?>
