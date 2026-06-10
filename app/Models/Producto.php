<?php

namespace App\Models;

use App\Support\FormatoDatos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Producto extends Model
{
    protected $fillable = [
        'codigo',
        'nombre',
        'categoria',
        'descripcion',
        'stock_actual',
        'stock_minimo',
        'precio_compra',
        'precio_venta',
        'estado',
    ];

    protected static function booted(): void
    {
        static::creating(function (Producto $producto) {
            if (empty($producto->codigo)) {
                $producto->codigo = self::generarCodigoProducto();
            }

            if ($producto->stock_actual === null) {
                $producto->stock_actual = 0;
            }
        });

        static::saving(function (Producto $producto) {
            /*
            |--------------------------------------------------------------------------
            | 1. Normalización de datos
            |--------------------------------------------------------------------------
            */

            $producto->codigo = FormatoDatos::codigo($producto->codigo);
            $producto->nombre = FormatoDatos::titulo($producto->nombre);
            $producto->categoria = FormatoDatos::espacios($producto->categoria);
            $producto->descripcion = FormatoDatos::oracion($producto->descripcion);
            $producto->estado = FormatoDatos::estado($producto->estado);

            /*
            |--------------------------------------------------------------------------
            | 2. Validaciones
            |--------------------------------------------------------------------------
            */

            if (mb_strlen($producto->nombre) < 2 || mb_strlen($producto->nombre) > 100) {
                throw ValidationException::withMessages([
                    'nombre' => 'El nombre del producto debe tener entre 2 y 100 caracteres.',
                ]);
            }

            if (! preg_match('/^[\pL\pN\s.,&\/()\-]+$/u', $producto->nombre)) {
                throw ValidationException::withMessages([
                    'nombre' => 'El nombre solo puede contener letras, números, espacios y signos básicos.',
                ]);
            }

            if (! self::categoriaValida($producto->categoria)) {
                throw ValidationException::withMessages([
                    'categoria' => 'Seleccione una categoría válida.',
                ]);
            }

            if ($producto->descripcion && mb_strlen($producto->descripcion) > 250) {
                throw ValidationException::withMessages([
                    'descripcion' => 'La descripción no debe superar los 250 caracteres.',
                ]);
            }

            if ((int) $producto->stock_actual < 0) {
                throw ValidationException::withMessages([
                    'stock_actual' => 'El stock actual no puede ser negativo.',
                ]);
            }

            if ((int) $producto->stock_minimo < 0) {
                throw ValidationException::withMessages([
                    'stock_minimo' => 'El stock mínimo no puede ser negativo.',
                ]);
            }

            if ((float) $producto->precio_compra < 0) {
                throw ValidationException::withMessages([
                    'precio_compra' => 'El precio de compra no puede ser negativo.',
                ]);
            }

            if ((float) $producto->precio_venta < 0) {
                throw ValidationException::withMessages([
                    'precio_venta' => 'El precio de venta no puede ser negativo.',
                ]);
            }

            if ((float) $producto->precio_venta < (float) $producto->precio_compra) {
                throw ValidationException::withMessages([
                    'precio_venta' => 'El precio de venta no debe ser menor al precio de compra.',
                ]);
            }

            if (! in_array($producto->estado, ['Activo', 'Inactivo'], true)) {
                throw ValidationException::withMessages([
                    'estado' => 'El estado solo puede ser Activo o Inactivo.',
                ]);
            }
        });
    }

    private static function generarCodigoProducto(): string
    {
        $siguiente = ((int) self::max('id')) + 1;

        do {
            $codigo = 'PROD-' . str_pad((string) $siguiente, 6, '0', STR_PAD_LEFT);
            $siguiente++;
        } while (self::where('codigo', $codigo)->exists());

        return $codigo;
    }

    private static function categoriaValida(string $categoria): bool
{
    return in_array($categoria, [
        'Víveres y abarrotes',
        'Granos y cereales',
        'Bebidas',
        'Limpieza',
        'Tecnología',
        'Oficina',
        'Ferretería',
        'Farmacia',
        'Otros',
    ], true);
}

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    public function movimientosBodega()
    {
        return $this->hasMany(MovimientoBodega::class);
    }
}