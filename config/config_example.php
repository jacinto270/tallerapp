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
// En producción se redirige siempre a HTTPS. Si el hosting ya usa HTTPS pero sale el error
// «demasiadas redirecciones», pon false (pasa en algunos hostings gratuitos)
$forzarHttps = true;
// Clave secreta: genera una con  php -r "echo bin2hex(random_bytes(32));"
$claveSecreta = '';

// ---------- Correo ----------
// 'ninguno': sin correo. Se entra solo con contraseña; las contraseñas iniciales y olvidadas
//            las pone el admin (temporales, que el usuario cambia al entrar)
// 'archivo': los correos se guardan en almacen/correos (solo para desarrollo)
// 'smtp': se envían de verdad (activa el segundo paso, los enlaces y los avisos por email)
$correoModo = 'ninguno';
// Segundo paso al entrar (código por email). Solo funciona si hay correo ('smtp')
$dosPasos = false;
$correoRemitente = '';
$correoNombre = 'Taller Genil';
$smtpServidor = '';
$smtpPuerto = 587;
$smtpUsuario = '';
$smtpPassword = '';
