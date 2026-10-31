<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    protected $fillable = [
        'user_id', 'opened_at', 'closed_at', 'opening_cash',
        'expected_cash', 'actual_cash', 'difference', 'notes', 'status',
        'verified_at', 'verified_by',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'verified_at' => 'datetime',
        'opening_cash' => 'integer',
        'expected_cash' => 'integer',
        'actual_cash' => 'integer',
        'difference' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Pemilik yang menandai tutup kas ini sudah dicek. */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** Total penjualan tunai yang sudah lunas di sesi ini. */
    public function cashSalesTotal(): int
    {
        return (int) $this->transactions()
            ->where('payment_method', 'tunai')
            ->where('status', 'lunas')
            ->sum('total');
    }

    /** Hitung kas seharusnya dan selisih saat kasir menutup kas. */
    public function close(int $actualCash, ?string $notes = null): void
    {
        $expected = $this->opening_cash + $this->cashSalesTotal();

        $this->update([
            'closed_at' => now(),
            'expected_cash' => $expected,
            'actual_cash' => $actualCash,
            'difference' => $actualCash - $expected,
            'notes' => $notes,
            'status' => 'tutup',
        ]);
    }
}