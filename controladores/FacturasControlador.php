<?php
// Controlador de las facturas: listado, emitir factura y ver/imprimir.
// No contiene SQL ni HTML.
class FacturasControlador extends Controlador
{
    public function __construct(private FacturaModelo $facturas, private ReparacionModelo $reparaciones) {}

    // index.php?accion=facturas  (&q=texto para buscar)
    public function listado(): void
    {
        $busqueda = trim($_GET['q'] ?? '');

        $this->mostrar('facturas', [
            'titulo' => 'Facturas',
            'facturas' => $this->facturas->todas($busqueda),
            'pendientes' => $busqueda === '' ? $this->facturas->pendientes() : [],
            'resumen' => $this->facturas->resumen(),
            'tipoIva' => self::IVA,
            'busqueda' => $busqueda,
            'aviso' => ($_GET['msg'] ?? '') === 'facturada' ? 'Factura emitida.' : '',
        ]);
    }

    // index.php?accion=factura&id=3  (id de la reparación; &imprimir=1 abre la impresión)
    // Factura si ya está emitida; si no, presupuesto (en el taller) o «sin facturar»
    public function ver(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $reparacion = $id ? $this->reparaciones->buscar($id) : null;

        if (!$reparacion) {
            $this->noEncontrada();
            return;
        }

        $avisos = ['facturada' => 'Factura emitida y vehículo entregado. ¡Gracias por correr con nosotros!'];
        $this->documento($reparacion, [], $avisos[$_GET['msg'] ?? ''] ?? '');
    }

    // index.php?accion=facturar&id=3 (POST)
    // Emite la factura; si el coche seguía en el taller, además lo entrega
    public function facturar(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $reparacion = $id ? $this->reparaciones->buscar($id) : null;

        if (!$reparacion) {
            $this->noEncontrada();
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir("index.php?accion=factura&id=$id");
        }

        try {
            $this->facturas->facturar($id, self::IVA);
            $volver = ($_POST['volver'] ?? '') === 'facturas' ? 'index.php?accion=facturas&msg=facturada'
                    : "index.php?accion=factura&id=$id&msg=facturada&imprimir=1";
            $this->redirigir($volver);
        } catch (RuntimeException $e) {
            $errores = [$e->getMessage()];
        } catch (PDOException $e) {
            $errores = ['No se ha podido emitir la factura.'];
        }

        $this->documento($this->reparaciones->buscar($id), $errores);
    }

    // ---------- Área del cliente (rol «usuario»): solo sus facturas ----------

    // index.php?accion=mis_facturas
    public function misFacturas(): void
    {
        $clienteId = (int) (Seguridad::usuario()['cliente_id'] ?? 0);
        $this->mostrar('mis_facturas', [
            'titulo' => 'Mis facturas',
            'facturas' => $clienteId ? $this->facturas->deCliente($clienteId) : [],
            'pendienteDeFicha' => !$clienteId,
        ]);
    }

    // index.php?accion=mi_factura&id=3  (&imprimir=1)
    public function miFactura(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $clienteId = (int) (Seguridad::usuario()['cliente_id'] ?? 0);

        // Si no es suya (o no está facturada), «no encontrada»: ni siquiera se revela que existe
        if (!$id || !$clienteId || !$this->facturas->facturaEsDelCliente($id, $clienteId)) {
            $this->noEncontrada();
            return;
        }
        $this->documento($this->reparaciones->buscar($id), [], '', true);
    }

    // Muestra la factura emitida, o el presupuesto si todavía no hay factura.
    // $paraCliente: vista del cliente, sin botones de gestión
    private function documento(array $reparacion, array $errores, string $aviso = '', bool $paraCliente = false): void
    {
        $factura = $this->facturas->deReparacion($reparacion['id']);
        $piezas = $this->reparaciones->piezas($reparacion['id']);

        if ($factura) {
            // Los importes guardados al facturar: la factura no cambia nunca
            $tipo = 'factura';
            $base = (float) $factura['base'];
            $porcentajeIva = (float) $factura['tipo_iva'];
            $iva = (float) $factura['iva'];
            $total = (float) $factura['total'];
            $numero = $factura['numero'];
            $fecha = $factura['fecha'];
        } else {
            $tipo = $reparacion['fecha_salida'] === null ? 'presupuesto' : 'pendiente';
            $base = $reparacion['mano_de_obra'] + array_sum(array_column($piezas, 'importe'));
            $porcentajeIva = self::IVA * 100;
            $iva = round($base * self::IVA, 2);
            $total = $base + $iva;
            $numero = sprintf('P-%s-%05d', date('Y'), $reparacion['id']);
            $fecha = date('Y-m-d');
        }

        $titulos = ['factura' => 'Factura ' . $numero, 'presupuesto' => 'Presupuesto', 'pendiente' => 'Sin facturar'];

        $this->mostrar('factura', [
            'titulo' => $titulos[$tipo],
            'tipo' => $tipo,
            'reparacion' => $reparacion,
            'piezas' => $piezas,
            'base' => $base,
            'porcentajeIva' => $porcentajeIva,
            'iva' => $iva,
            'total' => $total,
            'numero' => $numero,
            'fecha' => $fecha,
            'errores' => $errores,
            'aviso' => $aviso,
            'imprimir' => $tipo === 'factura' && ($_GET['imprimir'] ?? '') === '1',
            'paraCliente' => $paraCliente,
        ]);
    }
}
