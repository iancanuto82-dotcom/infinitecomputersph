<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $fillable = [
        'created_by',
        'sale_id',
        'quotation_name',
        'customer_name',
        'customer_contact',
        'notes',
        'subtotal',
        'labor_fee',
        'discount_type',
        'discount',
        'discount_amount',
        'grand_total',
        'items',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'labor_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'items' => 'array',
    ];
}
