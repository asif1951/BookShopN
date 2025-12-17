<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Author;
use App\Models\Category;
use App\Models\Publisher;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookController extends Controller
{
    // Show all books with filtering
    public function index(Request $request)
    {
        $query = Book::query();

        // Search filter
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                    ->orWhere('author', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSortColumns = ['title', 'author', 'category', 'price', 'stock', 'created_at'];
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Get books with pagination - 10 items per page
        $books = $query->paginate(10)->withQueryString();

        // Get unique values for dropdowns
        $authors = Author::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $publishers = Publisher::orderBy('name')->get();

        return Inertia::render('CreateBook', [
            'books' => $books,
            'filters' => [
                'search' => $request->search ?? '',
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
            'dropdowns' => [
                'authors' => $authors,
                'categories' => $categories,
                'publishers' => $publishers,
            ],
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

        return redirect()->route('books.index')->with('success', 'Book created successfully!');
    }

    // Update book
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

        return redirect()->route('books.index')->with('success', 'Book updated successfully!');
    }

    // Delete book
    public function destroy(Book $book)
    {
        $book->delete();

        return redirect()->route('books.index')->with('success', 'Book deleted successfully!');
    }

    // Show book details with reviews and other books in same category
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

        // Get other books in same category
        $otherBooks = Book::where('category', $book->category)
            ->where('id', '!=', $book->id)
            ->where('stock', '>', 0)
            ->with('feedbacks')
            ->take(8)
            ->get()
            ->map(function ($otherBook) {
                // Calculate average rating for each other book
                $avgRating = 0;
                if ($otherBook->feedbacks->count() > 0) {
                    $avgRating = $otherBook->feedbacks->avg('rating');
                }

                return [
                    'id' => $otherBook->id,
                    'title' => $otherBook->title,
                    'author' => $otherBook->author,
                    'price' => $otherBook->price,
                    'stock' => $otherBook->stock,
                    'photo' => $otherBook->photo,
                    'category' => $otherBook->category,
                    'average_rating' => round($avgRating, 1),
                    'reviews_count' => $otherBook->feedbacks->count(),
                ];
            });

        return Inertia::render('BookDetails', [
            'book' => $book,
            'feedbacks' => $feedbacks, // FIXED: Add this line
            'otherBooks' => $otherBooks // Add other books in same category
        ]);
    }
}