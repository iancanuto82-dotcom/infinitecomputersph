<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReplacementWarrantyItem extends Model
{
    protected $fillable = [
        'processed_by',
        'product_id',
        'expense_id',
        'product_name',
        'type',
        'quantity',
        'unit_cost',
        'total_cost',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}

