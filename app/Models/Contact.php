<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A message sent through the Contact Us form. */
class Contact extends Model
{
    public const STATUSES = ['new', 'read', 'replied', 'archived'];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'product_interest',
        'message',
        'status',
        'ip_address',
    ];
}
