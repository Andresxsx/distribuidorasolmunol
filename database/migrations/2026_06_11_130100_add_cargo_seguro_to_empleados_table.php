<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            if (! Schema::hasColumn('empleados', 'cargo_id')) {
                $table->foreignId('cargo_id')->nullable()->after('codigo_empleado')->constrained('cargos')->nullOnDelete();
            }

            if (! Schema::hasColumn('empleados', 'tiene_seguro')) {
                $table->boolean('tiene_seguro')->default(false)->after('estado');
            }

            if (! Schema::hasColumn('empleados', 'tipo_seguro')) {
                $table->string('tipo_seguro')->nullable()->after('tiene_seguro');
            }

            if (! Schema::hasColumn('empleados', 'numero_afiliacion')) {
                $table->string('numero_afiliacion')->nullable()->after('tipo_seguro');
            }

            if (! Schema::hasColumn('empleados', 'estado_seguro')) {
                $table->string('estado_seguro')->default('No registrado')->after('numero_afiliacion');
            }

            if (! Schema::hasColumn('empleados', 'fecha_afiliacion')) {
                $table->date('fecha_afiliacion')->nullable()->after('estado_seguro');
            }
        });

        $empleados = DB::table('empleados')->select('id', 'cargo', 'departamento')->get();

        foreach ($empleados as $empleado) {
            $cargo = DB::table('cargos')
                ->where('nombre', $empleado->cargo)
                ->first();

            if (! $cargo && $empleado->cargo) {
                DB::table('cargos')->insert([
                    'nombre' => $empleado->cargo,
                    'departamento' => $empleado->departamento ?: 'Administración',
                    'salario_base' => 470.00,
                    'estado' => 'Activo',
                    'descripcion' => 'Cargo creado automáticamente por migración desde empleados existentes.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $cargo = DB::table('cargos')->where('nombre', $empleado->cargo)->first();
            }

            if ($cargo) {
                DB::table('empleados')
                    ->where('id', $empleado->id)
                    ->update([
                        'cargo_id' => $cargo->id,
                        'cargo' => $cargo->nombre,
                        'departamento' => $cargo->departamento,
                        'sueldo' => $cargo->salario_base,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            if (Schema::hasColumn('empleados', 'cargo_id')) {
                $table->dropConstrainedForeignId('cargo_id');
            }

            foreach (['tiene_seguro', 'tipo_seguro', 'numero_afiliacion', 'estado_seguro', 'fecha_afiliacion'] as $column) {
                if (Schema::hasColumn('empleados', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
