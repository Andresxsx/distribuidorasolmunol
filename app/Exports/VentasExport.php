<?php

namespace App\Exports;

use App\Models\Venta;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class VentasExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
    public function __construct(
        private ?Carbon $desde = null,
        private ?Carbon $hasta = null,
    ) {}

    public function collection()
    {
        return Venta::with(['cliente', 'producto', 'user'])
            ->when($this->desde && $this->hasta, fn ($query) => $query->whereBetween('fecha', [$this->desde->toDateString(), $this->hasta->toDateString()]))
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
            ->get();
    }

    public function headings(): array
    {
        return ['N° Venta', 'Fecha', 'Cliente', 'Cédula / RUC Cliente', 'Producto', 'Cantidad', 'Precio unitario', 'Total', 'Registrado por', 'Observación', 'Fecha de registro'];
    }

    public function map($venta): array
    {
        return [
            $venta->numero_venta,
            optional($venta->fecha)->format('d/m/Y'),
            $venta->cliente->nombre ?? 'Cliente eliminado',
            $venta->cliente->cedula_ruc ?? 'No disponible',
            $venta->producto->nombre ?? 'Producto eliminado',
            $venta->cantidad,
            number_format((float) $venta->precio_unitario, 2, '.', ''),
            number_format((float) $venta->total, 2, '.', ''),
            $venta->user->name ?? 'Sistema',
            $venta->observacion ?? '',
            optional($venta->created_at)->format('d/m/Y H:i'),
        ];
    }

    public function title(): string
    {
        return 'Reporte de Ventas';
    }
}
