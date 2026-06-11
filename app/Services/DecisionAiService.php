<?php

namespace App\Services;

use App\Models\Cargo;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Empleado;
use App\Models\MovimientoBodega;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\SancionEmpleado;
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

        $fallback = $this->respuestaLocalDirectiva($pregunta);
        $apiKey = config('services.google_ai.api_key');
        $model = config('services.google_ai.model', 'gemini-2.5-flash');

        if (! $apiKey) {
            return $fallback;
        }

        $contexto = $this->generarContextoSistema($pregunta);

        $prompt = <<<PROMPT
Eres el asistente IA directivo de Distribuidora Solmunol.
Debes apoyar decisiones empresariales usando únicamente datos del ERP.

Reglas obligatorias:
1. Responde solo sobre módulos del ERP: empleados, talento humano, cargos, salarios, sanciones, seguro, compras, proveedores, ventas, clientes, productos, stock, bodega, reportes y dashboard.
2. No inventes datos. Si el sistema no registra algo, dilo como limitación.
3. No uses asteriscos ni markdown.
4. Responde con criterio de toma de decisiones, no con frases cortas.
5. No ordenes despidos, multas o sanciones definitivas sin evidencia registrada. Recomienda revisión, seguimiento, capacitación o advertencia documentada cuando falten evidencias.
6. Termina siempre con: Fin de recomendación.

Formato obligatorio:
Diagnóstico:
Datos encontrados:
Hallazgos:
Nivel de riesgo o prioridad:
Recomendación administrativa:
Plan de acción:
Decisión sugerida:
Limitaciones de los datos:
Fin de recomendación.

Contexto real del ERP:
{$contexto}

