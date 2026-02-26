<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Throwable;

final class AuditLogger
{
    /**
     * @param array<string, mixed> $meta
     */
    public static function record(
        ?Request $request,
        string $action,
        string $targetType,
        ?int $targetId = null,
        ?string $targetName = null,
        ?string $description = null,
        array $meta = []
    ): void {
        $request ??= request();

        try {
            $user = $request?->user();

            AuditLog::query()->create([
                'user_id' => $user?->id,
                'action' => trim($action),
                'target_type' => trim($targetType),
                'target_id' => $targetId,
                'target_name' => $targetName !== null ? trim($targetName) : null,
                'description' => $description !== null ? trim($description) : null,
                'meta' => $meta === [] ? null : $meta,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            // Do not block business actions if logging fails.
        }
    }
}
