// Neon Rush: minijuego de carreras para la sala de espera.
// Cambia de carril para esquivar el tráfico y recoge monedas.
(() => {
  'use strict';

  const lienzo = document.getElementById('juego');
  if (!lienzo) return;
  const ctx = lienzo.getContext('2d');

  const ANCHO = 400, ALTO = 600;
  const CARRILES = 3;
  const ARCEN = 50;
  const ANCHO_CARRIL = (ANCHO - ARCEN * 2) / CARRILES;
  const COLORES = ['#ff2e88', '#ff8a00', '#a855f7', '#facc15', '#22c55e'];
  const centro = carril => ARCEN + ANCHO_CARRIL * carril + ANCHO_CARRIL / 2;

  const $ = id => document.getElementById(id);
  const puntosEl = $('puntos'), velocidadEl = $('velocidad'), recordEl = $('record');
  const mensajeEl = $('mensaje-juego'), textoEl = $('texto-juego'), arrancarEl = $('arrancar');
  const tituloEl = mensajeEl.querySelector('.titulo-juego');

  let record = 0;
  try { record = Number(localStorage.getItem('neonRushRecord')) || 0; } catch (e) { /* sin almacenamiento */ }
  recordEl.textContent = record;

  let estado = 'inicio';            // inicio | jugando | fin
  let jugador, rivales, monedas, chispas;
  let puntos, velocidad, recorrido, proximoRival, proximaMoneda;
  let finEn = 0;

  function preparar() {
    jugador = { carril: 1, x: centro(1), y: ALTO - 110, w: 44, h: 80 };
    rivales = [];
    monedas = [];
    chispas = [];
    puntos = 0;
    velocidad = 280;
    recorrido = 0;
    proximoRival = 0.6;
    proximaMoneda = 1.2;
  }

  function arrancar() {
    if (estado === 'jugando' || performance.now() - finEn < 600) return;
    preparar();
    estado = 'jugando';
    mensajeEl.hidden = true;
  }

  function girar(direccion) {
    if (estado !== 'jugando') { arrancar(); return; }
    jugador.carril = Math.max(0, Math.min(CARRILES - 1, jugador.carril + direccion));
  }

  function nuevoRival() {
    // Nunca se tapan los tres carriles a la vez: siempre queda un hueco
    const ocupados = new Set(rivales.filter(r => r.y < 200).map(r => r.carril));
    const libres = [0, 1, 2].filter(c => !ocupados.has(c));
    if (libres.length <= 1) return;
    const carril = libres[Math.floor(Math.random() * libres.length)];
    rivales.push({
      carril, x: centro(carril), y: -100, w: 44, h: 80,
      color: COLORES[Math.floor(Math.random() * COLORES.length)],
      ritmo: 0.45 + Math.random() * 0.3,
    });
  }

  function nuevaMoneda() {
    const carril = Math.floor(Math.random() * CARRILES);
    if (rivales.some(r => r.carril === carril && r.y < 80)) return;
    monedas.push({ x: centro(carril), y: -30, r: 12, giro: 0 });
  }

  function explotar(x, y, color, cantidad) {
    for (let i = 0; i < cantidad; i++) {
      const angulo = Math.random() * Math.PI * 2;
      const fuerza = 60 + Math.random() * 260;
      chispas.push({ x, y, vx: Math.cos(angulo) * fuerza, vy: Math.sin(angulo) * fuerza, vida: 0.5 + Math.random() * 0.6, color });
    }
  }

  const choca = (a, b) =>
    Math.abs(a.x - b.x) < (a.w + b.w) / 2 - 8 && Math.abs(a.y - b.y) < (a.h + b.h) / 2 - 8;

  function terminar() {
    estado = 'fin';
    finEn = performance.now();
    explotar(jugador.x, jugador.y, '#00f0ff', 40);
    explotar(jugador.x, jugador.y, '#ff2e88', 30);

    const total = Math.floor(puntos);
    let texto = total + ' puntos.';
    if (total > record) {
      record = total;
      recordEl.textContent = record;
      texto = '¡Nuevo récord! ' + texto;
      try { localStorage.setItem('neonRushRecord', String(record)); } catch (e) { /* sin almacenamiento */ }
    }
    tituloEl.textContent = '¡Accidente!';
    textoEl.textContent = texto + ' Pulsa ESPACIO o toca para volver a correr.';
    arrancarEl.textContent = 'Otra carrera';
    setTimeout(() => { mensajeEl.hidden = false; }, 500);
  }

  function actualizar(dt) {
    chispas.forEach(c => { c.x += c.vx * dt; c.y += c.vy * dt; c.vx *= 0.96; c.vy *= 0.96; c.vida -= dt; });
    chispas = chispas.filter(c => c.vida > 0);

    if (estado !== 'jugando') {
      recorrido = (recorrido + 60 * dt) % 80;
      return;
    }

    velocidad = Math.min(900, velocidad + 9 * dt);
    recorrido = (recorrido + velocidad * dt) % 80;
    puntos += velocidad * dt / 20;
    jugador.x += (centro(jugador.carril) - jugador.x) * Math.min(1, dt * 14);

    if ((proximoRival -= dt) <= 0) {
      nuevoRival();
      proximoRival = Math.max(0.32, 0.95 - velocidad / 1400) + Math.random() * 0.35;
    }
    if ((proximaMoneda -= dt) <= 0) {
      nuevaMoneda();
      proximaMoneda = 1 + Math.random() * 1.6;
    }

    rivales.forEach(r => { r.y += velocidad * r.ritmo * dt; });
    rivales = rivales.filter(r => r.y < ALTO + 100);

    monedas.forEach(m => { m.y += velocidad * dt; m.giro += dt * 6; });
    monedas = monedas.filter(m => {
      if (choca(jugador, { x: m.x, y: m.y, w: m.r * 2 + 16, h: m.r * 2 + 16 })) {
        puntos += 50;
        explotar(m.x, m.y, '#ffd400', 18);
        return false;
      }
      return m.y < ALTO + 40;
    });

    if (rivales.some(r => choca(jugador, r))) terminar();

    puntosEl.textContent = Math.floor(puntos);
    velocidadEl.textContent = Math.round(velocidad / 2.2) + ' km/h';
  }

  // ---------- Dibujo ----------

  function rectRedondo(x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
  }

  function dibujarCarretera() {
    const cielo = ctx.createLinearGradient(0, 0, 0, ALTO);
    cielo.addColorStop(0, '#12052b');
    cielo.addColorStop(1, '#05050c');
    ctx.fillStyle = cielo;
    ctx.fillRect(0, 0, ANCHO, ALTO);

    // Rejilla de neón en los arcenes
    ctx.strokeStyle = 'rgba(168, 85, 247, .3)';
    ctx.lineWidth = 1;
    for (let y = -80 + recorrido; y < ALTO; y += 40) {
      ctx.beginPath();
      ctx.moveTo(0, y); ctx.lineTo(ARCEN, y);
      ctx.moveTo(ANCHO - ARCEN, y); ctx.lineTo(ANCHO, y);
      ctx.stroke();
    }

    // Asfalto
    ctx.fillStyle = '#10111f';
    ctx.fillRect(ARCEN, 0, ANCHO - ARCEN * 2, ALTO);

    // Pianos rojos y blancos
    for (let y = -80 + recorrido, i = 0; y < ALTO; y += 20, i++) {
      ctx.fillStyle = i % 2 ? '#ffffff' : '#e11d48';
      ctx.fillRect(ARCEN - 10, y, 8, 20);
      ctx.fillRect(ANCHO - ARCEN + 2, y, 8, 20);
    }

    // Bordes de neón
    ctx.save();
    ctx.shadowBlur = 16;
    ctx.fillStyle = ctx.shadowColor = '#ff2e88';
    ctx.fillRect(ARCEN - 2, 0, 3, ALTO);
    ctx.fillStyle = ctx.shadowColor = '#00f0ff';
    ctx.fillRect(ANCHO - ARCEN - 1, 0, 3, ALTO);
    ctx.restore();

    // Líneas de carril
    ctx.fillStyle = 'rgba(255, 255, 255, .5)';
    for (let c = 1; c < CARRILES; c++) {
      const x = ARCEN + ANCHO_CARRIL * c - 2;
      for (let y = -80 + recorrido; y < ALTO; y += 80) ctx.fillRect(x, y, 4, 40);
    }
  }

  function dibujarCoche(coche, color, esJugador) {
    const { x, y, w, h } = coche;
    const izq = x - w / 2, arriba = y - h / 2;
    ctx.save();

    // Ruedas
    ctx.fillStyle = '#000';
    ctx.fillRect(izq - 4, arriba + 10, 7, 18);
    ctx.fillRect(izq + w - 3, arriba + 10, 7, 18);
    ctx.fillRect(izq - 4, arriba + h - 28, 7, 18);
    ctx.fillRect(izq + w - 3, arriba + h - 28, 7, 18);

    // Carrocería con brillo de neón
    ctx.shadowColor = color;
    ctx.shadowBlur = esJugador ? 24 : 12;
    ctx.fillStyle = color;
    rectRedondo(izq, arriba, w, h, 12);
    ctx.fill();
    ctx.shadowBlur = 0;

    // Franja de carreras
    ctx.fillStyle = 'rgba(255, 255, 255, .85)';
    ctx.fillRect(x - 4, arriba + 2, 8, h - 4);

    // Parabrisas y luna trasera
    ctx.fillStyle = '#0b1030';
    rectRedondo(izq + 6, arriba + 20, w - 12, 16, 4); ctx.fill();
    rectRedondo(izq + 7, arriba + h - 26, w - 14, 12, 4); ctx.fill();

    // Alerón
    ctx.fillStyle = esJugador ? '#00f0ff' : '#111';
    ctx.fillRect(izq - 2, arriba + h - 6, w + 4, 6);

    // Faros delante y pilotos detrás
    ctx.fillStyle = '#fffbe6';
    ctx.fillRect(izq + 4, arriba + 2, 9, 4);
    ctx.fillRect(izq + w - 13, arriba + 2, 9, 4);
    ctx.fillStyle = ctx.shadowColor = '#ff1f3d';
    ctx.shadowBlur = 10;
    ctx.fillRect(izq + 4, arriba + h - 11, 9, 3);
    ctx.fillRect(izq + w - 13, arriba + h - 11, 9, 3);

    ctx.restore();
  }

  function dibujarFaros() {
    const { x, y, h } = jugador;
    const morro = y - h / 2;
    const luz = ctx.createLinearGradient(0, morro, 0, morro - 150);
    luz.addColorStop(0, 'rgba(255, 251, 230, .3)');
    luz.addColorStop(1, 'rgba(255, 251, 230, 0)');
    ctx.fillStyle = luz;
    ctx.beginPath();
    ctx.moveTo(x - 16, morro);
    ctx.lineTo(x + 16, morro);
    ctx.lineTo(x + 42, morro - 150);
    ctx.lineTo(x - 42, morro - 150);
    ctx.closePath();
    ctx.fill();
  }

  function dibujarMoneda(m) {
    ctx.save();
    ctx.translate(m.x, m.y);
    ctx.scale(Math.abs(Math.cos(m.giro)) * 0.7 + 0.3, 1);
    ctx.shadowColor = '#ffd400';
    ctx.shadowBlur = 18;
    ctx.fillStyle = '#ffd400';
    ctx.beginPath();
    ctx.arc(0, 0, m.r, 0, Math.PI * 2);
    ctx.fill();
    ctx.shadowBlur = 0;
    ctx.fillStyle = '#7a5200';
    ctx.font = 'bold 14px sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('€', 0, 1);
    ctx.restore();
  }

  function dibujar() {
    dibujarCarretera();
    monedas.forEach(dibujarMoneda);
    rivales.forEach(r => dibujarCoche(r, r.color, false));
    if (estado !== 'fin') {
      dibujarFaros();
      dibujarCoche(jugador, '#00f0ff', true);
    }
    chispas.forEach(c => {
      ctx.globalAlpha = Math.max(0, Math.min(1, c.vida * 1.5));
      ctx.fillStyle = c.color;
      ctx.fillRect(c.x - 2, c.y - 2, 4, 4);
    });
    ctx.globalAlpha = 1;
  }

  // ---------- Controles ----------

  document.addEventListener('keydown', ev => {
    const tecla = ev.key;
    if (tecla === 'ArrowLeft' || tecla === 'a' || tecla === 'A') {
      girar(-1); ev.preventDefault();
    } else if (tecla === 'ArrowRight' || tecla === 'd' || tecla === 'D') {
      girar(1); ev.preventDefault();
    } else if (tecla === ' ' || (tecla === 'Enter' && estado !== 'jugando')) {
      arrancar(); ev.preventDefault();
    }
  });

  lienzo.addEventListener('pointerdown', ev => {
    const caja = lienzo.getBoundingClientRect();
    girar(ev.clientX - caja.left < caja.width / 2 ? -1 : 1);
  });
  mensajeEl.addEventListener('click', arrancar);
  $('izquierda').addEventListener('pointerdown', ev => { ev.preventDefault(); girar(-1); });
  $('derecha').addEventListener('pointerdown', ev => { ev.preventDefault(); girar(1); });

  // ---------- Bucle ----------

  const nitidez = Math.min(window.devicePixelRatio || 1, 2);
  lienzo.width = ANCHO * nitidez;
  lienzo.height = ALTO * nitidez;
  ctx.setTransform(nitidez, 0, 0, nitidez, 0, 0);

  let anterior = performance.now();
  function bucle(ahora) {
    const dt = Math.min(0.05, (ahora - anterior) / 1000);   // si la pestaña estuvo oculta, no salta
    anterior = ahora;
    actualizar(dt);
    dibujar();
    requestAnimationFrame(bucle);
  }

  preparar();
  requestAnimationFrame(bucle);
})();
