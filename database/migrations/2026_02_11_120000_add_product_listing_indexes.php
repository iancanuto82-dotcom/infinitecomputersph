<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_active', 'category_id', 'id'], 'products_active_category_id_idx');
            $table->index(['is_active', 'price'], 'products_active_price_idx');
            $table->index(['is_active', 'stock'], 'products_active_stock_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_active_category_id_idx');
            $table->dropIndex('products_active_price_idx');
            $table->dropIndex('products_active_stock_idx');
        });
    }
};

