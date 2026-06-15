<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->expenses()
            ->with('category')
            ->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('start_date')) {
            $query->where('expense_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('expense_date', '<=', $request->end_date);
        }

        if ($request->filled('category_ids')) {
            $ids = array_filter((array) $request->category_ids, 'is_numeric');
            if (!empty($ids)) {
                $query->whereIn('category_id', $ids);
            }
        }

        if ($request->filled('search')) {
            $query->where('description', 'ilike', '%'.$request->search.'%');
        }

        $perPage = min((int) $request->get('per_page', 10), 100);

        return response()->json($query->paginate($perPage));
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $this->validateCategoryOwnership($request);

        $expense = $request->user()->expenses()->create($request->validated());
        $expense->load('category');

        return $this->created($expense, 'Expense created successfully.');
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): JsonResponse
    {
        $this->authorizeExpense($request, $expense);

        if ($request->filled('category_id')) {
            $this->validateCategoryOwnership($request);
        }

        $expense->update($request->validated());

        return $this->ok($expense->fresh(['category']), 'Expense updated successfully.');
    }

    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        $this->authorizeExpense($request, $expense);

        $expense->delete();

        return $this->message('Expense deleted successfully.');
    }

    private function authorizeExpense(Request $request, Expense $expense): void
    {
        if ($expense->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized.');
        }
    }

    private function validateCategoryOwnership(Request $request): void
    {
        $exists = $request->user()->categories()
            ->where('id', $request->category_id)
            ->exists();

        if (! $exists) {
            abort(422, 'The selected category does not belong to you.');
        }
    }
}
