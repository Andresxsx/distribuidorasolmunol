<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cargos')) {
            Schema::create('cargos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre')->unique();
                $table->string('departamento');
                $table->decimal('salario_base', 10, 2)->default(0);
                $table->string('estado')->default('Activo');
                $table->text('descripcion')->nullable();
                $table->timestamps();
            });
        }

        $cargos = [
            ['nombre' => 'Gerente', 'departamento' => 'Dirección', 'salario_base' => 1200.00, 'estado' => 'Activo', 'descripcion' => 'Responsable de dirección general y toma de decisiones estratégicas.'],
            ['nombre' => 'Administrador', 'departamento' => 'Administración', 'salario_base' => 900.00, 'estado' => 'Activo', 'descripcion' => 'Responsable de gestión administrativa, coordinación y control operativo.'],
            ['nombre' => 'Vendedor', 'departamento' => 'Ventas', 'salario_base' => 550.00, 'estado' => 'Activo', 'descripcion' => 'Responsable de atención al cliente y registro de ventas.'],
            ['nombre' => 'Bodeguero', 'departamento' => 'Bodega', 'salario_base' => 500.00, 'estado' => 'Activo', 'descripcion' => 'Responsable de inventario, recepción y movimientos de bodega.'],
            ['nombre' => 'Comprador', 'departamento' => 'Compras', 'salario_base' => 650.00, 'estado' => 'Activo', 'descripcion' => 'Responsable de abastecimiento y relación con proveedores.'],
            ['nombre' => 'Contador', 'departamento' => 'Contabilidad', 'salario_base' => 750.00, 'estado' => 'Activo', 'descripcion' => 'Responsable de registro contable y control financiero.'],
            ['nombre' => 'Asistente administrativo', 'departamento' => 'Administración', 'salario_base' => 470.00, 'estado' => 'Activo', 'descripcion' => 'Apoyo en tareas administrativas y documentación.'],
            ['nombre' => 'Jefe de talento humano', 'departamento' => 'Talento Humano', 'salario_base' => 800.00, 'estado' => 'Activo', 'descripcion' => 'Responsable de gestión del personal, seguimiento y cumplimiento laboral.'],
            ['nombre' => 'Analista de sistemas', 'departamento' => 'Sistemas', 'salario_base' => 850.00, 'estado' => 'Activo', 'descripcion' => 'Responsable de soporte tecnológico y sistemas de información.'],
        ];

        foreach ($cargos as $cargo) {
            DB::table('cargos')->updateOrInsert(
                ['nombre' => $cargo['nombre']],
                array_merge($cargo, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cargos');
    }
};
