<?php

$servidor = "";
$nombreBase = "";
$usuario = "";  
$password = "";

// ---------- Aplicación y seguridad ----------
// 'desarrollo' en tu ordenador; 'produccion' al publicar (oculta errores y exige HTTPS)
$entorno = 'desarrollo';
// Dirección pública de la aplicación, terminada en / (se usa en los enlaces de los correos)
$urlBase = 'http://localhost/tallerapp/';
// Clave secreta: genera una con  php -r "echo bin2hex(random_bytes(32));"
$claveSecreta = '';

// ---------- Correo ----------
// 'archivo': los correos se guardan en almacen/correos (solo para desarrollo)
// 'smtp': se envían de verdad (obligatorio en producción)
$correoModo = 'archivo';
$correoRemitente = '';
$correoNombre = 'Taller Genil';
$smtpServidor = '';
$smtpPuerto = 587;
$smtpUsuario = '';
$smtpPassword = '';
