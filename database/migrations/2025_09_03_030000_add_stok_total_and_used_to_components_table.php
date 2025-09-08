<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table) {
            if (!Schema::hasColumn('components', 'stok_total')) {
                $table->integer('stok_total')->default(0)->after('quantity_available');
            }
            if (!Schema::hasColumn('components', 'stok_used')) {
                $table->integer('stok_used')->default(0)->after('stok_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            if (Schema::hasColumn('components', 'stok_used')) {
                $table->dropColumn('stok_used');
            }
            if (Schema::hasColumn('components', 'stok_total')) {
                $table->dropColumn('stok_total');
            }
        });
    }
};
