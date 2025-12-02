<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Inertia\Inertia;
use App\Models\Publisher;
use Illuminate\Http\Request;

class PublisherController extends Controller
{
    public function index()
    {
        $publishers = Publisher::orderBy('id', 'desc')->paginate(10);

        // Check if it's an API request
        if (request()->wantsJson()) {
            return response()->json($publishers);
        }

        return Inertia::render('CreatePublisher', [
            'publishers' => $publishers
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Publisher::create([
            'name' => $request->name
        ]);

        $publishers = Publisher::latest()->paginate(10);

        return Inertia::render('CreatePublisher', [
            'publishers' => $publishers
        ])->with('success', 'Publisher created successfully!');
    }

    public function update(Request $request, $id)
{
    $publisher = Publisher::findOrFail($id);

    $publisher->update([
        'name' => $request->name,
        'address' => $request->address,
        'phone' => $request->phone,
    ]);

    return redirect()->route('publishers.index')
                     ->with('success', 'Publisher updated successfully!');
}


    public function destroy(Publisher $publisher)
    {
        $publisher->delete();
        
        $publishers = Publisher::latest()->paginate(10);

        return redirect()
        ->route('publishers.index')
        ->with('success', 'Publisher deleted successfully!');
    }

    public function show(Publisher $publisher)
    {
        $books = Book::where('publisher', $publisher->name)->get();
        
        return Inertia::render('PublisherDetails', [
            'publisher' => $publisher,
            'books' => $books
        ]);
    }
}