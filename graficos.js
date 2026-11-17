// Gráficos de la página de ventas, dibujados en SVG sin librerías.
// Los datos vienen en <script id="datos-graficos">; las tablas de cada gráfico
// están en el HTML, así que sin JavaScript los datos se siguen pudiendo leer.
(() => {
  'use strict';

  const fuente = document.getElementById('datos-graficos');
  if (!fuente) return;
  const datos = JSON.parse(fuente.textContent);

  const NS = 'http://www.w3.org/2000/svg';
  const euros = new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR', useGrouping: 'always' });
  const eurosRedondo = new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0, useGrouping: 'always' });
  const escapar = texto => String(texto).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);

  // Medir texto para recortar etiquetas largas sin que se salgan
  const medidor = document.createElement('canvas').getContext('2d');
  function recortar(texto, ancho, fuenteCss) {
    medidor.font = fuenteCss;
    if (medidor.measureText(texto).width <= ancho) return texto;
    let corto = texto;
    while (corto.length > 1 && medidor.measureText(corto + '…').width > ancho) corto = corto.slice(0, -1);
    return corto.trimEnd() + '…';
  }

  // Escala «bonita» para el eje: 0, 250, 500… o 0, 1.000, 2.000…
  function escala(maximo, marcas = 4) {
    if (maximo <= 0) return { tope: 1, paso: 1 };
    const bruto = maximo / marcas;
    const potencia = Math.pow(10, Math.floor(Math.log10(bruto)));
    const paso = [1, 2, 2.5, 5, 10].map(f => f * potencia).find(p => p >= bruto);
    return { tope: Math.ceil(maximo / paso) * paso, paso };
  }

  // Barra con la punta redondeada (4px) y la base recta
  function barraHorizontal(x, y, ancho, alto, radio = 4) {
    if (ancho <= radio) return `M${x},${y}h${ancho}v${alto}h${-ancho}Z`;
    return `M${x},${y}h${ancho - radio}a${radio},${radio} 0 0 1 ${radio},${radio}v${alto - 2 * radio}`
         + `a${radio},${radio} 0 0 1 ${-radio},${radio}h${-(ancho - radio)}Z`;
  }
  function barraVertical(x, yBase, ancho, alto, redondear, radio = 4) {
    const y = yBase - alto;
    if (!redondear || alto <= radio) return `M${x},${yBase}v${-alto}h${ancho}v${alto}Z`;
    return `M${x},${yBase}V${y + radio}a${radio},${radio} 0 0 1 ${radio},${-radio}h${ancho - 2 * radio}`
         + `a${radio},${radio} 0 0 1 ${radio},${radio}V${yBase}Z`;
  }

  // ---------- Bocadillo (tooltip) ----------

  function crearBocadillo(contenedor) {
    const bocadillo = document.createElement('div');
    bocadillo.className = 'bocadillo-grafico';
    bocadillo.hidden = true;
    contenedor.appendChild(bocadillo);
    return {
      mostrar(html, x, y) {
        bocadillo.innerHTML = html;
        bocadillo.hidden = false;
        const ancho = contenedor.clientWidth;
        const caja = bocadillo.getBoundingClientRect();
        let izquierda = x + 14;
        if (izquierda + caja.width > ancho) izquierda = Math.max(0, x - caja.width - 14);
        bocadillo.style.left = izquierda + 'px';
        bocadillo.style.top = Math.max(0, y - caja.height / 2) + 'px';
      },
      ocultar() { bocadillo.hidden = true; },
    };
  }

  // Activa hover, foco con teclado y toque en las zonas de cada marca
  function interaccion(contenedor, svg, bocadillo, textoDe) {
    // Retraso de la animación de cada barra. Se pone desde JS porque la política de
    // seguridad (CSP) no permite atributos style="" escritos en el HTML
    svg.querySelectorAll('[data-retraso]').forEach(el => { el.style.animationDelay = el.dataset.retraso + 'ms'; });
    const zonas = svg.querySelectorAll('.zona');
    const activar = (zona, x, y) => {
      svg.classList.add('con-foco');
      svg.querySelectorAll('.activa').forEach(el => el.classList.remove('activa'));
      svg.querySelectorAll(`[data-i="${zona.dataset.i}"]`).forEach(el => el.classList.add('activa'));
      bocadillo.mostrar(textoDe(+zona.dataset.i), x, y);
    };
    const desactivar = () => {
      svg.classList.remove('con-foco');
      svg.querySelectorAll('.activa').forEach(el => el.classList.remove('activa'));
      bocadillo.ocultar();
    };
    zonas.forEach(zona => {
      zona.addEventListener('pointermove', ev => {
        const caja = contenedor.getBoundingClientRect();
        activar(zona, ev.clientX - caja.left, ev.clientY - caja.top);
      });
      zona.addEventListener('focus', () => {
        const caja = contenedor.getBoundingClientRect();
        const r = zona.getBoundingClientRect();
        activar(zona, r.left - caja.left + r.width / 2, r.top - caja.top + r.height / 2);
      });
      zona.addEventListener('blur', desactivar);
    });
    svg.addEventListener('pointerleave', desactivar);
  }

  // ---------- Barras horizontales (piezas, averías, mecánicos) ----------

  function barras(contenedor, filas, { etiqueta, valor, textoValor, detalle }, animar) {
    const ancho = contenedor.clientWidth;
    const alturaFila = 36, grosor = 18;
    const alto = filas.length * alturaFila;
    const anchoEtiqueta = Math.min(190, Math.max(110, ancho * 0.38));
    const anchoValor = 78;
    const anchoBarras = Math.max(40, ancho - anchoEtiqueta - anchoValor);
    const maximo = Math.max(...filas.map(valor), 1);
    const fuenteEtiqueta = '500 13px "Exo 2", system-ui, sans-serif';

    let svg = `<svg class="grafico-svg${animar ? ' animar' : ''}" width="${ancho}" height="${alto}" viewBox="0 0 ${ancho} ${alto}">`;
    filas.forEach((fila, i) => {
      const y = i * alturaFila;
      const largo = Math.max(2, (valor(fila) / maximo) * anchoBarras);
      const yBarra = y + (alturaFila - grosor) / 2;
      svg += `<text class="eje-texto" x="0" y="${y + alturaFila / 2}" dominant-baseline="central">${escapar(recortar(etiqueta(fila), anchoEtiqueta - 10, fuenteEtiqueta))}</text>`
           + `<path class="marca serie-1 barra-h" data-i="${i}" data-retraso="${i * 60}" d="${barraHorizontal(anchoEtiqueta, yBarra, largo, grosor)}"/>`
           + `<text class="valor-texto" x="${anchoEtiqueta + largo + 8}" y="${y + alturaFila / 2}" dominant-baseline="central">${escapar(textoValor(fila))}</text>`
           + `<rect class="zona" data-i="${i}" tabindex="0" x="0" y="${y}" width="${ancho}" height="${alturaFila}" aria-label="${escapar(etiqueta(fila) + ': ' + textoValor(fila))}"/>`;
    });
    svg += '</svg>';

    contenedor.innerHTML = svg;
    const bocadillo = crearBocadillo(contenedor);
    interaccion(contenedor, contenedor.querySelector('svg'), bocadillo, i => detalle(filas[i]));
  }

  // ---------- Columnas apiladas (ventas por mes) ----------

  function columnasMeses(contenedor, meses, animar) {
    const ancho = contenedor.clientWidth;
    const alto = 280;
    const margen = { arriba: 26, derecha: 8, abajo: 30, izquierda: 62 };
    const anchoUtil = ancho - margen.izquierda - margen.derecha;
    const altoUtil = alto - margen.arriba - margen.abajo;
    const totales = meses.map(m => m.mano_obra + m.piezas);
    const { tope, paso } = escala(Math.max(...totales));
    const banda = anchoUtil / meses.length;
    const grosor = Math.min(24, banda * 0.6);
    const yDe = v => margen.arriba + altoUtil - (v / tope) * altoUtil;
    const base = yDe(0);
    const hueco = 2;   // separación entre los dos tramos de la columna

    let svg = `<svg class="grafico-svg${animar ? ' animar' : ''}" width="${ancho}" height="${alto}" viewBox="0 0 ${ancho} ${alto}">`;

    // Rejilla y eje Y
    for (let v = 0; v <= tope + 1e-9; v += paso) {
      const y = Math.round(yDe(v)) + 0.5;
      svg += `<line class="rejilla" x1="${margen.izquierda}" x2="${ancho - margen.derecha}" y1="${y}" y2="${y}"/>`
           + `<text class="eje-texto" x="${margen.izquierda - 8}" y="${y}" text-anchor="end" dominant-baseline="central">${eurosRedondo.format(v)}</text>`;
    }

    // Etiquetas solo en el mes más alto y en el mes actual
    const iMaximo = totales.indexOf(Math.max(...totales));
    const iActual = meses.length - 1;
    const pasoEtiquetas = ancho < 480 ? 2 : 1;

    meses.forEach((mes, i) => {
      const x = margen.izquierda + banda * i + (banda - grosor) / 2;
      const altoMano = (mes.mano_obra / tope) * altoUtil;
      const altoPiezas = (mes.piezas / tope) * altoUtil;
      const total = totales[i];

      svg += `<g class="columna" data-i="${i}" data-retraso="${i * 40}">`;
      if (mes.mano_obra > 0) {
        svg += `<path class="marca serie-1" data-i="${i}" d="${barraVertical(x, base, grosor, altoMano, mes.piezas === 0)}"/>`;
      }
      if (mes.piezas > 0) {
        const baseSuperior = base - altoMano - (mes.mano_obra > 0 ? hueco : 0);
        svg += `<path class="marca serie-2" data-i="${i}" d="${barraVertical(x, baseSuperior, grosor, Math.max(1, altoPiezas - (mes.mano_obra > 0 ? hueco : 0)), true)}"/>`;
      }
      svg += '</g>';

      if (total > 0 && (i === iMaximo || i === iActual)) {
        svg += `<text class="valor-texto" x="${x + grosor / 2}" y="${yDe(total) - 8}" text-anchor="middle">${eurosRedondo.format(total)}</text>`;
      }
      if (i % pasoEtiquetas === (meses.length - 1) % pasoEtiquetas) {
        svg += `<text class="eje-texto" x="${x + grosor / 2}" y="${alto - 10}" text-anchor="middle">${escapar(mes.mes)}</text>`;
      }
      svg += `<rect class="zona" data-i="${i}" tabindex="0" x="${margen.izquierda + banda * i}" y="${margen.arriba}" width="${banda}" height="${altoUtil}"`
           + ` aria-label="${escapar(mes.mes + ' ' + mes.anio + ': ' + euros.format(total))}"/>`;
    });

    // Línea base
    svg += `<line class="eje" x1="${margen.izquierda}" x2="${ancho - margen.derecha}" y1="${Math.round(base) + 0.5}" y2="${Math.round(base) + 0.5}"/>`;
    svg += '</svg>';

    contenedor.innerHTML = svg;
    const bocadillo = crearBocadillo(contenedor);
    interaccion(contenedor, contenedor.querySelector('svg'), bocadillo, i => {
      const m = meses[i];
      return `<strong>${escapar(m.mes)} ${m.anio}</strong>`
           + `<span><i class="muestra serie-1"></i>Mano de obra <b>${euros.format(m.mano_obra)}</b></span>`
           + `<span><i class="muestra serie-2"></i>Piezas <b>${euros.format(m.piezas)}</b></span>`
           + `<span class="bocadillo-total">Total <b>${euros.format(m.mano_obra + m.piezas)}</b></span>`
           + `<span class="suave">${m.reparaciones} ${m.reparaciones === 1 ? 'reparación' : 'reparaciones'}</span>`;
    });
  }

  // ---------- Qué gráfico va en cada sitio ----------

  const unidades = n => n + (n === 1 ? ' ud.' : ' uds.');
  const dibujos = {
    meses: (el, animar) => columnasMeses(el, datos.meses, animar),
    piezas: (el, animar) => barras(el, datos.piezas, {
      etiqueta: p => p.nombre,
      valor: p => p.unidades,
      textoValor: p => unidades(p.unidades),
      detalle: p => `<strong>${escapar(p.nombre)}</strong><span class="suave">${escapar(p.referencia)}</span>`
                  + `<span>${unidades(p.unidades)} · <b>${euros.format(p.importe)}</b></span>`,
    }, animar),
    averias: (el, animar) => barras(el, datos.averias, {
      etiqueta: a => a.tipo,
      valor: a => a.reparaciones,
      textoValor: a => String(a.reparaciones),
      detalle: a => `<strong>${escapar(a.tipo)}</strong><span>${a.reparaciones} ${a.reparaciones === 1 ? 'reparación' : 'reparaciones'}</span>`,
    }, animar),
    mecanicos: (el, animar) => barras(el, datos.mecanicos, {
      etiqueta: m => m.nombre,
      valor: m => m.total,
      textoValor: m => eurosRedondo.format(m.total),
      detalle: m => `<strong>${escapar(m.nombre)}</strong><span><b>${euros.format(m.total)}</b></span>`
                  + `<span class="suave">${m.reparaciones} ${m.reparaciones === 1 ? 'reparación' : 'reparaciones'}</span>`,
    }, animar),
  };

  document.querySelectorAll('[data-grafico]').forEach(contenedor => {
    const dibujar = dibujos[contenedor.dataset.grafico];
    if (!dibujar) return;
    let anchoAnterior = 0, primera = true, pendiente = false;
    const redibujar = () => {
      const ancho = contenedor.clientWidth;
      if (!ancho || ancho === anchoAnterior) return;
      anchoAnterior = ancho;
      dibujar(contenedor, primera);
      primera = false;
    };
    redibujar();
    if ('ResizeObserver' in window) {
      new ResizeObserver(() => {
        if (pendiente) return;
        pendiente = true;
        requestAnimationFrame(() => { pendiente = false; redibujar(); });
      }).observe(contenedor);
    }
  });
})();
