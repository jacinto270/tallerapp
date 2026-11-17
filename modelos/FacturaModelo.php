<?php
// Modelo: todo el SQL de las facturas
class FacturaModelo
{
    public function __construct(private PDO $pdo) {}

    // Todas las facturas, de la más reciente a la más antigua.
    // $busqueda filtra por número, cliente o matrícula
    public function todas(string $busqueda = ''): array
    {
        $sql = "SELECT facturas.id, facturas.numero, facturas.fecha, facturas.base, facturas.iva, facturas.total,
                       reparaciones.id AS reparacion_id, reparaciones.descripcion,
                       vehiculo.matricula, vehiculo.marca, vehiculo.modelo,
                       cliente.id AS cliente_id, cliente.nombre AS cliente_nombre, cliente.apellidos AS cliente_apellidos
                FROM facturas
                JOIN reparaciones ON reparaciones.id = facturas.id_reparacion
                JOIN vehiculo ON vehiculo.id = reparaciones.id_vehiculo
                JOIN cliente ON cliente.id = vehiculo.id_cliente";
        $parametros = [];

        if ($busqueda !== '') {
            $sql .= " WHERE facturas.numero LIKE ? OR vehiculo.matricula LIKE ?
                         OR CONCAT(cliente.nombre, ' ', cliente.apellidos) LIKE ?";
            $parametros = array_fill(0, 3, '%' . addcslashes($busqueda, '%_\\') . '%');
        }
        $sql .= " ORDER BY facturas.fecha DESC, facturas.id DESC";

        $consulta = $this->pdo->prepare($sql);
        $consulta->execute($parametros);
        return $consulta->fetchAll();
    }

