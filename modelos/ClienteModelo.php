<?php
// Modelo: todo el SQL de los clientes y sus vehículos
class ClienteModelo
{
    public function __construct(private PDO $pdo) {}

    // Todos los clientes con cuántos vehículos y reparaciones tienen.
    // $busqueda filtra por nombre, teléfono o matrícula de alguno de sus coches
    public function todos(string $busqueda = ''): array
    {
        $sql = "SELECT cliente.id, cliente.nombre, cliente.apellidos, cliente.telefono,
                       COUNT(DISTINCT vehiculo.id) AS vehiculos,
                       COUNT(reparaciones.id) AS reparaciones,
                       COALESCE(SUM(reparaciones.id IS NOT NULL AND reparaciones.fecha_salida IS NULL), 0) AS en_taller
                FROM cliente
                LEFT JOIN vehiculo ON vehiculo.id_cliente = cliente.id
                LEFT JOIN reparaciones ON reparaciones.id_vehiculo = vehiculo.id";
        $parametros = [];

        if ($busqueda !== '') {
            $sql .= " WHERE CONCAT(cliente.nombre, ' ', cliente.apellidos) LIKE ?
                         OR cliente.telefono LIKE ?
                         OR EXISTS (SELECT 1 FROM vehiculo v2
                                    WHERE v2.id_cliente = cliente.id AND v2.matricula LIKE ?)";
            $parametros = array_fill(0, 3, '%' . addcslashes($busqueda, '%_\\') . '%');
        }
        $sql .= " GROUP BY cliente.id, cliente.nombre, cliente.apellidos, cliente.telefono
                  ORDER BY cliente.apellidos, cliente.nombre";

        $consulta = $this->pdo->prepare($sql);
        $consulta->execute($parametros);
        return $consulta->fetchAll();
    }

    // Ficha de cliente nueva (al dar de alta a un cliente que aún no estaba en el taller)
    public function crear(string $nombre, string $apellidos, string $telefono): int
    {
        $insertar = $this->pdo->prepare("INSERT INTO cliente (nombre, apellidos, telefono) VALUES (?, ?, ?)");
        $insertar->execute([$nombre, $apellidos, $telefono]);
        return (int) $this->pdo->lastInsertId();
    }

    // Un cliente; null si no existe
    public function buscar(int $id): ?array
    {
        $consulta = $this->pdo->prepare(
            "SELECT id, nombre, apellidos, telefono FROM cliente WHERE id = ?");
        $consulta->execute([$id]);
        return $consulta->fetch() ?: null;
    }

    // Los vehículos de un cliente
    public function vehiculos(int $clienteId): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT id, matricula, marca, modelo, anio_fabricacion
             FROM vehiculo
             WHERE id_cliente = ?
             ORDER BY marca, modelo");
        $consulta->execute([$clienteId]);
        return $consulta->fetchAll();
    }

    // Todas las reparaciones de los vehículos de un cliente, con su total sin IVA
    // (mano de obra más las piezas), de la más reciente a la más antigua
    public function reparaciones(int $clienteId): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT reparaciones.id, reparaciones.id_vehiculo, reparaciones.fecha_entrada,
                    reparaciones.fecha_salida, reparaciones.descripcion, mecanico.nombre AS mecanico,
                    reparaciones.mano_de_obra + COALESCE((
                        SELECT SUM(piezas_reparaciones.unidades * piezas.precio)
                        FROM piezas_reparaciones
                        JOIN piezas ON piezas.id = piezas_reparaciones.id_piezas
                        WHERE piezas_reparaciones.id_reparaciones = reparaciones.id
                    ), 0) AS total
             FROM reparaciones
             JOIN vehiculo ON vehiculo.id = reparaciones.id_vehiculo
             JOIN mecanico ON mecanico.id = reparaciones.id_mecanico
             WHERE vehiculo.id_cliente = ?
             ORDER BY reparaciones.fecha_entrada DESC");
        $consulta->execute([$clienteId]);
        return $consulta->fetchAll();
    }
}
