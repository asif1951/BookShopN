<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'category',
        'publisher',
        'price',
        'stock',
        'photo',
        'description',
        'average_rating'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'average_rating' => 'decimal:1'
    ];

    // Check if book is in stock
    public function inStock()
    {
        return $this->stock > 0;
    }

    // Decrement stock
    public function decrementStock($quantity)
    {
        $this->decrement('stock', $quantity);
    }

    // Increment stock
    public function incrementStock($quantity)
    {
        $this->increment('stock', $quantity);
    }

    // Relationship with order items
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Add this relationship - specify the custom table name
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }
}