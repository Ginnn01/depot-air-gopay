<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    protected $fillable = [
        'invoice_number', 'user_id', 'cash_session_id', 'customer_id', 'total',
        'payment_method', 'status', 'cash_received', 'change_amount', 'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => 'lunas', 'paid_at' => now()]);
    }

    /** Membuat nomor invoice, contoh: TRX-20261027-0001 */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'TRX-' . now()->format('Ymd') . '-';
        $countToday = static::whereDate('created_at', today())->count() + 1;

        return $prefix . str_pad($countToday, 4, '0', STR_PAD_LEFT);
    }
}
