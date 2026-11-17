<?php
// Modelo: las cifras de ventas para el apartado de estadísticas.
// «Ventas» = reparaciones entregadas (con fecha de salida), sin IVA:
// mano de obra más el importe de sus piezas. Las fechas van como [desde, hasta).
class EstadisticaModelo
{
    public function __construct(private PDO $pdo) {}

    // Importe de las piezas de la reparación «r»
    private const IMPORTE_PIEZAS = "COALESCE((
        SELECT SUM(pr.unidades * p.precio)
        FROM piezas_reparaciones pr
        JOIN piezas p ON p.id = pr.id_piezas
        WHERE pr.id_reparaciones = r.id), 0)";

    // Tipos de avería y las palabras (sin tildes) que los identifican.
    // El orden importa: gana el primer tipo que coincida
    private const TIPOS_AVERIA = [
        'Refrigeración'          => ['refrigera', 'radiador', 'sobrecalent', 'termostato'],
        'Batería y arranque'     => ['bateria', 'arranque', 'arranca', 'alternador', 'calentador'],
        'Frenos'                 => ['fren', 'pastilla', 'disco'],
        'Embrague y transmisión' => ['embrague', 'transmision', 'caja de cambios'],
        'Mantenimiento'          => ['mantenimiento', 'revision', 'aceite', 'filtro', 'itv'],
        'Distribución y correas' => ['distribucion', 'correa'],
        'Suspensión y dirección' => ['suspension', 'amortiguador', 'rotula', 'direccion', 'tren delantero'],
        'Motor y encendido'      => ['motor', 'encendido', 'bujia', 'bobina', 'lambda', 'tirones'],
        'Neumáticos'             => ['neumatico', 'pinchazo', 'rueda', 'equilibrado'],
        'Alumbrado y accesorios' => ['lampara', 'escobilla', 'faro'],
    ];

    // Total vendido entre dos fechas
    public function ventas(string $desde, string $hasta): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT COUNT(*) AS reparaciones,
                    COALESCE(SUM(r.mano_de_obra), 0) AS mano_obra,
                    COALESCE(SUM(" . self::IMPORTE_PIEZAS . "), 0) AS piezas
             FROM reparaciones r
             WHERE r.fecha_salida >= ? AND r.fecha_salida < ?");
        $consulta->execute([$desde, $hasta]);
        $fila = $consulta->fetch();

        return [
            'reparaciones' => (int) $fila['reparaciones'],
            'mano_obra' => (float) $fila['mano_obra'],
            'piezas' => (float) $fila['piezas'],
            'total' => (float) $fila['mano_obra'] + (float) $fila['piezas'],
        ];
    }

    // Ventas agrupadas por mes: ['2026-03' => [mano_obra, piezas, reparaciones], …]
    public function ventasPorMes(string $desde, string $hasta): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT DATE_FORMAT(r.fecha_salida, '%Y-%m') AS mes,
                    SUM(r.mano_de_obra) AS mano_obra,
                    SUM(" . self::IMPORTE_PIEZAS . ") AS piezas,
                    COUNT(*) AS reparaciones
             FROM reparaciones r
             WHERE r.fecha_salida >= ? AND r.fecha_salida < ?
             GROUP BY mes");
        $consulta->execute([$desde, $hasta]);
        return $consulta->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    }

    // Piezas vendidas, de la que más unidades tiene a la que menos
    public function piezasVendidas(string $desde, string $hasta): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT p.nombre_pieza AS nombre, p.numero_referencia AS referencia,
                    SUM(pr.unidades) AS unidades,
                    SUM(pr.unidades * p.precio) AS importe
             FROM piezas_reparaciones pr
             JOIN piezas p ON p.id = pr.id_piezas
             JOIN reparaciones r ON r.id = pr.id_reparaciones
             WHERE r.fecha_salida >= ? AND r.fecha_salida < ?
             GROUP BY p.id, p.nombre_pieza, p.numero_referencia
             ORDER BY unidades DESC, importe DESC");
        $consulta->execute([$desde, $hasta]);
        return $consulta->fetchAll();
    }

    // Lo vendido por cada mecánico
    public function ventasPorMecanico(string $desde, string $hasta): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT m.nombre, COUNT(*) AS reparaciones,
                    SUM(r.mano_de_obra + " . self::IMPORTE_PIEZAS . ") AS total
             FROM reparaciones r
             JOIN mecanico m ON m.id = r.id_mecanico
             WHERE r.fecha_salida >= ? AND r.fecha_salida < ?
             GROUP BY m.id, m.nombre
             ORDER BY total DESC");
        $consulta->execute([$desde, $hasta]);
        return $consulta->fetchAll();
    }

    // Las averías más comunes de los coches que entraron entre dos fechas,
    // agrupadas por tipo según las palabras de la descripción
    public function averiasComunes(string $desde, string $hasta): array
    {
        $consulta = $this->pdo->prepare(
            "SELECT descripcion FROM reparaciones WHERE fecha_entrada >= ? AND fecha_entrada < ?");
        $consulta->execute([$desde, $hasta]);

        $cuenta = [];
        foreach ($consulta->fetchAll(PDO::FETCH_COLUMN) as $descripcion) {
            $tipo = self::tipoDeAveria($descripcion);
            $cuenta[$tipo] = ($cuenta[$tipo] ?? 0) + 1;
        }
        arsort($cuenta);

        // «Otras» siempre al final, aunque tenga muchas
        if (isset($cuenta['Otras'])) {
            $otras = $cuenta['Otras'];
            unset($cuenta['Otras']);
            $cuenta['Otras'] = $otras;
        }

        $averias = [];
        foreach ($cuenta as $tipo => $reparaciones) {
            $averias[] = ['tipo' => $tipo, 'reparaciones' => $reparaciones];
        }
        return $averias;
    }

    private static function tipoDeAveria(string $descripcion): string
    {
        $texto = strtr(mb_strtolower($descripcion), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u']);
        foreach (self::TIPOS_AVERIA as $tipo => $palabras) {
            foreach ($palabras as $palabra) {
                if (str_contains($texto, $palabra)) {
                    return $tipo;
                }
            }
        }
        return 'Otras';
    }
}
