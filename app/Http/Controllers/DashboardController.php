<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    // Show all books
    public function index(Request $request)
    {
        $query = Book::query();
        
        // Search by author
        if ($request->has('author') && $request->author) {
            $query->where('author', 'like', '%' . $request->author . '%');
        }
        
        // Search by category
        if ($request->has('category') && $request->category) {
            $query->where('category', 'like', '%' . $request->category . '%');
        }
        
        // Search by publisher
        if ($request->has('publisher') && $request->publisher) {
            $query->where('publisher', 'like', '%' . $request->publisher . '%');
        }
        
        $books = $query->get();
        
        return Inertia::render('Dashboard', [
            'books' => $books,
            'isAdmin' => auth()->user()->role === 'admin',
            'userRole' => auth()->user()->role,
            'filters' => $request->only(['author', 'category', 'publisher'])
        ]);
    }

    // Store new book
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'photo' => 'nullable|image|max:2048',
        ]);

        $book = Book::create($validated);

        if ($request->hasFile('photo')) {
            $book->update([
                'photo' => $request->file('photo')->store('books', 'public'),
            ]);
        }

        return back()->with('success', 'Book created successfully!');
    }

    // Update book
    public function update(Request $request, Book $book)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
        ]);

        $book->update($validated);

        return back()->with('success', 'Book updated successfully!');
    }

    // Delete book
    public function destroy(Book $book)
    {
        $book->delete();

        return back()->with('success', 'Book deleted successfully!');
    }
}