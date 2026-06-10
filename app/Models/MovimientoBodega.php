<?php

namespace App\Models;

use App\Support\FormatoDatos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MovimientoBodega extends Model
{
    protected $table = 'movimientos_bodega';

    protected $fillable = [
        'codigo_movimiento',
        'producto_id',
        'tipo_movimiento',
        'origen',
        'documento_referencia',
        'cantidad',
        'stock_anterior',
        'stock_nuevo',
        'user_id',
        'fecha',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'cantidad' => 'integer',
        'stock_anterior' => 'integer',
        'stock_nuevo' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (MovimientoBodega $movimiento) {
            if (empty($movimiento->codigo_movimiento)) {
                $movimiento->codigo_movimiento = self::generarCodigoMovimiento();
            }

            if (! $movimiento->user_id && Auth::check()) {
                $movimiento->user_id = Auth::id();
            }

            if (! $movimiento->fecha) {
                $movimiento->fecha = now();
            }
        });

        static::saving(function (MovimientoBodega $movimiento) {
            /*
            |--------------------------------------------------------------------------
            | 1. Normalización de datos
            |--------------------------------------------------------------------------
            */

            $movimiento->codigo_movimiento = FormatoDatos::codigo($movimiento->codigo_movimiento);
            $movimiento->documento_referencia = FormatoDatos::codigo($movimiento->documento_referencia);
            $movimiento->observacion = FormatoDatos::oracion($movimiento->observacion);

            /*
            |--------------------------------------------------------------------------
            | 2. Validaciones
            |--------------------------------------------------------------------------
            */

            if (! Producto::whereKey($movimiento->producto_id)->exists()) {
                throw ValidationException::withMessages([
                    'producto_id' => 'El producto del movimiento no existe.',
                ]);
            }

            if (! in_array($movimiento->tipo_movimiento, ['Entrada', 'Salida', 'Ajuste'], true)) {
                throw ValidationException::withMessages([
                    'tipo_movimiento' => 'El tipo de movimiento no es válido.',
                ]);
            }

            if (! in_array($movimiento->origen, ['Compra', 'Venta', 'Corrección'], true)) {
                throw ValidationException::withMessages([
                    'origen' => 'El origen del movimiento no es válido.',
                ]);
            }

            if ((int) $movimiento->cantidad <= 0) {
                throw ValidationException::withMessages([
                    'cantidad' => 'La cantidad del movimiento debe ser mayor a cero.',
                ]);
            }

            if ((int) $movimiento->stock_anterior < 0) {
                throw ValidationException::withMessages([
                    'stock_anterior' => 'El stock anterior no puede ser negativo.',
                ]);
            }

            if ((int) $movimiento->stock_nuevo < 0) {
                throw ValidationException::withMessages([
                    'stock_nuevo' => 'El stock nuevo no puede ser negativo.',
                ]);
            }

            if ($movimiento->documento_referencia && mb_strlen($movimiento->documento_referencia) > 30) {
                throw ValidationException::withMessages([
                    'documento_referencia' => 'El documento de referencia no debe superar los 30 caracteres.',
                ]);
            }

            if ($movimiento->observacion && mb_strlen($movimiento->observacion) > 200) {
                throw ValidationException::withMessages([
                    'observacion' => 'La observación no debe superar los 200 caracteres.',
                ]);
            }
        });
    }

    private static function generarCodigoMovimiento(): string
    {
        $siguiente = ((int) self::max('id')) + 1;

        do {
            $codigo = 'MOV-' . str_pad((string) $siguiente, 6, '0', STR_PAD_LEFT);
            $siguiente++;
        } while (self::where('codigo_movimiento', $codigo)->exists());

        return $codigo;
    }

    public static function registrar(array $datos): self
    {
        return self::create($datos);
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