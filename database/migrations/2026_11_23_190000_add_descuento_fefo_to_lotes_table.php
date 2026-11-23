<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            if (!Schema::hasColumn('lotes', 'descuento_porcentaje')) {
                $table->decimal('descuento_porcentaje', 5, 2)->default(0)->after('stock_bodega');
            }
            if (!Schema::hasColumn('lotes', 'motivo_descuento')) {
                $table->string('motivo_descuento', 80)->nullable()->after('descuento_porcentaje');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            if (Schema::hasColumn('lotes', 'motivo_descuento')) {
                $table->dropColumn('motivo_descuento');
            }
            if (Schema::hasColumn('lotes', 'descuento_porcentaje')) {
                $table->dropColumn('descuento_porcentaje');
            }
        });
    }
};
