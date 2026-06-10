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
            $table->string('codigo_empleado')->unique()->nullable()->after('id');
        });

        DB::table('empleados')
            ->orderBy('id')
            ->get()
            ->each(function ($empleado, $index) {
                DB::table('empleados')
                    ->where('id', $empleado->id)
                    ->update([
                        'codigo_empleado' => 'EMP-' . str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn('codigo_empleado');
        });
    }
};