Pregunta del usuario:
{$pregunta}
PROMPT;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $apiKey,
            ])
                ->timeout(45)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'contents' => [[
                        'parts' => [[
                            'text' => $prompt,
                        ]],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.15,
                        'maxOutputTokens' => 1800,
                    ],
                ]);

            if (! $response->successful()) {
                return $fallback;
            }

            $texto = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
            $texto = $this->limpiarTextoIa($texto);

            if (mb_strlen($texto, 'UTF-8') < 650 || ! str_contains($texto, 'Decisión sugerida')) {
                return $fallback;
            }

            if (! str_contains($texto, 'Fin de recomendación.')) {
                $texto .= "\nFin de recomendación.";
            }

            return $texto;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    private function limpiarTextoIa(string $texto): string
    {
        $texto = trim($texto);
        $texto = str_replace(['**', '###', '##', '#'], '', $texto);
        $texto = preg_replace('/^\s*[-•]\s*/m', '', $texto);
        $texto = preg_replace('/\n{3,}/', "\n\n", $texto);

        return trim((string) $texto);
    }

    private function preguntaPermitida(string $pregunta): bool
    {
        $texto = mb_strtolower($pregunta, 'UTF-8');

        $permitidas = [
            'erp', 'solmunol', 'empresa', 'negocio', 'decisión', 'decision', 'decisiones', 'recomienda', 'recomendación', 'recomendacion',
            'compra', 'compras', 'proveedor', 'proveedores', 'venta', 'ventas', 'cliente', 'clientes', 'producto', 'productos', 'stock', 'inventario', 'bodega', 'movimiento', 'movimientos', 'reporte', 'reportes', 'dashboard', 'ganancia', 'utilidad', 'rentabilidad',
            'empleado', 'empleados', 'talento humano', 'personal', 'trabajador', 'colaborador', 'cargo', 'cargos', 'salario', 'sueldo', 'sanción', 'sancion', 'sanciones', 'descuento', 'seguro', 'iess', 'desempeño', 'desempeno', 'rendimiento', 'responsabilidad', 'responsabilidades', 'cumplimiento', 'anomalía', 'anomalia', 'advertencia', 'capacitación', 'capacitacion', 'multa', 'despido', 'despedir', 'departamento', 'asistencia', 'atrasos', 'puntualidad', 'seguimiento', 'plan de mejora',
            'semestral', 'anual', 'trimestral', 'mensual', 'semanal', 'diario', 'periodo', 'período', 'fecha', 'fechas',
        ];

        foreach ($permitidas as $palabra) {
            if (str_contains($texto, $palabra)) {
                return true;
            }
        }

        return $this->buscarEmpleadoMencionado($pregunta) !== null;
    }

    private function respuestaLocalDirectiva(string $pregunta): string
    {
        if ($this->esPreguntaTalentoHumano($pregunta)) {
            return $this->respuestaTalentoHumano($pregunta);
        }

        if ($this->contiene($pregunta, ['proveedor', 'proveedores'])) {
            return $this->respuestaProveedores($pregunta);
        }

        if ($this->contiene($pregunta, ['compra', 'compras'])) {
            return $this->respuestaCompras($pregunta);
        }

        if ($this->contiene($pregunta, ['venta', 'ventas', 'cliente', 'clientes'])) {
            return $this->respuestaVentas($pregunta);
        }

        if ($this->contiene($pregunta, ['producto', 'productos', 'stock', 'inventario', 'bodega'])) {
            return $this->respuestaStockBodega($pregunta);
        }

        return $this->respuestaGeneral();
    }

    private function respuestaTalentoHumano(string $pregunta): string
    {
        $empleado = $this->buscarEmpleadoMencionado($pregunta);
        $total = Empleado::count();
        $activos = Empleado::where('estado', 'Activo')->count();
        $sinSeguro = Empleado::where('estado', 'Activo')->where(function ($query) {
            $query->where('tiene_seguro', false)->orWhereNull('tiene_seguro');
        })->count();
        $sancionesAplicadas = SancionEmpleado::where('estado', 'Aplicada')->count();
        $descuentos = round((float) SancionEmpleado::where('estado', 'Aplicada')->sum('valor_descuento'), 2);

        if (! $empleado) {
            $cargos = Cargo::where('estado', 'Activo')->count();

            return <<<TXT
Diagnóstico:
El área de Talento Humano registra {$total} empleado(s), de los cuales {$activos} están activos. Existen {$cargos} cargo(s) activos con salario base fijo y {$sancionesAplicadas} sanción(es) aplicada(s) con descuentos acumulados por {$descuentos}.

Datos encontrados:
Empleados activos: {$activos}.
Empleados activos sin seguro registrado: {$sinSeguro}.
Sanciones aplicadas: {$sancionesAplicadas}.
Descuentos salariales aplicados: {$descuentos}.
Seguro obligatorio aplicado: IESS, con aporte personal 9.45%, patronal 11.15% y total 20.60% sobre salario base.

Hallazgos:
La empresa controla cargos fijos, salario base por cargo, seguro IESS automático y sanciones con motivo. El salario neto debe descontar el aporte personal IESS y las sanciones aplicadas.

Nivel de riesgo o prioridad:
Medio. El riesgo aumenta si existen sanciones repetidas sin plan de mejora, cargos sin salario fijo o fechas de ingreso incoherentes.

Recomendación administrativa:
Validar que todos los empleados tengan cargo asignado, fecha de ingreso lógica y seguro IESS activo. Las sanciones deben afectar el salario neto únicamente cuando estén en estado Aplicada.

Plan de acción:
1. Revisar el módulo Cargos y confirmar salarios base.
2. Confirmar que el seguro IESS esté automático en todos los empleados.
3. Registrar sanciones solo con motivo claro y evidencia administrativa.
4. Comparar salario base, IESS personal, sanciones y salario neto.
5. Pedir a Talento Humano seguimiento mensual de casos con sanciones.

Decisión sugerida:
Priorizar actualización de cargos, seguros y sanciones antes de aplicar medidas fuertes contra empleados.

Limitaciones de los datos:
El ERP registra cargos, salario, seguro y sanciones, pero la decisión final debe revisarse con Talento Humano y el reglamento interno.
Fin de recomendación.
TXT;
        }

        $nombre = trim("{$empleado->nombres} {$empleado->apellidos}");
        $sanciones = $empleado->sanciones()->orderByDesc('fecha')->limit(5)->get();
        $totalSanciones = round((float) $empleado->sancionesAplicadas()->sum('valor_descuento'), 2);
        $aportePersonal = $empleado->aporte_personal_iess;
        $aportePatronal = $empleado->aporte_patronal_iess;
        $totalAporteIess = $empleado->total_aporte_iess;
        $sueldoNeto = $empleado->sueldo_neto_estimado;
        $seguro = "IESS fijo, estado {$empleado->estado_seguro}, afiliación {$empleado->numero_afiliacion}";
        $detalleSanciones = $sanciones->isEmpty()
            ? 'No tiene sanciones registradas.'
            : $sanciones->map(fn ($s) => $this->formatearFecha($s->fecha) . ": {$s->tipo}, estado {$s->estado}, descuento {$s->valor_descuento}, motivo: {$s->motivo}")->implode("\n");

        $riesgo = 'Bajo';
        $motivo = 'No existen sanciones aplicadas ni alertas críticas registradas.';

        if (! $empleado->tiene_seguro && $empleado->estado === 'Activo') {
            $riesgo = 'Medio';
            $motivo = 'El empleado está activo, pero no tiene seguro registrado.';
        }

        if ($totalSanciones > 0) {
            $riesgo = 'Medio';
            $motivo = 'El empleado tiene sanciones aplicadas con descuento salarial.';
        }

        if (in_array($empleado->estado, ['Suspendido', 'Inactivo', 'Retirado'], true)) {
            $riesgo = 'Alto';
            $motivo = "El empleado tiene estado {$empleado->estado}.";
        }

        return <<<TXT
Diagnóstico:
Se analizó al empleado {$nombre}. Su cargo es {$empleado->cargo}, pertenece al departamento {$empleado->departamento} y tiene estado {$empleado->estado}.

Datos encontrados:
Salario base por cargo: {$empleado->sueldo}.
Aporte personal IESS 9.45%: {$aportePersonal}.
Aporte patronal IESS 11.15%: {$aportePatronal}.
Total aporte IESS 20.60%: {$totalAporteIess}.
Sanciones aplicadas: {$totalSanciones}.
Salario neto estimado: {$sueldoNeto}.
Seguro: {$seguro}.
Fecha de ingreso: {$this->formatearFecha($empleado->fecha_ingreso)}.
Fecha de afiliación IESS: {$this->formatearFecha($empleado->fecha_afiliacion)}.
Sanciones registradas:
{$detalleSanciones}

Hallazgos:
El salario base debe mantenerse igual para todos los empleados con el cargo {$empleado->cargo}. El descuento fijo del empleado es el aporte personal IESS y cualquier descuento adicional debe venir desde sanciones registradas con motivo y estado Aplicada.

Nivel de riesgo o prioridad:
{$riesgo}. {$motivo}

Recomendación administrativa:
No aplicar despido, multa adicional ni sanción definitiva sin revisar evidencias. Si hay sanciones, validar el motivo y mantener seguimiento por Talento Humano. El seguro IESS debe permanecer automático y la fecha de afiliación debe coincidir con la fecha de ingreso.

Plan de acción:
1. Confirmar que el cargo y departamento sean correctos.
2. Revisar si el seguro IESS está activo y documentado.
3. Revisar sanciones y motivos registrados.
4. Calcular salario neto con aporte personal IESS y descuentos aplicados.
5. Definir seguimiento o capacitación según el historial.

Decisión sugerida:
Aplicar seguimiento administrativo y mantener el salario base según cargo. Solo descontar valores que estén registrados como sanciones aplicadas.

Limitaciones de los datos:
El ERP no reemplaza la revisión formal de Talento Humano ni el reglamento interno.
Fin de recomendación.
TXT;
    }

    private function respuestaProveedores(string $pregunta): string
    {
        $ranking = Compra::query()
            ->selectRaw('proveedor_id, COUNT(*) as transacciones, SUM(total) as total_comprado, MAX(fecha) as ultima_compra')
            ->with('proveedor')
            ->groupBy('proveedor_id')
            ->orderByDesc('transacciones')
            ->orderByDesc('total_comprado')
            ->limit(5)
            ->get();

        if ($ranking->isEmpty()) {
            return $this->sinDatos('proveedores y compras', 'Registrar compras asociadas a proveedores antes de elegir un proveedor principal.');
        }

        $mejor = $ranking->first();
        $proveedor = $mejor->proveedor;
        $nombre = $proveedor->nombre ?? 'Proveedor no disponible';
        $detalle = $ranking->map(function ($fila, $index) {
            $nombreProveedor = $fila->proveedor->nombre ?? 'Proveedor no disponible';
            $ultima = $this->formatearFecha($fila->ultima_compra);
            $posicion = $index + 1;

            return "{$posicion}. {$nombreProveedor}: {$fila->transacciones} compra(s), total {$fila->total_comprado}, última compra {$ultima}";
        })->implode("\n");

        return <<<TXT
Diagnóstico:
Se evaluó el comportamiento de proveedores usando las compras registradas en el ERP.

Datos encontrados:
Proveedor recomendado: {$nombre}.
Ranking de proveedores:
{$detalle}

Hallazgos:
El proveedor recomendado aparece con mejor comportamiento según frecuencia de compras y monto comprado. Estos criterios muestran relación comercial constante, pero no miden calidad, puntualidad de entrega ni condiciones de crédito si esos datos no están registrados.

Nivel de riesgo o prioridad:
Medio. La recomendación es útil para compras operativas, pero debe complementarse con revisión de precios, tiempos de entrega y calidad.

Recomendación administrativa:
Mantener a {$nombre} como proveedor prioritario para compras frecuentes, siempre comparando precio y disponibilidad antes de emitir nuevas órdenes.

Plan de acción:
1. Revisar los últimos precios ofrecidos por {$nombre}.
2. Comparar con al menos dos proveedores alternativos.
3. Confirmar disponibilidad de productos críticos.
4. Registrar observaciones de entrega y calidad en futuras compras.
5. Mantener negociación si el proveedor conserva buen comportamiento.

Decisión sugerida:
Seguir comprando principalmente a {$nombre}, pero no depender de un solo proveedor para productos críticos.

Limitaciones de los datos:
El ERP analiza compras registradas, no mide automáticamente calidad del producto, garantía ni puntualidad de entrega.
Fin de recomendación.
TXT;
    }

    private function respuestaCompras(string $pregunta): string
    {
        $total = round((float) Compra::sum('total'), 2);
        $cantidad = Compra::count();
        $ultimas = Compra::with(['proveedor', 'producto'])->orderByDesc('fecha')->limit(5)->get()
            ->map(fn ($c) => $this->formatearFecha($c->fecha) . ': ' . ($c->proveedor->nombre ?? 'Proveedor eliminado') . ', producto ' . ($c->producto->nombre ?? 'Producto eliminado') . ', total ' . $c->total)
            ->implode("\n");

        return <<<TXT
Diagnóstico:
El módulo de compras registra {$cantidad} compra(s) por un total de {$total}.

Datos encontrados:
Últimas compras:
{$ultimas}

Hallazgos:
Las compras deben priorizar productos con stock bajo, productos de alta rotación y proveedores con comportamiento estable.

Nivel de riesgo o prioridad:
Medio. Comprar sin revisar stock y ventas puede aumentar inventario inmovilizado.

Recomendación administrativa:
Autorizar compras solo cuando el producto esté bajo el mínimo o tenga salida constante en ventas.

Plan de acción:
1. Revisar productos críticos antes de comprar.
2. Comparar proveedor, precio y disponibilidad.
3. Evitar compras grandes de productos con baja rotación.
4. Revisar compras semanales, mensuales, trimestrales y semestrales según necesidad.
5. Controlar el impacto de las compras en la rentabilidad.

Decisión sugerida:
Comprar de forma selectiva, priorizando productos críticos y proveedores confiables.

Limitaciones de los datos:
El ERP no registra automáticamente condiciones de crédito ni tiempos de entrega.
Fin de recomendación.
TXT;
    }

    private function respuestaVentas(string $pregunta): string
    {
        $total = round((float) Venta::sum('total'), 2);
        $cantidad = Venta::count();
        $top = Venta::query()
            ->selectRaw('producto_id, SUM(cantidad) as unidades, SUM(total) as ingresos')
            ->with('producto')
            ->groupBy('producto_id')
            ->orderByDesc('ingresos')
            ->limit(5)
            ->get()
            ->map(fn ($v, $i) => ($i + 1) . '. ' . ($v->producto->nombre ?? 'Producto eliminado') . ": {$v->unidades} unidades, ingresos {$v->ingresos}")
            ->implode("\n");

        return <<<TXT
Diagnóstico:
El módulo de ventas registra {$cantidad} venta(s) por un total de {$total}.

Datos encontrados:
Productos con mayor aporte en ventas:
{$top}

Hallazgos:
Los productos con mayores ingresos deben mantenerse con stock suficiente y seguimiento de precio.

Nivel de riesgo o prioridad:
Medio. La empresa debe vigilar stock y margen para no perder ventas por falta de producto.

Recomendación administrativa:
Impulsar productos de mayor rotación, revisar clientes frecuentes y evitar descuentos sin análisis de margen.

Plan de acción:
1. Revisar ventas mensuales y trimestrales.
2. Mantener inventario de productos más vendidos.
3. Analizar clientes frecuentes.
4. Revisar precios de venta frente al costo.
5. Preparar reporte anual para comparar crecimiento.

Decisión sugerida:
Priorizar productos con mayor ingreso y asegurar reposición antes de que lleguen a stock mínimo.

Limitaciones de los datos:
El ERP mide ventas registradas, no mide satisfacción del cliente ni competencia externa.
Fin de recomendación.
TXT;
    }

    private function respuestaStockBodega(string $pregunta): string
    {
        $sinStock = Producto::where('estado', 'Activo')->where('stock_actual', '<=', 0)->count();
        $bajoStock = Producto::where('estado', 'Activo')->whereColumn('stock_actual', '<=', 'stock_minimo')->where('stock_actual', '>', 0)->count();
        $criticos = Producto::where('estado', 'Activo')->whereColumn('stock_actual', '<=', 'stock_minimo')->orderBy('stock_actual')->limit(5)->get()
            ->map(fn ($p) => "{$p->nombre}: stock {$p->stock_actual}, mínimo {$p->stock_minimo}")
            ->implode("\n");

        return <<<TXT
Diagnóstico:
El inventario registra {$sinStock} producto(s) sin stock y {$bajoStock} producto(s) bajo stock mínimo.

Datos encontrados:
Productos críticos:
{$criticos}

Hallazgos:
Los productos críticos pueden afectar ventas y atención al cliente si no se reponen a tiempo.

Nivel de riesgo o prioridad:
Alto si existen productos sin stock. Medio si solo hay productos bajo mínimo.

Recomendación administrativa:
Reponer primero productos sin stock y luego productos bajo mínimo, priorizando los que tengan mayor venta histórica.

Plan de acción:
1. Revisar productos sin stock.
2. Verificar proveedor recomendado para cada producto.
3. Registrar compra de reposición.
4. Controlar movimientos de bodega después de la compra.
5. Revisar stock semanalmente.

Decisión sugerida:
Ejecutar reposición controlada y evitar ventas de productos sin disponibilidad.

Limitaciones de los datos:
El ERP no predice demanda externa si no hay historial suficiente de ventas.
Fin de recomendación.
TXT;
    }

    private function respuestaGeneral(): string
    {
        $compras = round((float) Compra::sum('total'), 2);
        $ventas = round((float) Venta::sum('total'), 2);
        $resultado = round($ventas - $compras, 2);
        $empleados = Empleado::where('estado', 'Activo')->count();
        $productosCriticos = Producto::where('estado', 'Activo')->whereColumn('stock_actual', '<=', 'stock_minimo')->count();

        return <<<TXT
Diagnóstico:
La empresa registra ventas por {$ventas}, compras por {$compras}, resultado estimado de {$resultado}, {$empleados} empleado(s) activo(s) y {$productosCriticos} producto(s) críticos.

Datos encontrados:
El sistema integra compras, ventas, stock, bodega, clientes, proveedores y talento humano.

Hallazgos:
La prioridad debe definirse según rentabilidad, stock crítico y control de personal.

Nivel de riesgo o prioridad:
Medio. El riesgo aumenta si hay compras superiores a ventas, productos sin stock o empleados sin seguro/sanciones sin seguimiento.

Recomendación administrativa:
Tomar decisiones con base en reportes por periodo: diario, semanal, mensual, trimestral, semestral, anual o personalizado.

Plan de acción:
1. Revisar rentabilidad general.
2. Revisar productos críticos.
3. Revisar proveedores principales.
4. Revisar empleados sin seguro o con sanciones.
5. Generar reportes por periodo para decisiones directivas.

Decisión sugerida:
Priorizar control de inventario, compras selectivas y seguimiento de talento humano.

Limitaciones de los datos:
Las decisiones dependen de la calidad de los registros ingresados al ERP.
Fin de recomendación.
TXT;
    }

    private function sinDatos(string $modulo, string $accion): string
    {
        return <<<TXT
Diagnóstico:
No hay datos suficientes en el módulo {$modulo} para una recomendación completa.

Datos encontrados:
No existen registros suficientes para comparar comportamiento.

Hallazgos:
Sin registros no es posible calcular tendencias ni ranking confiable.

Nivel de riesgo o prioridad:
Medio. La empresa puede tomar decisiones con información incompleta.

Recomendación administrativa:
{$accion}

Plan de acción:
1. Registrar datos reales del módulo.
2. Validar que los registros tengan fecha y valores correctos.
3. Generar reportes por periodo.
4. Volver a consultar a la IA cuando existan datos suficientes.

Decisión sugerida:
No tomar decisiones definitivas hasta registrar información suficiente.

Limitaciones de los datos:
La recomendación está limitada por falta de registros.
Fin de recomendación.
TXT;
    }

    private function generarContextoSistema(string $pregunta): string
    {
        $compras = round((float) Compra::sum('total'), 2);
        $ventas = round((float) Venta::sum('total'), 2);
        $resultado = round($ventas - $compras, 2);
        $empleadosActivos = Empleado::where('estado', 'Activo')->count();
        $sinSeguro = Empleado::where('estado', 'Activo')->where(function ($query) {
            $query->where('tiene_seguro', false)->orWhereNull('tiene_seguro');
        })->count();
        $sanciones = SancionEmpleado::where('estado', 'Aplicada')->count();
        $descuentos = round((float) SancionEmpleado::where('estado', 'Aplicada')->sum('valor_descuento'), 2);
        $productosCriticos = Producto::where('estado', 'Activo')->whereColumn('stock_actual', '<=', 'stock_minimo')->count();
        $proveedores = Proveedor::where('estado', 'Activo')->count();

        return <<<CTX
Compras totales: {$compras}
Ventas totales: {$ventas}
Resultado estimado: {$resultado}
Proveedores activos: {$proveedores}
Productos críticos: {$productosCriticos}
Empleados activos: {$empleadosActivos}
Empleados activos sin seguro: {$sinSeguro}
Sanciones aplicadas: {$sanciones}
Descuentos salariales aplicados: {$descuentos}
{$this->resumenProveedorContexto()}
{$this->resumenEmpleadoMencionado($pregunta)}
CTX;
    }

    private function resumenProveedorContexto(): string
    {
        $ranking = Compra::query()
            ->selectRaw('proveedor_id, COUNT(*) as transacciones, SUM(total) as total_comprado, MAX(fecha) as ultima_compra')
            ->with('proveedor')
            ->groupBy('proveedor_id')
            ->orderByDesc('transacciones')
            ->orderByDesc('total_comprado')
            ->limit(5)
            ->get();

        if ($ranking->isEmpty()) {
            return 'Ranking de proveedores: sin compras suficientes.';
        }

        return 'Ranking de proveedores:' . "\n" . $ranking->map(function ($fila, $index) {
            $nombre = $fila->proveedor->nombre ?? 'Proveedor no disponible';
            $ultima = $this->formatearFecha($fila->ultima_compra);
            $posicion = $index + 1;

            return "{$posicion}. {$nombre}: {$fila->transacciones} compra(s), total {$fila->total_comprado}, última compra {$ultima}";
        })->implode("\n");
    }

    private function resumenEmpleadoMencionado(string $pregunta): string
    {
        $empleado = $this->buscarEmpleadoMencionado($pregunta);

        if (! $empleado) {
            return 'Empleado mencionado: no detectado.';
        }

        $sanciones = $empleado->sanciones()->orderByDesc('fecha')->limit(5)->get();
        $detalleSanciones = $sanciones->isEmpty()
            ? 'Sin sanciones registradas.'
            : $sanciones->map(fn ($s) => $this->formatearFecha($s->fecha) . ": {$s->tipo}, {$s->estado}, descuento {$s->valor_descuento}, motivo {$s->motivo}")->implode("\n");

        return <<<TXT
Empleado mencionado:
Nombre: {$empleado->nombres} {$empleado->apellidos}
Código: {$empleado->codigo_empleado}
Cargo: {$empleado->cargo}
Departamento: {$empleado->departamento}
Salario base: {$empleado->sueldo}
Aporte personal IESS 9.45%: {$empleado->aporte_personal_iess}
Aporte patronal IESS 11.15%: {$empleado->aporte_patronal_iess}
Total aporte IESS 20.60%: {$empleado->total_aporte_iess}
Total descuentos del empleado: {$empleado->total_descuentos_empleado}
Salario neto estimado: {$empleado->sueldo_neto_estimado}
Seguro: {$this->textoSeguro($empleado)}
Estado: {$empleado->estado}
Sanciones:
{$detalleSanciones}
TXT;
    }

    private function textoSeguro(Empleado $empleado): string
    {
        return trim("IESS fijo, {$empleado->estado_seguro}, afiliación {$empleado->numero_afiliacion}, fecha {$this->formatearFecha($empleado->fecha_afiliacion)}");
    }

    private function esPreguntaTalentoHumano(string $pregunta): bool
    {
        return $this->contiene($pregunta, ['empleado', 'empleados', 'talento humano', 'personal', 'cargo', 'salario', 'sueldo', 'sanción', 'sancion', 'seguro', 'iess', 'desempeño', 'desempeno', 'multa', 'despido', 'departamento', 'responsabilidad', 'cumplimiento'])
            || $this->buscarEmpleadoMencionado($pregunta) !== null;
    }

    private function buscarEmpleadoMencionado(string $pregunta): ?Empleado
    {
        $texto = mb_strtolower($pregunta, 'UTF-8');

        if ($texto === '') {
            return null;
        }

        try {
            return Empleado::query()->limit(500)->get()->first(function (Empleado $empleado) use ($texto) {
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

    private function contiene(string $pregunta, array $palabras): bool
    {
        $texto = mb_strtolower($pregunta, 'UTF-8');

        foreach ($palabras as $palabra) {
            if (str_contains($texto, mb_strtolower($palabra, 'UTF-8'))) {
                return true;
            }
        }

        return false;
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
