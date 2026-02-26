<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user');
            $table->json('admin_permissions')->nullable();
            $table->index('role');
        });

        DB::table('users')
            ->select(['id', 'name', 'email'])
            ->orderBy('id')
            ->chunkById(200, function ($users): void {
                foreach ($users as $user) {
                    $name = Str::lower(trim((string) ($user->name ?? '')));
                    $email = Str::lower(trim((string) ($user->email ?? '')));

                    $isLegacyAdmin = $name === 'admin'
                        || $email === 'admin'
                        || Str::startsWith($email, 'admin@');

                    if (! $isLegacyAdmin) {
                        continue;
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'role' => 'owner',
                            'admin_permissions' => null,
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'admin_permissions']);
        });
    }
};
