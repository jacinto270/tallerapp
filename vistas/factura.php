<?php require __DIR__ . '/cabecera.php'; ?>

<?php if ($aviso): ?>
  <p class="aviso no-imprimir"><?= e($aviso) ?></p>
<?php endif; ?>

<?php if ($errores): ?>
  <ul class="error no-imprimir">
    <?php foreach ($errores as $error): ?>
      <li><?= e($error) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<div class="acciones no-imprimir">
  <?php if ($paraCliente): ?>
    <a class="boton boton-secundario" href="index.php?accion=mis_facturas">← Mis facturas</a>
  <?php else: ?>
    <a class="boton boton-secundario" href="index.php?accion=ver&amp;id=<?= (int) $reparacion['id'] ?>">← Ficha de la reparación</a>
    <a class="boton boton-secundario" href="index.php?accion=facturas">Facturas</a>
  <?php endif; ?>

  <?php if ($tipo === 'factura' || $paraCliente): ?>
    <button type="button" data-imprimir>🖨 Imprimir factura</button>
  <?php else: ?>
    <button type="button" class="boton-secundario" data-imprimir>🖨 Imprimir <?= $tipo === 'presupuesto' ? 'presupuesto' : 'borrador' ?></button>
    <form method="post" action="index.php?accion=facturar&amp;id=<?= (int) $reparacion['id'] ?>"
          data-confirmar="<?= e($tipo === 'presupuesto'
              ? '¿El cliente ha pagado ' . euros($total) . '? Se emitirá la factura y se entregará el vehículo.'
              : '¿Emitir la factura de ' . euros($total) . '? Después no se podrá cambiar.') ?>"
          data-aceptar="Facturar">
      <?= csrf() ?>
      <button class="boton-verde"><?= $tipo === 'presupuesto' ? 'Cobrar, facturar y entregar' : 'Emitir factura' ?></button>
    </form>
  <?php endif; ?>
</div>

<article class="factura"<?= $imprimir ? ' data-auto-imprimir' : '' ?>>
  <header class="factura-cabeza">
    <div class="factura-marca">
      <?= cocheSvg('factura-logo', '70 26 276 82') ?>
      <div>
        <strong>Taller Mecánico Genil</strong>
        <small>Racing service</small>
      </div>
    </div>
    <div class="factura-numero">
      <span><?= $tipo === 'factura' ? 'Factura' : ($tipo === 'presupuesto' ? 'Presupuesto' : 'Borrador de factura') ?></span>
      <strong><?= $tipo === 'factura' ? e($numero) : 'Sin número' ?></strong>
      <span>Fecha: <?= fecha($fecha) ?></span>
    </div>
  </header>

  <?php if ($tipo === 'presupuesto'): ?>
    <p class="sello">Presupuesto provisional: el vehículo sigue en el taller y el importe todavía puede cambiar.</p>
  <?php elseif ($tipo === 'pendiente'): ?>
    <p class="sello">Borrador: esta reparación está entregada pero todavía no se ha emitido la factura.</p>
  <?php else: ?>
    <p class="sello sello-pagado">Pagada · Vehículo entregado el <?= fecha($reparacion['fecha_salida']) ?></p>
  <?php endif; ?>

  <div class="factura-datos">
    <div>
      <h2>Cliente</h2>
      <p><?= e($reparacion['cliente_nombre'] . ' ' . $reparacion['cliente_apellidos']) ?><br>
         Tel. <?= e($reparacion['telefono']) ?></p>
    </div>
    <div>
      <h2>Vehículo</h2>
      <p><?= e($reparacion['marca'] . ' ' . $reparacion['modelo']) ?><br>
         Matrícula <?= e($reparacion['matricula']) ?></p>
    </div>
    <div>
      <h2>Reparación n.º <?= (int) $reparacion['id'] ?></h2>
      <p>Entrada: <?= fecha($reparacion['fecha_entrada']) ?><br>
         Salida: <?= $reparacion['fecha_salida'] !== null ? fecha($reparacion['fecha_salida']) : 'en el taller' ?><br>
         Mecánico: <?= e($reparacion['mecanico']) ?></p>
    </div>
  </div>

  <h2>Avería</h2>
  <p><?= e($reparacion['descripcion']) ?></p>

  <div class="tabla-scroll">
    <table>
      <thead>
        <tr>
          <th>Concepto</th>
          <th>Referencia</th>
          <th class="num">Unidades</th>
          <th class="num">Precio</th>
          <th class="num">Importe</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <?php if ($reparacion['horas'] !== null): ?>
            <td>Mano de obra (precio por hora)</td>
            <td>—</td>
            <td class="num"><?= horas($reparacion['horas']) ?></td>
            <td class="num"><?= euros($reparacion['precio_hora']) ?></td>
          <?php else: ?>
            <td>Mano de obra</td>
            <td>—</td>
            <td class="num">1</td>
            <td class="num"><?= euros($reparacion['mano_de_obra']) ?></td>
          <?php endif; ?>
          <td class="num"><?= euros($reparacion['mano_de_obra']) ?></td>
        </tr>
        <?php foreach ($piezas as $pieza): ?>
          <tr>
            <td><?= e($pieza['nombre']) ?></td>
            <td><?= e($pieza['referencia']) ?></td>
            <td class="num"><?= (int) $pieza['unidades'] ?></td>
            <td class="num"><?= euros($pieza['precio']) ?></td>
            <td class="num"><?= euros($pieza['importe']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="factura-totales">
    <p><span>Base imponible</span><span><?= euros($base) ?></span></p>
    <p><span>IVA (<?= rtrim(rtrim(number_format($porcentajeIva, 2, ',', ''), '0'), ',') ?> %)</span><span><?= euros($iva) ?></span></p>
    <p class="factura-total"><span>Total</span><span><?= euros($total) ?></span></p>
  </div>

  <p class="factura-gracias">Gracias por confiar en nosotros. ¡Nos vemos en la próxima vuelta!</p>
</article>

<?php require __DIR__ . '/pie.php'; ?>
