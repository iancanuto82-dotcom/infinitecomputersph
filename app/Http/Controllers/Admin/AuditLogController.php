<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $action = trim((string) $request->query('action', ''));
        $targetType = trim((string) $request->query('target_type', ''));
        $actorId = $request->integer('actor');
        $search = trim((string) $request->query('search', ''));

        $query = AuditLog::query()
            ->with('user')
            ->latest('id');

        if ($action !== '') {
            $query->where('action', $action);
        }

        if ($targetType !== '') {
            $query->where('target_type', $targetType);
        }

        if ($actorId > 0) {
            $query->where('user_id', $actorId);
        }

        if ($search !== '') {
            $query->where(function ($logQuery) use ($search): void {
                $logQuery->where('description', 'like', "%{$search}%")
                    ->orWhere('target_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search): void {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query
            ->paginate(30)
            ->withQueryString();

        $actions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $targetTypes = AuditLog::query()
            ->select('target_type')
            ->distinct()
            ->orderBy('target_type')
            ->pluck('target_type');

        $actors = User::query()
            ->whereIn('id', AuditLog::query()->select('user_id')->whereNotNull('user_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.audit.index', [
            'logs' => $logs,
            'actions' => $actions,
            'targetTypes' => $targetTypes,
            'actors' => $actors,
            'selectedAction' => $action,
            'selectedTargetType' => $targetType,
            'selectedActorId' => $actorId > 0 ? $actorId : null,
            'search' => $search,
        ]);
    }
}
