<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('empleados', function (Blueprint $table) {
        $table->id();
        $table->string('cedula', 10)->unique();
        $table->string('nombres');
        $table->string('apellidos');
        $table->string('cargo');
        $table->string('departamento');
        $table->string('telefono')->nullable();
        $table->string('correo')->nullable();
        $table->decimal('sueldo', 10, 2)->default(0);
        $table->date('fecha_ingreso')->nullable();
        $table->string('estado')->default('Activo');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
