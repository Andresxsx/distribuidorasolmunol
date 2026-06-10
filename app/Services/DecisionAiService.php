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
            return 'Solo puedo responder preguntas relacionadas con la lógica del Distribuidora Solmunol.';
        }

        $apiKey = config('services.google_ai.api_key');
        $model = config('services.google_ai.model', 'gemini-2.5-flash');

        if (! $apiKey) {
            return $this->respuestaLocalDirectiva(
                'No se encontró la API key de Google AI, pero el ERP generó una decisión automática con los datos registrados.'
            );
        }

        $contexto = $this->generarContextoSistema();
        $decisionesAutomaticas = $this->generarDecisionesAutomaticas();

        $prompt = <<<PROMPT
Eres el asistente directivo inteligente del Distribuidora Solmunol.

Tu función es ayudar a tomar decisiones concretas para mejorar la empresa usando los datos reales del ERP.

No eres un asistente de ideas generales.
Debes decir qué decisión tomar, qué acción hacer y qué evitar.

Reglas:
1. Solo responde preguntas relacionadas con el Distribuidora Solmunol.
2. No respondas cultura general, recetas, deportes, política, medicina, historia, entretenimiento ni tareas externas.
3. Si la pregunta no es del ERP, responde exactamente:
Solo puedo responder preguntas relacionadas con la lógica del Distribuidora Solmunol.
4. Responde en español.
5. No inventes datos.
6. Usa únicamente el contexto del sistema.
7. Si hay pocos datos, igual da una decisión, pero aclara que la precisión mejorará con más registros.
8. No uses asteriscos ni markdown.
9. Responde corto y fácil de entender.
10. Usa frases directas como: "debes", "la decisión es", "se debe", "conviene".
11. Termina siempre con: Fin de recomendación.

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
                    'Google AI no respondió correctamente, pero el ERP generó una decisión automática con los datos actuales.'
                );
            }

            $texto = data_get($response->json(), 'candidates.0.content.parts.0.text');

            if (! $texto) {
                return $this->respuestaLocalDirectiva(
                    'Google AI no generó texto válido, pero el ERP calculó una decisión automática.'
                );
            }

            $texto = $this->limpiarTextoIa($texto);

            if (! str_contains($texto, 'Fin de recomendación.')) {
                $texto .= "\nFin de recomendación.";
            }

            return $texto;
        } catch (\Throwable $e) {
            return $this->respuestaLocalDirectiva(
                'No se pudo conectar con Google AI, pero el ERP generó una decisión automática con los datos registrados.'
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
            'solis',
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
            'empleado',
            'empleados',
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
        ];

        foreach ($palabrasPermitidas as $palabra) {
            if (str_contains($texto, $palabra)) {
                return true;
            }
        }

        return false;
    }

    private function generarContextoSistema(): string
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

    private function generarDecisionesAutomaticas(): string
    {
        $decisiones = [];

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
            $decisiones[] = "No aumentar compras generales. Se debe vender primero el inventario actual.";
        } elseif ($resultadoEstimado > 0) {
            $decisiones[] = "Reinvertir solo en productos con mayor salida. Evitar compras innecesarias.";
        } else {
            $decisiones[] = "Registrar más ventas y compras antes de tomar decisiones grandes.";
        }

        if ($totalVentas <= 0) {
            $decisiones[] = "Registrar ventas reales antes de hacer predicciones.";
        }

        if ($totalCompras <= 0) {
            $decisiones[] = "Registrar compras reales para calcular costos e inventario.";
        }

        if (empty($decisiones)) {
            $decisiones[] = "Mantener control semanal de ventas, compras y stock.";
        }

        return implode("\n", $decisiones);
    }

    private function respuestaLocalDirectiva(string $motivoFallback): string
    {
        $decisiones = $this->generarDecisionesAutomaticas();
        $lineaPrincipal = strtok($decisiones, "\n") ?: 'Mantener control semanal de ventas, compras y stock.';

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