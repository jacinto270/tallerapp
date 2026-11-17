<?php
// Funciones de ayuda para las vistas

// Escapa un texto antes de escribirlo en el HTML
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '');
}

// Campo oculto con el token anti-CSRF: va en TODOS los formularios POST
function csrf(): string
{
    return Seguridad::campoCsrf();
}

// 2026-11-03  →  03/11/2026
function fecha(string $iso): string
{
    return date('d/m/Y', strtotime($iso));
}

// 145  →  145,00 €
function euros($cantidad): string
{
    return number_format((float) $cantidad, 2, ',', '.') . ' €';
}

// 2.5  →  «2,5 h»;  3  →  «3 h»
function horas($horas): string
{
    return rtrim(rtrim(number_format((float) $horas, 2, ',', '.'), '0'), ',') . ' h';
}

// Chapa de estado: «En el taller» o «Entregado el 21/10/2026»
function estado(?string $fechaSalida): string
{
    if ($fechaSalida === null) {
        return '<strong class="chapa chapa-taller">En el taller</strong>';
    }
    return '<span class="chapa chapa-entregado">Entregado el ' . fecha($fechaSalida) . '</span>';
}

// Matrícula europea: franja azul con las 12 estrellas y la «E»,
// y el número con el formato español «1234 ABC»
function matricula(string $matricula): string
{
    static $estrellas = null;
    if ($estrellas === null) {
        // 12 estrellas amarillas en círculo, como en la bandera europea
        $estrellas = '';
        for ($i = 0; $i < 12; $i++) {
            $angulo = deg2rad($i * 30);
            $x = round(10 + 6.2 * sin($angulo), 2);
            $y = round(10 - 6.2 * cos($angulo), 2);
            $estrellas .= "<use href=\"#estrella-ue\" x=\"$x\" y=\"$y\"/>";
        }
    }

    $texto = strtoupper(trim($matricula));
    $texto = preg_replace('/^(\d{4})\s*-?\s*([A-Z]{3})$/', '$1 $2', $texto);

    return '<span class="matricula" title="Matrícula ' . e($texto) . '">'
         . '<span class="matricula-ue"><svg viewBox="0 0 20 20" aria-hidden="true" focusable="false">'
         . '<defs><path id="estrella-ue" d="M0-1.4.33-.45H1.33L.52.15.82 1.1 0 .52-.82 1.1-.52.15-1.33-.45H-.33Z" fill="#ffd400"/></defs>'
         . $estrellas . '</svg><b>E</b></span>'
         . '<span class="matricula-numero">' . e($texto) . '</span></span>';
}

// Iconos de taller: llave, rueda, engranaje, bandera, factura, cliente
function icono(string $nombre, string $clase = ''): string
{
    $dibujos = [
        'llave' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'rueda' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="10" stroke-dasharray="2 2.2" stroke-width="3" opacity=".55"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/><path d="M12 7v3.5M12 13.5V17M7 12h3.5M13.5 12H17"/>',
        'engranaje' => '<circle cx="12" cy="12" r="7.5" stroke-dasharray="3 2.9" stroke-width="4"/><circle cx="12" cy="12" r="5.5"/><circle cx="12" cy="12" r="2"/>',
        'bandera' => '<path d="M4 22V3"/><path d="M4 4h16v10H4z"/><path d="M4 4h4v5H4zM12 4h4v5h-4zM8 9h4v5H8zM16 9h4v5h-4z" fill="currentColor" stroke="none"/>',
        'factura' => '<path d="M6 2h9l4 4v16l-2.5-1.5L14 22l-2-1.5L10 22l-2.5-1.5L5 22V3a1 1 0 0 1 1-1z"/><path d="M9 8h6M9 12h6M9 16h4"/>',
        'cliente' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    ];

    return '<svg class="icono ' . e($clase) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
         . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
         . ($dibujos[$nombre] ?? '') . '</svg>';
}

