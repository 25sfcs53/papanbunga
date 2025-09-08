<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('base_price', 12, 2);
            $table->enum('discount_type', ['percent', 'fixed'])->nullable();
            $table->decimal('discount_value', 12, 2)->default(0.00);
            $table->decimal('final_price', 12, 2);
            $table->text('text_content')->nullable();
            $table->enum('status', ['Pending','Dirakit','Disewa','Selesai','Dibatalkan'])->default('Pending');
            $table->string('required_rack_color')->nullable();
            $table->date('delivery_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
