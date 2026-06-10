<?php

namespace App\Http\Controllers;

use App\Exports\ComprasExport;
use App\Exports\VentasExport;
use App\Models\Compra;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReporteController extends Controller
{
    public function comprasExcel()
    {
        return Excel::download(new ComprasExport, 'reporte_compras.xlsx');
    }

    public function ventasExcel()
    {
        return Excel::download(new VentasExport, 'reporte_ventas.xlsx');
    }

    public function comprasPdf()
    {
        $compras = Compra::with(['proveedor', 'producto', 'user'])
            ->orderByDesc('created_at')
            ->get();

        $totalCompras = $compras->sum('total');

        $pdf = Pdf::loadView('reportes.compras-pdf', [
            'compras' => $compras,
            'totalCompras' => $totalCompras,
            'fechaHora' => now()->format('d/m/Y H:i:s'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('reporte_compras.pdf');
    }

    public function ventasPdf()
    {
        $ventas = Venta::with(['cliente', 'producto', 'user'])
            ->orderByDesc('created_at')
            ->get();

        $totalVentas = $ventas->sum('total');

        $pdf = Pdf::loadView('reportes.ventas-pdf', [
            'ventas' => $ventas,
            'totalVentas' => $totalVentas,
            'fechaHora' => now()->format('d/m/Y H:i:s'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('reporte_ventas.pdf');
    }
}