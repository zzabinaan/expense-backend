<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $categories = $request->user()->categories()->orderBy('name')->get();

        return $this->ok($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $request->user()->categories()->create($request->validated());

        return $this->created($category, 'Category created successfully.');
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $this->authorizeCategory($request, $category);

        $category->update($request->validated());

        return $this->ok($category->fresh(), 'Category updated successfully.');
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        $this->authorizeCategory($request, $category);

        if ($category->expenses()->exists()) {
            return $this->error('Cannot delete category with existing expenses. Please reassign or delete expenses first.');
        }

        $category->delete();

        return $this->message('Category deleted successfully.');
    }

    private function authorizeCategory(Request $request, Category $category): void
    {
        if ($category->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized.');
        }
    }
}
