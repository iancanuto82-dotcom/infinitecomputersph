<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'invoice_no',
        'created_by',
        'sold_at',
        'customer_name',
        'customer_contact',
        'notes',
        'subtotal',
        'discount',
        'expenses',
        'grand_total',
        'payment_status',
        'amount_paid',
        'payment_mode',
        'sale_status',
        'cancelled_at',
        'refunded_at',
        'stock_restocked_at',
    ];

    protected $casts = [
        'sold_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'expenses' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'refunded_at' => 'datetime',
        'stock_restocked_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function effectiveStatus(): string
    {
        if ($this->refunded_at !== null) {
            return 'refunded';
        }

        if ($this->cancelled_at !== null) {
            return 'cancelled';
        }

        return (string) ($this->sale_status ?? 'completed');
    }
}
