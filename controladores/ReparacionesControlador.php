<?php
// Controlador: recibe la petición, usa los modelos y elige la vista.
// No contiene SQL ni HTML.
class ReparacionesControlador extends Controlador
{
    public function __construct(private ReparacionModelo $reparaciones, private CatalogoModelo $catalogo) {}

    // index.php?accion=listado  (&q=texto para buscar, &estado=taller|entregadas para filtrar)
    public function listado(): void
    {
        // TODO 1: pide las reparaciones al modelo y muestra la vista «listado»
        //         con dos datos: 'titulo' => 'Reparaciones' y 'reparaciones'
        $busqueda = trim($_GET['q'] ?? '');
        $estado = $_GET['estado'] ?? '';
        if (!in_array($estado, ['', 'taller', 'entregadas'], true)) {
            $estado = '';
        }

        $titulos = [
            '' => 'Reparaciones',
            'taller' => 'Vehículos en el taller',
            'entregadas' => 'Reparaciones entregadas',
        ];

        $this->mostrar('listado', [
            'titulo' => $titulos[$estado],
            'reparaciones' => $this->reparaciones->todas($busqueda, $estado),
            'resumen' => $this->reparaciones->resumen(),
            'busqueda' => $busqueda,
            'estado' => $estado,
        ]);
    }

    // index.php?accion=ver&id=3
    public function ver(): void
    {
        // TODO 3: lee el id de la dirección como número entero
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        // TODO 4: pide la reparación al modelo; si no hay id o no existe,
        //         llama a $this->noEncontrada() y termina con return
        $reparacion = $id ? $this->reparaciones->buscar($id) : null;

        if (!$reparacion) {
            $this->noEncontrada();
            return;
        }

        $avisos = [
            "creada"  => "Reparación creada. ¡A boxes!",
            "pieza"   => "Pieza añadida a la reparación.",
            "quitada" => "Pieza quitada de la reparación.",
            "mano_obra" => "Mano de obra actualizada.",
        ];
        $this->ficha($reparacion, [], [], $avisos[$_GET['msg'] ?? ''] ?? '');
    }

    // index.php?accion=anadir_pieza&id=3 (POST)
    public function anadirPieza(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $reparacion = $id ? $this->reparaciones->buscar($id) : null;

        if (!$reparacion) {
            $this->noEncontrada();
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir("index.php?accion=ver&id=$id");
        }

        $piezaId = filter_input(INPUT_POST, 'pieza_id', FILTER_VALIDATE_INT);
        $unidades = filter_input(INPUT_POST, 'unidades', FILTER_VALIDATE_INT);

        $errores = [];
        if ($reparacion['fecha_salida'] !== null) {
            $errores[] = "El vehículo ya ha salido del taller: no se pueden añadir piezas.";
        }
        $idsPiezas = array_column($this->catalogo->piezas(), 'id');
        if (!$piezaId || !in_array($piezaId, $idsPiezas)) {
            $errores[] = "Elige una pieza.";
        }
        if (!$unidades || $unidades < 1) {
            $errores[] = "El número de unidades debe ser 1 o más.";
        }

        if (!$errores) {
            try {
                $this->reparaciones->anadirPieza($id, $piezaId, $unidades);
                $this->redirigir("index.php?accion=ver&id=$id&msg=pieza");
            } catch (PDOException $e) {
                $errores[] = "No se ha podido añadir la pieza.";
            }
        }

        $this->ficha($reparacion, $errores, [
            'pieza_id' => $piezaId,
            'unidades' => $_POST['unidades'] ?? '',
        ]);
    }

    // index.php?accion=mano_obra&id=3 (POST con horas y precio_hora)
    // La mano de obra pasa a ser horas × precio por hora
    public function manoObra(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $reparacion = $id ? $this->reparaciones->buscar($id) : null;

        if (!$reparacion) {
            $this->noEncontrada();
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir("index.php?accion=ver&id=$id");
        }

        // Se acepta la coma decimal: «2,5» horas
        $horas = filter_var(str_replace(',', '.', $_POST['horas'] ?? ''), FILTER_VALIDATE_FLOAT);
        $precioHora = filter_var(str_replace(',', '.', $_POST['precio_hora'] ?? ''), FILTER_VALIDATE_FLOAT);

        $errores = [];
        if ($reparacion['fecha_salida'] !== null) {
            $errores[] = "El vehículo ya ha salido del taller: la mano de obra no se puede cambiar.";
        }
        if ($horas === false || $horas < 0 || $horas > 999) {
            $errores[] = "Las horas deben ser un número entre 0 y 999.";
        }
        if ($precioHora === false || $precioHora < 0 || $precioHora > 9999) {
            $errores[] = "El precio por hora debe ser un número entre 0 y 9.999 €.";
        }

        if (!$errores) {
            try {
                $this->reparaciones->guardarManoObra($id, round($horas, 2), round($precioHora, 2));
                $this->redirigir("index.php?accion=ver&id=$id&msg=mano_obra");
            } catch (PDOException $e) {
                $errores[] = "No se ha podido guardar la mano de obra.";
            }
        }

        $this->ficha($reparacion, $errores, [
            'horas' => $_POST['horas'] ?? '',
            'precio_hora' => $_POST['precio_hora'] ?? '',
        ]);
    }

