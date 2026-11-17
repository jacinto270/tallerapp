<?php require __DIR__ . '/cabecera.php'; ?>

<?php if ($errores): ?>
  <ul class="error">
    <?php foreach ($errores as $error): ?>
      <li><?= e($error) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<form method="post" action="index.php?accion=guardar" class="alta">
  <label for="vehiculo_id">Vehículo</label>
  <select name="vehiculo_id" id="vehiculo_id" required>
    <option value="">Elige un vehículo…</option>
    <?php foreach ($vehiculos as $vehiculo): ?>
      <option value="<?= e($vehiculo['id']) ?>" <?= ($valores['vehiculo_id'] ?? '') == $vehiculo['id'] ? 'selected' : '' ?>>
        <?= e($vehiculo['marca']) ?> <?= e($vehiculo['modelo']) ?> · <?= e($vehiculo['matricula']) ?> · <?= e($vehiculo['nombre']) ?> <?= e($vehiculo['apellidos']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <label for="mecanico_id">Mecánico</label>
  <select name="mecanico_id" id="mecanico_id" required>
    <option value="">Elige un mecánico…</option>
    <?php foreach ($mecanicos as $mecanico): ?>
      <option value="<?= e($mecanico['id']) ?>" <?= ($valores['mecanico_id'] ?? '') == $mecanico['id'] ? 'selected' : '' ?>>
        <?= e($mecanico['nombre']) ?> (<?= e($mecanico['especialidad']) ?>)
      </option>
    <?php endforeach; ?>
  </select>

  <label for="descripcion">Avería</label>
  <textarea name="descripcion" id="descripcion" maxlength="200" required><?= e($valores['descripcion'] ?? '') ?></textarea>

  <label for="mano_obra">Mano de obra (€)</label>
  <input type="number" name="mano_obra" id="mano_obra" min="0" step="0.01" required value="<?= e($valores['mano_obra'] ?? '') ?>">

  <?= csrf() ?>
  <button>Abrir la reparación</button>
</form>

<?php require __DIR__ . '/pie.php'; ?>  
