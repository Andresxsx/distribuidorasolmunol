<?php

namespace App\Models;

use App\Support\FormatoDatos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Cliente extends Model
{
    protected $fillable = [
        'cedula_ruc',
        'nombre',
        'telefono',
        'correo',
        'direccion',
        'estado',
    ];

    protected static function booted(): void
    {
        static::saving(function (Cliente $cliente) {
            /*
            |--------------------------------------------------------------------------
            | 1. Normalización de datos
            |--------------------------------------------------------------------------
            | Primero limpiamos y damos formato elegante a los datos.
            */

            $cliente->cedula_ruc = FormatoDatos::soloNumeros($cliente->cedula_ruc);
            $cliente->nombre = FormatoDatos::nombrePersona($cliente->nombre);
            $cliente->telefono = FormatoDatos::soloNumeros($cliente->telefono);
            $cliente->correo = FormatoDatos::correo($cliente->correo);
            $cliente->direccion = FormatoDatos::direccion($cliente->direccion);
            $cliente->estado = FormatoDatos::estado($cliente->estado);

            /*
            |--------------------------------------------------------------------------
            | 2. Validaciones
            |--------------------------------------------------------------------------
            | Después de formatear, validamos que los datos sean reales y correctos.
            */

            if (! self::validarCedulaRucEcuador($cliente->cedula_ruc)) {
                throw ValidationException::withMessages([
                    'cedula_ruc' => 'La cédula o RUC ingresado no es válido para Ecuador.',
                ]);
            }

            if (mb_strlen($cliente->nombre) < 3 || mb_strlen($cliente->nombre) > 120) {
                throw ValidationException::withMessages([
                    'nombre' => 'El nombre del cliente debe tener entre 3 y 120 caracteres.',
                ]);
            }

            if (! preg_match('/^[\pL\s]+$/u', $cliente->nombre)) {
                throw ValidationException::withMessages([
                    'nombre' => 'El nombre del cliente solo puede contener letras y espacios.',
                ]);
            }

            if (! self::validarTelefonoEcuador($cliente->telefono)) {
                throw ValidationException::withMessages([
                    'telefono' => 'El teléfono debe ser válido para Ecuador. Use celular 09XXXXXXXX o convencional 02XXXXXXX, 03XXXXXXX, etc.',
                ]);
            }

            if (! filter_var($cliente->correo, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'correo' => 'Ingrese un correo electrónico válido.',
                ]);
            }

            if (mb_strlen($cliente->correo) > 100) {
                throw ValidationException::withMessages([
                    'correo' => 'El correo no debe superar los 100 caracteres.',
                ]);
            }

            if ($cliente->direccion && (mb_strlen($cliente->direccion) < 5 || mb_strlen($cliente->direccion) > 150)) {
                throw ValidationException::withMessages([
                    'direccion' => 'La dirección debe tener entre 5 y 150 caracteres.',
                ]);
            }

            if (! in_array($cliente->estado, ['Activo', 'Inactivo'], true)) {
                throw ValidationException::withMessages([
                    'estado' => 'El estado solo puede ser Activo o Inactivo.',
                ]);
            }
        });
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    private static function validarCedulaRucEcuador(string $documento): bool
    {
        if (! preg_match('/^[0-9]+$/', $documento)) {
            return false;
        }

        if (strlen($documento) === 10) {
            return self::validarCedulaEcuador($documento);
        }

        if (strlen($documento) === 13) {
            return self::validarRucEcuador($documento);
        }

        return false;
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

    private static function validarRucEcuador(string $ruc): bool
    {
        if (! preg_match('/^[0-9]{13}$/', $ruc)) {
            return false;
        }

        if (preg_match('/^(\d)\1{12}$/', $ruc)) {
            return false;
        }

        $provincia = intval(substr($ruc, 0, 2));
        $tercerDigito = intval($ruc[2]);

        if ($provincia < 1 || $provincia > 24) {
            return false;
        }

        // RUC de persona natural: cédula válida + establecimiento mayor a 000
        if ($tercerDigito >= 0 && $tercerDigito <= 5) {
            $cedula = substr($ruc, 0, 10);
            $establecimiento = substr($ruc, 10, 3);

            return self::validarCedulaEcuador($cedula) && intval($establecimiento) > 0;
        }

        // RUC de entidad pública
        if ($tercerDigito === 6) {
            return self::validarRucPublico($ruc);
        }

        // RUC de sociedad privada
        if ($tercerDigito === 9) {
            return self::validarRucPrivado($ruc);
        }

        return false;
    }

    private static function validarRucPrivado(string $ruc): bool
    {
        $establecimiento = substr($ruc, 10, 3);

        if (intval($establecimiento) <= 0) {
            return false;
        }

        $coeficientes = [4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;

        for ($i = 0; $i < 9; $i++) {
            $suma += intval($ruc[$i]) * $coeficientes[$i];
        }

        $residuo = $suma % 11;
        $digitoCalculado = 11 - $residuo;

        if ($digitoCalculado === 11) {
            $digitoCalculado = 0;
        }

        if ($digitoCalculado === 10) {
            return false;
        }

        return $digitoCalculado === intval($ruc[9]);
    }

    private static function validarRucPublico(string $ruc): bool
    {
        $establecimiento = substr($ruc, 9, 4);

        if (intval($establecimiento) <= 0) {
            return false;
        }

        $coeficientes = [3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;

        for ($i = 0; $i < 8; $i++) {
            $suma += intval($ruc[$i]) * $coeficientes[$i];
        }

        $residuo = $suma % 11;
        $digitoCalculado = 11 - $residuo;

        if ($digitoCalculado === 11) {
            $digitoCalculado = 0;
        }

        if ($digitoCalculado === 10) {
            return false;
        }

        return $digitoCalculado === intval($ruc[8]);
    }

    private static function validarTelefonoEcuador(string $telefono): bool
    {
        return preg_match('/^(09[0-9]{8}|0[2-7][0-9]{7})$/', $telefono) === 1;
    }
}