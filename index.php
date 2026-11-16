<?php
// Controlador frontal: todas las peticiones de la aplicación entran por aquí
require __DIR__ . '/config/conexion.php';
require __DIR__ . '/vistas/ayudas.php';
require __DIR__ . '/modelos/ReparacionesModelo.php';
require __DIR__ . '/controladores/ReparacionesControlador.php';


$pdo = conectar();
$controlador = new ReparacionesControlador(
    new ReparacionModelo($pdo)
);

$accion = $_GET['accion'] ?? "";

match ($accion) {
    'listado' => $controlador->listado(),
    'ver'     => $controlador->ver(),
    default   => $controlador->noEncontrada(),
};
