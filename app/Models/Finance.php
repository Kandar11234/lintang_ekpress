<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Finance extends Model
{
    use HasFactory;

    protected $fillable = [
         'transaction_number',
    'user_id',
    'shipment_id',
    'type',
    'category',
    'description',
    'customer_name',  // ← TAMBAHKAN INI
    'amount',
    'payment_method',
    'transaction_date',
    'notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    // Generate transaction number otomatis
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($finance) {
            if (empty($finance->transaction_number)) {
                $year = date('y');
                $month = date('m');
                $prefix = $finance->type === 'income' ? 'IN-' : 'OUT-';
                $prefix .= "{$year}-{$month}-";
                
                $lastTransaction = self::where('transaction_number', 'LIKE', $prefix . '%')
                                       ->orderBy('id', 'desc')
                                       ->first();
                
                if ($lastTransaction && preg_match('/-(\d+)$/', $lastTransaction->transaction_number, $matches)) {
                    $lastNumber = (int)$matches[1];
                    $newNumber = $lastNumber + 1;
                } else {
                    $newNumber = 1;
                }
                
                $finance->transaction_number = $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}