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
    Schema::create('productos', function (Blueprint $table) {
        $table->id();
        $table->string('codigo')->unique();
        $table->string('nombre');
        $table->string('categoria');
        $table->text('descripcion')->nullable();
        $table->integer('stock_actual')->default(0);
        $table->integer('stock_minimo')->default(5);
        $table->decimal('precio_compra', 10, 2)->default(0);
        $table->decimal('precio_venta', 10, 2)->default(0);
        $table->string('estado')->default('Activo');
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
