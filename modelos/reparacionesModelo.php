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
        // Una reparación con su vehículo, su cliente y su mecánico; null si no existe
    public function buscar(int $id): ?array
    {
        // TODO 1: consulta preparada que una reparaciones, vehiculos, clientes y mecanicos.
        //         Columnas: id, fecha_entrada, fecha_salida, descripcion, mano_obra,
        //         matricula, marca, modelo, telefono y, con alias,
        //         cliente_nombre, cliente_apellidos y mecanico.
        //         Devuelve la fila, o null si no hay ninguna
        $consulta = $this->pdo->prepare("SELECT reparaciones.id, reparaciones.fecha_entrada, reparaciones.fecha_salida, 
                                          reparaciones.descripcion, reparaciones.mano_de_obra,
                                          vehiculo.matricula, vehiculo.marca, vehiculo.modelo,
                                          cliente.nombre as cliente_nombre, cliente.apellidos as cliente_apellidos, cliente.telefono,
                                          mecanico.nombre as mecanico
                                        FROM reparaciones
                                        JOIN vehiculo ON vehiculo.id = reparaciones.id_vehiculo
                                        JOIN cliente on cliente.id = vehiculo.id_cliente
                                        JOIN mecanico on mecanico.id = reparaciones.id_mecanico
                                        WHERE reparaciones.id = ?");
        $consulta->execute([$id]);
        $fila = $consulta->fetch();
        return $fila ?: null;
    }

    // Las piezas usadas en una reparación, con el importe de cada línea
    public function piezas(int $id): array
    {
        // TODO 2: consulta preparada que una reparacion_piezas y piezas.
        //         Columnas: referencia, nombre, cantidad, precio
        //         e importe (cantidad por precio), ordenadas por nombre
        $consulta = $this->pdo->prepare(

                    "SELECT piezas.numero_referencia AS referencia, piezas.nombre_pieza AS nombre,
                            piezas_reparaciones.unidades, piezas.precio,
                            (piezas_reparaciones.unidades * piezas.precio) AS importe
                     FROM piezas_reparaciones
                     JOIN piezas ON piezas.id = piezas_reparaciones.id_piezas
                     WHERE piezas_reparaciones.id_reparaciones = ?
                     ORDER BY piezas.nombre_pieza");
        $consulta->execute([$id]);
        return $consulta->fetchAll();   
    }
}