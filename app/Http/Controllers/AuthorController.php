<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Inertia\Inertia;
use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AuthorController extends Controller
{
    public function index()
    {
        $authors = Author::latest()->paginate(10);
        $authors->getCollection()->transform(function ($author) {
            $author->photo = $author->photo ? asset('storage/' . $author->photo) : null;
            return $author;
        });

        // Check if it's an API request
        if (request()->wantsJson()) {
            return response()->json($authors);
        }

        return Inertia::render('CreateAuthor', [
            'authors' => $authors
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string',
            'photo' => 'nullable|image|max:2048'
        ]);

        $data = $request->only('name', 'bio');

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('authors', 'public');
        }

        Author::create($data);

        $authors = Author::latest()->paginate(10);
        $authors->getCollection()->transform(function ($author) {
            $author->photo = $author->photo ? asset('storage/' . $author->photo) : null;
            return $author;
        });

        return Inertia::render('CreateAuthor', [
            'authors' => $authors
        ])->with('success', 'Author created successfully!');
    }

    public function update(Request $request, Author $author)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string',
            'photo' => 'nullable|image|max:2048'
        ]);

        $data = $request->only('name', 'bio');

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($author->photo) {
                Storage::disk('public')->delete($author->photo);
            }
            
            // Store new photo
            $data['photo'] = $request->file('photo')->store('authors', 'public');
        }

        $author->update($data);
        
        $authors = Author::latest()->paginate(10);
        $authors->getCollection()->transform(function ($author) {
            $author->photo = $author->photo ? asset('storage/' . $author->photo) : null;
            return $author;
        });
        
        return redirect()
            ->route('authors.index')
            ->with('success', 'Author updated successfully!');
    }

    public function destroy(Author $author)
    {
        $author->delete();

        return redirect()
            ->route('authors.index')
            ->with('success', 'Author deleted successfully!');
    }

    public function show(Author $author)
    {
        $books = Book::where('author', $author->name)->get();

        return Inertia::render('AuthorDetails', [
            'author' => $author,
            'books' => $books
        ]);
    }
}