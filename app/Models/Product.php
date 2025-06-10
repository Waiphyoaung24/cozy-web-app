<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use App\Models\OrderItem;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;
    protected $fillable = [
        'name',
        'price',
        'stock',
        'image',
        'category_id',
        // Add any other fields you use for Product creation/editing
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
