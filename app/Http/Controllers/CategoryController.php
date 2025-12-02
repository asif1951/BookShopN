<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Inertia\Inertia;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        // Start query
        $query = Category::query();

        // Apply search filter
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $categories = $query->orderBy('id', 'desc')->paginate(10);

        // Get statistics (for all categories, not filtered)
        $stats = [
            'total' => Category::count(),
        ];

        // Check if it's an API request
        if (request()->wantsJson()) {
            return response()->json($categories);
        }

        return Inertia::render('CreateCategory', [
            'categories' => $categories,
            'stats' => $stats,
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        Category::create(['name' => $request->name]);

        return redirect()->route('categories.index')->with('success', 'Category created successfully!');
    }

    public function update(Request $request, Category $category)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $category->update(['name' => $request->name]);

        return redirect()->route('categories.index')->with('success', 'Category updated successfully!');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Category deleted successfully!');
    }

    public function show(Category $category)
    {
        $books = Book::where('category', $category->name)->get();
        
        return Inertia::render('CategoryDetails', [
            'category' => $category,
            'books' => $books
        ]);
    }
}