// Turbo, el mecánico que guía por la aplicación
function mecanicoSvg(): string
{
    return <<<SVG
    <svg class="mecanico-svg" viewBox="0 0 120 170" aria-hidden="true" focusable="false">
      <ellipse cx="60" cy="164" rx="34" ry="5" fill="rgba(0,240,255,.3)"/>
      <rect x="42" y="118" width="14" height="40" rx="5" fill="#1d3fbb"/>
      <rect x="64" y="118" width="14" height="40" rx="5" fill="#1d3fbb"/>
      <rect x="37" y="152" width="21" height="9" rx="4" fill="#111"/>
      <rect x="62" y="152" width="21" height="9" rx="4" fill="#111"/>
      <rect x="24" y="78" width="12" height="40" rx="6" fill="#2453e6"/>
      <circle cx="30" cy="120" r="7" fill="#f1c27d"/>
      <rect x="34" y="72" width="52" height="56" rx="14" fill="#2453e6"/>
      <rect x="43" y="88" width="34" height="22" rx="4" fill="#1d3fbb"/>
      <path d="M44 72v17M76 72v17" stroke="#ff8a00" stroke-width="4"/>
      <circle cx="44" cy="90" r="2.5" fill="#ffd400"/>
      <circle cx="76" cy="90" r="2.5" fill="#ffd400"/>
      <text x="60" y="103" text-anchor="middle" font-family="Orbitron, sans-serif" font-size="8.5" font-weight="900" fill="#00f0ff">TURBO</text>
      <path d="M40 122h40" stroke="#ff2e88" stroke-width="3"/>
      <g class="mecanico-brazo">
        <rect x="80" y="46" width="12" height="40" rx="6" fill="#2453e6"/>
        <circle cx="86" cy="44" r="7" fill="#f1c27d"/>
        <g transform="translate(88 30) rotate(25)">
          <rect x="-2.5" y="-6" width="5" height="22" rx="2" fill="#c0c8d8"/>
          <path d="M-7-8a7 7 0 0 1 14 0l-4 2v-4h-6v4z" fill="#c0c8d8"/>
        </g>
      </g>
      <g class="mecanico-cabeza">
        <circle cx="38" cy="50" r="5" fill="#e0a96d"/>
        <circle cx="82" cy="50" r="5" fill="#e0a96d"/>
        <circle cx="60" cy="48" r="22" fill="#f1c27d"/>
        <circle cx="46" cy="56" r="3.5" fill="#ff8a8a" opacity=".5"/>
        <circle cx="74" cy="56" r="3.5" fill="#ff8a8a" opacity=".5"/>
        <g class="mecanico-ojos">
          <ellipse cx="52" cy="47" rx="3" ry="3.6" fill="#1b1b2f"/>
          <ellipse cx="68" cy="47" rx="3" ry="3.6" fill="#1b1b2f"/>
          <circle cx="53" cy="46" r="1" fill="#fff"/>
          <circle cx="69" cy="46" r="1" fill="#fff"/>
        </g>
        <path d="M50 56q10 6 20 0" stroke="#5a3a1a" stroke-width="4.5" fill="none" stroke-linecap="round"/>
        <path class="mecanico-boca" d="M54 62q6 4 12 0" stroke="#8a2b2b" stroke-width="2" fill="none" stroke-linecap="round"/>
        <path d="M37 40q1-21 23-21t23 21z" fill="#ff2e88"/>
        <path d="M58 38h30q4 0 1 5H58z" fill="#c4005a"/>
        <path d="M38 38h44" stroke="#ffd400" stroke-width="2"/>
        <circle cx="60" cy="29" r="4" fill="#00f0ff"/>
      </g>
    </svg>
    SVG;
}

// «Lucía Martín»  →  «LM»
function iniciales(string $nombre, string $apellidos): string
{
    return mb_strtoupper(mb_substr($nombre, 0, 1) . mb_substr($apellidos, 0, 1));
}

// 1 vehículo / 3 vehículos
function plural(int $n, string $singular, string $plural): string
{
    return $n . ' ' . ($n === 1 ? $singular : $plural);
}

// Dibujo del coche de carreras (logo y portada)
function cocheSvg(string $clase = '', string $viewBox = '0 0 340 120'): string
{
    return <<<SVG
    <svg class="$clase" viewBox="$viewBox" aria-hidden="true" focusable="false">
      <defs>
        <linearGradient id="carroceria" x1="0" x2="1">
          <stop offset="0" stop-color="#ff2e88"/><stop offset="1" stop-color="#ff8a00"/>
        </linearGradient>
      </defs>
      <g stroke-linecap="round" stroke-linejoin="round">
        <path d="M10 44h50M0 62h46M18 80h36" stroke="#00f0ff" stroke-width="3" opacity=".7"/>
        <path d="M84 52 74 40h38l2 10" fill="#ff2e88" stroke="#fff" stroke-width="1.5"/>
        <path d="M80 86 96 62q14-12 44-14l50-2q24-16 56-16l24 2q26 14 48 30l12 8q4 12-6 16Z" fill="url(#carroceria)" stroke="#fff" stroke-width="2"/>
        <path d="M204 50q16-12 40-12l18 2q14 6 22 14Z" fill="#0b1030" stroke="#00f0ff" stroke-width="2"/>
        <path d="M100 72h200" stroke="#fff" stroke-width="3" opacity=".55"/>
        <circle cx="124" cy="88" r="17" fill="#06060d" stroke="#00f0ff" stroke-width="4"/>
        <circle cx="290" cy="88" r="17" fill="#06060d" stroke="#00f0ff" stroke-width="4"/>
        <circle cx="124" cy="88" r="5" fill="#00f0ff"/>
        <circle cx="290" cy="88" r="5" fill="#00f0ff"/>
      </g>
    </svg>
    SVG;
}
