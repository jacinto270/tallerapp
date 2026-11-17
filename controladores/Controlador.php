<?php
// Clase base de los controladores: lo que comparten todos.
// No contiene SQL ni HTML.
abstract class Controlador
{
    // IVA que se aplica en las facturas
    protected const IVA = 0.21;

    // Precio por hora que se propone para la mano de obra (se puede cambiar en cada reparación)
    protected const PRECIO_HORA = 45.00;

    // Cualquier acción o elemento que no exista
    public function noEncontrada(): void
    {
        // TODO 2: responde con el código 404 y muestra la vista «no-encontrada»
        //         con el título «Página no encontrada»
        http_response_code(404);
        $this->mostrar('no-encontrada', [
            'titulo' => "Página no encontrada",
        ]);
    }

    // Sin permiso para esa página (o el formulario ha caducado)
    public function prohibido(string $mensaje): void
    {
        http_response_code(403);
        $this->mostrar('prohibido', [
            'titulo' => 'Acceso denegado',
            'mensaje' => $mensaje,
        ]);
    }

    // Carga una vista. extract() convierte ['titulo' => 'Reparaciones']
    // en la variable $titulo, y así con cada dato
    protected function mostrar(string $vista, array $datos): void
    {
        extract($datos);
        require __DIR__ . '/../vistas/' . $vista . '.php';
    }

    protected function redirigir(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}
