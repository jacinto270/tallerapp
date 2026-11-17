// Taller Genil · efectos e interacción de la interfaz.
// Todo es una mejora: sin JavaScript la aplicación sigue funcionando.
(() => {
  'use strict';

  const reducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const guardar = (clave, valor) => { try { localStorage.setItem(clave, valor); } catch (e) { /* sin almacenamiento */ } };
  const leer = clave => { try { return localStorage.getItem(clave); } catch (e) { return null; } };

  document.documentElement.classList.add('js');

  // ---------- Barra de carga al cambiar de página ----------

  const barra = document.createElement('div');
  barra.className = 'barra-carga';
  document.body.appendChild(barra);
  const cargando = () => barra.classList.add('activa');
  window.addEventListener('pageshow', () => barra.classList.remove('activa'));

  document.addEventListener('click', ev => {
    const enlace = ev.target.closest('a[href]');
    if (!enlace || ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey
        || enlace.target || enlace.hasAttribute('download')) return;
    const url = new URL(enlace.href, location.href);
    if (url.origin !== location.origin) return;
    if (url.pathname === location.pathname && url.search === location.search) return;   // ancla en la misma página
    cargando();
  });

  // ---------- Quita ?msg= de la dirección: recargar no repite el aviso ----------

  const direccion = new URL(location.href);
  if (direccion.searchParams.has('msg') || direccion.searchParams.has('imprimir')) {
    direccion.searchParams.delete('msg');
    direccion.searchParams.delete('imprimir');
    history.replaceState(null, '', direccion);
  }

  // ---------- Botón de tema: automático → claro → oscuro ----------

  const botonTema = document.querySelector('[data-boton-tema]');
  if (botonTema && window.TemaTaller) {
    const TEMAS = {
      auto:   { icono: '◐', texto: 'Auto',   siguiente: 'claro',  explicacion: 'Tema automático (el del sistema)' },
      claro:  { icono: '☀', texto: 'Claro',  siguiente: 'oscuro', explicacion: 'Tema claro' },
      oscuro: { icono: '☾', texto: 'Oscuro', siguiente: 'auto',   explicacion: 'Tema oscuro' },
    };
    const pintar = () => {
      const actual = TEMAS[window.TemaTaller.elegido()] || TEMAS.auto;
      botonTema.querySelector('.boton-tema-icono').textContent = actual.icono;
      botonTema.querySelector('.boton-tema-texto').textContent = actual.texto;
      botonTema.title = actual.explicacion + '. Pulsa para cambiar.';
      botonTema.setAttribute('aria-label', actual.explicacion + '. Pulsa para cambiar a ' + TEMAS[actual.siguiente].texto.toLowerCase() + '.');
    };
    botonTema.hidden = false;
    pintar();
    botonTema.addEventListener('click', () => {
      const actual = TEMAS[window.TemaTaller.elegido()] || TEMAS.auto;
      window.TemaTaller.elegir(actual.siguiente);
      pintar();
    });
  }

  // ---------- Cabecera compacta al desplazarse ----------

  const cabecera = document.querySelector('.cabecera');
  const alDesplazar = () => cabecera && cabecera.classList.toggle('compacta', window.scrollY > 30);
  window.addEventListener('scroll', alDesplazar, { passive: true });
  alDesplazar();

  // ---------- Contadores que suben ----------

  function contar(el) {
    const original = el.textContent;
    const fin = parseFloat(el.dataset.contar) || 0;
    const formato = el.dataset.formato === 'euros'
      ? new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' })
      : null;
    if (reducido || fin === 0) return;

    const inicio = performance.now();
    const duracion = 1100;
    const paso = ahora => {
      const p = Math.min(1, (ahora - inicio) / duracion);
      const valor = fin * (1 - Math.pow(1 - p, 3));
      el.textContent = p < 1 ? (formato ? formato.format(valor) : Math.round(valor)) : original;
      if (p < 1) requestAnimationFrame(paso);
    };
    requestAnimationFrame(paso);
  }

  // ---------- Aparición al hacer scroll ----------

  const aparecen = document.querySelectorAll('.portada, .marcador, .tarjeta, .tabla-scroll, .factura, main h2');
  if (!reducido && 'IntersectionObserver' in window) {
    const observador = new IntersectionObserver(entradas => {
      entradas.forEach(entrada => {
        if (!entrada.isIntersecting) return;
        entrada.target.classList.add('visible');
        observador.unobserve(entrada.target);
        entrada.target.querySelectorAll('[data-contar]').forEach(contar);
      });
    }, { threshold: 0.1 });
    aparecen.forEach((el, i) => {
      el.classList.add('revelar');
      el.style.transitionDelay = (i % 6) * 70 + 'ms';
      observador.observe(el);
    });
  }

  // ---------- Tablas como tarjetas en el móvil ----------
  // Cada celda recibe el título de su columna; el CSS lo muestra al lado del dato
  // cuando la pantalla es estrecha. La factura (papel) se queda como tabla.

  document.querySelectorAll('.tabla-scroll > table').forEach(tabla => {
    if (tabla.closest('.factura') || !tabla.tHead) return;
    const titulos = [...tabla.tHead.rows[0].cells].map(celda => celda.textContent.trim());
    [...tabla.tBodies].forEach(cuerpo => [...cuerpo.rows].forEach(fila => {
      [...fila.cells].forEach((celda, i) => { celda.dataset.etiqueta = titulos[i] || ''; });
    }));
    tabla.classList.add('tarjetas-movil');
    tabla.parentElement.classList.add('con-tarjetas');
  });

  // ---------- Filas de tabla que se pueden pulsar ----------

  document.querySelectorAll('tr[data-href]').forEach(fila => {
    fila.classList.add('fila-enlace');
    fila.addEventListener('click', ev => {
      if (ev.target.closest('a, button, input, select, textarea, form')) return;
      cargando();
      location.href = fila.dataset.href;
    });
  });

  // ---------- Filtro instantáneo mientras se escribe ----------

  const normalizar = texto => texto.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

  document.querySelectorAll('[data-filtrar]').forEach(campo => {
    const destino = document.querySelector(campo.dataset.filtrar);
    if (!destino) return;
    const elementos = [...(destino.tBodies ? destino.tBodies[0].rows : destino.children)];
    const textos = elementos.map(el => {
      const texto = normalizar(el.textContent);
      return texto + ' ' + texto.replace(/\s+/g, '');      // «1234 ABC» también se encuentra como «1234abc»
    });

    const nada = document.createElement('p');
    nada.className = 'vacio';
    nada.hidden = true;
    nada.textContent = 'Nada coincide con lo que escribes. Pulsa «Buscar» para buscar en todo.';
    (destino.closest('.tabla-scroll') || destino).after(nada);

    campo.addEventListener('input', () => {
      const busqueda = normalizar(campo.value.trim());
      let visibles = 0;
      elementos.forEach((el, i) => {
        const coincide = !busqueda || textos[i].includes(busqueda);
        el.hidden = !coincide;
        if (coincide) visibles++;
      });
      nada.hidden = visibles > 0;
    });
  });

  // Tecla «/» para ir al buscador
  document.addEventListener('keydown', ev => {
    if (ev.key !== '/' || ev.target.closest('input, textarea, select')) return;
    const buscador = document.querySelector('input[type="search"]');
    if (buscador) { ev.preventDefault(); buscador.focus(); buscador.select(); }
  });

  // ---------- Contador de caracteres en los textarea ----------

  document.querySelectorAll('textarea[maxlength]').forEach(area => {
    const contador = document.createElement('small');
    contador.className = 'contador';
    area.after(contador);
    const maximo = area.maxLength;
    const actualizar = () => {
      contador.textContent = area.value.length + ' / ' + maximo;
      contador.classList.toggle('casi', area.value.length > maximo * 0.9);
    };
    area.addEventListener('input', actualizar);
    actualizar();
  });

  // ---------- Mano de obra: botones de horas y cálculo en directo ----------

  document.querySelectorAll('form[data-mano-obra]').forEach(formulario => {
    const horas = formulario.querySelector('[name="horas"]');
    const precio = formulario.querySelector('[name="precio_hora"]');
    const resultado = formulario.querySelector('output');
    const formato = new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR', useGrouping: 'always' });
    const numero = campo => parseFloat(String(campo.value).replace(',', '.')) || 0;

    const recalcular = () => {
      resultado.textContent = '= ' + formato.format(Math.round(numero(horas) * numero(precio) * 100) / 100);
      resultado.classList.remove('latido');
      void resultado.offsetWidth;          // reinicia la animación
      resultado.classList.add('latido');
    };

    formulario.querySelectorAll('[data-horas]').forEach(boton => {
      boton.addEventListener('click', () => {
        const nuevo = Math.min(999, Math.max(0, numero(horas) + parseFloat(boton.dataset.horas)));
        horas.value = String(Math.round(nuevo * 100) / 100);
        recalcular();
      });
    });
    horas.addEventListener('input', recalcular);
    precio.addEventListener('input', recalcular);
  });

  // ---------- Alta de usuario: el cliente solo se pide para el rol «usuario» ----------

  document.querySelectorAll('[data-solo-rol]').forEach(bloque => {
    const formulario = bloque.closest('form');
    const desplegable = bloque.querySelector('select');
    const actualizar = () => {
      const rol = formulario.querySelector('[name="rol"]:checked');
      const visible = rol && rol.value === bloque.dataset.soloRol;
      bloque.hidden = !visible;
      // data-opcional: quien se registró solo puede quedarse sin ficha de momento
      if (desplegable) desplegable.required = visible && !bloque.hasAttribute('data-opcional');
    };
    formulario.querySelectorAll('[name="rol"]').forEach(radio => radio.addEventListener('change', actualizar));
    actualizar();
  });

  // ---------- Ventana de confirmación con estilo ----------

  let dialogo = null;
  function confirmar(texto, textoAceptar) {
    if (!dialogo) {
      dialogo = document.createElement('dialog');
      dialogo.className = 'dialogo';
      dialogo.innerHTML =
        '<div class="dialogo-icono" aria-hidden="true">🏁</div>' +
        '<p class="dialogo-texto"></p>' +
        '<div class="dialogo-botones">' +
        '<button type="button" class="boton-secundario" value="no">Cancelar</button>' +
        '<button type="button" class="dialogo-aceptar" value="si"></button>' +
        '</div>';
      document.body.appendChild(dialogo);
    }
    dialogo.querySelector('.dialogo-texto').textContent = texto;
    dialogo.querySelector('.dialogo-aceptar').textContent = textoAceptar || 'Aceptar';

    return new Promise(resolver => {
      const terminar = respuesta => { dialogo.close(); resolver(respuesta); };
      dialogo.querySelectorAll('button').forEach(boton => { boton.onclick = () => terminar(boton.value === 'si'); });
      dialogo.oncancel = ev => { ev.preventDefault(); terminar(false); };
      dialogo.onclick = ev => { if (ev.target === dialogo) terminar(false); };   // clic fuera
      dialogo.showModal();
      dialogo.querySelector('.dialogo-aceptar').focus();
    });
  }

  document.addEventListener('submit', ev => {
    const formulario = ev.target;
    const boton = ev.submitter;
    const texto = (boton && boton.dataset.confirmar) || formulario.dataset.confirmar;

    if (texto && formulario.dataset.confirmado !== '1') {
      ev.preventDefault();
      const enviar = () => {
        formulario.dataset.confirmado = '1';
        if (boton) formulario.requestSubmit(boton); else formulario.requestSubmit();
      };
      if (typeof HTMLDialogElement === 'undefined') {
        if (window.confirm(texto)) enviar();
        return;
      }
      confirmar(texto, (boton && boton.dataset.aceptar) || formulario.dataset.aceptar)
        .then(aceptado => { if (aceptado) enviar(); });
      return;
    }

    formulario.dataset.confirmado = '';
    cargando();
  });

  // ---------- Imprimir la factura ----------

  document.querySelectorAll('[data-imprimir]').forEach(boton => {
    boton.addEventListener('click', () => window.print());
  });
  const autoImprimir = document.querySelector('[data-auto-imprimir]');
  if (autoImprimir) {
    const listo = document.fonts ? document.fonts.ready : Promise.resolve();
    listo.then(() => setTimeout(() => window.print(), 500));
  }

  // ---------- Onda al pulsar los botones ----------

  if (!reducido) {
    document.addEventListener('pointerdown', ev => {
      const boton = ev.target.closest('button, .boton');
      if (!boton || boton.closest('.guia-personaje')) return;
      const caja = boton.getBoundingClientRect();
      const onda = document.createElement('span');
      onda.className = 'onda';
      onda.style.left = ev.clientX - caja.left + 'px';
      onda.style.top = ev.clientY - caja.top + 'px';
      boton.appendChild(onda);
      setTimeout(() => onda.remove(), 650);
    });
  }

  // ---------- Resplandor de neón que sigue al ratón ----------

  if (!reducido && window.matchMedia('(pointer: fine)').matches) {
    const luz = document.createElement('div');
    luz.className = 'resplandor';
    document.body.appendChild(luz);
    let x = 0, y = 0, pendiente = false;
    document.addEventListener('pointermove', ev => {
      x = ev.clientX; y = ev.clientY;
      if (pendiente) return;
      pendiente = true;
      requestAnimationFrame(() => {
        luz.style.transform = `translate(${x}px, ${y}px)`;
        pendiente = false;
      });
    });
  }

  // ---------- Turbo, el mecánico guía ----------

  const CONSEJOS = {
    listado: [
      { t: '¡Hola! Soy Turbo, tu mecánico de confianza. Te enseño el taller en un periquete.' },
      { t: 'Estos marcadores te dicen cuántas reparaciones hay y cuántos coches siguen en boxes. Púlsalos para filtrar.', s: '.marcadores' },
      { t: 'Escribe una matrícula, un cliente o una avería: la tabla se filtra mientras escribes. Pulsa «/» para venir aquí rápido.', s: '.buscador' },
      { t: 'Pulsa cualquier fila para abrir la ficha de esa reparación.', s: '#tabla-reparaciones' },
      { t: 'En el menú tienes los clientes, las facturas y la sala de espera con un juego de carreras. ¡A rodar!', s: '.menu' },
    ],
    ver: [
      { t: 'Esta es la ficha de la reparación: el coche, el cliente y la avería.', s: '.ficha-rejilla .tarjeta' },
      { t: 'Apunta aquí las horas de trabajo con los botones +½ y +1 h (o quítalas con −) y el precio por hora. El importe se calcula solo.', s: '#mano-obra' },
      { t: 'Las piezas montadas. Si te equivocas, quita una unidad con «−1» o la pieza entera con «Quitar».', s: 'main .tabla-scroll' },
      { t: 'Elige aquí la pieza y las unidades que necesita el coche, y pulsa «Añadir pieza».', s: 'form.linea' },
      { t: 'Cuando el cliente pague, pulsa el botón verde: se emite la factura, se entrega el coche y se abre para imprimir.', s: '.cuentas' },
    ],
    nueva: [
      { t: 'Elige el vehículo y el mecánico, describe la avería y pon la mano de obra.', s: 'form.alta' },
      { t: 'Al guardar te llevo a la ficha para que añadas las piezas. ¡Motores en marcha!' },
    ],
    clientes: [
      { t: 'Aquí tienes a todos los clientes, con sus coches y sus reparaciones.', s: '#rejilla-clientes' },
      { t: 'Busca por nombre, teléfono o matrícula.', s: '.buscador' },
    ],
    cliente: [
      { t: 'La ficha del cliente: cuántos coches tiene, cuántos están en el taller y lo facturado.', s: '.marcadores' },
      { t: 'Cada coche con su historial. Con «Nueva reparación» lo metes en boxes con el coche ya elegido.', s: '.vehiculo' },
    ],
    facturas: [
      { t: 'Todas las facturas emitidas. El botón 🖨 la abre lista para imprimir o guardar en PDF.', s: '#tabla-facturas' },
      { t: 'Aquí abajo están las reparaciones entregadas que todavía no tienen factura.', s: '#pendientes' },
    ],
    factura: [
      { t: 'Pulsa «Imprimir» para sacarla en papel o guardarla como PDF. El menú y los botones no salen en la impresión.', s: '.acciones' },
    ],
    ventas: [
      { t: 'Así va el negocio: lo vendido esta semana, este mes y este año, comparado con el periodo anterior.', s: '.marcadores' },
      { t: 'Con estos botones eliges el periodo de los gráficos: semana, mes, año o todo.', s: '.filtros' },
      { t: 'Pasa el ratón (o el dedo) por los gráficos para ver el detalle. Debajo de cada uno tienes los datos en una tabla.', s: '.graficos' },
    ],
    login: [
      { t: '¡Bienvenido a boxes! Entra con tu email y tu contraseña. Después te mandaré un código al email: así sabemos que eres tú.', s: '.acceso-caja' },
    ],
    registro: [
      { t: '¡Bienvenido! Rellena tus datos y elige una contraseña. Te mandaremos un email para confirmar que es tuyo.', s: '.acceso-caja' },
      { t: 'Para ver tus facturas, el taller vinculará tu cuenta con tu ficha de cliente cuando enseñes tu DNI en el mostrador.' },
    ],
    cambiar_password: [
      { t: 'Escribe tu contraseña actual y elige una nueva. Las sesiones que tengas abiertas en otros sitios se cerrarán.', s: '.acceso-caja' },
    ],
    codigo: [
      { t: 'Mira tu email: te he enviado un código de 6 cifras. Escríbelo aquí antes de 10 minutos.', s: '.campo-codigo' },
    ],
    mis_facturas: [
      { t: 'Aquí tienes todas tus facturas. Pulsa una para verla o 🖨 para imprimirla o guardarla en PDF.' },
    ],
    mi_cuenta: [
      { t: 'Tus datos y tus coches. Si algo no está bien, avisa al taller.' },
    ],
    usuarios: [
      { t: 'El registro de usuarios: los que están de alta, los de baja (con fecha y motivo) y todos los accesos.', s: '.filtros' },
      { t: 'Para dar de baja a alguien escribe el motivo y pulsa «Dar de baja». Sus datos no se borran y se le puede volver a dar de alta.' },
    ],
    usuario_nuevo: [
      { t: 'Rellena los datos. El usuario recibirá un email para verificar su dirección y elegir su contraseña.', s: 'form.alta' },
      { t: 'Si es un cliente, elige su ficha: solo verá sus facturas y sus datos.', s: '.roles' },
    ],
    sala: [
      { t: '¿Esperando tu coche? ¡Échate una carrera! Usa las flechas o toca la pista. Ojo con el tráfico.', s: '.pista' },
    ],
    perdida: [
      { t: '¡Uy! Te has salido de la pista. Pulsa el botón y te devuelvo a boxes.' },
    ],
  };
  const ALIAS = { guardar: 'nueva', anadir_pieza: 'ver', quitar_pieza: 'ver', mano_obra: 'ver', facturar: 'factura',
                  mi_factura: 'factura', usuario_guardar: 'usuario_nuevo', usuario_editar: 'usuario_nuevo', usuario_actualizar: 'usuario_nuevo', reenviar_codigo: 'codigo', recuperar: 'login' };

  const guia = document.getElementById('guia');
  if (guia) {
    const pagina = ALIAS[document.body.dataset.pagina] || document.body.dataset.pagina || 'listado';
    const consejos = (CONSEJOS[pagina] || CONSEJOS.listado).filter(c => !c.s || document.querySelector(c.s));
    const textoEl = guia.querySelector('.guia-texto');
    const pasoEl = guia.querySelector('.guia-paso');
    const siguienteEl = guia.querySelector('[data-guia="siguiente"]');
    const anteriorEl = guia.querySelector('[data-guia="anterior"]');
    let paso = 0, foco = null, escribiendo = null;

    guia.hidden = false;

    const quitarFoco = () => { if (foco) foco.classList.remove('guia-foco'); foco = null; };

    function escribir(texto) {
      clearInterval(escribiendo);
      guia.classList.add('hablando');
      if (reducido) {
        textoEl.textContent = texto;
        guia.classList.remove('hablando');
        return;
      }
      let i = 0;
      textoEl.textContent = '';
      escribiendo = setInterval(() => {
        textoEl.textContent = texto.slice(0, ++i);
        if (i >= texto.length) {
          clearInterval(escribiendo);
          guia.classList.remove('hablando');
        }
      }, 20);
    }

    function mostrarPaso(n, desplazar) {
      paso = (n + consejos.length) % consejos.length;
      const consejo = consejos[paso];
      pasoEl.textContent = (paso + 1) + ' / ' + consejos.length;
      anteriorEl.hidden = paso === 0;
      siguienteEl.textContent = paso === consejos.length - 1 ? '¡Entendido!' : 'Siguiente';

      quitarFoco();
      foco = consejo.s ? document.querySelector(consejo.s) : null;
      if (foco) {
        foco.classList.add('guia-foco');
        if (desplazar) foco.scrollIntoView({ behavior: reducido ? 'auto' : 'smooth', block: 'center' });
      }
      escribir(consejo.t);
    }

    function abrir(desplazar) {
      guia.classList.add('abierta');
      mostrarPaso(paso, desplazar);
      guardar('guia-vista-' + pagina, '1');
    }

    function cerrar() {
      guia.classList.remove('abierta', 'hablando');
      clearInterval(escribiendo);
      quitarFoco();
    }

    guia.addEventListener('click', ev => {
      const boton = ev.target.closest('[data-guia]');
      if (!boton) return;
      const accion = boton.dataset.guia;
      if (accion === 'abrir') {
        if (guia.classList.contains('abierta')) cerrar(); else abrir(true);
      } else if (accion === 'siguiente') {
        if (paso === consejos.length - 1) { cerrar(); paso = 0; } else mostrarPaso(paso + 1, true);
      } else if (accion === 'anterior') {
        mostrarPaso(paso - 1, true);
      } else if (accion === 'cerrar') {
        cerrar();
      }
    });

    document.addEventListener('keydown', ev => {
      if (ev.key === 'Escape' && guia.classList.contains('abierta') && !document.querySelector('dialog[open]')) cerrar();
    });

    // La primera vez que se visita cada página, Turbo se presenta solo
    if (!leer('guia-vista-' + pagina) && !autoImprimir) {
      setTimeout(() => abrir(false), 900);
    }
  }
})();
