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
use Illuminate\Support\Facades\Http;

class DecisionAiService
{
    public function responder(string $pregunta): string
    {
        $pregunta = trim($pregunta);

        if (! $this->preguntaPermitida($pregunta)) {
            return 'Solo puedo responder preguntas relacionadas con la lógica de Distribuidora Solmunol.';
        }

        $apiKey = config('services.google_ai.api_key');
        $model = config('services.google_ai.model', 'gemini-2.5-flash');

        if (! $apiKey) {
            return $this->respuestaLocalDirectiva(
                'No se encontró la API key de Google AI, pero el ERP generó una decisión automática con los datos registrados.',
                $pregunta
            );
        }

        $contexto = $this->generarContextoSistema($pregunta);
        $decisionesAutomaticas = $this->generarDecisionesAutomaticas($pregunta);

        $prompt = <<<PROMPT
Eres el asistente directivo inteligente de Distribuidora Solmunol.

Tu función es ayudar a tomar decisiones concretas para mejorar la empresa usando los datos reales del ERP.
Puedes analizar compras, ventas, productos, stock, bodega, clientes, proveedores, reportes, dashboard directivo, empleados, talento humano, desempeño laboral, anomalías administrativas y toma de decisiones empresariales.

No eres un asistente de ideas generales.
Debes decir qué decisión tomar, qué acción hacer y qué evitar.

Reglas:
1. Solo responde preguntas relacionadas con Distribuidora Solmunol y sus módulos del ERP.
2. No respondas cultura general, recetas, deportes, política, medicina, historia, entretenimiento ni tareas externas.
3. Si la pregunta no es del ERP, responde exactamente:
Solo puedo responder preguntas relacionadas con la lógica de Distribuidora Solmunol.
4. Responde en español.
5. No inventes datos.
6. Usa únicamente el contexto del sistema.
7. Si hay pocos datos, igual da una decisión, pero aclara que la precisión mejorará con más registros.
8. No uses asteriscos ni markdown.
9. Responde corto y fácil de entender.
10. Usa frases directas como: "debes", "la decisión es", "se debe", "conviene".
11. Cuando analices empleados, no ordenes despidos, multas o sanciones definitivas. Recomienda acciones prudentes como capacitación, seguimiento, advertencia documentada, revisión por Talento Humano, evaluación de desempeño o revisión según reglamento interno.
12. Si no existen métricas suficientes del empleado, aclara que el ERP aún no registra asistencia, puntualidad o productividad individual suficiente y recomienda crear seguimiento.
13. Termina siempre con: Fin de recomendación.

Contexto del ERP:
{$contexto}

Decisiones automáticas calculadas:
{$decisionesAutomaticas}

Pregunta del usuario:
{$pregunta}

Responde exactamente así:

Decisión principal:
Escribe la decisión clara.

Motivo:
Explica por qué en una frase.

Acción inmediata:
Di qué debe hacer ahora.

Qué evitar:
Di qué no debe hacer.

Dato a vigilar:
Indica qué dato debe revisar.

Fin de recomendación.
PROMPT;

        try {
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
                                [
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 700,
                    ],
                ]);

            if (! $response->successful()) {
                return $this->respuestaLocalDirectiva(
                    'Google AI no respondió correctamente, pero el ERP generó una decisión automática con los datos actuales.',
                    $pregunta
                );
            }

            $texto = data_get($response->json(), 'candidates.0.content.parts.0.text');

            if (! $texto) {
                return $this->respuestaLocalDirectiva(
                    'Google AI no generó texto válido, pero el ERP calculó una decisión automática.',
                    $pregunta
                );
            }

            $texto = $this->limpiarTextoIa($texto);

            if (! str_contains($texto, 'Fin de recomendación.')) {
                $texto .= "\nFin de recomendación.";
            }

