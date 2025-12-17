<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    // Show all books
    public function index(Request $request)
    {
        $query = Book::withCount('feedbacks')
            ->with(['feedbacks' => function ($query) {
                $query->select('book_id', 'rating');
            }]);

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

        $books = $query->get()->map(function ($book) {
            // Calculate average rating
            $averageRating = 0;
            if ($book->feedbacks_count > 0) {
                $totalRating = $book->feedbacks->sum('rating');
                $averageRating = round($totalRating / $book->feedbacks_count, 1);
            }

            // Calculate rating distribution
            $ratingDistribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
            foreach ($book->feedbacks as $feedback) {
                if ($feedback->rating >= 1 && $feedback->rating <= 5) {
                    $ratingDistribution[$feedback->rating]++;
                }
            }

            // Calculate percentages
            $ratingPercentages = [];
            for ($i = 5; $i >= 1; $i--) {
                $ratingPercentages[$i] = $book->feedbacks_count > 0 ?
                    round(($ratingDistribution[$i] / $book->feedbacks_count) * 100, 0) : 0;
            }

            return [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'category' => $book->category,
                'publisher' => $book->publisher,
                'description' => $book->description,
                'price' => $book->price,
                'stock' => $book->stock,
                'photo' => $book->photo,
                'feedbacks_count' => $book->feedbacks_count,
                'average_rating' => $averageRating,
                'rating_distribution' => $ratingDistribution,
                'rating_percentages' => $ratingPercentages,
                'created_at' => $book->created_at,
                'updated_at' => $book->updated_at
            ];
        });

        return Inertia::render('Dashboard', [
            'books' => $books,
            // 'isAdmin' => auth()->user()->role === 'admin',
            // 'userRole' => auth()->user()->role,
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
