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
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->boolean('is_refund')->default(false);
            $table->string('method', 100)->nullable();
            $table->string('reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('paid_at');
            $table->timestamps();

            $table->index(['sale_id', 'paid_at'], 'sale_payments_sale_paid_at_index');
            $table->index(['sale_id', 'is_refund'], 'sale_payments_sale_refund_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
