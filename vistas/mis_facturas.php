<?php require __DIR__ . '/cabecera.php'; ?>

<?php if ($pendienteDeFicha): ?>
  <section class="tarjeta">
    <h3><?= icono('llave') ?> Tu cuenta está casi lista</h3>
    <p>Para proteger los datos de nuestros clientes, el taller tiene que vincular tu cuenta con tu ficha de cliente.
       La próxima vez que vengas, enseña tu DNI en el mostrador y desde ese momento verás aquí tus facturas.</p>
  </section>
<?php elseif (!$facturas): ?>
  <p class="vacio">Todavía no tienes ninguna factura.</p>
<?php else: ?>
  <div class="tabla-scroll">
    <table>
      <thead>
        <tr><th>Número</th><th>Fecha</th><th>Vehículo</th><th>Reparación</th><th class="num">Total</th><th class="num">Ver</th></tr>
      </thead>
      <tbody>
        <?php foreach ($facturas as $f): ?>
          <tr data-href="index.php?accion=mi_factura&amp;id=<?= (int) $f['reparacion_id'] ?>">
            <td class="codigo"><?= e($f['numero']) ?></td>
            <td><?= fecha($f['fecha']) ?></td>
            <td><?= e($f['marca'] . ' ' . $f['modelo']) ?> <?= matricula($f['matricula']) ?></td>
            <td><?= e($f['descripcion']) ?></td>
            <td class="num"><strong><?= euros($f['total']) ?></strong></td>
            <td class="num">
              <a class="boton boton-mini boton-secundario" href="index.php?accion=mi_factura&amp;id=<?= (int) $f['reparacion_id'] ?>">Ver</a>
              <a class="boton boton-mini" href="index.php?accion=mi_factura&amp;id=<?= (int) $f['reparacion_id'] ?>&amp;imprimir=1" title="Imprimir">🖨</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/pie.php'; ?>