            return $texto;
        } catch (\Throwable $e) {
            return $this->respuestaLocalDirectiva(
                'No se pudo conectar con Google AI, pero el ERP generó una decisión automática con los datos registrados.',
                $pregunta
            );
        }
    }

    private function limpiarTextoIa(string $texto): string
    {
        $texto = trim($texto);
        $texto = str_replace(['**', '###', '##', '#'], '', $texto);
        $texto = preg_replace('/^\s*[-•]\s*/m', '', $texto);
        $texto = preg_replace('/\n{3,}/', "\n\n", $texto);

        return trim($texto);
    }

    private function preguntaPermitida(string $pregunta): bool
    {
        $texto = mb_strtolower($pregunta, 'UTF-8');

        $palabrasBloqueadas = [
            'capital',
            'francia',
            'receta',
            'pollo frito',
            'segunda guerra',
            'historia mundial',
            'medicina',
            'doctor',
            'política',
            'politica',
            'religión',
            'religion',
            'fútbol',
            'futbol',
            'película',
            'pelicula',
            'anime',
            'canción',
            'cancion',
            'rutina de ejercicios',
            'ejercicios en casa',
        ];

        foreach ($palabrasBloqueadas as $palabra) {
            if (str_contains($texto, $palabra)) {
                return false;
            }
        }

        $palabrasPermitidas = [
            'erp',
            'solmunol',
            'sistema',
            'módulo',
            'modulo',
            'compra',
            'compras',
            'venta',
            'ventas',
            'producto',
            'productos',
            'stock',
            'inventario',
            'bodega',
            'kardex',
            'movimiento',
            'movimientos',
            'cliente',
            'clientes',
            'proveedor',
            'proveedores',
            'reporte',
            'reportes',
            'dashboard',
            'directivo',
            'operativo',
            'administrador',
            'rol',
            'roles',
            'decisión',
            'decision',
            'decisiones',
            'decidir',
            'ganancia',
            'resultado',
            'utilidad',
            'rentabilidad',
            'sin stock',
            'bajo stock',
            'excel',
            'pdf',
            'reponer',
            'reposición',
            'reposicion',
            'vender',
            'comprar',
            'egresos',
            'ingresos',
            'kpis',
            'indicadores',
            'toma de decisiones',
            'valor de inventario',
            'productos críticos',
            'productos criticos',
            'más vendido',
            'mas vendido',
            'predicción',
            'prediccion',
            'pronóstico',
            'pronostico',
            'historial',
            'histórico',
            'historico',
            'tendencia',
            'proyección',
            'proyeccion',
            'mes siguiente',
            'próximo mes',
            'proximo mes',
            'siguiente mes',
            'analiza',
            'analizar',
            'recomienda',
            'recomendacion',
            'recomendación',
            'mejorar',
            'empresa',
            'negocio',
            'estrategia',
            'estratégica',
            'estrategica',
            'qué hago',
            'que hago',
            'qué debo hacer',
            'que debo hacer',
            'acción',
            'accion',
            'acciones',
            'crecer',
            'priorizar',
            'urgente',
            'controlar',
            'optimizar',

            // Talento humano y empleados
            'empleado',
            'empleados',
            'talento humano',
            'personal',
            'trabajador',
            'trabajadores',
            'colaborador',
            'colaboradores',
            'desempeño',
            'desempeno',
            'responsabilidad',
            'responsabilidades',
            'cumpliendo',
            'cumplimiento',
            'productividad',
            'rendimiento',
            'anomalía',
            'anomalia',
            'anomalías',
            'anomalias',
            'sanción',
            'sancion',
            'sanciones',
            'advertencia',
            'llamado de atención',
            'llamado de atencion',
            'capacitación',
            'capacitacion',
            'multa',
            'despido',
            'despedir',
            'contrato',
            'cargo',
            'área',
            'area',
            'departamento',
            'asistencia',
            'atrasos',
            'puntualidad',
            'plan de mejora',
            'evaluación',
            'evaluacion',
            'seguimiento',
            'talento',
            'humano',
        ];

        foreach ($palabrasPermitidas as $palabra) {
            if (str_contains($texto, $palabra)) {
                return true;
            }
        }

        try {
            return Empleado::query()
                ->select(['codigo_empleado', 'nombres', 'apellidos'])
                ->limit(300)
                ->get()
                ->contains(function (Empleado $empleado) use ($texto) {
                    $nombreCompleto = mb_strtolower(trim("{$empleado->nombres} {$empleado->apellidos}"), 'UTF-8');
                    $primerNombre = mb_strtolower(trim((string) explode(' ', (string) $empleado->nombres)[0]), 'UTF-8');
                    $codigo = mb_strtolower((string) $empleado->codigo_empleado, 'UTF-8');

                    return ($nombreCompleto !== '' && str_contains($texto, $nombreCompleto))
                        || ($primerNombre !== '' && str_contains($texto, $primerNombre))
                        || ($codigo !== '' && str_contains($texto, $codigo));
                });
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function generarContextoSistema(string $pregunta = ''): string
    {
        $totalCompras = round((float) Compra::sum('total'), 2);
        $totalVentas = round((float) Venta::sum('total'), 2);
        $resultadoEstimado = round($totalVentas - $totalCompras, 2);

        $clientesActivos = Cliente::where('estado', 'Activo')->count();
        $proveedoresActivos = Proveedor::where('estado', 'Activo')->count();
        $empleadosActivos = Empleado::where('estado', 'Activo')->count();
        $productosActivos = Producto::where('estado', 'Activo')->count();

        $productosBajoStock = Producto::where('estado', 'Activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->where('stock_actual', '>', 0)
            ->count();

        $productosSinStock = Producto::where('estado', 'Activo')
            ->where('stock_actual', '<=', 0)
            ->count();

        $valorInventario = Producto::where('estado', 'Activo')
            ->get()
            ->sum(function ($producto) {
                return (float) $producto->stock_actual * (float) $producto->precio_compra;
            });

        $valorInventario = round((float) $valorInventario, 2);

        $entradas = MovimientoBodega::where('tipo_movimiento', 'Entrada')->count();
        $salidas = MovimientoBodega::where('tipo_movimiento', 'Salida')->count();
        $ajustes = MovimientoBodega::where('tipo_movimiento', 'Ajuste')->count();

        $productosCriticos = Producto::where('estado', 'Activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->orderBy('stock_actual')
            ->limit(5)
            ->get()
            ->map(function ($producto) {
                return "{$producto->nombre}: stock {$producto->stock_actual}, mínimo {$producto->stock_minimo}";
            })
            ->implode("\n");

        $productosMasVendidos = Venta::query()
            ->selectRaw('producto_id, SUM(cantidad) as total_vendido, SUM(total) as total_ingresos')
            ->with('producto')
            ->groupBy('producto_id')
            ->orderByDesc('total_vendido')
            ->limit(5)
            ->get()
            ->map(function ($venta) {
                $nombre = $venta->producto->nombre ?? 'Producto eliminado';

                return "{$nombre}: {$venta->total_vendido} unidades vendidas, ingresos {$venta->total_ingresos}";
            })
            ->implode("\n");

        $ventasPorMes = Venta::all()
            ->groupBy(function ($venta) {
                return $this->formatearMes($venta->fecha);
            })
            ->map(function ($ventas, $mes) {
                return [
                    'mes' => $mes,
                    'cantidad' => $ventas->sum('cantidad'),
                    'total' => round((float) $ventas->sum('total'), 2),
                ];
            })
            ->sortBy('mes')
            ->values();

        $comprasPorMes = Compra::all()
            ->groupBy(function ($compra) {
                return $this->formatearMes($compra->fecha);
            })
            ->map(function ($compras, $mes) {
                return [
                    'mes' => $mes,
                    'cantidad' => $compras->sum('cantidad'),
                    'total' => round((float) $compras->sum('total'), 2),
                ];
            })
            ->sortBy('mes')
            ->values();

        $ultimosTresMesesVentas = $ventasPorMes->take(-3);
        $ultimosTresMesesCompras = $comprasPorMes->take(-3);

        $prediccionVentasProximoMes = $ultimosTresMesesVentas->count() > 0
            ? round((float) $ultimosTresMesesVentas->avg('total'), 2)
            : 0;

        $prediccionComprasProximoMes = $ultimosTresMesesCompras->count() > 0
            ? round((float) $ultimosTresMesesCompras->avg('total'), 2)
            : 0;

        $ultimasVentas = Venta::with(['cliente', 'producto'])
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get()
            ->map(function ($venta) {
                $fecha = $this->formatearFecha($venta->fecha);
                $cliente = $venta->cliente->nombre ?? 'Cliente eliminado';
                $producto = $venta->producto->nombre ?? 'Producto eliminado';

                return "{$fecha}: venta {$venta->numero_venta}, cliente {$cliente}, producto {$producto}, cantidad {$venta->cantidad}, total {$venta->total}";
            })
            ->implode("\n");

        $ultimasCompras = Compra::with(['proveedor', 'producto'])
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get()
            ->map(function ($compra) {
                $fecha = $this->formatearFecha($compra->fecha);
                $proveedor = $compra->proveedor->nombre ?? 'Proveedor eliminado';
                $producto = $compra->producto->nombre ?? 'Producto eliminado';

                return "{$fecha}: compra {$compra->numero_compra}, proveedor {$proveedor}, producto {$producto}, cantidad {$compra->cantidad}, total {$compra->total}";
            })
            ->implode("\n");

        $resumenEmpleados = $this->generarContextoTalentoHumano($pregunta);

        if ($productosCriticos === '') {
            $productosCriticos = 'No hay productos críticos.';
        }

        if ($productosMasVendidos === '') {
            $productosMasVendidos = 'No hay ventas suficientes para calcular productos más vendidos.';
        }

        if ($ultimasVentas === '') {
            $ultimasVentas = 'No hay ventas registradas.';
        }

        if ($ultimasCompras === '') {
            $ultimasCompras = 'No hay compras registradas.';
        }

        return <<<CTX
Resumen general:
Compras totales: {$totalCompras}
Ventas totales: {$totalVentas}
Resultado estimado: {$resultadoEstimado}
Valor aproximado de inventario: {$valorInventario}
Clientes activos: {$clientesActivos}
Proveedores activos: {$proveedoresActivos}
Empleados activos: {$empleadosActivos}
Productos activos: {$productosActivos}
Productos bajo stock: {$productosBajoStock}
Productos sin stock: {$productosSinStock}

Talento humano:
{$resumenEmpleados}

Movimientos de bodega:
Entradas: {$entradas}
Salidas: {$salidas}
Ajustes: {$ajustes}

Predicción simple:
Ventas estimadas próximo mes: {$prediccionVentasProximoMes}
Compras estimadas próximo mes: {$prediccionComprasProximoMes}

Productos críticos:
{$productosCriticos}

Productos más vendidos:
{$productosMasVendidos}

Últimas ventas:
{$ultimasVentas}

Últimas compras:
{$ultimasCompras}
CTX;
    }

    private function generarContextoTalentoHumano(string $pregunta = ''): string
    {
        $totalEmpleados = Empleado::count();
        $empleadosActivos = Empleado::where('estado', 'Activo')->count();
        $empleadosSuspendidos = Empleado::where('estado', 'Suspendido')->count();
        $empleadosInactivos = Empleado::whereIn('estado', ['Inactivo', 'Retirado'])->count();

        $porDepartamento = Empleado::query()
            ->selectRaw('departamento, COUNT(*) as total')
            ->groupBy('departamento')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($fila) => "{$fila->departamento}: {$fila->total}")
            ->implode("\n");

        $porCargo = Empleado::query()
            ->selectRaw('cargo, COUNT(*) as total')
            ->groupBy('cargo')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($fila) => "{$fila->cargo}: {$fila->total}")
            ->implode("\n");

        $empleadosNoActivos = Empleado::whereIn('estado', ['Suspendido', 'Inactivo', 'Retirado'])
            ->orderBy('estado')
            ->limit(10)
            ->get()
            ->map(function (Empleado $empleado) {
                return "{$empleado->codigo_empleado} - {$empleado->nombres} {$empleado->apellidos}: {$empleado->cargo}, {$empleado->departamento}, estado {$empleado->estado}";
            })
            ->implode("\n");

        $empleadoMencionado = $this->buscarEmpleadoMencionado($pregunta);
        $detalleEmpleado = $empleadoMencionado
            ? $this->formatearDetalleEmpleado($empleadoMencionado)
            : 'No se detectó un empleado específico en la pregunta. Para análisis individual, incluya nombre, apellido o código del empleado.';

        if ($porDepartamento === '') {
            $porDepartamento = 'No hay empleados registrados por departamento.';
        }

        if ($porCargo === '') {
            $porCargo = 'No hay empleados registrados por cargo.';
        }

        if ($empleadosNoActivos === '') {
            $empleadosNoActivos = 'No hay empleados suspendidos, inactivos o retirados registrados.';
        }

        return <<<TXT
Total de empleados: {$totalEmpleados}
Empleados activos: {$empleadosActivos}
Empleados suspendidos: {$empleadosSuspendidos}
Empleados inactivos o retirados: {$empleadosInactivos}

Empleados por departamento:
{$porDepartamento}

Empleados por cargo:
{$porCargo}

Empleados con estado no activo:
{$empleadosNoActivos}

Empleado mencionado:
{$detalleEmpleado}

Limitación importante:
El ERP registra datos laborales básicos del empleado, pero no registra todavía asistencia, puntualidad, sanciones históricas, productividad individual por empleado ni cumplimiento de tareas. Las decisiones de talento humano deben tomarse como recomendación preventiva y revisarse con Talento Humano.
TXT;
    }

    private function generarDecisionesAutomaticas(string $pregunta = ''): string
    {
        $decisiones = [];

        if ($this->esPreguntaTalentoHumano($pregunta)) {
            $decisiones = array_merge($decisiones, $this->generarDecisionesTalentoHumano($pregunta));
        }

        $totalCompras = (float) Compra::sum('total');
        $totalVentas = (float) Venta::sum('total');
        $resultadoEstimado = $totalVentas - $totalCompras;

        $productosSinStock = Producto::where('estado', 'Activo')
            ->where('stock_actual', '<=', 0)
            ->orderBy('stock_actual')
            ->limit(3)
            ->get();

        $productosBajoStock = Producto::where('estado', 'Activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->where('stock_actual', '>', 0)
            ->orderBy('stock_actual')
            ->limit(3)
            ->get();

        $productoMasVendido = Venta::query()
            ->selectRaw('producto_id, SUM(cantidad) as total_vendido')
            ->with('producto')
            ->groupBy('producto_id')
            ->orderByDesc('total_vendido')
            ->first();

        foreach ($productosSinStock as $producto) {
            $cantidadSugerida = max(((int) $producto->stock_minimo * 2), 1);

            $decisiones[] = "Comprar urgente {$cantidadSugerida} unidades de {$producto->nombre}, porque está sin stock.";
        }

        foreach ($productosBajoStock as $producto) {
            $cantidadSugerida = max((((int) $producto->stock_minimo * 2) - (int) $producto->stock_actual), 1);

            $decisiones[] = "Reponer {$cantidadSugerida} unidades de {$producto->nombre}, porque está cerca del mínimo.";
        }

        if ($productoMasVendido && $productoMasVendido->producto) {
            $producto = $productoMasVendido->producto;

            $decisiones[] = "Priorizar {$producto->nombre}, porque es el producto con mayor salida: {$productoMasVendido->total_vendido} unidades vendidas.";

            if ((int) $producto->stock_actual <= (int) $producto->stock_minimo) {
                $cantidadSugerida = max((((int) $producto->stock_minimo * 2) - (int) $producto->stock_actual), 1);

                $decisiones[] = "Comprar {$cantidadSugerida} unidades adicionales de {$producto->nombre}, porque vende bien y está cerca del mínimo.";
            } else {
                $decisiones[] = "Vigilar semanalmente el stock de {$producto->nombre}, porque vende bien.";
            }
        }

        if ($resultadoEstimado < 0) {
            $decisiones[] = 'No aumentar compras generales. Se debe vender primero el inventario actual.';
        } elseif ($resultadoEstimado > 0) {
            $decisiones[] = 'Reinvertir solo en productos con mayor salida. Evitar compras innecesarias.';
        } else {
            $decisiones[] = 'Registrar más ventas y compras antes de tomar decisiones grandes.';
        }

        if ($totalVentas <= 0) {
            $decisiones[] = 'Registrar ventas reales antes de hacer predicciones.';
        }

        if ($totalCompras <= 0) {
            $decisiones[] = 'Registrar compras reales para calcular costos e inventario.';
        }

        if (empty($decisiones)) {
            $decisiones[] = 'Mantener control semanal de ventas, compras, stock y talento humano.';
        }

        return implode("\n", $decisiones);
    }

    private function generarDecisionesTalentoHumano(string $pregunta): array
    {
        $decisiones = [];
        $empleado = $this->buscarEmpleadoMencionado($pregunta);

        if ($empleado) {
            $nombreCompleto = trim("{$empleado->nombres} {$empleado->apellidos}");
            $estado = (string) $empleado->estado;
            $departamento = (string) $empleado->departamento;
            $cargo = (string) $empleado->cargo;

            if ($estado === 'Activo') {
                $decisiones[] = "Mantener a {$nombreCompleto} activo, pero abrir seguimiento de desempeño de 30 días si se reportan dudas sobre sus responsabilidades.";
            } elseif ($estado === 'Suspendido') {
                $decisiones[] = "Revisar con Talento Humano el caso de {$nombreCompleto}, porque su estado es Suspendido antes de tomar una decisión definitiva.";
            } elseif (in_array($estado, ['Inactivo', 'Retirado'], true)) {
                $decisiones[] = "No asignar nuevas responsabilidades a {$nombreCompleto}, porque su estado actual es {$estado}.";
            }

            $decisiones[] = "Evaluar a {$nombreCompleto} según cargo {$cargo} y departamento {$departamento}, comparando tareas asignadas, registros y cumplimiento.";
            $decisiones[] = "No recomendar despido ni multa automática para {$nombreCompleto}; primero documentar evidencia, errores, asistencias y productividad.";

            return $decisiones;
        }

        $suspendidos = Empleado::where('estado', 'Suspendido')->count();
        $noActivos = Empleado::whereIn('estado', ['Inactivo', 'Retirado'])->count();

        if ($suspendidos > 0) {
            $decisiones[] = "Revisar {$suspendidos} empleado(s) suspendido(s) con Talento Humano antes de tomar decisiones definitivas.";
        }

        if ($noActivos > 0) {
            $decisiones[] = "Separar del análisis operativo a {$noActivos} empleado(s) inactivos o retirados.";
        }

        $decisiones[] = 'Crear seguimiento mensual de desempeño para empleados activos, incluyendo asistencia, puntualidad, errores y cumplimiento de tareas.';
        $decisiones[] = 'No aplicar sanciones sin evidencia registrada en el sistema y revisión de Talento Humano.';

        return $decisiones;
    }

    private function respuestaLocalDirectiva(string $motivoFallback, string $pregunta = ''): string
    {
        $decisiones = $this->generarDecisionesAutomaticas($pregunta);
        $lineaPrincipal = strtok($decisiones, "\n") ?: 'Mantener control semanal de ventas, compras, stock y talento humano.';

        if ($this->esPreguntaTalentoHumano($pregunta)) {
            return <<<TXT
Decisión principal:
{$lineaPrincipal}

Motivo:
{$motivoFallback} Para empleados, el ERP permite revisar datos básicos como estado, cargo, departamento, sueldo y fecha de ingreso, pero no debe decidir despidos o sanciones definitivas sin evidencia adicional.

Acción inmediata:
Revisa el estado del empleado, documenta observaciones y abre seguimiento por Talento Humano durante 30 días si existe duda de cumplimiento.

Qué evitar:
No apliques despido, multa o sanción definitiva solo por una respuesta automática de la IA.

Dato a vigilar:
Estado del empleado, cargo, departamento, asistencia, puntualidad, errores registrados y cumplimiento de responsabilidades.

Fin de recomendación.
TXT;
        }

        return <<<TXT
Decisión principal:
{$lineaPrincipal}

Motivo:
{$motivoFallback}

Acción inmediata:
Revisa productos críticos, productos más vendidos y registra nuevas ventas para mejorar el análisis.

Qué evitar:
No hagas compras grandes sin revisar primero el stock y las ventas registradas.

Dato a vigilar:
Ventas totales, compras totales, productos bajo stock y producto más vendido.

Fin de recomendación.
TXT;
    }

    private function esPreguntaTalentoHumano(string $pregunta): bool
    {
        $texto = mb_strtolower($pregunta, 'UTF-8');

        $palabras = [
            'empleado',
            'empleados',
            'talento humano',
            'personal',
            'trabajador',
            'colaborador',
            'desempeño',
            'desempeno',
            'responsabilidad',
            'responsabilidades',
            'productividad',
            'rendimiento',
            'anomalía',
            'anomalia',
            'sanción',
            'sancion',
            'advertencia',
            'capacitación',
            'capacitacion',
            'multa',
            'despido',
            'cargo',
            'área',
            'area',
            'departamento',
            'asistencia',
            'atrasos',
            'puntualidad',
            'seguimiento',
        ];

        foreach ($palabras as $palabra) {
            if (str_contains($texto, $palabra)) {
                return true;
            }
        }

        return $this->buscarEmpleadoMencionado($pregunta) !== null;
    }

    private function buscarEmpleadoMencionado(string $pregunta): ?Empleado
    {
        $texto = mb_strtolower($pregunta, 'UTF-8');

        if ($texto === '') {
            return null;
        }

        try {
            return Empleado::query()
                ->limit(300)
                ->get()
                ->first(function (Empleado $empleado) use ($texto) {
                    $nombreCompleto = mb_strtolower(trim("{$empleado->nombres} {$empleado->apellidos}"), 'UTF-8');
                    $nombres = mb_strtolower(trim((string) $empleado->nombres), 'UTF-8');
                    $apellidos = mb_strtolower(trim((string) $empleado->apellidos), 'UTF-8');
                    $primerNombre = mb_strtolower(trim((string) explode(' ', (string) $empleado->nombres)[0]), 'UTF-8');
                    $codigo = mb_strtolower((string) $empleado->codigo_empleado, 'UTF-8');
                    $cedula = mb_strtolower((string) $empleado->cedula, 'UTF-8');

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

    private function formatearDetalleEmpleado(Empleado $empleado): string
    {
        $fechaIngreso = $this->formatearFecha($empleado->fecha_ingreso);
        $dias = null;

        try {
            $dias = $empleado->fecha_ingreso
                ? Carbon::parse($empleado->fecha_ingreso)->diffInDays(now())
                : null;
        } catch (\Throwable $e) {
            $dias = null;
        }

        $antiguedad = $dias !== null ? "{$dias} días de antigüedad aproximada" : 'Antigüedad no calculada';

        return <<<TXT
Código: {$empleado->codigo_empleado}
Nombre: {$empleado->nombres} {$empleado->apellidos}
Cédula: {$empleado->cedula}
Cargo: {$empleado->cargo}
Departamento: {$empleado->departamento}
Sueldo: {$empleado->sueldo}
Fecha de ingreso: {$fechaIngreso}
Antigüedad: {$antiguedad}
Estado: {$empleado->estado}
TXT;
    }

    private function formatearMes($fecha): string
    {
        try {
            if (! $fecha) {
                return 'Sin fecha';
            }

            return Carbon::parse($fecha)->format('Y-m');
        } catch (\Throwable $e) {
            return 'Sin fecha';
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
}
