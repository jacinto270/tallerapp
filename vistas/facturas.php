<?php require __DIR__ . '/cabecera.php'; ?>

<?php if ($aviso): ?>
  <p class="aviso"><?= e($aviso) ?></p>
<?php endif; ?>

<div class="marcadores">
  <div class="marcador">
    <strong data-contar="<?= (int) $resumen['numero'] ?>"><?= (int) $resumen['numero'] ?></strong><span>Facturas emitidas</span>
  </div>
  <div class="marcador verde">
    <strong class="cifra-pequena" data-contar="<?= (float) $resumen['total'] ?>" data-formato="euros"><?= euros($resumen['total']) ?></strong><span>Facturado con IVA</span>
  </div>
  <div class="marcador naranja">
    <strong class="cifra-pequena" data-contar="<?= (float) $resumen['este_mes'] ?>" data-formato="euros"><?= euros($resumen['este_mes']) ?></strong><span>Este mes</span>
  </div>
  <a class="marcador" href="#pendientes">
    <strong data-contar="<?= count($pendientes) ?>"><?= count($pendientes) ?></strong><span>Pendientes de facturar</span>
  </a>
</div>

<form class="buscador" method="get" action="index.php" role="search">
  <input type="hidden" name="accion" value="facturas">
  <input type="search" name="q" value="<?= e($busqueda) ?>" data-filtrar="#tabla-facturas"
         placeholder="Busca por número, cliente o matrícula…" aria-label="Buscar facturas">
  <button>Buscar</button>
  <?php if ($busqueda !== ''): ?>
    <a class="boton boton-secundario" href="index.php?accion=facturas">Limpiar</a>
  <?php endif; ?>
</form>

<?php if (!$facturas): ?>
  <p class="vacio"><?= $busqueda !== '' ? 'No hay ninguna factura que coincida con la búsqueda.' : 'Todavía no se ha emitido ninguna factura.' ?></p>
<?php else: ?>
  <div class="tabla-scroll">
    <table id="tabla-facturas">
      <thead>
        <tr><th>Número</th><th>Fecha</th><th>Cliente</th><th>Vehículo</th><th class="num">Base</th><th class="num">IVA</th><th class="num">Total</th><th class="num">Imprimir</th></tr>
      </thead>
      <tbody>
        <?php foreach ($facturas as $f): ?>
          <tr data-href="index.php?accion=factura&amp;id=<?= (int) $f['reparacion_id'] ?>">
            <td><a class="codigo" href="index.php?accion=factura&amp;id=<?= (int) $f['reparacion_id'] ?>"><?= e($f['numero']) ?></a></td>
            <td><?= fecha($f['fecha']) ?></td>
            <td><a href="index.php?accion=cliente&amp;id=<?= (int) $f['cliente_id'] ?>"><?= e($f['cliente_nombre'] . ' ' . $f['cliente_apellidos']) ?></a></td>
            <td><?= e($f['marca'] . ' ' . $f['modelo']) ?> <?= matricula($f['matricula']) ?></td>
            <td class="num"><?= euros($f['base']) ?></td>
            <td class="num"><?= euros($f['iva']) ?></td>
            <td class="num"><strong><?= euros($f['total']) ?></strong></td>
            <td class="num">
              <a class="boton boton-mini boton-secundario" href="index.php?accion=factura&amp;id=<?= (int) $f['reparacion_id'] ?>&amp;imprimir=1" title="Imprimir la factura <?= e($f['numero']) ?>">🖨</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php if ($pendientes): ?>
  <h2 id="pendientes">Pendientes de facturar</h2>
  <p class="suave">Reparaciones entregadas que todavía no tienen factura. El importe incluye el IVA.</p>
  <div class="tabla-scroll">
    <table>
      <thead>
        <tr><th>Salida</th><th>Cliente</th><th>Vehículo</th><th>Avería</th><th class="num">Total</th><th class="num">Facturar</th></tr>
      </thead>
      <tbody>
        <?php foreach ($pendientes as $p): $conIva = $p['base'] + round($p['base'] * $tipoIva, 2); ?>
          <tr>
            <td><?= fecha($p['fecha_salida']) ?></td>
            <td><?= e($p['cliente_nombre'] . ' ' . $p['cliente_apellidos']) ?></td>
            <td><?= matricula($p['matricula']) ?></td>
            <td><a href="index.php?accion=factura&amp;id=<?= (int) $p['id'] ?>"><?= e($p['descripcion']) ?></a></td>
            <td class="num"><?= euros($conIva) ?></td>
            <td class="num">
              <form method="post" action="index.php?accion=facturar&amp;id=<?= (int) $p['id'] ?>"
                    data-confirmar="<?= e('¿Emitir la factura de ' . euros($conIva) . ' para ' . $p['cliente_nombre'] . ' ' . $p['cliente_apellidos'] . '?') ?>"
                    data-aceptar="Facturar">
                <?= csrf() ?>
                <input type="hidden" name="volver" value="facturas">
                <button class="boton-mini boton-verde">Facturar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/pie.php'; ?>
