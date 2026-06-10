<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Empleado;
use App\Models\MovimientoBodega;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DecisionAiService
{
    public function responder(string $pregunta): string
    {
        $pregunta = trim($pregunta);

        if (! $this->preguntaPermitida($pregunta)) {
            return 'Solo puedo responder preguntas relacionadas con la lógica de Distribuidora Solmunol.';
        }

        $respuestaLocal = $this->generarRespuestaDirectivaLocal($pregunta);
        $apiKey = config('services.google_ai.api_key');
        $model = config('services.google_ai.model', 'gemini-2.5-flash');

        if (! $apiKey) {
            return $respuestaLocal;
        }

        try {
            $prompt = $this->construirPrompt($pregunta, $respuestaLocal);
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $apiKey,
            ])
                ->timeout(45)
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.15,
                        'maxOutputTokens' => 1800,
                    ],
                ]);

            if (! $response->successful()) {
                return $respuestaLocal;
            }

            $texto = data_get($response->json(), 'candidates.0.content.parts.0.text');

            if (! is_string($texto) || trim($texto) === '') {
                return $respuestaLocal;
            }

            $texto = $this->limpiarTextoIa($texto);

            // Si Gemini devuelve una respuesta pobre o cortada, se usa la respuesta calculada por el ERP.
            if ($this->respuestaIaEsInsuficiente($texto)) {
                return $respuestaLocal;
            }

            return $this->asegurarCierre($texto);
        } catch (\Throwable $e) {
            return $respuestaLocal;
        }
    }

    private function construirPrompt(string $pregunta, string $respuestaBase): string
    {
        return <<<PROMPT
Eres el asistente IA directivo de Distribuidora Solmunol, un ERP de compras, ventas, bodega, productos, clientes, proveedores, empleados, reportes y dashboard directivo.

Tu obligación es ayudar a tomar decisiones empresariales usando únicamente los datos del ERP. No inventes información que no exista en el contexto. No respondas temas externos al ERP.

Reglas de respuesta:
1. Responde en español claro y profesional.
2. No uses markdown con asteriscos.
3. No respondas de forma breve. Debes entregar una recomendación útil para dirección.
4. Si faltan datos, dilo y plantea cómo recolectarlos.
5. Para talento humano, no ordenes despidos, multas ni sanciones definitivas. Recomienda revisión, seguimiento, capacitación, advertencia documentada o plan de mejora según evidencias.
6. Para proveedores, compara frecuencia, monto, compras recientes, estado y riesgos. Si faltan datos de calidad o tiempos de entrega, acláralo.
7. Para stock, indica productos críticos, cantidad sugerida y prioridad.
8. Para ventas, indica tendencia, productos/clientes relevantes, riesgos y acciones comerciales.
9. Para compras, indica control de gasto, proveedor recomendado y compras a evitar.
10. Termina siempre con: Fin de recomendación.

Usa este formato:
Diagnóstico:
Datos encontrados:
Hallazgos:
Nivel de prioridad o riesgo:
Recomendación administrativa:
Plan de acción:
Decisión sugerida:
Limitaciones de los datos:
Fin de recomendación.

Pregunta del usuario:
{$pregunta}

Análisis calculado por el ERP que debes ampliar sin contradecirlo:
{$respuestaBase}
PROMPT;
    }

    private function limpiarTextoIa(string $texto): string
    {
        $texto = trim($texto);
        $texto = str_replace(['**', '###', '##', '#'], '', $texto);
        $texto = preg_replace('/^\s*[-•]\s*/m', '', $texto);
        $texto = preg_replace('/\n{3,}/', "\n\n", (string) $texto);

        return trim((string) $texto);
    }

    private function respuestaIaEsInsuficiente(string $texto): bool
    {
        $normalizado = mb_strtolower($texto, 'UTF-8');

        if (mb_strlen($texto, 'UTF-8') < 600) {
            return true;
        }

        $seccionesNecesarias = [
            'diagnóstico',
            'datos encontrados',
            'recomendación',
            'plan de acción',
            'decisión sugerida',
        ];

        foreach ($seccionesNecesarias as $seccion) {
            if (! str_contains($normalizado, $seccion)) {
                return true;
            }
        }

        if (str_contains($normalizado, 'mot\n') || str_ends_with(trim($normalizado), 'mot')) {
            return true;
        }

        return false;
    }

    private function asegurarCierre(string $texto): string
    {
        $texto = trim($texto);

        if (! str_contains($texto, 'Fin de recomendación.')) {
            $texto .= "\nFin de recomendación.";
        }

        return $texto;
    }

    private function preguntaPermitida(string $pregunta): bool
    {
        $texto = $this->normalizar($pregunta);

        $palabrasBloqueadas = [
            'receta', 'pollo frito', 'segunda guerra', 'historia mundial', 'medicina', 'doctor',
            'politica', 'religion', 'futbol', 'pelicula', 'anime', 'cancion', 'rutina de ejercicios',
        ];

        foreach ($palabrasBloqueadas as $palabra) {
            if (str_contains($texto, $palabra)) {
                return false;
            }
        }

        $palabrasPermitidas = [
            'erp', 'solmunol', 'sistema', 'empresa', 'negocio', 'modulo', 'dashboard', 'reporte', 'reportes',
            'compra', 'compras', 'proveedor', 'proveedores', 'venta', 'ventas', 'cliente', 'clientes',
            'producto', 'productos', 'stock', 'inventario', 'bodega', 'kardex', 'movimiento', 'movimientos',
            'empleado', 'empleados', 'talento humano', 'personal', 'trabajador', 'colaborador', 'cargo', 'departamento',
            'desempeno', 'rendimiento', 'productividad', 'responsabilidad', 'cumplimiento', 'anomalia', 'anomalias',
            'decision', 'decisiones', 'decidir', 'recomienda', 'recomendacion', 'estrategia', 'accion', 'acciones',
            'ganancia', 'utilidad', 'rentabilidad', 'ingresos', 'egresos', 'indicadores', 'kpis', 'prediccion',
            'pronostico', 'proyeccion', 'tendencia', 'urgente', 'priorizar', 'mejor comportamiento', 'mejor proveedor',
            'sancion', 'advertencia', 'capacitacion', 'multa', 'despido', 'seguimiento', 'asistencia', 'puntualidad',
        ];

        foreach ($palabrasPermitidas as $palabra) {
            if (str_contains($texto, $palabra)) {
                return true;
            }
        }

        return $this->buscarEmpleadoMencionado($pregunta) !== null
            || $this->buscarProveedorMencionado($pregunta) !== null
            || $this->buscarProductoMencionado($pregunta) !== null
            || $this->buscarClienteMencionado($pregunta) !== null;
    }

    private function generarRespuestaDirectivaLocal(string $pregunta): string
    {
        try {
            $dominio = $this->detectarDominio($pregunta);

            return match ($dominio) {
                'talento' => $this->respuestaTalentoHumano($pregunta),
                'proveedores' => $this->respuestaProveedores($pregunta),
                'stock' => $this->respuestaStockProductos($pregunta),
                'ventas' => $this->respuestaVentasClientes($pregunta),
                'compras' => $this->respuestaCompras($pregunta),
                'bodega' => $this->respuestaBodega($pregunta),
                default => $this->respuestaGeneralEmpresa($pregunta),
            };
        } catch (\Throwable $e) {
            return $this->respuestaErrorControlado();
        }
    }

    private function detectarDominio(string $pregunta): string
    {
        $texto = $this->normalizar($pregunta);

        $mapa = [
            'talento' => ['empleado', 'talento humano', 'personal', 'trabajador', 'colaborador', 'desempeno', 'rendimiento', 'responsabilidad', 'cargo', 'departamento', 'sancion', 'multa', 'despido', 'capacitacion', 'asistencia', 'puntualidad', 'seguimiento'],
            'proveedores' => ['proveedor', 'proveedores', 'mejor proveedor', 'seguir comprando', 'comportamiento', 'abastecedor'],
            'stock' => ['stock', 'inventario', 'producto', 'productos', 'sin stock', 'bajo stock', 'reponer', 'reposicion', 'kardex'],
            'ventas' => ['venta', 'ventas', 'cliente', 'clientes', 'ingreso', 'ingresos', 'mas vendido', 'vender'],
            'compras' => ['compra', 'compras', 'egreso', 'egresos', 'comprar'],
            'bodega' => ['bodega', 'movimiento', 'movimientos', 'entrada', 'salida', 'ajuste'],
        ];

        foreach ($mapa as $dominio => $palabras) {
            foreach ($palabras as $palabra) {
                if (str_contains($texto, $palabra)) {
                    return $dominio;
                }
            }
        }

        if ($this->buscarEmpleadoMencionado($pregunta)) {
            return 'talento';
        }

        if ($this->buscarProveedorMencionado($pregunta)) {
            return 'proveedores';
        }

        if ($this->buscarProductoMencionado($pregunta)) {
            return 'stock';
        }

        if ($this->buscarClienteMencionado($pregunta)) {
            return 'ventas';
        }

        return 'general';
    }

    private function respuestaProveedores(string $pregunta): string
    {
        $totalProveedores = Proveedor::count();
        $proveedoresActivos = Proveedor::where('estado', 'Activo')->count();
        $totalCompras = (float) Compra::sum('total');
        $totalTransacciones = Compra::count();

        $ranking = $this->rankingProveedores();
        $mejor = $ranking->first();
        $proveedorMencionado = $this->buscarProveedorMencionado($pregunta);

        if ($proveedorMencionado) {
            $analisisIndividual = $this->analizarProveedor($proveedorMencionado);
            $proveedorNombre = $proveedorMencionado->nombre;
            $datosProveedor = $analisisIndividual['texto'];
            $decisionProveedor = $analisisIndividual['decision'];
            $riesgo = $analisisIndividual['riesgo'];
        } elseif ($mejor) {
            $proveedorNombre = $mejor['nombre'];
            $datosProveedor = $this->formatearRankingProveedores($ranking);
            $decisionProveedor = "Mantener a {$proveedorNombre} como proveedor preferente para compras recurrentes, siempre comparando precio y disponibilidad antes de emitir nuevas órdenes.";
            $riesgo = $mejor['riesgo'];
        } else {
            $proveedorNombre = 'No determinado';
            $datosProveedor = 'No existen compras suficientes para comparar proveedores.';
            $decisionProveedor = 'Registrar compras reales asociadas a proveedores antes de elegir un proveedor principal.';
            $riesgo = 'Medio';
        }

        return <<<TXT
Diagnóstico:
El módulo de proveedores registra {$totalProveedores} proveedores, de los cuales {$proveedoresActivos} están activos. El módulo de compras registra {$totalTransacciones} transacciones y un total acumulado de {$this->moneda($totalCompras)}.

Datos encontrados:
{$datosProveedor}

Hallazgos:
El mejor proveedor debe evaluarse por frecuencia de compras, monto acumulado, compras recientes y estado activo. Con los datos actuales, el proveedor recomendado es: {$proveedorNombre}.

Nivel de prioridad o riesgo:
{$riesgo}. La decisión se basa en historial de compras registrado. No se encontraron campos de calidad, tiempo de entrega, devoluciones o cumplimiento contractual, por lo que esos factores deben validarse manualmente.

Recomendación administrativa:
{$decisionProveedor}

Plan de acción:
1. Revisar las últimas compras realizadas con el proveedor recomendado.
2. Comparar precios del mismo producto con otros proveedores activos.
3. Validar disponibilidad, tiempo de entrega y calidad antes de una compra grande.
4. Mantener al menos un proveedor alterno para productos críticos.
5. Registrar observaciones de cumplimiento para mejorar futuras decisiones de compra.

Decisión sugerida:
Trabajar principalmente con {$proveedorNombre} si mantiene precios competitivos y disponibilidad, pero no depender de un solo proveedor sin comparar condiciones.

Limitaciones de los datos:
El ERP registra compras, montos y productos, pero no registra tiempos de entrega, reclamos, calidad del producto ni devoluciones por proveedor. Esos datos deben agregarse como control administrativo si se desea una evaluación más exacta.

Fin de recomendación.
TXT;
    }

    private function respuestaCompras(string $pregunta): string
    {
        $totalCompras = (float) Compra::sum('total');
        $cantidadCompras = Compra::count();
        $promedioCompra = $cantidadCompras > 0 ? $totalCompras / $cantidadCompras : 0;
        $rankingProveedores = $this->rankingProveedores()->take(3);
        $ultimasCompras = $this->ultimasComprasTexto(8);
        $productosCriticos = $this->productosCriticos();
        $productoCriticoTexto = $this->formatearProductosCriticos($productosCriticos);
        $proveedorTop = $rankingProveedores->first();

        $decision = $proveedorTop
            ? "Priorizar compras con {$proveedorTop['nombre']} solo para productos que realmente estén en bajo stock o tengan alta rotación."
            : 'Registrar más compras con proveedores para poder comparar costos y comportamiento.';

        return <<<TXT
Diagnóstico:
El módulo de compras registra {$cantidadCompras} compras por un valor total de {$this->moneda($totalCompras)}. El promedio por compra es {$this->moneda($promedioCompra)}.

Datos encontrados:
Principales proveedores por historial de compra:
{$this->formatearRankingProveedores($rankingProveedores)}

Productos que requieren atención:
{$productoCriticoTexto}

Últimas compras:
{$ultimasCompras}

Hallazgos:
Las compras deben concentrarse en productos con stock crítico, productos de alta salida y proveedores con historial estable. Comprar sin revisar stock puede aumentar inventario inmovilizado y reducir liquidez.

Nivel de prioridad o riesgo:
Medio. Hay datos suficientes para orientar compras, pero la decisión debe cruzarse con stock mínimo, ventas recientes y disponibilidad del proveedor.

Recomendación administrativa:
{$decision}

Plan de acción:
1. Revisar productos sin stock y bajo stock antes de comprar.
2. Solicitar cotización a los 2 o 3 proveedores con mejor historial.
3. Priorizar productos de mayor rotación y evitar compras amplias de productos lentos.
4. Registrar observaciones de precio, calidad y entrega en cada compra.
5. Revisar semanalmente si las compras están generando ventas reales.

Decisión sugerida:
Comprar de forma selectiva, no general. La prioridad debe ser reponer productos críticos y negociar con proveedores que tengan mejor frecuencia, monto razonable y compras recientes.

Limitaciones de los datos:
El ERP no registra aún cotizaciones externas, tiempo de entrega ni calidad recibida. La recomendación se basa en compras, stock y movimientos registrados.

Fin de recomendación.
TXT;
    }

    private function respuestaStockProductos(string $pregunta): string
    {
        $productosActivos = Producto::where('estado', 'Activo')->count();
        $productosSinStock = Producto::where('estado', 'Activo')->where('stock_actual', '<=', 0)->orderBy('stock_actual')->limit(5)->get();
        $productosBajoStock = Producto::where('estado', 'Activo')->whereColumn('stock_actual', '<=', 'stock_minimo')->where('stock_actual', '>', 0)->orderBy('stock_actual')->limit(5)->get();
        $productosMasVendidos = $this->productosMasVendidos(5);
        $productoMencionado = $this->buscarProductoMencionado($pregunta);

        $detalleProducto = $productoMencionado
            ? $this->analizarProductoTexto($productoMencionado)
            : 'No se detectó un producto específico en la pregunta. Se analiza el inventario general.';

        $sinStockTexto = $this->formatearProductosParaStock($productosSinStock);
        $bajoStockTexto = $this->formatearProductosParaStock($productosBajoStock);
        $masVendidosTexto = $this->formatearProductosMasVendidos($productosMasVendidos);
        $prioridad = $productosSinStock->count() > 0 ? 'Alta' : ($productosBajoStock->count() > 0 ? 'Media' : 'Baja');

        return <<<TXT
Diagnóstico:
El inventario registra {$productosActivos} productos activos. Hay {$productosSinStock->count()} productos sin stock dentro de los principales revisados y {$productosBajoStock->count()} productos bajo stock dentro de los principales revisados.

Datos encontrados:
Producto consultado:
{$detalleProducto}

Productos sin stock:
{$sinStockTexto}

Productos bajo stock:
{$bajoStockTexto}

Productos más vendidos:
{$masVendidosTexto}

Hallazgos:
El stock debe priorizarse según tres criterios: producto sin stock, producto bajo mínimo y producto con alta venta. Un producto muy vendido con stock bajo debe comprarse antes que un producto con poca salida.

Nivel de prioridad o riesgo:
{$prioridad}. Si existen productos sin stock, la empresa puede perder ventas. Si solo hay bajo stock, se recomienda reposición planificada.

Recomendación administrativa:
Reponer primero productos sin stock y luego productos bajo mínimo. Para productos más vendidos, mantener stock de seguridad superior al mínimo.

Plan de acción:
1. Generar lista de reposición con productos sin stock.
2. Calcular cantidad sugerida usando al menos el doble del stock mínimo.
3. Revisar proveedor con mejor historial para esos productos.
4. Evitar comprar productos sin rotación reciente.
5. Revisar semanalmente movimientos de entrada y salida.

Decisión sugerida:
Realizar una compra selectiva de reposición para productos críticos y vigilar productos más vendidos para evitar quiebres de stock.

Limitaciones de los datos:
La recomendación usa stock, ventas y movimientos registrados. No incluye demanda externa, temporada, promociones ni tiempos de entrega del proveedor.

Fin de recomendación.
TXT;
    }

    private function respuestaVentasClientes(string $pregunta): string
    {
        $totalVentas = (float) Venta::sum('total');
        $cantidadVentas = Venta::count();
        $promedioVenta = $cantidadVentas > 0 ? $totalVentas / $cantidadVentas : 0;
        $productosMasVendidos = $this->productosMasVendidos(5);
        $clientesTop = $this->rankingClientes(5);
        $ultimasVentas = $this->ultimasVentasTexto(8);
        $clienteMencionado = $this->buscarClienteMencionado($pregunta);

        $detalleCliente = $clienteMencionado
            ? $this->analizarClienteTexto($clienteMencionado)
            : 'No se detectó un cliente específico. Se analiza el comportamiento general de ventas y clientes.';

        $prioridad = $totalVentas > 0 ? 'Media' : 'Alta';

        return <<<TXT
Diagnóstico:
El módulo de ventas registra {$cantidadVentas} ventas por un total de {$this->moneda($totalVentas)}. El promedio por venta es {$this->moneda($promedioVenta)}.

Datos encontrados:
Cliente consultado:
{$detalleCliente}

Clientes principales:
{$this->formatearRankingClientes($clientesTop)}

Productos más vendidos:
{$this->formatearProductosMasVendidos($productosMasVendidos)}

Últimas ventas:
{$ultimasVentas}

Hallazgos:
Las decisiones comerciales deben enfocarse en productos con mayor salida y clientes con compras frecuentes o montos altos. Si las ventas se concentran en pocos productos, debe cuidarse el stock de esos productos.

Nivel de prioridad o riesgo:
{$prioridad}. Si las ventas son bajas o dependen de pocos clientes, existe riesgo comercial y se deben reforzar acciones de venta.

Recomendación administrativa:
Impulsar productos más vendidos, revisar clientes con mayor movimiento y evitar promociones en productos sin stock o con margen insuficiente.

Plan de acción:
1. Identificar los 5 productos con mayor salida.
2. Revisar clientes con mayor monto de compra y frecuencia.
3. Crear estrategia comercial para productos con stock disponible.
4. Evitar vender por encima del stock disponible.
5. Revisar semanalmente ingresos y productos con baja rotación.

Decisión sugerida:
Priorizar ventas de productos con stock disponible y alta rotación, mientras se da seguimiento a clientes principales para mantener ingresos constantes.

Limitaciones de los datos:
El ERP registra ventas y clientes, pero no registra satisfacción del cliente, campañas comerciales, descuentos perdidos ni razones de no compra.

Fin de recomendación.
TXT;
    }

    private function respuestaBodega(string $pregunta): string
    {
        $entradas = MovimientoBodega::where('tipo_movimiento', 'Entrada')->count();
        $salidas = MovimientoBodega::where('tipo_movimiento', 'Salida')->count();
        $ajustes = MovimientoBodega::where('tipo_movimiento', 'Ajuste')->count();
        $totalMovimientos = MovimientoBodega::count();
        $ultimosMovimientos = $this->ultimosMovimientosBodegaTexto(10);
        $productosCriticos = $this->formatearProductosCriticos($this->productosCriticos());
        $riesgo = $ajustes > ($entradas + $salidas) * 0.25 ? 'Alto' : ($ajustes > 0 ? 'Medio' : 'Bajo');

        return <<<TXT
Diagnóstico:
Bodega registra {$totalMovimientos} movimientos: {$entradas} entradas, {$salidas} salidas y {$ajustes} ajustes.

Datos encontrados:
Últimos movimientos:
{$ultimosMovimientos}

Productos críticos relacionados con inventario:
{$productosCriticos}

Hallazgos:
Las entradas reflejan compras, las salidas reflejan ventas y los ajustes deben revisarse porque pueden indicar correcciones, errores de registro o regularización de inventario.

Nivel de prioridad o riesgo:
{$riesgo}. El riesgo aumenta cuando existen muchos ajustes o productos críticos sin reposición.

Recomendación administrativa:
Mantener control semanal de bodega, revisar productos con movimientos frecuentes y documentar todo ajuste de inventario con una observación clara.

Plan de acción:
1. Revisar productos con salidas frecuentes y bajo stock.
2. Validar que las entradas correspondan a compras reales.
3. Auditar ajustes de inventario y justificar cada corrección.
4. Comparar stock físico con stock del sistema.
5. Reponer productos críticos según ventas y mínimo definido.

Decisión sugerida:
Usar movimientos de bodega como control de alerta temprana para evitar pérdidas, descuadres de stock y ventas no atendidas.

Limitaciones de los datos:
El ERP registra movimientos, pero no confirma conteo físico real ni pérdidas por daño, caducidad o robo. Esos controles deben documentarse con inventarios físicos.

Fin de recomendación.
TXT;
    }

    private function respuestaTalentoHumano(string $pregunta): string
    {
        $empleado = $this->buscarEmpleadoMencionado($pregunta);
        $totalEmpleados = Empleado::count();
        $activos = Empleado::where('estado', 'Activo')->count();
        $suspendidos = Empleado::where('estado', 'Suspendido')->count();
        $inactivos = Empleado::whereIn('estado', ['Inactivo', 'Retirado'])->count();

        if (! $empleado) {
            $porDepartamento = $this->empleadosPorDepartamentoTexto();
            $noActivos = $this->empleadosNoActivosTexto();

            return <<<TXT
Diagnóstico:
Talento Humano registra {$totalEmpleados} empleados: {$activos} activos, {$suspendidos} suspendidos y {$inactivos} inactivos o retirados.

Datos encontrados:
Distribución por departamento:
{$porDepartamento}

Empleados con estado no activo:
{$noActivos}

Hallazgos:
No se detectó un empleado específico en la pregunta. Para una decisión individual, escriba nombre, apellido, cédula o código del empleado.

Nivel de prioridad o riesgo:
Medio. La empresa tiene datos básicos de empleados, pero no registra aún asistencia, puntualidad, sanciones históricas ni productividad individual.

Recomendación administrativa:
Crear indicadores formales para evaluar desempeño: asistencia, puntualidad, cumplimiento de tareas, errores operativos, observaciones del jefe inmediato y capacitaciones recibidas.

Plan de acción:
1. Seleccionar el empleado específico que se desea analizar.
2. Registrar métricas de desempeño y cumplimiento por cargo.
3. Revisar empleados suspendidos o inactivos antes de asignar responsabilidades.
4. Establecer seguimiento mensual por Talento Humano.
5. Documentar observaciones antes de cualquier sanción.

Decisión sugerida:
No tomar decisiones disciplinarias generales sin evidencia individual. Primero medir desempeño y luego aplicar seguimiento, capacitación o revisión administrativa.

Limitaciones de los datos:
El ERP registra información laboral básica, pero no evidencia todavía faltas, atrasos, sanciones ni productividad individual.

Fin de recomendación.
TXT;
        }

        $evaluacion = $this->evaluarEmpleadoParaDecision($empleado);
        $nombreCompleto = trim("{$empleado->nombres} {$empleado->apellidos}");
        $fechaIngreso = $this->formatearFecha($empleado->fecha_ingreso);
        $antiguedad = $this->calcularAntiguedad($empleado->fecha_ingreso);
        $hallazgos = implode("\n", $evaluacion['hallazgos']);
        $plan = implode("\n", $evaluacion['plan']);

        return <<<TXT
Diagnóstico:
Se analizó al empleado {$nombreCompleto} con los datos registrados en Talento Humano.

Datos encontrados:
Código: {$empleado->codigo_empleado}
Cédula: {$empleado->cedula}
Cargo: {$empleado->cargo}
Departamento: {$empleado->departamento}
Estado: {$empleado->estado}
Fecha de ingreso: {$fechaIngreso}
Antigüedad aproximada: {$antiguedad}
Sueldo registrado: {$this->moneda((float) $empleado->sueldo)}

Hallazgos:
{$hallazgos}

Nivel de prioridad o riesgo:
{$evaluacion['riesgo']}. {$evaluacion['motivo_riesgo']}

Recomendación administrativa:
{$evaluacion['recomendacion']}

Plan de acción:
{$plan}

Decisión sugerida:
{$evaluacion['decision']}

Limitaciones de los datos:
El ERP no registra todavía asistencia, puntualidad, sanciones históricas, atrasos ni productividad individual directa. Por eso no se debe concluir una falta grave sin evidencia adicional.

Fin de recomendación.
TXT;
    }

    private function respuestaGeneralEmpresa(string $pregunta): string
    {
        $totalCompras = (float) Compra::sum('total');
        $totalVentas = (float) Venta::sum('total');
        $resultado = $totalVentas - $totalCompras;
        $productosCriticos = $this->productosCriticos();
        $productosCriticosTexto = $this->formatearProductosCriticos($productosCriticos);
        $proveedoresTop = $this->formatearRankingProveedores($this->rankingProveedores()->take(3));
        $productosTop = $this->formatearProductosMasVendidos($this->productosMasVendidos(5));
        $riesgo = $resultado < 0 ? 'Alto' : ($productosCriticos->count() > 0 ? 'Medio' : 'Bajo');
        $decision = $resultado < 0
            ? 'Controlar compras generales y concentrarse en vender inventario actual antes de aumentar gastos.'
            : 'Reinvertir de forma selectiva en productos con mayor rotación y stock crítico.';

        return <<<TXT
Diagnóstico:
Distribuidora Solmunol registra ventas por {$this->moneda($totalVentas)} y compras por {$this->moneda($totalCompras)}. El resultado estimado ventas menos compras es {$this->moneda($resultado)}.

Datos encontrados:
Productos críticos:
{$productosCriticosTexto}

Proveedores principales:
{$proveedoresTop}

Productos más vendidos:
{$productosTop}

Hallazgos:
La empresa debe equilibrar ventas, compras y stock. Si se compra más de lo que se vende, aumenta el riesgo de inventario inmovilizado. Si hay productos críticos, puede perder ventas por falta de disponibilidad.

Nivel de prioridad o riesgo:
{$riesgo}. El riesgo se calcula según resultado estimado y productos críticos.

Recomendación administrativa:
{$decision}

Plan de acción:
1. Revisar productos críticos y productos más vendidos.
2. Comprar solo productos con rotación comprobada o bajo stock.
3. Analizar proveedores con mejor historial antes de nuevas compras.
4. Dar seguimiento a ventas por producto y cliente.
5. Revisar semanalmente dashboard, reportes y movimientos de bodega.

Decisión sugerida:
Tomar decisiones de compra y venta con base en rotación, stock mínimo y comportamiento de proveedores, no por intuición.

Limitaciones de los datos:
El ERP calcula con registros internos. No incluye mercado externo, competencia, promociones ni demanda futura real.

Fin de recomendación.
TXT;
    }

    private function rankingProveedores()
    {
        return Compra::query()
            ->selectRaw('proveedor_id, COUNT(*) as total_compras, SUM(total) as total_monto, SUM(cantidad) as total_unidades, MAX(fecha) as ultima_fecha')
            ->with('proveedor')
            ->groupBy('proveedor_id')
            ->get()
            ->map(function ($fila) {
                $proveedor = $fila->proveedor;
                $totalCompras = (int) $fila->total_compras;
                $totalMonto = (float) $fila->total_monto;
                $ultimaFecha = $fila->ultima_fecha;
                $estado = $proveedor->estado ?? 'No disponible';
                $diasUltimaCompra = $this->diasDesde($ultimaFecha);
                $recenciaPuntos = $diasUltimaCompra !== null ? max(0, 30 - min($diasUltimaCompra, 30)) : 0;
                $score = ($totalCompras * 5) + ($totalMonto / 100) + $recenciaPuntos + ($estado === 'Activo' ? 20 : 0);
                $riesgo = $estado !== 'Activo' ? 'Alto' : ($totalCompras >= 3 ? 'Bajo' : 'Medio');

                return [
                    'id' => $fila->proveedor_id,
                    'nombre' => $proveedor->nombre ?? 'Proveedor eliminado',
                    'estado' => $estado,
                    'total_compras' => $totalCompras,
                    'total_monto' => round($totalMonto, 2),
                    'total_unidades' => (int) $fila->total_unidades,
                    'ultima_fecha' => $ultimaFecha ? $this->formatearFecha($ultimaFecha) : 'Sin fecha',
                    'score' => round($score, 2),
                    'riesgo' => $riesgo,
                ];
            })
            ->sortByDesc('score')
            ->values();
    }

    private function analizarProveedor(Proveedor $proveedor): array
    {
        $compras = Compra::where('proveedor_id', $proveedor->id)->get();
        $cantidad = $compras->count();
        $total = (float) $compras->sum('total');
        $unidades = (int) $compras->sum('cantidad');
        $ultima = optional($compras->sortByDesc('fecha')->first())->fecha;
        $riesgo = $proveedor->estado !== 'Activo' ? 'Alto' : ($cantidad >= 3 ? 'Bajo' : 'Medio');
        $decision = $proveedor->estado !== 'Activo'
            ? "No realizar nuevas compras con {$proveedor->nombre} hasta actualizar o validar su estado."
            : "Mantener a {$proveedor->nombre} como opción de compra si sus precios y disponibilidad siguen siendo competitivos.";

        $texto = "{$proveedor->nombre}: estado {$proveedor->estado}, {$cantidad} compras registradas, {$unidades} unidades compradas, monto acumulado {$this->moneda($total)}, última compra {$this->formatearFecha($ultima)}.";

        return [
            'texto' => $texto,
            'decision' => $decision,
            'riesgo' => $riesgo,
        ];
    }

    private function formatearRankingProveedores($ranking): string
    {
        if ($ranking->isEmpty()) {
            return 'No hay compras suficientes para calcular ranking de proveedores.';
        }

        return $ranking->take(5)->values()->map(function ($item, $index) {
            $posicion = $index + 1;

            return "{$posicion}. {$item['nombre']} - estado {$item['estado']}, compras {$item['total_compras']}, unidades {$item['total_unidades']}, monto {$this->moneda($item['total_monto'])}, última compra {$item['ultima_fecha']}.";
        })->implode("\n");
    }

    private function productosMasVendidos(int $limite = 5)
    {
        return Venta::query()
            ->selectRaw('producto_id, SUM(cantidad) as total_vendido, SUM(total) as total_ingresos')
            ->with('producto')
            ->groupBy('producto_id')
            ->orderByDesc('total_vendido')
            ->limit($limite)
            ->get();
    }

    private function formatearProductosMasVendidos($items): string
    {
        if ($items->isEmpty()) {
            return 'No hay ventas suficientes para calcular productos más vendidos.';
        }

        return $items->values()->map(function ($item, $index) {
            $posicion = $index + 1;
            $producto = $item->producto;
            $nombre = $producto->nombre ?? 'Producto eliminado';
            $stock = $producto ? $producto->stock_actual : 'No disponible';

            return "{$posicion}. {$nombre} - {$item->total_vendido} unidades vendidas, ingresos {$this->moneda((float) $item->total_ingresos)}, stock actual {$stock}.";
        })->implode("\n");
    }

    private function productosCriticos()
    {
        return Producto::where('estado', 'Activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->orderBy('stock_actual')
            ->limit(8)
            ->get();
    }

    private function formatearProductosCriticos($productos): string
    {
        if ($productos->isEmpty()) {
            return 'No hay productos críticos según stock mínimo.';
        }

        return $productos->values()->map(function (Producto $producto, int $index) {
            $posicion = $index + 1;
            $cantidadSugerida = max(((int) $producto->stock_minimo * 2) - (int) $producto->stock_actual, 1);

            return "{$posicion}. {$producto->nombre} - stock {$producto->stock_actual}, mínimo {$producto->stock_minimo}, sugerido comprar {$cantidadSugerida} unidades.";
        })->implode("\n");
    }

    private function formatearProductosParaStock($productos): string
    {
        if ($productos->isEmpty()) {
            return 'No se encontraron productos en esta condición.';
        }

        return $productos->values()->map(function (Producto $producto, int $index) {
            $posicion = $index + 1;
            $cantidadSugerida = max(((int) $producto->stock_minimo * 2) - (int) $producto->stock_actual, 1);

            return "{$posicion}. {$producto->nombre} - categoría {$producto->categoria}, stock {$producto->stock_actual}, mínimo {$producto->stock_minimo}, compra sugerida {$cantidadSugerida}.";
        })->implode("\n");
    }

    private function analizarProductoTexto(Producto $producto): string
    {
        $ventas = Venta::where('producto_id', $producto->id)->count();
        $cantidadVendida = (int) Venta::where('producto_id', $producto->id)->sum('cantidad');
        $compras = Compra::where('producto_id', $producto->id)->count();
        $cantidadComprada = (int) Compra::where('producto_id', $producto->id)->sum('cantidad');
        $estadoStock = (int) $producto->stock_actual <= 0 ? 'Sin stock' : ((int) $producto->stock_actual <= (int) $producto->stock_minimo ? 'Bajo stock' : 'Stock aceptable');

        return "{$producto->nombre}: código {$producto->codigo}, categoría {$producto->categoria}, estado {$producto->estado}, stock {$producto->stock_actual}, mínimo {$producto->stock_minimo}, precio compra {$this->moneda((float) $producto->precio_compra)}, precio venta {$this->moneda((float) $producto->precio_venta)}, estado de stock {$estadoStock}, ventas registradas {$ventas}, unidades vendidas {$cantidadVendida}, compras registradas {$compras}, unidades compradas {$cantidadComprada}.";
    }

    private function rankingClientes(int $limite = 5)
    {
        return Venta::query()
            ->selectRaw('cliente_id, COUNT(*) as total_ventas, SUM(total) as total_monto, SUM(cantidad) as total_unidades, MAX(fecha) as ultima_fecha')
            ->with('cliente')
            ->groupBy('cliente_id')
            ->orderByDesc('total_monto')
            ->limit($limite)
            ->get();
    }

    private function formatearRankingClientes($clientes): string
    {
        if ($clientes->isEmpty()) {
            return 'No hay ventas suficientes para calcular clientes principales.';
        }

        return $clientes->values()->map(function ($item, int $index) {
            $posicion = $index + 1;
            $cliente = $item->cliente;
            $nombre = $cliente->nombre ?? 'Cliente eliminado';
            $estado = $cliente->estado ?? 'No disponible';

            return "{$posicion}. {$nombre} - estado {$estado}, ventas {$item->total_ventas}, unidades {$item->total_unidades}, monto {$this->moneda((float) $item->total_monto)}, última venta {$this->formatearFecha($item->ultima_fecha)}.";
        })->implode("\n");
    }

    private function analizarClienteTexto(Cliente $cliente): string
    {
        $ventas = Venta::where('cliente_id', $cliente->id)->get();
        $cantidad = $ventas->count();
        $total = (float) $ventas->sum('total');
        $unidades = (int) $ventas->sum('cantidad');
        $ultima = optional($ventas->sortByDesc('fecha')->first())->fecha;

        return "{$cliente->nombre}: estado {$cliente->estado}, documento {$cliente->cedula_ruc}, ventas registradas {$cantidad}, unidades compradas {$unidades}, monto acumulado {$this->moneda($total)}, última venta {$this->formatearFecha($ultima)}.";
    }

    private function ultimasComprasTexto(int $limite = 8): string
    {
        $compras = Compra::with(['proveedor', 'producto'])->orderByDesc('fecha')->orderByDesc('created_at')->limit($limite)->get();

        if ($compras->isEmpty()) {
            return 'No hay compras registradas.';
        }

        return $compras->map(function (Compra $compra) {
            $proveedor = $compra->proveedor->nombre ?? 'Proveedor eliminado';
            $producto = $compra->producto->nombre ?? 'Producto eliminado';

            return "{$this->formatearFecha($compra->fecha)}: {$compra->numero_compra}, proveedor {$proveedor}, producto {$producto}, cantidad {$compra->cantidad}, total {$this->moneda((float) $compra->total)}.";
        })->implode("\n");
    }

    private function ultimasVentasTexto(int $limite = 8): string
    {
        $ventas = Venta::with(['cliente', 'producto'])->orderByDesc('fecha')->orderByDesc('created_at')->limit($limite)->get();

        if ($ventas->isEmpty()) {
            return 'No hay ventas registradas.';
        }

        return $ventas->map(function (Venta $venta) {
            $cliente = $venta->cliente->nombre ?? 'Cliente eliminado';
            $producto = $venta->producto->nombre ?? 'Producto eliminado';

            return "{$this->formatearFecha($venta->fecha)}: {$venta->numero_venta}, cliente {$cliente}, producto {$producto}, cantidad {$venta->cantidad}, total {$this->moneda((float) $venta->total)}.";
        })->implode("\n");
    }

    private function ultimosMovimientosBodegaTexto(int $limite = 10): string
    {
        $movimientos = MovimientoBodega::with('producto')->orderByDesc('fecha')->orderByDesc('created_at')->limit($limite)->get();

        if ($movimientos->isEmpty()) {
            return 'No hay movimientos de bodega registrados.';
        }

        return $movimientos->map(function (MovimientoBodega $movimiento) {
            $producto = $movimiento->producto->nombre ?? 'Producto eliminado';

            return "{$this->formatearFecha($movimiento->fecha)}: {$movimiento->codigo_movimiento}, {$movimiento->tipo_movimiento}, producto {$producto}, cantidad {$movimiento->cantidad}, stock {$movimiento->stock_anterior} a {$movimiento->stock_nuevo}, origen {$movimiento->origen}.";
        })->implode("\n");
    }

    private function empleadosPorDepartamentoTexto(): string
    {
        $items = Empleado::query()
            ->selectRaw('departamento, COUNT(*) as total')
            ->groupBy('departamento')
            ->orderByDesc('total')
            ->get();

        if ($items->isEmpty()) {
            return 'No hay empleados registrados por departamento.';
        }

        return $items->map(fn ($fila) => "{$fila->departamento}: {$fila->total}")->implode("\n");
    }

    private function empleadosNoActivosTexto(): string
    {
        $empleados = Empleado::whereIn('estado', ['Suspendido', 'Inactivo', 'Retirado'])->orderBy('estado')->limit(10)->get();

        if ($empleados->isEmpty()) {
            return 'No hay empleados suspendidos, inactivos o retirados registrados.';
        }

        return $empleados->map(function (Empleado $empleado) {
            return "{$empleado->codigo_empleado} - {$empleado->nombres} {$empleado->apellidos}: {$empleado->cargo}, {$empleado->departamento}, estado {$empleado->estado}.";
        })->implode("\n");
    }

    private function evaluarEmpleadoParaDecision(Empleado $empleado): array
    {
        $estado = trim((string) $empleado->estado);
        $cargo = $this->normalizar((string) $empleado->cargo);
        $departamento = $this->normalizar((string) $empleado->departamento);
        $hallazgos = [];

        $hallazgos[] = "El empleado está registrado con estado {$empleado->estado}.";
        $hallazgos[] = "El cargo registrado es {$empleado->cargo} y el departamento registrado es {$empleado->departamento}.";

        $inconsistencia = $this->hayInconsistenciaCargoDepartamento($cargo, $departamento);

        if ($inconsistencia) {
            $hallazgos[] = 'Se detecta una posible inconsistencia entre cargo y departamento. Esto no prueba incumplimiento del empleado, pero sí exige revisión administrativa.';
        } else {
            $hallazgos[] = 'No se detecta inconsistencia evidente entre cargo y departamento con los datos disponibles.';
        }

        if ($estado === 'Suspendido') {
            return [
                'riesgo' => 'Alto',
                'motivo_riesgo' => 'El empleado figura como suspendido y requiere revisión formal antes de asignarle responsabilidades.',
                'hallazgos' => $hallazgos,
                'recomendacion' => 'Revisar el caso con Talento Humano, documentar el motivo de suspensión y verificar si corresponde mantenerla, levantarla o aplicar plan de mejora.',
                'plan' => [
                    '1. Revisar motivo administrativo de la suspensión.',
                    '2. Confirmar evidencias con el responsable de área.',
                    '3. Citar al empleado o supervisor para aclaración.',
                    '4. Definir seguimiento, capacitación o medida disciplinaria según reglamento.',
                    '5. Actualizar el estado del empleado en el sistema.',
                ],
                'decision' => 'No asignar nuevas responsabilidades hasta que Talento Humano revise y documente el caso.',
            ];
        }

        if (in_array($estado, ['Inactivo', 'Retirado'], true)) {
            return [
                'riesgo' => 'Alto',
                'motivo_riesgo' => "El empleado figura como {$estado}, por lo que no debe considerarse operativo.",
                'hallazgos' => $hallazgos,
                'recomendacion' => 'Validar que no tenga responsabilidades activas, accesos al sistema o tareas pendientes.',
                'plan' => [
                    '1. Confirmar estado laboral real.',
                    '2. Retirar accesos operativos si corresponde.',
                    '3. Revisar tareas pendientes asociadas.',
                    '4. Actualizar registros de Talento Humano.',
                    '5. Mantener evidencia documental del cambio de estado.',
                ],
                'decision' => 'Excluirlo de actividades operativas y revisar cierre administrativo.',
            ];
        }

        if ($inconsistencia) {
            return [
                'riesgo' => 'Medio',
                'motivo_riesgo' => 'Existe posible inconsistencia de datos entre cargo y departamento, pero no hay evidencia de incumplimiento laboral.',
                'hallazgos' => $hallazgos,
                'recomendacion' => 'Revisar si el cargo corresponde a las funciones reales del empleado y corregir el registro si fue error de digitación.',
                'plan' => [
                    '1. Confirmar funciones reales con el responsable de área.',
                    '2. Corregir cargo o departamento si el dato está mal registrado.',
                    '3. Definir responsabilidades medibles para 30 días.',
                    '4. Registrar observaciones de cumplimiento en Talento Humano.',
                    '5. Reevaluar después del periodo de seguimiento.',
                ],
                'decision' => 'Aplicar revisión administrativa y seguimiento, no sanción.',
            ];
        }

        return [
            'riesgo' => 'Bajo',
            'motivo_riesgo' => 'El empleado está activo y no se observan anomalías críticas con los datos disponibles.',
            'hallazgos' => $hallazgos,
            'recomendacion' => 'Mantenerlo activo y crear indicadores de seguimiento para evaluar mejor su desempeño.',
            'plan' => [
                '1. Mantener al empleado en sus funciones actuales.',
                '2. Registrar asistencia, puntualidad y cumplimiento de tareas.',
                '3. Definir metas por cargo o departamento.',
                '4. Revisar desempeño al cierre del mes.',
                '5. Considerar capacitación solo si aparecen errores repetidos.',
            ],
            'decision' => 'Continuar con seguimiento normal y no aplicar sanción.',
        ];
    }

    private function hayInconsistenciaCargoDepartamento(string $cargo, string $departamento): bool
    {
        $reglas = [
            ['cargos' => ['vendedor', 'ventas', 'cajero', 'comercial'], 'departamentos' => ['ventas', 'comercial']],
            ['cargos' => ['bodeguero', 'bodega', 'inventario', 'almacen'], 'departamentos' => ['bodega', 'logistica', 'inventario']],
            ['cargos' => ['contador', 'contable', 'financiero'], 'departamentos' => ['contabilidad', 'finanzas', 'administracion']],
            ['cargos' => ['sistemas', 'soporte', 'tecnico', 'programador'], 'departamentos' => ['sistemas', 'tecnologia', 'ti']],
            ['cargos' => ['recursos humanos', 'talento humano', 'rrhh'], 'departamentos' => ['talento humano', 'recursos humanos', 'rrhh']],
        ];

        foreach ($reglas as $regla) {
            $cargoCoincide = collect($regla['cargos'])->contains(fn ($palabra) => str_contains($cargo, $palabra));

            if ($cargoCoincide) {
                $departamentoCoincide = collect($regla['departamentos'])->contains(fn ($palabra) => str_contains($departamento, $palabra));

                return ! $departamentoCoincide;
            }
        }

        return false;
    }

    private function buscarEmpleadoMencionado(string $pregunta): ?Empleado
    {
        $texto = $this->normalizar($pregunta);

        if ($texto === '') {
            return null;
        }

        try {
            return Empleado::query()->limit(500)->get()->first(function (Empleado $empleado) use ($texto) {
                $nombreCompleto = $this->normalizar(trim("{$empleado->nombres} {$empleado->apellidos}"));
                $nombres = $this->normalizar((string) $empleado->nombres);
                $apellidos = $this->normalizar((string) $empleado->apellidos);
                $primerNombre = $this->normalizar((string) explode(' ', (string) $empleado->nombres)[0]);
                $codigo = $this->normalizar((string) $empleado->codigo_empleado);
                $cedula = $this->normalizar((string) $empleado->cedula);

                return ($nombreCompleto !== '' && str_contains($texto, $nombreCompleto))
                    || ($nombres !== '' && str_contains($texto, $nombres))
                    || ($apellidos !== '' && str_contains($texto, $apellidos))
                    || ($primerNombre !== '' && str_contains($texto, $primerNombre))
                    || ($codigo !== '' && str_contains($texto, $codigo))
                    || ($cedula !== '' && str_contains($texto, $cedula));
            });
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buscarProveedorMencionado(string $pregunta): ?Proveedor
    {
        $texto = $this->normalizar($pregunta);

        try {
            return Proveedor::query()->limit(500)->get()->first(function (Proveedor $proveedor) use ($texto) {
                return str_contains($texto, $this->normalizar((string) $proveedor->nombre))
                    || str_contains($texto, $this->normalizar((string) $proveedor->ruc));
            });
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buscarProductoMencionado(string $pregunta): ?Producto
    {
        $texto = $this->normalizar($pregunta);

        try {
            return Producto::query()->limit(500)->get()->first(function (Producto $producto) use ($texto) {
                return str_contains($texto, $this->normalizar((string) $producto->nombre))
                    || str_contains($texto, $this->normalizar((string) $producto->codigo));
            });
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buscarClienteMencionado(string $pregunta): ?Cliente
    {
        $texto = $this->normalizar($pregunta);

        try {
            return Cliente::query()->limit(500)->get()->first(function (Cliente $cliente) use ($texto) {
                return str_contains($texto, $this->normalizar((string) $cliente->nombre))
                    || str_contains($texto, $this->normalizar((string) $cliente->cedula_ruc));
            });
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function diasDesde($fecha): ?int
    {
        try {
            if (! $fecha) {
                return null;
            }

            return Carbon::parse($fecha)->diffInDays(now());
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function calcularAntiguedad($fecha): string
    {
        try {
            if (! $fecha) {
                return 'No calculada';
            }

            $dias = Carbon::parse($fecha)->diffInDays(now());

            if ($dias >= 365) {
                $anios = round($dias / 365, 1);
                return "{$anios} año(s) aproximadamente";
            }

            return "{$dias} días aproximadamente";
        } catch (\Throwable $e) {
            return 'No calculada';
        }
    }

    private function formatearFecha($fecha): string
    {
        try {
            if (! $fecha) {
                return 'Sin fecha';
            }

            return Carbon::parse($fecha)->format('d/m/Y');
        } catch (\Throwable $e) {
            return 'Sin fecha';
        }
    }

    private function moneda(float $valor): string
    {
        return '$' . number_format($valor, 2, '.', ',');
    }

    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n',
        ]);

        return preg_replace('/\s+/', ' ', $texto) ?: '';
    }

    private function respuestaErrorControlado(): string
    {
        return <<<TXT
Diagnóstico:
No se pudo completar el análisis automático por un error temporal al consultar los datos del ERP.

Datos encontrados:
La solicitud pertenece a la lógica de Distribuidora Solmunol, pero el servicio no logró procesar toda la información.

Hallazgos:
Puede existir una consulta pesada, un campo faltante o una conexión temporal lenta con la base de datos.

Nivel de prioridad o riesgo:
Medio. La IA no debe tomar decisiones sin datos completos.

Recomendación administrativa:
Reintentar la consulta y, si persiste, revisar los logs del sistema.

Plan de acción:
1. Verificar conexión con la base de datos.
2. Revisar si el módulo consultado tiene registros.
3. Revisar logs de Render o Laravel.
4. Probar con una pregunta más específica.
5. Validar que el usuario tenga permisos de Administrador o Directivo.

Decisión sugerida:
No tomar una decisión definitiva hasta obtener datos completos del ERP.

Limitaciones de los datos:
El análisis no pudo completarse en esta solicitud.

Fin de recomendación.
TXT;
    }
}
