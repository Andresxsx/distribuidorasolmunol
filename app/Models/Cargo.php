<?php

namespace App\Models;

use App\Support\FormatoDatos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Cargo extends Model
{
    protected $fillable = [
        'nombre',
        'departamento',
        'salario_base',
        'estado',
        'descripcion',
    ];

    protected $casts = [
        'salario_base' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (Cargo $cargo) {
            $cargo->nombre = FormatoDatos::espacios($cargo->nombre);
            $cargo->departamento = FormatoDatos::espacios($cargo->departamento);
            $cargo->descripcion = FormatoDatos::oracion($cargo->descripcion);
            $cargo->estado = FormatoDatos::estado($cargo->estado);

            if (mb_strlen((string) $cargo->nombre) < 2 || mb_strlen((string) $cargo->nombre) > 80) {
                throw ValidationException::withMessages([
                    'nombre' => 'El nombre del cargo debe tener entre 2 y 80 caracteres.',
                ]);
            }

            if (! self::departamentoValido((string) $cargo->departamento)) {
                throw ValidationException::withMessages([
                    'departamento' => 'Seleccione un departamento válido.',
                ]);
            }

            if ((float) $cargo->salario_base <= 0) {
                throw ValidationException::withMessages([
                    'salario_base' => 'El salario base debe ser mayor a cero.',
                ]);
            }

            if (! in_array($cargo->estado, ['Activo', 'Inactivo'], true)) {
                throw ValidationException::withMessages([
                    'estado' => 'Seleccione un estado válido.',
                ]);
            }
        });
    }

    public function empleados()
    {
        return $this->hasMany(Empleado::class);
    }

    public static function departamentos(): array
    {
        return [
            'Dirección' => 'Dirección',
            'Administración' => 'Administración',
            'Talento Humano' => 'Talento Humano',
            'Bodega' => 'Bodega',
            'Compras' => 'Compras',
            'Ventas' => 'Ventas',
            'Contabilidad' => 'Contabilidad',
            'Sistemas' => 'Sistemas',
        ];
    }

    public static function departamentoValido(string $departamento): bool
    {
        return array_key_exists($departamento, self::departamentos());
    }
}
