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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hold_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('qty');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending_payment', 'paid', 'cancelled'])->default('pending_payment');
            $table->timestamps();

            $table->unique('hold_id'); // 1 order per hold
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
