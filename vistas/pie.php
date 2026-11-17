  </main>
  <footer class="pie">
    <div class="contenedor">
      <div class="pie-herramientas" aria-hidden="true">
        <?= icono('llave') ?><?= icono('engranaje', 'girando-lento') ?><?= icono('rueda', 'girando') ?><?= icono('bandera') ?>
      </div>
      <p>Taller Mecánico Genil · Ingeniería de competición para tu coche de cada día</p>
      <p><a href="index.php?accion=sala">¿Esperando tu coche? Echa una carrera →</a></p>
    </div>
  </footer>

  <!-- Turbo, el mecánico guía (lo activa app.js) -->
  <aside class="guia no-imprimir" id="guia" hidden aria-label="Ayuda del mecánico">
    <div class="guia-bocadillo" role="status" aria-live="polite">
      <p class="guia-nombre">Turbo · tu mecánico</p>
      <p class="guia-texto"></p>
      <div class="guia-botones">
        <button type="button" class="boton-mini boton-secundario" data-guia="anterior" aria-label="Consejo anterior">←</button>
        <span class="guia-paso"></span>
        <button type="button" class="boton-mini" data-guia="siguiente">Siguiente</button>
      </div>
      <button type="button" class="guia-cerrar" data-guia="cerrar" aria-label="Cerrar los consejos">×</button>
    </div>
    <button type="button" class="guia-personaje" data-guia="abrir" aria-label="Pedir ayuda a Turbo, el mecánico">
      <?= mecanicoSvg() ?>
    </button>
  </aside>
</body>
</html>
