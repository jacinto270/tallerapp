<?php
// Controlador de los clientes: listado, ficha con sus coches y sala de espera.
// No contiene SQL ni HTML.
class ClientesControlador extends Controlador
{
    public function __construct(private ClienteModelo $clientes) {}

    // index.php?accion=clientes  (&q=texto para buscar)
    public function listado(): void
    {
        $busqueda = trim($_GET['q'] ?? '');

        $this->mostrar('clientes', [
            'titulo' => 'Clientes',
            'clientes' => $this->clientes->todos($busqueda),
            'busqueda' => $busqueda,
        ]);
    }

    // index.php?accion=cliente&id=3
    public function ver(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $cliente = $id ? $this->clientes->buscar($id) : null;

        if (!$cliente) {
            $this->noEncontrada();
            return;
        }

        // Reparte las reparaciones entre los vehículos del cliente
        $porVehiculo = [];
        $reparaciones = $this->clientes->reparaciones($id);
        foreach ($reparaciones as $reparacion) {
            $porVehiculo[$reparacion['id_vehiculo']][] = $reparacion;
        }

        $vehiculos = $this->clientes->vehiculos($id);
        foreach ($vehiculos as &$vehiculo) {
            $vehiculo['reparaciones'] = $porVehiculo[$vehiculo['id']] ?? [];
        }
        unset($vehiculo);

        $entregadas = array_filter($reparaciones, fn($r) => $r['fecha_salida'] !== null);

        $this->mostrar('cliente', [
            'titulo' => $cliente['nombre'] . ' ' . $cliente['apellidos'],
            'cliente' => $cliente,
            'vehiculos' => $vehiculos,
            'numReparaciones' => count($reparaciones),
            'enTaller' => count($reparaciones) - count($entregadas),
            'facturado' => array_sum(array_column($entregadas, 'total')),
        ]);
    }

    // index.php?accion=sala
    // Sala de espera con un minijuego para el cliente que espera su coche
    public function salaEspera(): void
    {
        $this->mostrar('sala', [
            'titulo' => 'Sala de espera',
        ]);
    }
}
