<?php
// Controlador de las estadísticas de ventas.
// No contiene SQL ni HTML.
class EstadisticasControlador extends Controlador
{
    private const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    public function __construct(private EstadisticaModelo $estadisticas) {}

    // index.php?accion=ventas  (&periodo=semana|mes|anio|todo)
    public function ver(): void
    {
        $periodo = $_GET['periodo'] ?? 'anio';
        if (!in_array($periodo, ['semana', 'mes', 'anio', 'todo'], true)) {
            $periodo = 'anio';
        }

        $hoy = new DateTimeImmutable('today');
        $lunes = $hoy->modify('monday this week');
        $primeroMes = $hoy->modify('first day of this month');
        $primeroAnio = $hoy->setDate((int) $hoy->format('Y'), 1, 1);

        // Cada periodo: [desde, hasta, inicio del periodo anterior]
        $rangos = [
            'semana' => [$lunes, $lunes->modify('+1 week'), $lunes->modify('-1 week')],
            'mes'    => [$primeroMes, $primeroMes->modify('+1 month'), $primeroMes->modify('-1 month')],
            'anio'   => [$primeroAnio, $primeroAnio->modify('+1 year'), $primeroAnio->modify('-1 year')],
        ];

        // Resumen de la semana, el mes y el año, comparado con el periodo anterior
        $resumen = [];
        foreach ($rangos as $clave => [$desde, $hasta, $anterior]) {
            $actual = $this->estadisticas->ventas($this->dia($desde), $this->dia($hasta));
            $previo = $this->estadisticas->ventas($this->dia($anterior), $this->dia($desde));
            $resumen[$clave] = [
                'total' => $actual['total'],
                'reparaciones' => $actual['reparaciones'],
                'variacion' => $previo['total'] > 0 ? ($actual['total'] - $previo['total']) / $previo['total'] * 100 : null,
            ];
        }

        // El periodo elegido en los filtros
        [$desde, $hasta] = $periodo === 'todo'
            ? ['2000-01-01', '2100-01-01']
            : [$this->dia($rangos[$periodo][0]), $this->dia($rangos[$periodo][1])];

        $ventas = $this->estadisticas->ventas($desde, $hasta);
        $piezas = $this->estadisticas->piezasVendidas($desde, $hasta);

        $this->mostrar('ventas', [
            'titulo' => 'Ventas',
            'periodo' => $periodo,
            'nombrePeriodo' => ['semana' => 'esta semana', 'mes' => 'este mes', 'anio' => 'este año', 'todo' => 'desde el principio'][$periodo],
            'resumen' => $resumen,
            'ventas' => $ventas,
            'ticketMedio' => $ventas['reparaciones'] > 0 ? $ventas['total'] / $ventas['reparaciones'] : 0,
            'unidadesPiezas' => array_sum(array_column($piezas, 'unidades')),
            'meses' => $this->ultimosMeses($primeroMes, 12),
            'piezas' => array_slice($piezas, 0, 8),
            'averias' => $this->estadisticas->averiasComunes($desde, $hasta),
            'mecanicos' => $this->estadisticas->ventasPorMecanico($desde, $hasta),
        ]);
    }

    // Los últimos $n meses (el actual incluido), con 0 en los meses sin ventas
    private function ultimosMeses(DateTimeImmutable $primeroMes, int $n): array
    {
        $inicio = $primeroMes->modify('-' . ($n - 1) . ' months');
        $porMes = $this->estadisticas->ventasPorMes($this->dia($inicio), $this->dia($primeroMes->modify('+1 month')));

        $meses = [];
        for ($i = 0; $i < $n; $i++) {
            $mes = $inicio->modify("+$i months");
            $clave = $mes->format('Y-m');
            $fila = $porMes[$clave] ?? ['mano_obra' => 0, 'piezas' => 0, 'reparaciones' => 0];
            $meses[] = [
                'mes' => self::MESES[(int) $mes->format('n') - 1],
                'anio' => $mes->format('Y'),
                'mano_obra' => round((float) $fila['mano_obra'], 2),
                'piezas' => round((float) $fila['piezas'], 2),
                'reparaciones' => (int) $fila['reparaciones'],
            ];
        }
        return $meses;
    }

    private function dia(DateTimeImmutable $fecha): string
    {
        return $fecha->format('Y-m-d');
    }
}
