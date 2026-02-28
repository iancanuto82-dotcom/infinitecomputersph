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
        Schema::create('replacement_warranty_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->string('product_name');
            $table->string('type', 20);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total_cost', 12, 2);
            $table->dateTime('processed_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('processed_at', 'replacement_warranty_processed_at_index');
            $table->index('type', 'replacement_warranty_type_index');
            $table->index('product_name', 'replacement_warranty_product_name_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('replacement_warranty_items');
    }
};

