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
    Schema::create('ventas', function (Blueprint $table) {
        $table->id();

        $table->foreignId('cliente_id')
            ->constrained('clientes')
            ->cascadeOnUpdate()
            ->restrictOnDelete();

        $table->foreignId('producto_id')
            ->constrained('productos')
            ->cascadeOnUpdate()
            ->restrictOnDelete();

        $table->date('fecha');
        $table->integer('cantidad');
        $table->decimal('precio_unitario', 10, 2);
        $table->decimal('total', 10, 2)->default(0);

        $table->foreignId('user_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->string('observacion')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
