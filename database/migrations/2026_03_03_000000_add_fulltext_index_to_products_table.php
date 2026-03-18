<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'products_name_description_fulltext_idx';

    public function up(): void
    {
        if (! $this->supportsFullText()) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->fullText(['name', 'description'], self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        if (! $this->supportsFullText()) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropFullText(self::INDEX_NAME);
        });
    }

    private function supportsFullText(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
