<?php

namespace App\Models;

use App\Support\FormatoDatos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Empleado extends Model
{
    protected $fillable = [
        'codigo_empleado',
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
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'sueldo' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Empleado $empleado) {
            if (empty($empleado->codigo_empleado)) {
                $empleado->codigo_empleado = self::generarCodigoEmpleado();
            }
        });

        static::saving(function (Empleado $empleado) {
            /*
            |--------------------------------------------------------------------------
            | 1. Normalización de datos
            |--------------------------------------------------------------------------
            */

            $empleado->codigo_empleado = FormatoDatos::codigo($empleado->codigo_empleado);
            $empleado->cedula = FormatoDatos::soloNumeros($empleado->cedula);
            $empleado->nombres = FormatoDatos::nombrePersona($empleado->nombres);
            $empleado->apellidos = FormatoDatos::nombrePersona($empleado->apellidos);
            $empleado->cargo = FormatoDatos::espacios($empleado->cargo);
            $empleado->departamento = FormatoDatos::espacios($empleado->departamento);
            $empleado->telefono = FormatoDatos::soloNumeros($empleado->telefono);
            $empleado->correo = FormatoDatos::correo($empleado->correo);
            $empleado->estado = FormatoDatos::estado($empleado->estado);

            /*
            |--------------------------------------------------------------------------
            | 2. Validaciones
            |--------------------------------------------------------------------------
            */

            if (! self::validarCedulaEcuador($empleado->cedula)) {
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

            if (mb_strlen($empleado->nombres) < 2 || mb_strlen($empleado->nombres) > 80) {
                throw ValidationException::withMessages([
                    'nombres' => 'Los nombres deben tener entre 2 y 80 caracteres.',
                ]);
            }

            if (! preg_match('/^[\pL\s]+$/u', $empleado->nombres)) {
                throw ValidationException::withMessages([
                    'nombres' => 'Los nombres solo pueden contener letras y espacios.',
                ]);
            }

            if (mb_strlen($empleado->apellidos) < 2 || mb_strlen($empleado->apellidos) > 80) {
                throw ValidationException::withMessages([
                    'apellidos' => 'Los apellidos deben tener entre 2 y 80 caracteres.',
                ]);
            }

            if (! preg_match('/^[\pL\s]+$/u', $empleado->apellidos)) {
                throw ValidationException::withMessages([
                    'apellidos' => 'Los apellidos solo pueden contener letras y espacios.',
                ]);
            }

            if (! self::cargoValido($empleado->cargo)) {
                throw ValidationException::withMessages([
                    'cargo' => 'Seleccione un cargo válido.',
                ]);
            }

            if (! self::departamentoValido($empleado->departamento)) {
                throw ValidationException::withMessages([
                    'departamento' => 'Seleccione un departamento válido.',
                ]);
            }

            if (! preg_match('/^09[0-9]{8}$/', $empleado->telefono)) {
                throw ValidationException::withMessages([
                    'telefono' => 'El teléfono debe ser un celular ecuatoriano válido. Ejemplo: 0993050589.',
                ]);
            }

            if (! filter_var($empleado->correo, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'correo' => 'Ingrese un correo electrónico válido.',
                ]);
            }

            if (mb_strlen($empleado->correo) > 100) {
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

            if ($empleado->fecha_ingreso->isFuture()) {
                throw ValidationException::withMessages([
                    'fecha_ingreso' => 'La fecha de ingreso no puede ser futura.',
                ]);
            }

            if (! in_array($empleado->estado, ['Activo', 'Inactivo', 'Suspendido', 'Retirado'], true)) {
                throw ValidationException::withMessages([
                    'estado' => 'Seleccione un estado válido.',
                ]);
            }
        });
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

    private static function validarCedulaEcuador(string $cedula): bool
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
        return in_array($cargo, [
            'Gerente',
            'Administrador',
            'Vendedor',
            'Bodeguero',
            'Comprador',
            'Contador',
            'Asistente administrativo',
            'Jefe de talento humano',
            'Analista de sistemas',
        ], true);
    }

    private static function departamentoValido(string $departamento): bool
    {
        return in_array($departamento, [
            'Dirección',
            'Administración',
            'Talento Humano',
            'Bodega',
            'Compras',
            'Ventas',
            'Contabilidad',
            'Sistemas',
        ], true);
    }
}