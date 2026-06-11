<?php

namespace App\Models;

use App\Support\FormatoDatos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SancionEmpleado extends Model
{
    protected $table = 'sancion_empleados';

    protected $fillable = [
        'empleado_id',
        'fecha',
        'tipo',
        'motivo',
        'valor_descuento',
        'estado',
        'observacion',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'valor_descuento' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (SancionEmpleado $sancion) {
            if (! $sancion->user_id && Auth::check()) {
                $sancion->user_id = Auth::id();
            }
        });

        static::saving(function (SancionEmpleado $sancion) {
            $sancion->tipo = FormatoDatos::espacios($sancion->tipo);
            $sancion->motivo = FormatoDatos::oracion($sancion->motivo);
            $sancion->observacion = FormatoDatos::oracion($sancion->observacion);
            $sancion->estado = FormatoDatos::estado($sancion->estado);

            if (! Empleado::find($sancion->empleado_id)) {
                throw ValidationException::withMessages([
                    'empleado_id' => 'Seleccione un empleado válido.',
                ]);
            }

            if (! $sancion->fecha) {
                throw ValidationException::withMessages([
                    'fecha' => 'La fecha de la sanción es obligatoria.',
                ]);
            }

            if ($sancion->fecha->isFuture()) {
                throw ValidationException::withMessages([
                    'fecha' => 'La fecha de sanción no puede ser futura.',
                ]);
            }

            if (! in_array($sancion->tipo, array_keys(self::tipos()), true)) {
                throw ValidationException::withMessages([
                    'tipo' => 'Seleccione un tipo de sanción válido.',
                ]);
            }

            if (mb_strlen((string) $sancion->motivo) < 5 || mb_strlen((string) $sancion->motivo) > 200) {
                throw ValidationException::withMessages([
                    'motivo' => 'El motivo debe tener entre 5 y 200 caracteres.',
                ]);
            }

            if ((float) $sancion->valor_descuento < 0) {
                throw ValidationException::withMessages([
                    'valor_descuento' => 'El descuento no puede ser negativo.',
                ]);
            }

            if (! in_array($sancion->estado, array_keys(self::estados()), true)) {
                throw ValidationException::withMessages([
                    'estado' => 'Seleccione un estado válido.',
                ]);
            }
        });
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function tipos(): array
    {
        return [
            'Llamado de atención' => 'Llamado de atención',
            'Advertencia documentada' => 'Advertencia documentada',
            'Descuento salarial' => 'Descuento salarial',
            'Suspensión preventiva' => 'Suspensión preventiva',
            'Plan de mejora' => 'Plan de mejora',
            'Capacitación obligatoria' => 'Capacitación obligatoria',
        ];
    }

    public static function estados(): array
    {
        return [
            'Pendiente' => 'Pendiente',
            'Aplicada' => 'Aplicada',
            'Anulada' => 'Anulada',
        ];
    }
}
