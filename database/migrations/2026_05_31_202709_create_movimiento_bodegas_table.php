<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_bodega', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_movimiento')->unique()->nullable();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('tipo_movimiento');
            $table->string('origen');
            $table->string('documento_referencia')->nullable();

            $table->integer('cantidad');
            $table->integer('stock_anterior');
            $table->integer('stock_nuevo');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('fecha')->useCurrent();
            $table->string('observacion')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_bodega');
    }
};