<?php

use App\Models\Book;
use Inertia\Inertia;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\BookController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublisherController;

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

Route::get('/', [DashboardController::class, 'index']);



// ✅ FIXED: Move stock-data route outside auth middleware
Route::get('/books/stock-data', function () {
    $books = \App\Models\Book::select('id', 'stock')->get();
    return response()->json($books);
});

// ✅ Order List Route - শুধু authenticated users এর জন্য
// ✅ Order List Route - User শুধু নিজের Orders দেখতে পারবে
Route::get('/order-list', function (Request $request) {
    $query = Order::with(['user', 'items.book']);

    // যদি user admin না হয়, শুধু নিজের orders দেখাবে
    if (auth()->user()->role !== 'admin') {
        $query->where('user_id', auth()->id());
    }

    // Search filter
    if ($request->has('search') && $request->search) {
        $query->where(function ($q) use ($request) {
            $q->where('id', 'like', '%' . $request->search . '%')
                ->orWhere('transaction_id', 'like', '%' . $request->search . '%')
                ->orWhereHas('user', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                });
        });
    }

    // Status filter
    if ($request->has('status') && $request->status) {
        $query->where('status', $request->status);
    }

    // Payment status filter
    if ($request->has('payment_status') && $request->payment_status) {
        $query->where('payment_status', $request->payment_status);
    }

    // Date range filter
    if ($request->has('date_range') && $request->date_range) {
        $now = now();
        switch ($request->date_range) {
            case 'today':
                $query->whereDate('created_at', $now->toDateString());
                break;
            case 'week':
                $query->whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()]);
                break;
            case 'month':
                $query->whereBetween('created_at', [$now->startOfMonth(), $now->endOfMonth()]);
                break;
            case 'year':
                $query->whereBetween('created_at', [$now->startOfYear(), $now->endOfYear()]);
                break;
        }
    }

    // Sorting
    $sortField = $request->get('sort_field', 'created_at');
    $sortDirection = $request->get('sort_direction', 'asc');
    $query->orderBy($sortField, $sortDirection);

    $orders = $query->paginate(10)->withQueryString();

    return Inertia::render('OrderList', [
        'orders' => $orders,
        'filters' => $request->only(['search', 'status', 'payment_status', 'date_range', 'sort_field', 'sort_direction']),
        'isAdmin' => auth()->user()->role === 'admin'
    ]);
})->middleware(['auth'])->name('order.list');

// Dashboard Route
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// ✅ Add this API route for getting other books in same category
Route::get('/api/categories/{category}/books', function ($categoryId, Request $request) {
    $excludeBookId = $request->query('exclude');
    
    $query = Book::where('category_id', $categoryId)
                ->where('stock', '>', 0) // Only available books
                ->with('feedbacks'); // Get feedbacks for rating calculation
    
    // Exclude specific book if provided
    if ($excludeBookId) {
        $query->where('id', '!=', $excludeBookId);
    }
    
    $books = $query->take(8)->get();
    
    // Format the response with average rating
    $formattedBooks = $books->map(function ($book) {
        // Calculate average rating
        $averageRating = 0;
        if ($book->feedbacks->count() > 0) {
            $averageRating = $book->feedbacks->avg('rating');
        }
        
        return [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'price' => $book->price,
            'stock' => $book->stock,
            'photo' => $book->photo,
            'category_id' => $book->category_id,
            'average_rating' => round($averageRating, 1),
            'reviews_count' => $book->feedbacks->count(),
        ];
    });
    
    return response()->json($formattedBooks);
});

Route::get('/books/{id}', [BookController::class, 'show'])->name('books.show');

//to find authors
Route::get('/api/authors', function () {
    $authors = \App\Models\Author::select('id', 'name')->get();
    return response()->json($authors);
});

// to find categories
Route::get('/api/categories', function () {
    $categories = \App\Models\Category::select('id', 'name')->get();
    return response()->json($categories);
});

// to find the publishers
Route::get('/api/publishers', function () {
    $publishers = \App\Models\Publisher::select('id', 'name')->get();
    return response()->json($publishers);
});

Route::middleware('auth')->group(function () {
    // Profile Routes - সব authenticated users এর জন্য
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dashboard Route - সব authenticated users এর জন্য
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Feedback Routes - সব authenticated users এর জন্য
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
    Route::put('/feedback/{feedback}', [FeedbackController::class, 'update']);
    Route::delete('/feedback/{feedback}', [FeedbackController::class, 'destroy']);

    // Payment Routes - সব authenticated users এর জন্য
    Route::get('/payment', [PaymentController::class, 'showPaymentPage'])->name('payment.page');
    Route::post('/create-payment-intent', [PaymentController::class, 'createPaymentIntent']);
    Route::post('/save-order', [PaymentController::class, 'saveOrder']);
    Route::get('/check-stock', [PaymentController::class, 'checkStock']);
    Route::get('/payment/success', [PaymentController::class, 'paymentSuccess'])->name('payment.success');
    Route::get('/payment/cancel', [PaymentController::class, 'paymentCancel'])->name('payment.cancel');
    Route::post('/create-stripe-payment-intent', [PaymentController::class, 'createStripePaymentIntent']);
    Route::post('/confirm-stripe-payment', [PaymentController::class, 'confirmStripePayment']);
    Route::put('/orders/{order}/status', [PaymentController::class, 'updateOrderStatus'])->name('orders.status.update');

    // API Routes for Dropdowns - সব authenticated users এর জন্য





});

// Admin Only Routes - শুধু admin role যাদের
Route::middleware(['auth'])->group(function () {
    // Book Management - শুধু admin
    Route::get('/create-book', [BookController::class, 'index'])->name('books.index');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');
    Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');

    // Category Management - শুধু admin
    Route::get('/create-category', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Author Management - শুধু admin
    Route::get('/create-author', [AuthorController::class, 'index'])->name('authors.index');
    Route::post('/authors', [AuthorController::class, 'store'])->name('authors.store');
    Route::put('/authors/{author}', [AuthorController::class, 'update'])->name('authors.update');
    Route::delete('/authors/{author}', [AuthorController::class, 'destroy'])->name('authors.destroy');
    Route::get('/authors', [AuthorController::class, 'index'])->name('authors.index');


    // Publisher Management - শুধু admin
    Route::get('/create-publisher', [PublisherController::class, 'index'])->name('publishers.index');
    Route::post('/publishers', [PublisherController::class, 'store'])->name('publishers.store');
    Route::put('/publishers/{publisher}', [PublisherController::class, 'update'])->name('publishers.update');
    Route::delete('/publishers/{publisher}', [PublisherController::class, 'destroy'])->name('publishers.destroy');

    // User Management - শুধু admin
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/toggle-role', [UserController::class, 'toggleRole'])->name('users.toggle-role');
});

require __DIR__ . '/auth.php';

// Public author/category/publisher show routes
Route::get('/authors/{author}', [AuthorController::class, 'show'])->name('authors.show');
Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/publishers/{publisher}', [PublisherController::class, 'show'])->name('publishers.show');

// Book resource routes
Route::resource('books', BookController::class)->only(['show']);
// routes/api.php বা routes/web.php
Route::post('/generate-orders-pdf', [OrderController::class, 'generatePDF']);