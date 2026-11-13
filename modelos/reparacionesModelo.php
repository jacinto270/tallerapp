<?php
// Modelo: todo el SQL de las reparaciones está aquí, y solo aquí
class ReparacionModelo
{
    public function __construct(private PDO $pdo) {}

    // Todas las reparaciones, de la más reciente a la más antigua
    public function todas(): array
    {
        $sql ="SELECT reparaciones.id, reparaciones.fecha_entrada, reparaciones.fecha_salida, reparaciones.descripcion, vehiculo.matricula, vehiculo.marca, vehiculo.modelo, mecanico.nombre as mecanico
                FROM reparaciones, vehiculo, mecanico 
                WHERE reparaciones.id_vehiculo = vehiculo.id AND reparaciones.id_mecanico = mecanico.id 
                ORDER BY reparaciones.fecha_entrada DESC";

        $consulta = $this->pdo->query($sql);
        return $consulta->fetchAll();
    }
}