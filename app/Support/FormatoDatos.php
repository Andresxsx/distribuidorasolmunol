<?php

namespace App\Support;

class FormatoDatos
{
    public static function espacios(?string $valor): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $valor));
    }

    public static function soloNumeros(?string $valor): string
    {
        return preg_replace('/\D+/', '', (string) $valor);
    }

    public static function correo(?string $valor): string
    {
        return mb_strtolower(self::espacios($valor), 'UTF-8');
    }

    public static function codigo(?string $valor): string
    {
        return mb_strtoupper(self::espacios($valor), 'UTF-8');
    }

    public static function nombrePersona(?string $valor): string
    {
        $valor = mb_strtolower(self::espacios($valor), 'UTF-8');

        return mb_convert_case($valor, MB_CASE_TITLE, 'UTF-8');
    }

    public static function titulo(?string $valor): string
    {
        $valor = mb_strtolower(self::espacios($valor), 'UTF-8');
        $valor = mb_convert_case($valor, MB_CASE_TITLE, 'UTF-8');

        $palabrasMinusculas = [
            'De', 'Del', 'La', 'Las', 'Los', 'El', 'Y', 'En', 'A', 'Por', 'Para',
        ];

        $partes = explode(' ', $valor);

        foreach ($partes as $i => $palabra) {
            if ($i > 0 && in_array($palabra, $palabrasMinusculas, true)) {
                $partes[$i] = mb_strtolower($palabra, 'UTF-8');
            }
        }

        return implode(' ', $partes);
    }

    public static function razonSocial(?string $valor): string
    {
        $valor = self::espacios($valor);
        $valor = mb_strtoupper($valor, 'UTF-8');

        $valor = str_replace([' S A', ' S. A', ' S.A'], ' S.A.', $valor);
        $valor = str_replace([' LTDA', ' LTDA.'], ' LTDA.', $valor);

        return $valor;
    }

    public static function direccion(?string $valor): string
    {
        $valor = self::titulo($valor);

        $reemplazos = [
            '/\bAv\b\.?/u' => 'Av.',
            '/\bCdla\b\.?/u' => 'Cdla.',
            '/\bCoop\b\.?/u' => 'Coop.',
            '/\bMz\b\.?/u' => 'Mz.',
            '/\bNro\b\.?/u' => 'Nro.',
            '/\bKm\b\.?/u' => 'Km',
        ];

        foreach ($reemplazos as $patron => $reemplazo) {
            $valor = preg_replace($patron, $reemplazo, $valor);
        }

        return $valor;
    }

    public static function oracion(?string $valor): string
    {
        $valor = mb_strtolower(self::espacios($valor), 'UTF-8');

        if ($valor === '') {
            return '';
        }

        return mb_strtoupper(mb_substr($valor, 0, 1, 'UTF-8'), 'UTF-8') .
            mb_substr($valor, 1, null, 'UTF-8');
    }

    public static function estado(?string $valor): string
    {
        return self::titulo($valor);
    }
}