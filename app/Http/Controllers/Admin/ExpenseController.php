<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(): View
    {
        $year = request()->string('year')->toString();
        $month = request()->string('month')->toString();
        $search = trim(request()->string('q')->toString());

        $selectedYear = $year !== '' && $year !== 'all' ? (int) $year : null;
        $selectedMonthNumber = $month !== '' && $month !== 'all' ? (int) $month : null;

        $query = Expense::query()->with('creator:id,name,first_name,last_name');

        if ($selectedYear) {
            $query->whereYear('spent_at', $selectedYear);
        }

        if ($selectedMonthNumber && $selectedMonthNumber >= 1 && $selectedMonthNumber <= 12) {
            $query->whereMonth('spent_at', $selectedMonthNumber);
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner->where('title', 'like', '%'.$search.'%')
                    ->orWhere('category', 'like', '%'.$search.'%')
                    ->orWhere('notes', 'like', '%'.$search.'%');
            });
        }

        $expenses = $query
            ->orderByDesc('spent_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $totalAmount = (float) Expense::query()->sum('amount');
        $currentMonthAmount = (float) Expense::query()
            ->whereBetween('spent_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        $yearExpression = $this->yearExpression('spent_at');
        $years = Expense::query()
            ->selectRaw($yearExpression.' as year')
            ->groupByRaw($yearExpression)
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($value) => (int) $value)
            ->values();

        return view('admin.expenses.index', [
            'expenses' => $expenses,
            'totalAmount' => $totalAmount,
            'currentMonthAmount' => $currentMonthAmount,
            'selectedYear' => $selectedYear,
            'selectedMonthNumber' => $selectedMonthNumber,
            'search' => $search,
            'years' => $years,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'spent_at' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $expense = Expense::query()->create([
            'created_by' => $request->user()?->id,
            'spent_at' => Carbon::parse($validated['spent_at']),
            'title' => trim((string) $validated['title']),
            'category' => isset($validated['category']) ? trim((string) $validated['category']) : null,
            'amount' => round((float) $validated['amount'], 2),
            'notes' => isset($validated['notes']) ? trim((string) $validated['notes']) : null,
        ]);

        AuditLogger::record($request, 'created', 'expense', (int) $expense->id, (string) $expense->title, 'Recorded expense.', [
            'amount' => (float) $expense->amount,
            'spent_at' => optional($expense->spent_at)->toIso8601String(),
        ]);

        return redirect()->route('admin.expenses')->with('status', 'Expense recorded.');
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $title = (string) $expense->title;
        $amount = (float) $expense->amount;
        $spentAt = optional($expense->spent_at)->toIso8601String();
        $expense->delete();

        AuditLogger::record($request, 'deleted', 'expense', (int) $expense->id, $title, 'Deleted expense.', [
            'amount' => $amount,
            'spent_at' => $spentAt,
        ]);

        return back()->with('status', 'Expense deleted.');
    }

    private function yearExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => "strftime('%Y', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY')",
            default => "DATE_FORMAT({$column}, '%Y')",
        };
    }
}
