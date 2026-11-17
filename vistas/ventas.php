<?php
require __DIR__ . '/cabecera.php';

// Flecha y texto de la comparación con el periodo anterior
$variacion = function (?float $porcentaje, string $anterior): string {
    if ($porcentaje === null) {
        return '<small class="delta">Sin ventas ' . $anterior . '</small>';
    }
    $sube = $porcentaje >= 0;
    return '<small class="delta ' . ($sube ? 'sube' : 'baja') . '">'
         . ($sube ? '▲ +' : '▼ ') . number_format($porcentaje, 0, ',', '.') . ' % vs ' . $anterior . '</small>';
};

$periodos = ['semana' => 'Esta semana', 'mes' => 'Este mes', 'anio' => 'Este año', 'todo' => 'Todo'];
?>

<p class="suave nota-ventas">Ventas = reparaciones entregadas, sin IVA (mano de obra + piezas).</p>

<div class="marcadores">
  <?php foreach (['semana' => ['Esta semana', 'la semana pasada'], 'mes' => ['Este mes', 'el mes pasado'], 'anio' => ['Este año', 'el año pasado']] as $clave => [$etiqueta, $anterior]): ?>
    <div class="marcador kpi">
      <span><?= $etiqueta ?></span>
      <strong class="cifra-pequena" data-contar="<?= $resumen[$clave]['total'] ?>" data-formato="euros"><?= euros($resumen[$clave]['total']) ?></strong>
      <?= $variacion($resumen[$clave]['variacion'], $anterior) ?>
      <small class="suave"><?= plural($resumen[$clave]['reparaciones'], 'reparación entregada', 'reparaciones entregadas') ?></small>
    </div>
  <?php endforeach; ?>
</div>

<nav class="filtros" aria-label="Periodo de los gráficos">
  <?php foreach ($periodos as $valor => $texto): ?>
    <a href="index.php?accion=ventas&amp;periodo=<?= $valor ?>"<?= $periodo === $valor ? ' class="activo" aria-current="page"' : '' ?>><?= $texto ?></a>
  <?php endforeach; ?>
</nav>

<div class="marcadores marcadores-mini">
  <div class="marcador">
    <?= icono('bandera', 'marcador-icono') ?>
    <strong data-contar="<?= $ventas['reparaciones'] ?>"><?= $ventas['reparaciones'] ?></strong><span>Entregadas <?= $nombrePeriodo ?></span>
  </div>
  <div class="marcador verde">
    <?= icono('factura', 'marcador-icono') ?>
    <strong class="cifra-pequena" data-contar="<?= $ventas['total'] ?>" data-formato="euros"><?= euros($ventas['total']) ?></strong><span>Vendido <?= $nombrePeriodo ?></span>
  </div>
  <div class="marcador">
    <?= icono('cliente', 'marcador-icono') ?>
    <strong class="cifra-pequena" data-contar="<?= round($ticketMedio, 2) ?>" data-formato="euros"><?= euros($ticketMedio) ?></strong><span>Ticket medio</span>
  </div>
  <div class="marcador naranja">
    <?= icono('engranaje', 'marcador-icono') ?>
    <strong data-contar="<?= (int) $unidadesPiezas ?>"><?= (int) $unidadesPiezas ?></strong><span>Piezas vendidas</span>
  </div>
</div>

