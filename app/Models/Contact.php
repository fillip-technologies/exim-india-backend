<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    public const TYPE_CONTACT = 'contact';
    public const TYPE_ORDER = 'order';

    public const STATUSES = ['new', 'read', 'replied', 'archived'];

    protected $fillable = [
        'type',
        'name',
        'email',
        'phone',
        'company',
        'product_interest',
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
}
