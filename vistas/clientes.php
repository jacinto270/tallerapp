<?php require __DIR__ . '/cabecera.php'; ?>

<form class="buscador" method="get" action="index.php" role="search">
  <input type="hidden" name="accion" value="clientes">
  <input type="search" name="q" value="<?= e($busqueda) ?>" data-filtrar="#rejilla-clientes"
         placeholder="Busca por nombre, teléfono o matrícula…" aria-label="Buscar clientes">
  <button>Buscar</button>
  <?php if ($busqueda !== ''): ?>
    <a class="boton boton-secundario" href="index.php?accion=clientes">Limpiar</a>
  <?php endif; ?>
</form>

<p class="resultado">
  <?= plural(count($clientes), 'cliente', 'clientes') ?><?= $busqueda !== '' ? ' para «' . e($busqueda) . '»' : '' ?>
</p>

<?php if (!$clientes): ?>
  <p class="vacio">No hay ningún cliente que coincida con la búsqueda.</p>
<?php else: ?>
  <div class="rejilla" id="rejilla-clientes">
    <?php foreach ($clientes as $cliente): ?>
      <article class="tarjeta cliente">
        <div class="cliente-cabeza">
          <span class="avatar"><?= e(iniciales($cliente['nombre'], $cliente['apellidos'])) ?></span>
          <div>
            <h3><a href="index.php?accion=cliente&amp;id=<?= (int) $cliente['id'] ?>"><?= e($cliente['nombre'] . ' ' . $cliente['apellidos']) ?></a></h3>
            <a class="suave" href="tel:<?= e($cliente['telefono']) ?>"><?= e($cliente['telefono']) ?></a>
          </div>
        </div>
        <div class="datos">
          <span class="dato"><strong><?= (int) $cliente['vehiculos'] ?></strong> <?= (int) $cliente['vehiculos'] === 1 ? 'vehículo' : 'vehículos' ?></span>
          <span class="dato"><strong><?= (int) $cliente['reparaciones'] ?></strong> <?= (int) $cliente['reparaciones'] === 1 ? 'reparación' : 'reparaciones' ?></span>
          <?php if ($cliente['en_taller'] > 0): ?>
            <strong class="chapa chapa-taller"><?= (int) $cliente['en_taller'] ?> en el taller</strong>
          <?php endif; ?>
        </div>
        <a class="boton boton-mini boton-secundario" href="index.php?accion=cliente&amp;id=<?= (int) $cliente['id'] ?>">Ver ficha</a>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/pie.php'; ?>
