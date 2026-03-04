<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    public const STATUS_CONVERTED = 'converted';
    public const STATUS_PENDING = 'pending';

    protected $fillable = [
        'user_id',
        'amount_original',
        'currency',
        'exchange_rate',
        'amount_brl',
        'status',
        'failure_reason',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_original' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'amount_brl' => 'decimal:2',
            'converted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
