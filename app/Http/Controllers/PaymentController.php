<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Book;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;

class PaymentController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    // ✅ ADD THIS METHOD: Show Payment Page
    public function showPaymentPage()
    {
        return Inertia::render('Payment');
    }

    // ✅ FIXED: Create Stripe Payment Intent with optimized metadata
    public function createStripePaymentIntent(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:1',
                'items' => 'required|array'
            ]);

            // Check stock first
            $stockCheck = $this->checkStockAvailability($request->items);
            if (!$stockCheck['available']) {
                return response()->json([
                    'error' => $stockCheck['message']
                ], 400);
            }

            $amount = $request->amount * 100; // Convert to cents

            // ✅ FIXED: Optimize metadata to stay within 500 character limit
            $optimizedItems = $this->optimizeItemsForMetadata($request->items);
            
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'usd',
                'payment_method_types' => ['card'],
                'metadata' => [
                    'user_id' => (string) auth()->id(),
                    'item_count' => (string) count($request->items),
                    'items_summary' => $optimizedItems,
                    'order_hash' => $this->generateOrderHash($request->items)
                ]
            ]);

            // ✅ Store full order data temporarily in session or cache
            $this->storeTemporaryOrderData($paymentIntent->id, [
                'items' => $request->items,
                'total_amount' => $request->amount,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Stripe payment error: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Payment Intent Creation Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error creating payment: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ FIXED: Optimize items data for metadata
    private function optimizeItemsForMetadata($items)
    {
        $itemStrings = [];
        
        foreach ($items as $item) {
            // Only store essential data: id:quantity
            $itemStrings[] = $item['id'] . ':' . $item['quantity'];
        }
        
        $metadata = implode(',', $itemStrings);
        
        // Final check - truncate if necessary (very unlikely now)
        if (strlen($metadata) > 500) {
            $metadata = substr($metadata, 0, 497) . '...';
        }
        
        return $metadata;
    }

    // ✅ Generate unique hash for order verification
    private function generateOrderHash($items)
    {
        $data = '';
        foreach ($items as $item) {
            $data .= $item['id'] . $item['quantity'];
        }
        $data .= auth()->id();
        
        return substr(md5($data), 0, 20); // Shorter hash
    }

    // ✅ Store temporary order data in session
    private function storeTemporaryOrderData($paymentIntentId, $orderData)
    {
        // Store in session (valid for current request cycle)
        session(['stripe_order_' . $paymentIntentId => $orderData]);
    }

    // ✅ FIXED: Confirm Stripe Payment with session data
    public function confirmStripePayment(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
            'items' => 'required|array', // Keep for backup
            'total_amount' => 'required|numeric|min:0'
        ]);

        return DB::transaction(function () use ($request) {
            try {
                // Verify payment with Stripe
                $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

                if ($paymentIntent->status !== 'succeeded') {
                    return response()->json([
                        'error' => 'Payment not completed. Status: ' . $paymentIntent->status
                    ], 400);
                }

                // ✅ Get items from session/cache first, fallback to request
                $sessionKey = 'stripe_order_' . $request->payment_intent_id;
                $orderData = session($sessionKey);
                
                if (!$orderData) {
                    // Fallback to request data
                    $orderData = [
                        'items' => $request->items,
                        'total_amount' => $request->total_amount,
                        'user_id' => auth()->id()
                    ];
                }

                $items = $orderData['items'];

                // Check stock availability
                $stockCheck = $this->checkStockAvailability($items);
                if (!$stockCheck['available']) {
                    return response()->json([
                        'error' => $stockCheck['message']
                    ], 400);
                }

                // Verify order hash matches
                $expectedHash = $this->generateOrderHash($items);
                if ($paymentIntent->metadata->order_hash !== $expectedHash) {
                    return response()->json([
                        'error' => 'Order verification failed'
                    ], 400);
                }

                // Create Order with 'pending' status for online payments
                $order = Order::create([
                    'user_id' => auth()->id(),
                    'total_amount' => $orderData['total_amount'],
                    'payment_method' => 'stripe',
                    'payment_status' => 'paid',
                    'transaction_id' => $request->payment_intent_id,
                    'status' => 'pending' // ✅ CHANGED: 'confirmed' to 'pending'
                ]);

                // Create Order Items and Update Stock
                foreach ($items as $item) {
                    $book = Book::find($item['id']);
                    
                    if ($book) {
                        OrderItem::create([
                            'order_id' => $order->id,
                            'book_id' => $item['id'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['price'],
                            'total_price' => $item['price'] * $item['quantity']
                        ]);

                        // Update book stock
                        $book->decrement('stock', $item['quantity']);
                        $book->save();
                        
                        Log::info("Stock updated for book: {$book->title}", [
                            'book_id' => $book->id,
                            'quantity_sold' => $item['quantity'],
                            'remaining_stock' => $book->stock,
                            'order_id' => $order->id
                        ]);
                    }
                }

                // ✅ Clean up session data
                session()->forget($sessionKey);

                return response()->json([
                    'success' => true,
                    'order_id' => $order->id,
                    'transaction_id' => $request->payment_intent_id,
                    'message' => 'Stripe payment completed successfully!'
                ]);

            } catch (ApiErrorException $e) {
                Log::error('Stripe confirmation error: ' . $e->getMessage());
                return response()->json([
                    'error' => 'Stripe verification failed: ' . $e->getMessage()
                ], 500);
            } catch (\Exception $e) {
                Log::error('Payment confirmation failed: ' . $e->getMessage());
                return response()->json([
                    'error' => 'Payment confirmation failed: ' . $e->getMessage()
                ], 500);
            }
        });
    }

    public function checkStock(Request $request)
    {
        try {
            $items = json_decode($request->items, true);
            
            $outOfStockItems = [];
            $insufficientStockItems = [];

            foreach ($items as $item) {
                $book = Book::find($item['id']);
                
                if (!$book) {
                    $outOfStockItems[] = $item['title'];
                    continue;
                }

                if ($book->stock < $item['quantity']) {
                    $insufficientStockItems[] = [
                        'title' => $item['title'],
                        'requested' => $item['quantity'],
                        'available' => $book->stock
                    ];
                }
            }

            if (!empty($outOfStockItems)) {
                return response()->json([
                    'available' => false,
                    'message' => 'Some items are out of stock: ' . implode(', ', $outOfStockItems)
                ]);
            }

            if (!empty($insufficientStockItems)) {
                $errorMessage = 'Insufficient stock: ';
                foreach ($insufficientStockItems as $item) {
                    $errorMessage .= "{$item['title']} (Available: {$item['available']}, Requested: {$item['requested']}), ";
                }
                $errorMessage = rtrim($errorMessage, ', ');
                
                return response()->json([
                    'available' => false,
                    'message' => $errorMessage
                ]);
            }

            return response()->json([
                'available' => true,
                'message' => 'All items are in stock'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'available' => false,
                'message' => 'Error checking stock: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ FIXED: Single saveOrder method for all payment methods
    public function saveOrder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'total_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'payment_status' => 'required|string',
            'transaction_id' => 'nullable|string',
            'mobile_number' => 'nullable|string'
        ]);

        // If payment method is stripe, use confirmStripePayment instead
        if ($request->payment_method === 'stripe') {
            return $this->confirmStripePayment($request);
        }

        return DB::transaction(function () use ($request) {
            try {
                // Check stock availability
                $stockCheck = $this->checkStockAvailability($request->items);
                if (!$stockCheck['available']) {
                    return response()->json([
                        'error' => $stockCheck['message']
                    ], 400);
                }

                // Generate transaction ID if not provided
                $transactionId = $request->transaction_id ?? (
                    $request->payment_method === 'cod' ? 'cod_' . uniqid() :
                    $request->payment_method . '_' . uniqid()
                );

                // Determine initial status based on payment method
                $initialStatus = 'pending'; // All orders start as pending

                // Create Order
                $order = Order::create([
                    'user_id' => auth()->id(),
                    'total_amount' => $request->total_amount,
                    'payment_method' => $request->payment_method,
                    'payment_status' => $request->payment_status,
                    'transaction_id' => $transactionId,
                    'status' => $initialStatus, // ✅ This will be 'pending'
                    'mobile_number' => $request->mobile_number
                ]);

                // Create Order Items and Update Stock
                foreach ($request->items as $item) {
                    $book = Book::find($item['id']);
                    
                    if ($book) {
                        OrderItem::create([
                            'order_id' => $order->id,
                            'book_id' => $item['id'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['price'],
                            'total_price' => $item['price'] * $item['quantity']
                        ]);

                        // Update book stock
                        $book->decrement('stock', $item['quantity']);
                        $book->save();
                        
                        Log::info("Stock updated for book: {$book->title}", [
                            'book_id' => $book->id,
                            'quantity_sold' => $item['quantity'],
                            'remaining_stock' => $book->stock,
                            'order_id' => $order->id
                        ]);
                    }
                }

                return response()->json([
                    'success' => true,
                    'order_id' => $order->id,
                    'transaction_id' => $transactionId,
                    'message' => 'Order completed successfully! Stock updated.'
                ]);

            } catch (\Exception $e) {
                Log::error('Order processing failed: ' . $e->getMessage());
                return response()->json([
                    'error' => 'Order processing failed: ' . $e->getMessage()
                ], 500);
            }
        });
    }

    // Helper function for stock check
    private function checkStockAvailability($items)
    {
        $outOfStockItems = [];
        $insufficientStockItems = [];

        foreach ($items as $item) {
            $book = Book::find($item['id']);
            
            if (!$book) {
                $outOfStockItems[] = $item['title'];
                continue;
            }

            if ($book->stock < $item['quantity']) {
                $insufficientStockItems[] = [
                    'title' => $item['title'],
                    'requested' => $item['quantity'],
                    'available' => $book->stock
                ];
            }
        }

        if (!empty($outOfStockItems)) {
            return [
                'available' => false,
                'message' => 'Some items are out of stock: ' . implode(', ', $outOfStockItems)
            ];
        }

        if (!empty($insufficientStockItems)) {
            $errorMessage = 'Insufficient stock: ';
            foreach ($insufficientStockItems as $item) {
                $errorMessage .= "{$item['title']} (Available: {$item['available']}, Requested: {$item['requested']}), ";
            }
            $errorMessage = rtrim($errorMessage, ', ');
            
            return [
                'available' => false,
                'message' => $errorMessage
            ];
        }

        return ['available' => true, 'message' => 'All items in stock'];
    }

    // ✅ NEW: Update Order Status
    public function updateOrderStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled'
        ]);

        try {
            $oldStatus = $order->status;
            $order->update(['status' => $request->status]);

            Log::info("Order status updated", [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'order' => $order->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Order status update failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update order status: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ NEW: Get Orders for Admin
    public function getOrders(Request $request)
    {
        try {
            $query = Order::with(['user', 'items.book'])
                        ->latest();

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            $orders = $query->paginate(10);

            return response()->json([
                'orders' => $orders
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch orders: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch orders'
            ], 500);
        }
    }

    public function paymentSuccess()
    {
        return Inertia::render('PaymentSuccess');
    }

    public function paymentCancel()
    {
        return Inertia::render('PaymentCancel');
    }
}