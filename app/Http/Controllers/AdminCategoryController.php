<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Support\Feedback;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCategoryController extends Controller
{
    public function index()
    {
        $categories = Category::query()->withCount(['tasks', 'habits'])->orderBy('name')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Category::create(['user_id' => null, ...$data]);

        return Feedback::success(back(), __('ui.feedback.category_created'));
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validated($request));

        return Feedback::success(back(), __('ui.feedback.category_updated'));
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return Feedback::success(back(), __('ui.feedback.category_deleted'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('categories', 'name')->ignore($request->route('category'))],
            'color' => ['required', 'regex:/\A#[0-9a-fA-F]{6}\z/'],
        ]);
    }
}
