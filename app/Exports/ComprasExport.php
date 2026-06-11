<?php

namespace App\Exports;

use App\Models\Compra;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ComprasExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
    public function __construct(
        private ?Carbon $desde = null,
        private ?Carbon $hasta = null,
    ) {}

    public function collection()
    {
        return Compra::with(['proveedor', 'producto', 'user'])
            ->when($this->desde && $this->hasta, fn ($query) => $query->whereBetween('fecha', [$this->desde->toDateString(), $this->hasta->toDateString()]))
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
            ->get();
    }

    public function headings(): array
    {
        return ['N° Compra', 'Fecha', 'Proveedor', 'RUC Proveedor', 'Producto', 'Cantidad', 'Precio unitario', 'Total', 'Registrado por', 'Observación', 'Fecha de registro'];
    }

    public function map($compra): array
    {
        return [
            $compra->numero_compra,
            optional($compra->fecha)->format('d/m/Y'),
            $compra->proveedor->nombre ?? 'Proveedor eliminado',
            $compra->proveedor->ruc ?? 'No disponible',
            $compra->producto->nombre ?? 'Producto eliminado',
            $compra->cantidad,
            number_format((float) $compra->precio_unitario, 2, '.', ''),
            number_format((float) $compra->total, 2, '.', ''),
            $compra->user->name ?? 'Sistema',
            $compra->observacion ?? '',
            optional($compra->created_at)->format('d/m/Y H:i'),
        ];
    }

    public function title(): string
    {
        return 'Reporte de Compras';
    }
}