<div class="graficos">
  <section class="tarjeta grafico grafico-ancho" aria-labelledby="g-meses">
    <h3 id="g-meses">Ventas de los últimos 12 meses</h3>
    <p class="grafico-sub">Por mes de entrega. Pasa el ratón por un mes para ver el detalle.</p>
    <ul class="leyenda">
      <li><span class="muestra serie-1"></span>Mano de obra</li>
      <li><span class="muestra serie-2"></span>Piezas</li>
    </ul>
    <div class="grafico-lienzo" data-grafico="meses" role="img" aria-label="Gráfico de columnas apiladas con las ventas de cada mes"></div>
    <details class="tabla-grafico">
      <summary>Ver los datos en una tabla</summary>
      <div class="tabla-scroll">
        <table>
          <thead><tr><th>Mes</th><th class="num">Reparaciones</th><th class="num">Mano de obra</th><th class="num">Piezas</th><th class="num">Total</th></tr></thead>
          <tbody>
            <?php foreach ($meses as $m): ?>
              <tr>
                <td><?= e(ucfirst($m['mes']) . ' ' . $m['anio']) ?></td>
                <td class="num"><?= $m['reparaciones'] ?></td>
                <td class="num"><?= euros($m['mano_obra']) ?></td>
                <td class="num"><?= euros($m['piezas']) ?></td>
                <td class="num"><?= euros($m['mano_obra'] + $m['piezas']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </details>
  </section>

  <section class="tarjeta grafico" aria-labelledby="g-piezas">
    <h3 id="g-piezas">Piezas más vendidas</h3>
    <p class="grafico-sub">Unidades vendidas <?= $nombrePeriodo ?></p>
    <?php if (!$piezas): ?>
      <p class="vacio">No se ha vendido ninguna pieza <?= $nombrePeriodo ?>.</p>
    <?php else: ?>
      <div class="grafico-lienzo" data-grafico="piezas" role="img" aria-label="Barras con las piezas más vendidas"></div>
      <details class="tabla-grafico">
        <summary>Ver los datos en una tabla</summary>
        <div class="tabla-scroll">
          <table>
            <thead><tr><th>Pieza</th><th class="num">Unidades</th><th class="num">Importe</th></tr></thead>
            <tbody>
              <?php foreach ($piezas as $p): ?>
                <tr><td><?= e($p['nombre']) ?></td><td class="num"><?= (int) $p['unidades'] ?></td><td class="num"><?= euros($p['importe']) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </details>
    <?php endif; ?>
  </section>

  <section class="tarjeta grafico" aria-labelledby="g-averias">
    <h3 id="g-averias">Averías más comunes</h3>
    <p class="grafico-sub">Coches que entraron <?= $nombrePeriodo ?>, por tipo de avería</p>
    <?php if (!$averias): ?>
      <p class="vacio">No ha entrado ningún coche <?= $nombrePeriodo ?>.</p>
    <?php else: ?>
      <div class="grafico-lienzo" data-grafico="averias" role="img" aria-label="Barras con los tipos de avería más comunes"></div>
      <details class="tabla-grafico">
        <summary>Ver los datos en una tabla</summary>
        <div class="tabla-scroll">
          <table>
            <thead><tr><th>Tipo de avería</th><th class="num">Reparaciones</th></tr></thead>
            <tbody>
              <?php foreach ($averias as $a): ?>
                <tr><td><?= e($a['tipo']) ?></td><td class="num"><?= $a['reparaciones'] ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </details>
    <?php endif; ?>
  </section>

  <section class="tarjeta grafico grafico-ancho" aria-labelledby="g-mecanicos">
    <h3 id="g-mecanicos">Ventas por mecánico</h3>
    <p class="grafico-sub">Lo vendido en las reparaciones de cada mecánico <?= $nombrePeriodo ?></p>
    <?php if (!$mecanicos): ?>
      <p class="vacio">No se ha entregado ninguna reparación <?= $nombrePeriodo ?>.</p>
    <?php else: ?>
      <div class="grafico-lienzo" data-grafico="mecanicos" role="img" aria-label="Barras con lo vendido por cada mecánico"></div>
      <details class="tabla-grafico">
        <summary>Ver los datos en una tabla</summary>
        <div class="tabla-scroll">
          <table>
            <thead><tr><th>Mecánico</th><th class="num">Reparaciones</th><th class="num">Vendido</th></tr></thead>
            <tbody>
              <?php foreach ($mecanicos as $m): ?>
                <tr><td><?= e($m['nombre']) ?></td><td class="num"><?= (int) $m['reparaciones'] ?></td><td class="num"><?= euros($m['total']) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </details>
    <?php endif; ?>
  </section>
</div>

<script type="application/json" id="datos-graficos"><?= json_encode([
    'meses' => $meses,
    'piezas' => array_map(fn($p) => ['nombre' => $p['nombre'], 'referencia' => $p['referencia'], 'unidades' => (int) $p['unidades'], 'importe' => (float) $p['importe']], $piezas),
    'averias' => $averias,
    'mecanicos' => array_map(fn($m) => ['nombre' => $m['nombre'], 'reparaciones' => (int) $m['reparaciones'], 'total' => (float) $m['total']], $mecanicos),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<script src="graficos.js"></script>

<?php require __DIR__ . '/pie.php'; ?>
