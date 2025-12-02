<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    public function store(Request $request)
{
    $request->validate([
        'book_id' => 'required|integer',
        'rating' => 'required|integer|between:1,5',
        'review' => 'required|string|max:1000',
    ]);

    Feedback::create([
        'user_id' => auth()->id(),
        'user_name' => auth()->user()->name,
        'book_id' => $request->book_id,
        'rating' => $request->rating,
        'review' => $request->review,
    ]);

    // ⭐ Update average rating & total reviews in books table
    $avgRating = Feedback::where('book_id', $request->book_id)->avg('rating');
    $totalReviews = Feedback::where('book_id', $request->book_id)->count();

    Book::where('id', $request->book_id)->update([
        'avg_rating' => round($avgRating, 2),
        'total_reviews' => $totalReviews,
    ]);

    return redirect()->back();
}
public function update(Request $request, Feedback $feedback)
    {
        // Check if the feedback belongs to the authenticated user
        if ($feedback->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string',
        ]);

        $feedback->update($validated);

        return back()->with('success', 'Review updated successfully!');
    }

    public function destroy(Feedback $feedback)
    {
        // Check if the feedback belongs to the authenticated user
        if ($feedback->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $feedback->delete();

        return back()->with('success', 'Review deleted successfully!');
    }


}
