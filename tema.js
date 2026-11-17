// Aplica el tema (claro u oscuro) ANTES de pintar la página, para que no parpadee.
// Se carga en el <head> sin «defer». El botón que lo cambia está en app.js.
//   'auto'   → el del sistema (y cambia si cambia el sistema)
//   'claro' / 'oscuro' → el que eligió el usuario (se recuerda en este navegador)
(() => {
  'use strict';
  let elegido = 'auto';
  try { elegido = localStorage.getItem('tema') || 'auto'; } catch (e) { /* sin almacenamiento */ }
  if (!['auto', 'claro', 'oscuro'].includes(elegido)) elegido = 'auto';

  const sistemaClaro = window.matchMedia('(prefers-color-scheme: light)');
  const raiz = document.documentElement;

  function aplicar() {
    raiz.dataset.tema = elegido === 'auto' ? (sistemaClaro.matches ? 'claro' : 'oscuro') : elegido;
    raiz.dataset.temaElegido = elegido;
  }

  aplicar();
  sistemaClaro.addEventListener('change', () => { if (elegido === 'auto') aplicar(); });

  window.TemaTaller = {
    elegido: () => elegido,
    elegir(nuevo) {
      elegido = nuevo;
      try { localStorage.setItem('tema', nuevo); } catch (e) { /* sin almacenamiento */ }
      aplicar();
    },
  };
})();
