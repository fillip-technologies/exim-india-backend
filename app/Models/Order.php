<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** An order / sample-request enquiry placed from a product page. */
class Order extends Model
{
    public const STATUSES = ['new', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'product_id',
        'quantity',
        'address',
        'message',
        'status',
        'ip_address',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
