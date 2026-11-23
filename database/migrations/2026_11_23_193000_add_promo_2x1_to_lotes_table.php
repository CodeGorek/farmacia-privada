<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            if (!Schema::hasColumn('lotes', 'promo_2x1')) {
                $table->boolean('promo_2x1')->default(false)->after('motivo_descuento');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            if (Schema::hasColumn('lotes', 'promo_2x1')) {
                $table->dropColumn('promo_2x1');
            }
        });
    }
};
