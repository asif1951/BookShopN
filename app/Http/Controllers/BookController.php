<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookController extends Controller
{
    // Show all books
    public function index()
    {
        $books = Book::all();

        return Inertia::render('CreateBook', [
            'books' => $books,
            'flash' => session('success'),
        ]);
    }

    // Store new book
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'publisher' => 'required|string|max:255',
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
    // ✅ SIMPLE FIX: Update book
public function update(Request $request, Book $book)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'author' => 'required|string|max:255',
        'publisher' => 'required|string|max:255',
        'category' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric',
        'stock' => 'required|integer',
        'photo' => 'nullable|image|max:2048',
    ]);

    // ✅ Handle photo upload
    if ($request->hasFile('photo')) {
        $validated['photo'] = $request->file('photo')->store('books', 'public');
    }

    $book->update($validated);

    return back()->with('success', 'Book updated successfully!');
}


    // Delete book
    public function destroy(Book $book)
    {
        $book->delete();

        return back()->with('success', 'Book deleted successfully!');
    }

    public function show($id)
    {
        $book = Book::with(['feedbacks.user'])->findOrFail($id);
        
        // Format feedbacks with user information
        $feedbacks = $book->feedbacks->map(function ($feedback) {
            return [
                'id' => $feedback->id,
                'rating' => $feedback->rating,
                'review' => $feedback->review,
                'user_id' => $feedback->user_id,
                'user_name' => $feedback->user->name ?? 'Unknown User', // Add null check
                'created_at' => $feedback->created_at,
            ];
        });

        return Inertia::render('BookDetails', [
            'book' => $book,
            'feedbacks' => $feedbacks // FIXED: Add this line
        ]);
    }
}