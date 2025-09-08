<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['papan', 'rak', 'font']);
            $table->string('color')->nullable();
            $table->integer('quantity_total')->default(0);
            $table->integer('quantity_rented')->default(0);
            $table->integer('quantity_repair')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
