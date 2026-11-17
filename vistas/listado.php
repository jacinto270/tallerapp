<?php require __DIR__ . '/cabecera.php'; ?>

<?php if ($estado === '' && $busqueda === ''): ?>
  <section class="portada">
    <p class="eslogan">Tu coche, a punto para la pista</p>
    <p>Diagnóstico, piezas y mano de obra con precisión de boxes. Sigue cada reparación desde que entra hasta que vuelve a rodar.</p>
    <?= cocheSvg('portada-coche') ?>
  </section>
<?php endif; ?>

<div class="marcadores">
  <a class="marcador" href="index.php?accion=listado">
    <?= icono('llave', 'marcador-icono') ?>
    <strong data-contar="<?= (int) $resumen['total'] ?>"><?= (int) $resumen['total'] ?></strong><span>Reparaciones</span>
  </a>
  <a class="marcador naranja" href="index.php?accion=listado&amp;estado=taller">
    <?= icono('rueda', 'marcador-icono girando') ?>
    <strong data-contar="<?= (int) $resumen['en_taller'] ?>"><?= (int) $resumen['en_taller'] ?></strong><span>En el taller ahora</span>
  </a>
  <a class="marcador verde" href="index.php?accion=listado&amp;estado=entregadas">
    <?= icono('bandera', 'marcador-icono') ?>
    <strong data-contar="<?= (int) $resumen['entregadas'] ?>"><?= (int) $resumen['entregadas'] ?></strong><span>Entregadas</span>
  </a>
</div>

<form class="buscador" method="get" action="index.php" role="search">
  <input type="hidden" name="accion" value="listado">
  <?php if ($estado !== ''): ?>
    <input type="hidden" name="estado" value="<?= e($estado) ?>">
  <?php endif; ?>
  <input type="search" name="q" value="<?= e($busqueda) ?>" data-filtrar="#tabla-reparaciones"
         placeholder="Busca por matrícula, marca, cliente, avería o mecánico…" aria-label="Buscar reparaciones">
  <button>Buscar</button>
  <?php if ($busqueda !== ''): ?>
    <a class="boton boton-secundario" href="index.php?accion=listado<?= $estado !== '' ? '&amp;estado=' . e($estado) : '' ?>">Limpiar</a>
  <?php endif; ?>
</form>

<nav class="filtros" aria-label="Filtrar por estado">
  <?php foreach (['' => 'Todas', 'taller' => 'En el taller', 'entregadas' => 'Entregadas'] as $valor => $texto):
    $url = 'index.php?accion=listado'
         . ($valor !== '' ? '&estado=' . $valor : '')
         . ($busqueda !== '' ? '&q=' . urlencode($busqueda) : ''); ?>
    <a href="<?= e($url) ?>"<?= $estado === $valor ? ' class="activo" aria-current="page"' : '' ?>><?= e($texto) ?></a>
  <?php endforeach; ?>
</nav>

<?php if ($busqueda !== ''): ?>
  <p class="resultado"><?= plural(count($reparaciones), 'resultado', 'resultados') ?> para «<?= e($busqueda) ?>»</p>
<?php endif; ?>

<?php if (!$reparaciones): ?>
  <p class="vacio">
    <?= $busqueda !== '' ? 'No hay ninguna reparación que coincida con la búsqueda.'
       : ($estado === 'taller' ? 'Ahora mismo no hay ningún vehículo en el taller.' : 'Todavía no hay ninguna reparación.') ?>
  </p>
<?php else: ?>
  <div class="tabla-scroll">
    <table id="tabla-reparaciones">
      <thead>
        <tr><th>Entrada</th><th>Vehículo</th><th>Cliente</th><th>Avería</th><th>Mecánico</th><th>Estado</th></tr>
      </thead>
      <tbody>
        <!-- TODO 3: una fila por reparación. Usa las funciones de ayudas.php:
             fecha() para las fechas y e() para escapar los textos.
             Vehículo: «Marca Modelo (matrícula)».
             Estado: «En el taller» en negrita, o «Salió el 21/10/2026» -->
        <?php foreach ($reparaciones as $re): ?>
          <tr data-href="index.php?accion=ver&amp;id=<?= (int) $re['id'] ?>">
            <td><?= fecha($re['fecha_entrada']) ?></td>
            <td>
              <?= e($re['marca'] . ' ' . $re['modelo']) ?><br>
              <?= matricula($re['matricula']) ?>
            </td>
            <td>
              <a href="index.php?accion=cliente&amp;id=<?= (int) $re['cliente_id'] ?>"><?= e($re['cliente_nombre'] . ' ' . $re['cliente_apellidos']) ?></a>
            </td>
            <td>
              <a href="index.php?accion=ver&amp;id=<?= (int) $re['id'] ?>"><?= e($re['descripcion']) ?></a>
            </td>
            <td><?= e($re['mecanico']) ?></td>
            <td>
              <?= estado($re['fecha_salida']) ?>
              <?php if ($re['factura_numero']): ?>
                <a class="mini-enlace" href="index.php?accion=factura&amp;id=<?= (int) $re['id'] ?>"><?= e($re['factura_numero']) ?></a>
              <?php elseif ($re['fecha_salida'] !== null): ?>
                <span class="mini-enlace sin-factura">Sin facturar</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/pie.php'; ?>
