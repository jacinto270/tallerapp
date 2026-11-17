<?php
// * @var array $reparacion
// * @var array $piezas
// * @var float $total
// * @var string $titulo

require __DIR__ . '/cabecera.php'; ?>

<?php if ($aviso): ?>
  <p class="aviso"><?= e($aviso) ?></p>
<?php endif; ?>

<?php if ($errores): ?>
  <ul class="error">
    <?php foreach ($errores as $error): ?>
      <li><?= e($error) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<div class="acciones">
  <a class="boton boton-secundario" href="index.php">← Reparaciones</a>
  <?php if ($reparacion['factura_numero']): ?>
    <a class="boton boton-secundario" href="index.php?accion=factura&amp;id=<?= (int) $reparacion['id'] ?>&amp;imprimir=1">🖨 Imprimir factura <?= e($reparacion['factura_numero']) ?></a>
  <?php else: ?>
    <a class="boton boton-secundario" href="index.php?accion=factura&amp;id=<?= (int) $reparacion['id'] ?>"><?= $enTaller ? 'Ver presupuesto' : 'Ver borrador de factura' ?></a>
  <?php endif; ?>
  <?= estado($reparacion['fecha_salida']) ?>
  <?php if ($reparacion['factura_numero']): ?>
    <span class="chapa chapa-facturada">Facturada</span>
  <?php endif; ?>
</div>

<div class="ficha-rejilla">
  <section class="tarjeta">
    <dl>
      <dt>Vehículo</dt>
      <dd><?= e($reparacion['marca'] . ' ' . $reparacion['modelo']) ?> <?= matricula($reparacion['matricula']) ?></dd>
      <!-- TODO 7: Cliente («Nombre Apellidos · teléfono»), Mecánico, Avería,
           Entrada y Salida («En el taller» si todavía no ha salido) -->
      <dt>Cliente</dt>
      <dd>
        <a href="index.php?accion=cliente&amp;id=<?= (int) $reparacion['cliente_id'] ?>"><?= e($reparacion['cliente_nombre'] . ' ' . $reparacion['cliente_apellidos']) ?></a>
        · <a href="tel:<?= e($reparacion['telefono']) ?>"><?= e($reparacion['telefono']) ?></a>
      </dd>
      <dt>Mecánico</dt>
      <dd><?= e($reparacion['mecanico']) ?></dd>
      <dt>Avería</dt>
      <dd><?= e($reparacion['descripcion']) ?></dd>
      <dt>Entrada</dt>
      <dd><?= fecha($reparacion['fecha_entrada']) ?></dd>
      <dt>Salida</dt>
      <dd><?= $reparacion['fecha_salida'] === null ? 'En el taller' : fecha($reparacion['fecha_salida']) ?></dd>
    </dl>
  </section>

  <!-- TODO 9: «Mano de obra: 80,00 €» y, con la clase «total», «Total: 145,00 €» -->
  <section class="tarjeta cuentas">
    <p>Mano de obra<?= $reparacion['horas'] !== null ? ' (' . horas($reparacion['horas']) . ' × ' . euros($reparacion['precio_hora']) . ')' : '' ?>: <span><?= euros($reparacion['mano_de_obra']) ?></span></p>
    <p>Piezas: <span><?= euros($totalPiezas) ?></span></p>
    <p class="total">Total: <span><?= euros($total) ?></span></p>
    <p class="con-iva">Con IVA (<?= $porcentajeIva ?> %): <span><?= euros($totalConIva) ?></span></p>

    <?php if ($enTaller): ?>
      <form method="post" action="index.php?accion=facturar&amp;id=<?= (int) $reparacion['id'] ?>"
            data-confirmar="<?= e('¿El cliente ha pagado ' . euros($totalConIva) . '? Se emitirá la factura y se entregará el vehículo.') ?>"
            data-aceptar="Facturar y entregar">
        <?= csrf() ?>
        <button class="boton-verde boton-ancho">Cobrar, facturar y entregar</button>
      </form>
    <?php elseif (!$reparacion['factura_numero']): ?>
      <form method="post" action="index.php?accion=facturar&amp;id=<?= (int) $reparacion['id'] ?>"
            data-confirmar="<?= e('¿Emitir la factura de ' . euros($totalConIva) . '? Después no se podrá cambiar.') ?>"
            data-aceptar="Facturar">
        <?= csrf() ?>
        <button class="boton-verde boton-ancho">Emitir factura</button>
      </form>
    <?php else: ?>
      <a class="boton boton-ancho" href="index.php?accion=factura&amp;id=<?= (int) $reparacion['id'] ?>&amp;imprimir=1">🖨 Imprimir factura</a>
    <?php endif; ?>
  </section>
</div>

<?php if ($enTaller):
  $horasValor = $valores['horas'] ?? ($reparacion['horas'] !== null ? (float) $reparacion['horas'] : '');
  $precioValor = $valores['precio_hora'] ?? ($reparacion['precio_hora'] !== null ? (float) $reparacion['precio_hora'] : $precioHoraPropuesto);
