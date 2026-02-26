<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bundle_ads', function (Blueprint $table) {
            $table->id();
            $table->string('bundle_type', 20); // entry or gaming
            $table->string('image_url', 2048)->nullable();
            $table->string('link_url', 2048)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['bundle_type', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bundle_ads');
    }
};
