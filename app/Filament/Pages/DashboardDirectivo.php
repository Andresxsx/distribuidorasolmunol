<?php

namespace App\Filament\Pages;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Empleado;
use App\Models\MovimientoBodega;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Venta;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class DashboardDirectivo extends Page
{
    
    protected static ?string $navigationLabel = 'Dashboard Directivo';

    protected static ?string $title = 'Dashboard Directivo';

    protected static ?string $slug = 'dashboard-directivo';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';
    
protected static string|\UnitEnum|null $navigationGroup = 'Dirección';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.dashboard-directivo';

    public static function canAccess(): bool
{
    return auth()->user()?->puedeConsultarDirectivo() ?? false;
}

public static function shouldRegisterNavigation(): bool
{
    return auth()->user()?->puedeConsultarDirectivo() ?? false;
}

    public function getViewData(): array
    {
        /*
        |--------------------------------------------------------------------------
        | Datos financieros principales
        |--------------------------------------------------------------------------
        */

        $totalCompras = (float) Compra::sum('total');
        $totalVentas = (float) Venta::sum('total');

        // No es ganancia real contable. Es resultado estimado: ventas - compras.
        $gananciaEstimada = $totalVentas - $totalCompras;

        /*
        |--------------------------------------------------------------------------
        | Inventario
        |--------------------------------------------------------------------------
        */

        $productosBajoStock = Producto::where('estado', 'Activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->where('stock_actual', '>', 0)
            ->count();

        $productosSinStock = Producto::where('estado', 'Activo')
            ->where('stock_actual', '<=', 0)
            ->count();

        $productosCriticos = Producto::where('estado', 'Activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->orderBy('stock_actual')
            ->limit(8)
            ->get();

        $valorInventario = Producto::where('estado', 'Activo')
            ->get()
            ->sum(function ($producto) {
                return (float) $producto->stock_actual * (float) $producto->precio_compra;
            });

        /*
        |--------------------------------------------------------------------------
        | Personas y entidades activas
        |--------------------------------------------------------------------------
        */

        $empleadosActivos = Empleado::where('estado', 'Activo')->count();
        $clientesActivos = Cliente::where('estado', 'Activo')->count();
        $proveedoresActivos = Proveedor::where('estado', 'Activo')->count();

        /*
        |--------------------------------------------------------------------------
        | Últimas transacciones
        |--------------------------------------------------------------------------
        */

        $ultimasCompras = Compra::with(['proveedor', 'producto', 'user'])
            ->latest()
            ->limit(5)
            ->get();

        $ultimasVentas = Venta::with(['cliente', 'producto', 'user'])
            ->latest()
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Productos más vendidos
        |--------------------------------------------------------------------------
        */

        $productosMasVendidos = Venta::query()
            ->selectRaw('producto_id, SUM(cantidad) as total_vendido, SUM(total) as total_ingresos')
            ->with('producto')
            ->groupBy('producto_id')
            ->orderByDesc('total_vendido')
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Movimientos de bodega
        |--------------------------------------------------------------------------
        */

        $entradasBodega = MovimientoBodega::where('tipo_movimiento', 'Entrada')->count();
        $salidasBodega = MovimientoBodega::where('tipo_movimiento', 'Salida')->count();
        $ajustesBodega = MovimientoBodega::where('tipo_movimiento', 'Ajuste')->count();

        /*
        |--------------------------------------------------------------------------
        | Retorno a la vista
        |--------------------------------------------------------------------------
        */

        return [
            'usuario' => Auth::user(),
            'fechaHora' => now()->format('d/m/Y H:i:s'),

            'totalCompras' => $totalCompras,
            'totalVentas' => $totalVentas,
            'gananciaEstimada' => $gananciaEstimada,
            'valorInventario' => $valorInventario,

            'productosBajoStock' => $productosBajoStock,
            'productosSinStock' => $productosSinStock,
            'productosCriticos' => $productosCriticos,

            'empleadosActivos' => $empleadosActivos,
            'clientesActivos' => $clientesActivos,
            'proveedoresActivos' => $proveedoresActivos,

            'ultimasCompras' => $ultimasCompras,
            'ultimasVentas' => $ultimasVentas,

            'productosMasVendidos' => $productosMasVendidos,

            'entradasBodega' => $entradasBodega,
            'salidasBodega' => $salidasBodega,
            'ajustesBodega' => $ajustesBodega,

            'decision' => $this->generarDecisionDirectiva(),
        ];
    }

    private function generarDecisionDirectiva(): string
    {
        $totalCompras = (float) Compra::sum('total');
        $totalVentas = (float) Venta::sum('total');

        /*
        |--------------------------------------------------------------------------
        | 1. Prioridad máxima: productos sin stock
        |--------------------------------------------------------------------------
        */

        $productoSinStock = Producto::where('estado', 'Activo')
            ->where('stock_actual', '<=', 0)
            ->orderBy('nombre')
            ->first();

        if ($productoSinStock) {
            return 'El producto "' . $productoSinStock->nombre . '" está sin stock. Se recomienda realizar una compra urgente para evitar pérdida de ventas.';
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Segunda prioridad: productos bajo stock
        |--------------------------------------------------------------------------
        */

        $productoBajoStock = Producto::where('estado', 'Activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->where('stock_actual', '>', 0)
            ->orderBy('stock_actual')
            ->first();

        if ($productoBajoStock) {
            return 'El producto "' . $productoBajoStock->nombre . '" tiene stock bajo. Se recomienda reponer inventario antes de que se agote.';
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Productos con buena rotación
        |--------------------------------------------------------------------------
        */

        $productoMasVendido = Venta::query()
            ->selectRaw('producto_id, SUM(cantidad) as total_vendido')
            ->with('producto')
            ->groupBy('producto_id')
            ->orderByDesc('total_vendido')
            ->first();

        if ($productoMasVendido && $productoMasVendido->producto) {
            return 'El producto "' . $productoMasVendido->producto->nombre . '" es el más vendido actualmente. Se recomienda mantener stock suficiente para cubrir la demanda.';
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Análisis financiero simple
        |--------------------------------------------------------------------------
        */

        if ($totalCompras > $totalVentas) {
            return 'Las compras superan a las ventas. Esto puede ser normal si existe inventario disponible, pero se recomienda revisar la rotación de productos y evitar compras innecesarias.';
        }

        if ($totalVentas > $totalCompras) {
            return 'Las ventas superan a las compras. El negocio presenta un resultado comercial positivo según los registros actuales.';
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Sin alertas
        |--------------------------------------------------------------------------
        */

        return 'El sistema no detecta alertas críticas por el momento. Se recomienda continuar monitoreando compras, ventas e inventario.';
    }
}