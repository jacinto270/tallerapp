<?php require __DIR__ . '/cabecera.php'; ?>

<div class="sala">
  <div class="pista">
    <div class="hud" aria-live="polite">
      <div>Puntos<strong id="puntos">0</strong></div>
      <div>Velocidad<strong id="velocidad">0 km/h</strong></div>
      <div>Récord<strong id="record">0</strong></div>
    </div>

    <div class="lienzo-caja">
      <canvas id="juego" width="400" height="600"
              aria-label="Juego de carreras: cambia de carril para esquivar el tráfico y recoger monedas"></canvas>
      <div class="mensaje-juego" id="mensaje-juego">
        <p class="titulo-juego">Neon Rush</p>
        <p id="texto-juego">Pulsa ESPACIO o toca la pista para arrancar</p>
        <button type="button" id="arrancar">Arrancar</button>
      </div>
    </div>

    <div class="mandos">
      <button type="button" id="izquierda" aria-label="Carril de la izquierda">◀</button>
      <button type="button" id="derecha" aria-label="Carril de la derecha">▶</button>
    </div>
  </div>

  <section class="tarjeta">
    <h2>¿Esperando tu coche?</h2>
    <p>Mientras nuestros mecánicos trabajan en boxes, demuestra lo que vales al volante.
       Cuanto más aguantes, más rápido irás.</p>

    <h3>Cómo se juega</h3>
    <ul class="lista-neon">
      <li><strong>◀ ▶</strong> o <strong>A / D</strong> para cambiar de carril. En el móvil, toca a la izquierda o a la derecha de la pista.</li>
      <li>Esquiva el tráfico: un choque y se acaba la carrera.</li>
      <li>Recoge las monedas <strong>€</strong>: cada una vale 50 puntos.</li>
      <li>Tu récord se guarda en este dispositivo.</li>
    </ul>
  </section>
</div>

<script src="juego.js"></script>

<?php require __DIR__ . '/pie.php'; ?>
