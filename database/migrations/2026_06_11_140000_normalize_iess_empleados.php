<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('empleados')) {
            return;
        }

        $columnasRequeridas = [
            'tiene_seguro',
            'tipo_seguro',
            'numero_afiliacion',
            'estado_seguro',
            'fecha_afiliacion',
            'fecha_ingreso',
            'cedula',
            'estado',
        ];

        foreach ($columnasRequeridas as $columna) {
            if (! Schema::hasColumn('empleados', $columna)) {
                return;
            }
        }

        DB::table('empleados')
            ->where(function ($query) {
                $query->whereNull('fecha_ingreso')
                    ->orWhere('fecha_ingreso', '<', '2000-01-01')
                    ->orWhere('fecha_ingreso', '>', now()->toDateString());
            })
            ->update([
                'fecha_ingreso' => now()->toDateString(),
                'updated_at' => now(),
            ]);

        DB::table('empleados')->chunkById(100, function ($empleados) {
            foreach ($empleados as $empleado) {
                DB::table('empleados')
                    ->where('id', $empleado->id)
                    ->update([
                        'tiene_seguro' => true,
                        'tipo_seguro' => 'IESS',
                        'numero_afiliacion' => $empleado->numero_afiliacion ?: $empleado->cedula,
                        'estado_seguro' => in_array($empleado->estado, ['Retirado', 'Inactivo'], true) ? 'Inactivo' : 'Activo',
                        'fecha_afiliacion' => $empleado->fecha_ingreso,
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    public function down(): void
    {
        // No se revierte para no perder datos laborales corregidos.
    }
};