    // index.php?accion=quitar_pieza&id=3 (POST con linea_id y modo=una|todas)
    public function quitarPieza(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $reparacion = $id ? $this->reparaciones->buscar($id) : null;

        if (!$reparacion) {
            $this->noEncontrada();
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir("index.php?accion=ver&id=$id");
        }

        $lineaId = filter_input(INPUT_POST, 'linea_id', FILTER_VALIDATE_INT);
        $soloUna = ($_POST['modo'] ?? '') === 'una';

        $errores = [];
        if ($reparacion['fecha_salida'] !== null) {
            $errores[] = "El vehículo ya ha salido del taller: no se pueden quitar piezas.";
        } elseif (!$lineaId) {
            $errores[] = "Elige la pieza que quieres quitar.";
        } else {
            try {
                if ($this->reparaciones->quitarPieza($id, $lineaId, $soloUna)) {
                    $this->redirigir("index.php?accion=ver&id=$id&msg=quitada");
                }
                $errores[] = "Esa pieza no está en esta reparación.";
            } catch (PDOException $e) {
                $errores[] = "No se ha podido quitar la pieza.";
            }
        }

        $this->ficha($reparacion, $errores, []);
    }

    // index.php?accion=nueva  (&vehiculo=5 para dejar elegido ese vehículo)
    public function nueva(): void
    {
        $vehiculoId = filter_input(INPUT_GET, 'vehiculo', FILTER_VALIDATE_INT);
        $this->formulario([], $vehiculoId ? ['vehiculo_id' => $vehiculoId] : []);
    }

    // index.php?accion=guardar (POST)
    public function guardar(): void
    {
        // TODO 4: si la petición no es POST, redirige a index.php?accion=nueva
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('index.php?accion=nueva');
        }

        // TODO 5: lee vehiculo_id y mecanico_id como enteros, descripcion sin espacios
        //         a los lados y mano_obra como número decimal (FILTER_VALIDATE_FLOAT)
        $vehiculoId = filter_input(INPUT_POST, 'vehiculo_id', FILTER_VALIDATE_INT);
        $mecanicoId = filter_input(INPUT_POST, 'mecanico_id', FILTER_VALIDATE_INT);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $manoObra = filter_input(INPUT_POST, 'mano_obra', FILTER_VALIDATE_FLOAT);

        // TODO 6: guarda en el array $errores un mensaje por cada dato incorrecto
        $errores = [];
        if (!$vehiculoId) {
            $errores[] = "Elige un vehículo.";
        }
        if (!$mecanicoId) {
            $errores[] = "Elige un mecánico.";
        }
        if ($descripcion === '' || mb_strlen($descripcion) > 200) {
            $errores[] = "Describe la avería (200 caracteres como máximo).";
        }
        if ($manoObra === false || $manoObra === null || $manoObra < 0) {
            $errores[] = "La mano de obra debe ser un número igual o mayor que 0.";
        }

        // TODO 7: si no hay errores, crea la reparación con el modelo y redirige
        //         a su ficha con &msg=creada. Si el modelo lanza una PDOException,
        //         añade el error «No se ha podido guardar la reparación.»
        if (!$errores) {
            try {
                $id = $this->reparaciones->crear($vehiculoId, $mecanicoId, $descripcion, $manoObra);
                $this->redirigir("index.php?accion=ver&id=$id&msg=creada");
            } catch (PDOException $e) {
                $errores[] = "No se ha podido guardar la reparación.";
            }
        }

        // TODO 8: si has llegado aquí, hay errores: vuelve a mostrar el formulario
        //         con $errores y con los valores que escribió el usuario
        $this->formulario($errores, [
            'vehiculo_id' => $vehiculoId,
            'mecanico_id' => $mecanicoId,
            'descripcion' => $descripcion,
            'mano_obra' =>  $_POST['mano_obra'] ?? '',
        ]);
    }

    // Muestra la ficha de una reparación, con los formularios de piezas si sigue en el taller
    private function ficha(array $reparacion, array $errores, array $valores, string $aviso = ''): void
    {
        // TODO 5: pide sus piezas y calcula el total:
        //         mano de obra más la suma de los importes
        $piezas = $this->reparaciones->piezas($reparacion['id']);
        $totalPiezas = array_sum(array_column($piezas, 'importe'));
        $total = $reparacion['mano_de_obra'] + $totalPiezas;
        $enTaller = $reparacion['fecha_salida'] === null;

        // TODO 6: muestra la vista «ficha» con cuatro datos: titulo
        //         («Reparación n.º 3»), reparacion, piezas y total
        $this->mostrar('ficha', [
            'titulo' => "Reparación n.º " . $reparacion['id'],
            'reparacion' => $reparacion,
            'piezas' => $piezas,
            'totalPiezas' => $totalPiezas,
            'total' => $total,
            'porcentajeIva' => self::IVA * 100,
            'totalConIva' => $total + round($total * self::IVA, 2),
            'aviso' => $aviso,
            'enTaller' => $enTaller,
            'catalogoPiezas' => $enTaller ? $this->catalogo->piezas() : [],
            'precioHoraPropuesto' => self::PRECIO_HORA,
            'errores' => $errores,
            'valores' => $valores,
        ]);
    }

    // Muestra el formulario de nueva reparación, vacío o con errores
    private function formulario(array $errores, array $valores): void
    {
        $this->mostrar('formulario', [
            'titulo' => 'Nueva reparación',
            'vehiculos' => $this->catalogo->vehiculos(),
            'mecanicos' => $this->catalogo->mecanicos(),
            'errores' => $errores,
            'valores' => $valores,
        ]);
    }
}
