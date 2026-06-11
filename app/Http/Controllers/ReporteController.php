<?php

namespace App\Http\Controllers;

use App\Exports\ComprasExport;
use App\Exports\VentasExport;
use App\Models\Compra;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReporteController extends Controller
{
    public function comprasExcel(Request $request)
    {
        [$desde, $hasta, $etiqueta] = $this->resolverPeriodo($request);

        return Excel::download(new ComprasExport($desde, $hasta), 'reporte_compras_' . $etiqueta . '.xlsx');
    }

    public function ventasExcel(Request $request)
    {
        [$desde, $hasta, $etiqueta] = $this->resolverPeriodo($request);

        return Excel::download(new VentasExport($desde, $hasta), 'reporte_ventas_' . $etiqueta . '.xlsx');
    }

    public function comprasPdf(Request $request)
    {
        [$desde, $hasta, $etiqueta] = $this->resolverPeriodo($request);

        $compras = Compra::with(['proveedor', 'producto', 'user'])
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
            ->get();

        $totalCompras = $compras->sum('total');

        $pdf = Pdf::loadView('reportes.compras-pdf', [
            'compras' => $compras,
            'totalCompras' => $totalCompras,
            'fechaHora' => now()->format('d/m/Y H:i:s'),
            'periodo' => $etiqueta,
            'fechaInicio' => $desde->format('d/m/Y'),
            'fechaFin' => $hasta->format('d/m/Y'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('reporte_compras_' . $etiqueta . '.pdf');
    }

    public function ventasPdf(Request $request)
    {
        [$desde, $hasta, $etiqueta] = $this->resolverPeriodo($request);

        $ventas = Venta::with(['cliente', 'producto', 'user'])
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
            ->get();

        $totalVentas = $ventas->sum('total');

        $pdf = Pdf::loadView('reportes.ventas-pdf', [
            'ventas' => $ventas,
            'totalVentas' => $totalVentas,
            'fechaHora' => now()->format('d/m/Y H:i:s'),
            'periodo' => $etiqueta,
            'fechaInicio' => $desde->format('d/m/Y'),
            'fechaFin' => $hasta->format('d/m/Y'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('reporte_ventas_' . $etiqueta . '.pdf');
    }

    private function resolverPeriodo(Request $request): array
    {
        $periodo = strtolower((string) $request->query('periodo', 'todo'));
        $hoy = now();

        [$desde, $hasta] = match ($periodo) {
            'hoy', 'diario', 'dia', 'día' => [$hoy->copy()->startOfDay(), $hoy->copy()->endOfDay()],
            'semana', 'semanal' => [$hoy->copy()->startOfWeek(), $hoy->copy()->endOfWeek()],
            'mes', 'mensual' => [$hoy->copy()->startOfMonth(), $hoy->copy()->endOfMonth()],
            'trimestre', 'trimestral' => [$hoy->copy()->startOfQuarter(), $hoy->copy()->endOfQuarter()],
            'semestre', 'semestral' => [$hoy->month <= 6 ? $hoy->copy()->startOfYear() : $hoy->copy()->month(7)->startOfMonth(), $hoy->month <= 6 ? $hoy->copy()->month(6)->endOfMonth() : $hoy->copy()->endOfYear()],
            'anio', 'año', 'anual' => [$hoy->copy()->startOfYear(), $hoy->copy()->endOfYear()],
            'personalizado' => [
                Carbon::parse($request->query('desde', $hoy->copy()->startOfYear()->toDateString()))->startOfDay(),
                Carbon::parse($request->query('hasta', $hoy->copy()->toDateString()))->endOfDay(),
            ],
            default => [Carbon::create(2000, 1, 1)->startOfDay(), $hoy->copy()->endOfDay()],
        };

        if ($desde->greaterThan($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $etiqueta = $periodo === 'personalizado'
            ? 'personalizado_' . $desde->format('Ymd') . '_' . $hasta->format('Ymd')
            : preg_replace('/[^a-z0-9_]/', '', str_replace(['ñ', ' '], ['n', '_'], $periodo));

        if ($etiqueta === '') {
            $etiqueta = 'todo';
        }

        return [$desde, $hasta, $etiqueta];
    }
}
