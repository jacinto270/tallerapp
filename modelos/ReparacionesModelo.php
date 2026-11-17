<?php
// Modelo: todo el SQL de las reparaciones está aquí, y solo aquí
class ReparacionModelo
{
    public function __construct(private PDO $pdo) {}

    // Las reparaciones, de la más reciente a la más antigua.
    // $busqueda filtra por matrícula, marca, modelo, avería, mecánico o cliente.
    // $estado: '' (todas), 'taller' (sin fecha de salida) o 'entregadas'
    public function todas(string $busqueda = '', string $estado = ''): array
    {
        $sql = "SELECT reparaciones.id, reparaciones.fecha_entrada, reparaciones.fecha_salida, reparaciones.descripcion,
                       vehiculo.matricula, vehiculo.marca, vehiculo.modelo, mecanico.nombre AS mecanico,
                       cliente.id AS cliente_id, cliente.nombre AS cliente_nombre, cliente.apellidos AS cliente_apellidos,
                       facturas.numero AS factura_numero
                FROM reparaciones
                JOIN vehiculo ON reparaciones.id_vehiculo = vehiculo.id
                JOIN mecanico ON reparaciones.id_mecanico = mecanico.id
                JOIN cliente ON vehiculo.id_cliente = cliente.id
                LEFT JOIN facturas ON facturas.id_reparacion = reparaciones.id
                WHERE 1 = 1";
        $parametros = [];

        if ($busqueda !== '') {
            $sql .= " AND (vehiculo.matricula LIKE ? OR vehiculo.marca LIKE ? OR vehiculo.modelo LIKE ?
                           OR reparaciones.descripcion LIKE ? OR mecanico.nombre LIKE ?
                           OR CONCAT(cliente.nombre, ' ', cliente.apellidos) LIKE ?)";
            // addcslashes: que % y _ escritos por el usuario se busquen tal cual
            $parametros = array_fill(0, 6, '%' . addcslashes($busqueda, '%_\\') . '%');
        }
        if ($estado === 'taller') {
            $sql .= " AND reparaciones.fecha_salida IS NULL";
        } elseif ($estado === 'entregadas') {
            $sql .= " AND reparaciones.fecha_salida IS NOT NULL";
        }
        $sql .= " ORDER BY reparaciones.fecha_entrada DESC";

        $consulta = $this->pdo->prepare($sql);
        $consulta->execute($parametros);
        return $consulta->fetchAll();
    }

    // Cuántas reparaciones hay en total, en el taller y entregadas
    public function resumen(): array
    {
        return $this->pdo->query(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(fecha_salida IS NULL), 0) AS en_taller,
                    COALESCE(SUM(fecha_salida IS NOT NULL), 0) AS entregadas
             FROM reparaciones"
        )->fetch();
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
                                          reparaciones.horas, reparaciones.precio_hora,
                                          vehiculo.id AS vehiculo_id, vehiculo.matricula, vehiculo.marca, vehiculo.modelo,
                                          cliente.id AS cliente_id,
                                          cliente.nombre as cliente_nombre, cliente.apellidos as cliente_apellidos, cliente.telefono,
                                          mecanico.nombre as mecanico,
                                          facturas.numero AS factura_numero
                                        FROM reparaciones
                                        JOIN vehiculo ON vehiculo.id = reparaciones.id_vehiculo
                                        JOIN cliente on cliente.id = vehiculo.id_cliente
                                        JOIN mecanico on mecanico.id = reparaciones.id_mecanico
                                        LEFT JOIN facturas ON facturas.id_reparacion = reparaciones.id
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
        //         linea_id identifica la línea para poder quitarla
        $consulta = $this->pdo->prepare(

                    "SELECT piezas_reparaciones.id AS linea_id,
                            piezas.numero_referencia AS referencia, piezas.nombre_pieza AS nombre,
                            piezas_reparaciones.unidades, piezas.precio,
                            (piezas_reparaciones.unidades * piezas.precio) AS importe
                     FROM piezas_reparaciones
                     JOIN piezas ON piezas.id = piezas_reparaciones.id_piezas
                     WHERE piezas_reparaciones.id_reparaciones = ?
                     ORDER BY piezas.nombre_pieza");
        $consulta->execute([$id]);
        return $consulta->fetchAll();
    }
        // Abre una reparación con la fecha de hoy y devuelve su id
    public function crear(int $vehiculoId, int $mecanicoId, string $descripcion, float $manoObra): int
    {
        // TODO 3: INSERT preparado; la fecha de entrada la pone MySQL con CURDATE().
        //         Devuelve el id nuevo con lastInsertId(), convertido a entero
        $insertar = $this->pdo->prepare(
            "INSERT INTO reparaciones (id_vehiculo, id_mecanico, descripcion, mano_de_obra)
             VALUES (?, ?,  ?, ?)");

        $insertar->execute([
                            $vehiculoId,
                            $mecanicoId,
                            $descripcion,
                            $manoObra
                            ]);

        return (int)$this->pdo->lastInsertId();
    }

    // Guarda la mano de obra por horas: horas × precio por hora.
    // Solo mientras el coche sigue en el taller (una reparación facturada no cambia).
    // fecha_entrada = fecha_entrada evita que su ON UPDATE la cambie
    public function guardarManoObra(int $id, float $horas, float $precioHora): bool
    {
        $actualizar = $this->pdo->prepare(
            "UPDATE reparaciones
             SET horas = ?, precio_hora = ?, mano_de_obra = ?, fecha_entrada = fecha_entrada
             WHERE id = ? AND fecha_salida IS NULL");
        $actualizar->execute([$horas, $precioHora, round($horas * $precioHora, 2), $id]);
        return $actualizar->rowCount() === 1;
    }

    // Añade unidades de una pieza a una reparación.
    // Si la pieza ya estaba en la reparación, suma las unidades a la línea existente
    public function anadirPieza(int $reparacionId, int $piezaId, int $unidades): void
    {
        $consulta = $this->pdo->prepare(
            "SELECT id FROM piezas_reparaciones
             WHERE id_reparaciones = ? AND id_piezas = ?");
        $consulta->execute([$reparacionId, $piezaId]);
        $lineaId = $consulta->fetchColumn();

        if ($lineaId) {
            $actualizar = $this->pdo->prepare(
                "UPDATE piezas_reparaciones SET unidades = unidades + ? WHERE id = ?");
            $actualizar->execute([$unidades, $lineaId]);
        } else {
            $insertar = $this->pdo->prepare(
                "INSERT INTO piezas_reparaciones (id_reparaciones, id_piezas, unidades)
                 VALUES (?, ?, ?)");
            $insertar->execute([$reparacionId, $piezaId, $unidades]);
        }
    }

    // Quita piezas de una reparación.
    // Con $soloUna resta una unidad (si solo quedaba una, borra la línea);
    // sin él, borra la línea entera. Devuelve false si la línea no es de esa reparación
    public function quitarPieza(int $reparacionId, int $lineaId, bool $soloUna): bool
    {
        if ($soloUna) {
            $restar = $this->pdo->prepare(
                "UPDATE piezas_reparaciones SET unidades = unidades - 1
                 WHERE id = ? AND id_reparaciones = ? AND unidades > 1");
            $restar->execute([$lineaId, $reparacionId]);
            if ($restar->rowCount() === 1) {
                return true;
            }
        }

        $borrar = $this->pdo->prepare(
            "DELETE FROM piezas_reparaciones WHERE id = ? AND id_reparaciones = ?");
        $borrar->execute([$lineaId, $reparacionId]);
        return $borrar->rowCount() === 1;
    }
}
