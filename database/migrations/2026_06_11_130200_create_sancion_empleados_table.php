<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sancion_empleados')) {
            Schema::create('sancion_empleados', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnUpdate()->cascadeOnDelete();
                $table->date('fecha')->default(now());
                $table->string('tipo')->default('Llamado de atención');
                $table->string('motivo');
                $table->decimal('valor_descuento', 10, 2)->default(0);
                $table->string('estado')->default('Pendiente');
                $table->text('observacion')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sancion_empleados');
    }
};
