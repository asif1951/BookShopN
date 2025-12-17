<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.book']);
        
        // যদি user admin না হয়, শুধু নিজের orders দেখাবে
        if (auth()->user()->role !== 'admin') {
            $query->where('user_id', auth()->id());
        }
        
        // Search and filters here...
        
        $orders = $query->orderBy('id', 'desc')
                       ->paginate(10);

        return Inertia::render('OrderList', [
            'orders' => $orders,
            'filters' => $request->only(['search', 'status', 'payment_status'])
        ]);
    }
}