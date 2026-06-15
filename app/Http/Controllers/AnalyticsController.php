<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    use ApiResponse;

    public function summary(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $todayTotal = Expense::forUser($userId)
            ->whereDate('expense_date', $today)
            ->sum('amount');

        $monthTotal = Expense::forUser($userId)
            ->whereBetween('expense_date', [$monthStart, $monthEnd])
            ->sum('amount');

        $monthCount = Expense::forUser($userId)
            ->whereBetween('expense_date', [$monthStart, $monthEnd])
            ->count();

        return $this->ok([
            'today_total' => (float) $todayTotal,
            'month_total' => (float) $monthTotal,
            'month_transaction_count' => $monthCount,
        ]);
    }

    public function byCategory(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $data = Expense::forUser($userId)
            ->whereBetween('expenses.expense_date', [$monthStart, $monthEnd])
            ->join('categories', 'expenses.category_id', '=', 'categories.id')
            ->select('categories.name', 'categories.color', DB::raw('SUM(expenses.amount) as total'))
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'color' => $row->color,
                'total' => (float) $row->total,
            ]);

        return $this->ok($data);
    }

    public function trend(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $start = Carbon::now()->subDays(29)->startOfDay();
        $end = Carbon::now()->endOfDay();

        $rows = Expense::forUser($userId)
            ->whereBetween('expense_date', [$start, $end])
            ->select(DB::raw('DATE(expense_date) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('DATE(expense_date)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $trend = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $trend[] = [
                'date' => $date,
                'total' => (float) ($rows->get($date)?->total ?? 0),
            ];
        }

        return $this->ok($trend);
    }

    public function topCategories(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $data = Expense::forUser($userId)
            ->whereBetween('expenses.expense_date', [$monthStart, $monthEnd])
            ->join('categories', 'expenses.category_id', '=', 'categories.id')
            ->select('categories.name', 'categories.color', DB::raw('SUM(expenses.amount) as total'))
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'color' => $row->color,
                'total' => (float) $row->total,
            ]);

        return $this->ok($data);
    }
}
