<?php

namespace App\Models;

use App\Support\FormatoDatos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class Empleado extends Model
{
    public const FECHA_MINIMA_INGRESO = '2000-01-01';

    public const APORTE_PERSONAL_IESS = 9.45;

    public const APORTE_PATRONAL_IESS = 11.15;

    public const TOTAL_APORTE_IESS = 20.60;

    protected $fillable = [
        'codigo_empleado',
        'cargo_id',
        'cedula',
        'nombres',
        'apellidos',
        'cargo',
        'departamento',
        'telefono',
        'correo',
        'sueldo',
        'fecha_ingreso',
        'estado',
        'tiene_seguro',
        'tipo_seguro',
        'numero_afiliacion',
        'estado_seguro',
        'fecha_afiliacion',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'fecha_afiliacion' => 'date',
        'sueldo' => 'decimal:2',
        'tiene_seguro' => 'boolean',
    ];

    protected $appends = [
        'aporte_personal_iess',
        'aporte_patronal_iess',
        'total_aporte_iess',
        'total_sanciones_aplicadas',
        'total_descuentos_empleado',
        'sueldo_neto_estimado',
    ];

    protected static function booted(): void
    {
        static::creating(function (Empleado $empleado) {
            if (empty($empleado->codigo_empleado)) {
                $empleado->codigo_empleado = self::generarCodigoEmpleado();
            }
        });

        static::saving(function (Empleado $empleado) {
            if ($empleado->cargo_id) {
                $cargo = Cargo::find($empleado->cargo_id);

                if (! $cargo || $cargo->estado !== 'Activo') {
                    throw ValidationException::withMessages([
                        'cargo_id' => 'Seleccione un cargo activo válido.',
                    ]);
                }

                $empleado->cargo = $cargo->nombre;
                $empleado->departamento = $cargo->departamento;
                $empleado->sueldo = $cargo->salario_base;
            } elseif ($empleado->cargo) {
                $cargo = Cargo::where('nombre', $empleado->cargo)->first();

                if ($cargo) {
                    $empleado->cargo_id = $cargo->id;
                    $empleado->cargo = $cargo->nombre;
                    $empleado->departamento = $cargo->departamento;
                    $empleado->sueldo = $cargo->salario_base;
                }
            }

            $empleado->codigo_empleado = FormatoDatos::codigo($empleado->codigo_empleado);
            $empleado->cedula = FormatoDatos::soloNumeros($empleado->cedula);
            $empleado->nombres = FormatoDatos::nombrePersona($empleado->nombres);
            $empleado->apellidos = FormatoDatos::nombrePersona($empleado->apellidos);
            $empleado->cargo = FormatoDatos::espacios($empleado->cargo);
            $empleado->departamento = FormatoDatos::espacios($empleado->departamento);
            $empleado->telefono = FormatoDatos::soloNumeros($empleado->telefono);
            $empleado->correo = FormatoDatos::correo($empleado->correo);
            $empleado->estado = FormatoDatos::estado($empleado->estado);

            if (! self::validarCedulaEcuador((string) $empleado->cedula)) {
                throw ValidationException::withMessages([
                    'cedula' => 'La cédula ingresada no es válida para Ecuador.',
                ]);
            }

            $cedulaDuplicada = self::where('cedula', $empleado->cedula)
                ->when($empleado->exists, fn ($query) => $query->where('id', '!=', $empleado->id))
                ->exists();

            if ($cedulaDuplicada) {
                throw ValidationException::withMessages([
                    'cedula' => 'Ya existe un empleado registrado con esta cédula.',
                ]);
            }

            if (mb_strlen((string) $empleado->nombres) < 2 || mb_strlen((string) $empleado->nombres) > 80) {
                throw ValidationException::withMessages([
                    'nombres' => 'Los nombres deben tener entre 2 y 80 caracteres.',
                ]);
            }

            if (! preg_match('/^[\pL\s]+$/u', (string) $empleado->nombres)) {
                throw ValidationException::withMessages([
                    'nombres' => 'Los nombres solo pueden contener letras y espacios.',
                ]);
            }

            if (mb_strlen((string) $empleado->apellidos) < 2 || mb_strlen((string) $empleado->apellidos) > 80) {
                throw ValidationException::withMessages([
                    'apellidos' => 'Los apellidos deben tener entre 2 y 80 caracteres.',
                ]);
            }

            if (! preg_match('/^[\pL\s]+$/u', (string) $empleado->apellidos)) {
                throw ValidationException::withMessages([
                    'apellidos' => 'Los apellidos solo pueden contener letras y espacios.',
                ]);
            }

            if (! $empleado->cargo_id && ! self::cargoValido((string) $empleado->cargo)) {
                throw ValidationException::withMessages([
                    'cargo_id' => 'Seleccione un cargo válido.',
                ]);
            }

            if (! Cargo::departamentoValido((string) $empleado->departamento)) {
                throw ValidationException::withMessages([
                    'departamento' => 'Seleccione un departamento válido.',
                ]);
            }

            if (! preg_match('/^09[0-9]{8}$/', (string) $empleado->telefono)) {
                throw ValidationException::withMessages([
                    'telefono' => 'El teléfono debe ser un celular ecuatoriano válido. Ejemplo: 0993050589.',
                ]);
            }

            if (! filter_var($empleado->correo, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'correo' => 'Ingrese un correo electrónico válido.',
                ]);
            }

            if (mb_strlen((string) $empleado->correo) > 100) {
                throw ValidationException::withMessages([
                    'correo' => 'El correo no debe superar los 100 caracteres.',
                ]);
            }

            if ((float) $empleado->sueldo <= 0) {
                throw ValidationException::withMessages([
                    'sueldo' => 'El sueldo debe ser mayor a cero.',
                ]);
            }

            if (! $empleado->fecha_ingreso) {
                throw ValidationException::withMessages([
                    'fecha_ingreso' => 'La fecha de ingreso es obligatoria.',
                ]);
            }

            $fechaIngreso = Carbon::parse($empleado->fecha_ingreso)->startOfDay();
            $fechaMinima = Carbon::parse(self::FECHA_MINIMA_INGRESO)->startOfDay();
            $hoy = Carbon::today();

            if ($fechaIngreso->isFuture() || $fechaIngreso->gt($hoy)) {
                throw ValidationException::withMessages([
                    'fecha_ingreso' => 'La fecha de ingreso no puede ser futura.',
                ]);
            }

            if ($fechaIngreso->lt($fechaMinima)) {
                throw ValidationException::withMessages([
                    'fecha_ingreso' => 'La fecha de ingreso no puede ser anterior al 01/01/2000. Revise que no haya escrito una fecha incoherente.',
                ]);
            }

            if (! in_array($empleado->estado, ['Activo', 'Inactivo', 'Suspendido', 'Retirado'], true)) {
                throw ValidationException::withMessages([
                    'estado' => 'Seleccione un estado válido.',
                ]);
            }

            // Seguro fijo ecuatoriano: todo empleado registrado queda bajo IESS.
            // El número de afiliación se usa como la cédula cuando no se ingresa otro dato.
            $empleado->tiene_seguro = true;
            $empleado->tipo_seguro = 'IESS';
            $empleado->numero_afiliacion = FormatoDatos::codigo($empleado->numero_afiliacion ?: $empleado->cedula);
            $empleado->fecha_afiliacion = $fechaIngreso->toDateString();
            $empleado->estado_seguro = in_array($empleado->estado, ['Retirado', 'Inactivo'], true) ? 'Inactivo' : 'Activo';
        });
    }

    public function cargoAsignado()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function sanciones()
    {
        return $this->hasMany(SancionEmpleado::class);
    }

    public function sancionesAplicadas()
    {
        return $this->hasMany(SancionEmpleado::class)->where('estado', 'Aplicada');
    }

    public function getAportePersonalIessAttribute(): float
    {
        return round(((float) $this->sueldo * self::APORTE_PERSONAL_IESS) / 100, 2);
    }

    public function getAportePatronalIessAttribute(): float
    {
        return round(((float) $this->sueldo * self::APORTE_PATRONAL_IESS) / 100, 2);
    }

    public function getTotalAporteIessAttribute(): float
    {
        return round($this->aporte_personal_iess + $this->aporte_patronal_iess, 2);
    }

    public function getTotalSancionesAplicadasAttribute(): float
    {
        return round((float) $this->sancionesAplicadas()->sum('valor_descuento'), 2);
    }

    public function getTotalDescuentosEmpleadoAttribute(): float
    {
        return round($this->aporte_personal_iess + $this->total_sanciones_aplicadas, 2);
    }

    public function getSueldoNetoEstimadoAttribute(): float
    {
        return max(round((float) $this->sueldo - (float) $this->total_descuentos_empleado, 2), 0);
    }

    private static function generarCodigoEmpleado(): string
    {
        $siguiente = ((int) self::max('id')) + 1;

        do {
            $codigo = 'EMP-' . str_pad((string) $siguiente, 6, '0', STR_PAD_LEFT);
            $siguiente++;
        } while (self::where('codigo_empleado', $codigo)->exists());

        return $codigo;
    }

    public static function validarCedulaEcuador(string $cedula): bool
    {
        if (! preg_match('/^[0-9]{10}$/', $cedula)) {
            return false;
        }

        if (preg_match('/^(\d)\1{9}$/', $cedula)) {
            return false;
        }

        $provincia = intval(substr($cedula, 0, 2));
        $tercerDigito = intval($cedula[2]);

        if ($provincia < 1 || $provincia > 24) {
            return false;
        }

        if ($tercerDigito > 5) {
            return false;
        }

        $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $suma = 0;

        for ($i = 0; $i < 9; $i++) {
            $valor = intval($cedula[$i]) * $coeficientes[$i];

            if ($valor >= 10) {
                $valor -= 9;
            }

            $suma += $valor;
        }

        $digitoCalculado = (10 - ($suma % 10)) % 10;
        $digitoReal = intval($cedula[9]);

        return $digitoCalculado === $digitoReal;
    }

    private static function cargoValido(string $cargo): bool
    {
        return Cargo::where('nombre', $cargo)->exists();
    }
}