?>
  <section class="tarjeta mano-obra" id="mano-obra">
    <h3><?= icono('llave') ?> Mano de obra</h3>
    <form method="post" action="index.php?accion=mano_obra&amp;id=<?= (int) $reparacion['id'] ?>" class="mano-obra-calculo" data-mano-obra>
      <?= csrf() ?>
      <div>
        <label for="horas">Horas</label>
        <div class="horas-control">
          <button type="button" class="boton-secundario" data-horas="-1" aria-label="Quitar una hora">−1 h</button>
          <button type="button" class="boton-secundario" data-horas="-0.5" aria-label="Quitar media hora">−½</button>
          <input type="number" name="horas" id="horas" min="0" max="999" step="0.25" required
                 value="<?= e((string) $horasValor) ?>" placeholder="0">
          <button type="button" class="boton-secundario" data-horas="0.5" aria-label="Añadir media hora">+½</button>
          <button type="button" class="boton-secundario" data-horas="1" aria-label="Añadir una hora">+1 h</button>
        </div>
      </div>
      <div class="precio-hora">
        <label for="precio_hora">Precio por hora (€)</label>
        <input type="number" name="precio_hora" id="precio_hora" min="0" max="9999" step="0.01" required
               value="<?= e((string) $precioValor) ?>">
      </div>
      <output class="mano-obra-resultado" for="horas precio_hora" aria-live="polite">
        = <?= $horasValor !== '' ? euros((float) $horasValor * (float) $precioValor) : euros($reparacion['mano_de_obra']) ?>
      </output>
      <button>Guardar mano de obra</button>
    </form>
    <?php if ($reparacion['horas'] === null): ?>
      <p class="mano-obra-actual">Ahora la mano de obra es un importe fijo de <?= euros($reparacion['mano_de_obra']) ?>. Al guardar se calculará por horas.</p>
    <?php endif; ?>
  </section>
<?php endif; ?>

<h2>Piezas</h2>
<!-- TODO 8: si no hay piezas, «No se ha usado ninguna pieza.»
     Si hay, una tabla: Pieza, Unidades, Precio e Importe -->
<?php if (!$piezas): ?>
  <p class="vacio">No se ha usado ninguna pieza.</p>
<?php else: ?>
  <div class="tabla-scroll">
    <table>
      <thead>
        <tr>
          <th>Pieza</th>
          <th>Referencia</th>
          <th class="num">Unidades</th>
          <th class="num">Precio</th>
          <th class="num">Importe</th>
          <?php if ($enTaller): ?><th class="num">Quitar</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($piezas as $pieza): ?>
          <tr>
            <td><?= e($pieza['nombre']) ?></td>
            <td class="suave"><?= e($pieza['referencia']) ?></td>
            <td class="num"><?= (int) $pieza['unidades'] ?></td>
            <td class="num"><?= euros($pieza['precio']) ?></td>
            <td class="num"><?= euros($pieza['importe']) ?></td>
            <?php if ($enTaller): ?>
              <td class="num">
                <form method="post" action="index.php?accion=quitar_pieza&amp;id=<?= (int) $reparacion['id'] ?>" class="botonera">
                  <?= csrf() ?>
                  <input type="hidden" name="linea_id" value="<?= (int) $pieza['linea_id'] ?>">
                  <?php if ($pieza['unidades'] > 1): ?>
                    <button name="modo" value="una" class="boton-mini boton-secundario" title="Quitar una unidad">−1</button>
                  <?php endif; ?>
                  <button name="modo" value="todas" class="boton-mini boton-peligro"
                          data-confirmar="<?= e('¿Quitar «' . $pieza['nombre'] . '» de la reparación?') ?>"
                          data-aceptar="Quitar">Quitar</button>
                </form>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php if ($enTaller): ?>
  <section class="tarjeta">
    <h3>Añadir pieza</h3>
    <form method="post" action="index.php?accion=anadir_pieza&amp;id=<?= (int) $reparacion['id'] ?>" class="linea">
      <?= csrf() ?>
      <div class="campo">
        <label for="pieza_id">Pieza</label>
        <select name="pieza_id" id="pieza_id" required>
          <option value="">Elige una pieza…</option>
          <?php foreach ($catalogoPiezas as $opcion): ?>
            <option value="<?= (int) $opcion['id'] ?>" <?= ($valores['pieza_id'] ?? '') == $opcion['id'] ? 'selected' : '' ?>>
              <?= e($opcion['nombre']) ?> · <?= e($opcion['referencia']) ?> · <?= euros($opcion['precio']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="campo-corto">
        <label for="unidades">Unidades</label>
        <input type="number" name="unidades" id="unidades" min="1" step="1" required value="<?= e($valores['unidades'] ?? '1') ?>">
      </div>
      <button>Añadir pieza</button>
    </form>
  </section>
<?php endif; ?>

<?php require __DIR__ . '/pie.php'; ?>
