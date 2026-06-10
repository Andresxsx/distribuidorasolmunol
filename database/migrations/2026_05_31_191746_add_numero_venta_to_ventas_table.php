<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('numero_venta')->unique()->nullable();
        });

        DB::table('ventas')
            ->orderBy('id')
            ->get()
            ->each(function ($venta, $index) {
                DB::table('ventas')
                    ->where('id', $venta->id)
                    ->update([
                        'numero_venta' => 'VENT-' . str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('numero_venta');
        });
    }
};