<?php

namespace App\Models;

use App\Support\FormatoDatos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class Venta extends Model
{
    protected $fillable = [
        'numero_venta',
        'cliente_id',
        'producto_id',
        'fecha',
        'cantidad',
        'precio_unitario',
        'total',
        'user_id',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Venta $venta) {
            if (empty($venta->numero_venta)) {
                $venta->numero_venta = self::generarNumeroVenta();
            }

            if (! $venta->user_id && Auth::check()) {
                $venta->user_id = Auth::id();
            }
        });

        static::saving(function (Venta $venta) {
            /*
            |--------------------------------------------------------------------------
            | 1. Normalización de datos
            |--------------------------------------------------------------------------
            */

            $venta->numero_venta = FormatoDatos::codigo($venta->numero_venta);
            $venta->observacion = FormatoDatos::oracion($venta->observacion);

            /*
            |--------------------------------------------------------------------------
            | 2. Validaciones generales
            |--------------------------------------------------------------------------
            */

            if (! $venta->fecha) {
                throw ValidationException::withMessages([
                    'fecha' => 'La fecha de venta es obligatoria.',
                ]);
            }

            if ($venta->fecha->isFuture()) {
                throw ValidationException::withMessages([
                    'fecha' => 'La fecha de venta no puede ser futura.',
                ]);
            }

            $cliente = Cliente::find($venta->cliente_id);

            if (! $cliente || $cliente->estado !== 'Activo') {
                throw ValidationException::withMessages([
                    'cliente_id' => 'Seleccione un cliente activo.',
                ]);
            }

            $producto = Producto::find($venta->producto_id);

            if (! $producto || $producto->estado !== 'Activo') {
                throw ValidationException::withMessages([
                    'producto_id' => 'Seleccione un producto activo.',
                ]);
            }

            if ((int) $venta->cantidad <= 0) {
                throw ValidationException::withMessages([
                    'cantidad' => 'La cantidad vendida debe ser mayor a cero.',
                ]);
            }

            if ((float) $venta->precio_unitario <= 0) {
                throw ValidationException::withMessages([
                    'precio_unitario' => 'El precio unitario debe ser mayor a cero.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 3. Validación de stock
            |--------------------------------------------------------------------------
            | Si es venta nueva, usa el stock actual.
            | Si se edita la misma venta con el mismo producto, devuelve virtualmente
            | la cantidad anterior para calcular el stock disponible real.
            */

            $stockDisponible = (int) $producto->stock_actual;

            if (
                $venta->exists &&
                (int) $venta->getOriginal('producto_id') === (int) $venta->producto_id
            ) {
                $stockDisponible += (int) $venta->getOriginal('cantidad');
            }

            if ((int) $venta->cantidad > $stockDisponible) {
                throw ValidationException::withMessages([
                    'cantidad' => 'No hay suficiente stock disponible. Stock actual disponible: ' . $stockDisponible . '.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 4. Cálculo automático
            |--------------------------------------------------------------------------
            */

            $venta->total = round((int) $venta->cantidad * (float) $venta->precio_unitario, 2);
        });

        static::created(function (Venta $venta) {
            $producto = Producto::find($venta->producto_id);

            if ($producto) {
                $stockAnterior = (int) $producto->stock_actual;

                $producto->decrement('stock_actual', (int) $venta->cantidad);
                $producto->refresh();

                MovimientoBodega::registrar([
                    'producto_id' => $producto->id,
                    'tipo_movimiento' => 'Salida',
                    'origen' => 'Venta',
                    'documento_referencia' => $venta->numero_venta,
                    'cantidad' => (int) $venta->cantidad,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => (int) $producto->stock_actual,
                    'user_id' => $venta->user_id,
                    'observacion' => 'Salida por venta registrada.',
                ]);
            }
        });

        static::updated(function (Venta $venta) {
            $productoAnteriorId = (int) $venta->getOriginal('producto_id');
            $cantidadAnterior = (int) $venta->getOriginal('cantidad');

            /*
            |--------------------------------------------------------------------------
            | Caso 1: Se edita la venta pero se mantiene el mismo producto
            |--------------------------------------------------------------------------
            */

            if ($productoAnteriorId === (int) $venta->producto_id) {
                $producto = Producto::find($venta->producto_id);

                if ($producto) {
                    $diferencia = (int) $venta->cantidad - $cantidadAnterior;

                    if ($diferencia > 0) {
                        $stockAnterior = (int) $producto->stock_actual;

                        $producto->decrement('stock_actual', $diferencia);
                        $producto->refresh();

                        MovimientoBodega::registrar([
                            'producto_id' => $producto->id,
                            'tipo_movimiento' => 'Ajuste',
                            'origen' => 'Venta',
                            'documento_referencia' => $venta->numero_venta,
                            'cantidad' => $diferencia,
                            'stock_anterior' => $stockAnterior,
                            'stock_nuevo' => (int) $producto->stock_actual,
                            'user_id' => $venta->user_id,
                            'observacion' => 'Ajuste por aumento de cantidad en venta.',
                        ]);
                    }

                    if ($diferencia < 0) {
                        $stockAnterior = (int) $producto->stock_actual;

                        $producto->increment('stock_actual', abs($diferencia));
                        $producto->refresh();

                        MovimientoBodega::registrar([
                            'producto_id' => $producto->id,
                            'tipo_movimiento' => 'Ajuste',
                            'origen' => 'Venta',
                            'documento_referencia' => $venta->numero_venta,
                            'cantidad' => abs($diferencia),
                            'stock_anterior' => $stockAnterior,
                            'stock_nuevo' => (int) $producto->stock_actual,
                            'user_id' => $venta->user_id,
                            'observacion' => 'Ajuste por reducción de cantidad en venta.',
                        ]);
                    }
                }

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Caso 2: Se cambió el producto de la venta
            |--------------------------------------------------------------------------
            */

            $productoAnterior = Producto::find($productoAnteriorId);

            if ($productoAnterior) {
                $stockAnterior = (int) $productoAnterior->stock_actual;

                $productoAnterior->increment('stock_actual', $cantidadAnterior);
                $productoAnterior->refresh();

                MovimientoBodega::registrar([
                    'producto_id' => $productoAnterior->id,
                    'tipo_movimiento' => 'Ajuste',
                    'origen' => 'Venta',
                    'documento_referencia' => $venta->numero_venta,
                    'cantidad' => $cantidadAnterior,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => (int) $productoAnterior->stock_actual,
                    'user_id' => $venta->user_id,
                    'observacion' => 'Ajuste por cambio de producto en venta.',
                ]);
            }

            $productoNuevo = Producto::find($venta->producto_id);

            if ($productoNuevo) {
                $stockAnterior = (int) $productoNuevo->stock_actual;

                $productoNuevo->decrement('stock_actual', (int) $venta->cantidad);
                $productoNuevo->refresh();

                MovimientoBodega::registrar([
                    'producto_id' => $productoNuevo->id,
                    'tipo_movimiento' => 'Ajuste',
                    'origen' => 'Venta',
                    'documento_referencia' => $venta->numero_venta,
                    'cantidad' => (int) $venta->cantidad,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => (int) $productoNuevo->stock_actual,
                    'user_id' => $venta->user_id,
                    'observacion' => 'Ajuste de salida por nuevo producto en venta.',
                ]);
            }
        });

        static::deleted(function (Venta $venta) {
            $producto = Producto::find($venta->producto_id);

            if ($producto) {
                $stockAnterior = (int) $producto->stock_actual;

                $producto->increment('stock_actual', (int) $venta->cantidad);
                $producto->refresh();

                MovimientoBodega::registrar([
                    'producto_id' => $producto->id,
                    'tipo_movimiento' => 'Entrada',
                    'origen' => 'Corrección',
                    'documento_referencia' => $venta->numero_venta,
                    'cantidad' => (int) $venta->cantidad,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => (int) $producto->stock_actual,
                    'user_id' => $venta->user_id,
                    'observacion' => 'Entrada por eliminación de venta.',
                ]);
            }
        });
    }

    private static function generarNumeroVenta(): string
    {
        $siguiente = ((int) self::max('id')) + 1;

        do {
            $numero = 'VENT-' . str_pad((string) $siguiente, 6, '0', STR_PAD_LEFT);
            $siguiente++;
        } while (self::where('numero_venta', $numero)->exists());

        return $numero;
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}