<?php require __DIR__ . '/cabecera.php'; ?>

<div class="acciones">
  <a class="boton boton-secundario" href="index.php?accion=clientes">← Clientes</a>
  <a class="boton boton-secundario" href="tel:<?= e($cliente['telefono']) ?>">Llamar · <?= e($cliente['telefono']) ?></a>
</div>

<div class="marcadores">
  <div class="marcador">
    <?= icono('rueda', 'marcador-icono') ?>
    <strong data-contar="<?= count($vehiculos) ?>"><?= count($vehiculos) ?></strong><span><?= count($vehiculos) === 1 ? 'Vehículo' : 'Vehículos' ?></span>
  </div>
  <div class="marcador">
    <?= icono('llave', 'marcador-icono') ?>
    <strong data-contar="<?= $numReparaciones ?>"><?= $numReparaciones ?></strong><span><?= $numReparaciones === 1 ? 'Reparación' : 'Reparaciones' ?></span>
  </div>
  <div class="marcador naranja">
    <?= icono('engranaje', 'marcador-icono girando-lento') ?>
    <strong data-contar="<?= $enTaller ?>"><?= $enTaller ?></strong><span>En el taller ahora</span>
  </div>
  <div class="marcador verde">
    <?= icono('factura', 'marcador-icono') ?>
    <strong class="cifra-pequena" data-contar="<?= (float) $facturado ?>" data-formato="euros"><?= euros($facturado) ?></strong><span>Facturado (sin IVA)</span>
  </div>
</div>

<h2>Vehículos</h2>

<?php if (!$vehiculos): ?>
  <p class="vacio">Este cliente todavía no tiene ningún vehículo.</p>
<?php endif; ?>

<?php foreach ($vehiculos as $vehiculo): ?>
  <section class="tarjeta vehiculo">
    <div class="vehiculo-cabeza">
      <?= matricula($vehiculo['matricula']) ?>
      <h3><?= e($vehiculo['marca'] . ' ' . $vehiculo['modelo']) ?></h3>
      <span class="suave">Año <?= e($vehiculo['anio_fabricacion']) ?></span>
      <a class="boton boton-mini" href="index.php?accion=nueva&amp;vehiculo=<?= (int) $vehiculo['id'] ?>">Nueva reparación</a>
    </div>

    <?php if (!$vehiculo['reparaciones']): ?>
      <p class="vacio">Sin reparaciones todavía.</p>
    <?php else: ?>
      <div class="tabla-scroll">
        <table>
          <thead>
            <tr><th>Entrada</th><th>Avería</th><th>Mecánico</th><th>Estado</th><th class="num">Total</th></tr>
          </thead>
          <tbody>
            <?php foreach ($vehiculo['reparaciones'] as $re): ?>
              <tr>
                <td><?= fecha($re['fecha_entrada']) ?></td>
                <td><a href="index.php?accion=ver&amp;id=<?= (int) $re['id'] ?>"><?= e($re['descripcion']) ?></a></td>
                <td><?= e($re['mecanico']) ?></td>
                <td><?= estado($re['fecha_salida']) ?></td>
                <td class="num"><?= euros($re['total']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
<?php endforeach; ?>

<?php require __DIR__ . '/pie.php'; ?>