    // Las facturas de un cliente (para su área privada)
    public function deCliente(int $clienteId): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT facturas.numero, facturas.fecha, facturas.base, facturas.iva, facturas.total,
                    reparaciones.id AS reparacion_id, reparaciones.descripcion,
                    vehiculo.matricula, vehiculo.marca, vehiculo.modelo
             FROM facturas
             JOIN reparaciones ON reparaciones.id = facturas.id_reparacion
             JOIN vehiculo ON vehiculo.id = reparaciones.id_vehiculo
             WHERE vehiculo.id_cliente = ?
             ORDER BY facturas.fecha DESC, facturas.id DESC");
        $consulta->execute([$clienteId]);
        return $consulta->fetchAll();
    }

    // ¿La reparación está facturada y es de un coche de este cliente?
    public function facturaEsDelCliente(int $reparacionId, int $clienteId): bool
    {
        $consulta = $this->pdo->prepare(
            "SELECT 1
             FROM facturas
             JOIN reparaciones ON reparaciones.id = facturas.id_reparacion
             JOIN vehiculo ON vehiculo.id = reparaciones.id_vehiculo
             WHERE facturas.id_reparacion = ? AND vehiculo.id_cliente = ?");
        $consulta->execute([$reparacionId, $clienteId]);
        return (bool) $consulta->fetchColumn();
    }

    // Reparaciones entregadas que todavía no tienen factura
    public function pendientes(): array
    {
        return $this->pdo->query(
            "SELECT reparaciones.id, reparaciones.fecha_salida, reparaciones.descripcion,
                    vehiculo.matricula, vehiculo.marca, vehiculo.modelo,
                    cliente.nombre AS cliente_nombre, cliente.apellidos AS cliente_apellidos,
                    reparaciones.mano_de_obra + COALESCE((
                        SELECT SUM(piezas_reparaciones.unidades * piezas.precio)
                        FROM piezas_reparaciones
                        JOIN piezas ON piezas.id = piezas_reparaciones.id_piezas
                        WHERE piezas_reparaciones.id_reparaciones = reparaciones.id
                    ), 0) AS base
             FROM reparaciones
             JOIN vehiculo ON vehiculo.id = reparaciones.id_vehiculo
             JOIN cliente ON cliente.id = vehiculo.id_cliente
             LEFT JOIN facturas ON facturas.id_reparacion = reparaciones.id
             WHERE reparaciones.fecha_salida IS NOT NULL AND facturas.id IS NULL
             ORDER BY reparaciones.fecha_salida DESC"
        )->fetchAll();
    }

    // Cuántas facturas hay y cuánto suman, en total y este mes
    public function resumen(): array
    {
        return $this->pdo->query(
            "SELECT COUNT(*) AS numero,
                    COALESCE(SUM(total), 0) AS total,
                    COALESCE(SUM(CASE WHEN fecha >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN total END), 0) AS este_mes
             FROM facturas"
        )->fetch();
    }

    // La factura de una reparación; null si todavía no se ha facturado
    public function deReparacion(int $reparacionId): ?array
    {
        $consulta = $this->pdo->prepare(
            "SELECT id, numero, fecha, base, tipo_iva, iva, total
             FROM facturas WHERE id_reparacion = ?");
        $consulta->execute([$reparacionId]);
        return $consulta->fetch() ?: null;
    }

    // Factura una reparación y devuelve el número de la factura.
    // Si el coche seguía en el taller, también cierra la reparación (se entrega).
    // Todo va en una transacción: o se hace entero o no se hace nada.
    // Lanza RuntimeException si la reparación no existe o ya estaba facturada
    public function facturar(int $reparacionId, float $tipoIva): string
    {
        $this->pdo->beginTransaction();
        try {
            // FOR UPDATE bloquea la fila: dos clics a la vez no crean dos facturas
            $consulta = $this->pdo->prepare(
                "SELECT id, fecha_salida, mano_de_obra FROM reparaciones WHERE id = ? FOR UPDATE");
            $consulta->execute([$reparacionId]);
            $reparacion = $consulta->fetch();
            if (!$reparacion) {
                throw new RuntimeException('La reparación no existe.');
            }

            if ($this->deReparacion($reparacionId)) {
                throw new RuntimeException('Esta reparación ya está facturada.');
            }

            if ($reparacion['fecha_salida'] === null) {
                // fecha_entrada = fecha_entrada evita que su ON UPDATE la cambie
                $cerrar = $this->pdo->prepare(
                    "UPDATE reparaciones SET fecha_salida = NOW(), fecha_entrada = fecha_entrada WHERE id = ?");
                $cerrar->execute([$reparacionId]);
            }

            $consulta = $this->pdo->prepare(
                "SELECT COALESCE(SUM(piezas_reparaciones.unidades * piezas.precio), 0)
                 FROM piezas_reparaciones
                 JOIN piezas ON piezas.id = piezas_reparaciones.id_piezas
                 WHERE piezas_reparaciones.id_reparaciones = ?");
            $consulta->execute([$reparacionId]);
            $base = round($reparacion['mano_de_obra'] + $consulta->fetchColumn(), 2);
            $iva = round($base * $tipoIva, 2);

            // Número correlativo por año: F-2026-00001, F-2026-00002…
            $anio = date('Y');
            $consulta = $this->pdo->prepare(
                "SELECT COALESCE(MAX(CAST(SUBSTRING(numero, 8) AS UNSIGNED)), 0) + 1
                 FROM facturas WHERE numero LIKE ? FOR UPDATE");
            $consulta->execute(["F-$anio-%"]);
            $numero = sprintf('F-%s-%05d', $anio, $consulta->fetchColumn());

            $insertar = $this->pdo->prepare(
                "INSERT INTO facturas (id_reparacion, numero, fecha, base, tipo_iva, iva, total)
                 VALUES (?, ?, NOW(), ?, ?, ?, ?)");
            $insertar->execute([$reparacionId, $numero, $base, $tipoIva * 100, $iva, $base + $iva]);

            $this->pdo->commit();
            return $numero;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
