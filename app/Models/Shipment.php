<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_number',
        'sender_name',
        'sender_phone',
        'sender_address',
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        'origin',
        'destination',
        'weight',
        'package_type',
        'status',
        'cost',
        'notes',
        'estimated_delivery',
        'actual_delivery',
        'user_id',
    ];

    protected $casts = [
        'estimated_delivery' => 'date',
        'actual_delivery' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Generate tracking number otomatis
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shipment) {
            if (empty($shipment->tracking_number)) {
                // Format: LPE-YY-MM-XX (contoh: LPE-25-11-01)
                $year = date('y');  // 25 untuk 2025
                $month = date('m'); // 11 untuk November
                
                // Cari nomor terakhir di bulan ini
                $prefix = "LPE-{$year}-{$month}-";
                $lastShipment = self::where('tracking_number', 'LIKE', $prefix . '%')
                                   ->orderBy('id', 'desc')
                                   ->first();
                
                if ($lastShipment && preg_match('/LPE-\d{2}-\d{2}-(\d+)$/', $lastShipment->tracking_number, $matches)) {
                    $lastNumber = (int)$matches[1];
                    $newNumber = $lastNumber + 1;
                } else {
                    // Nomor awal bulan ini
                    $newNumber = 1;
                }
                
                $shipment->tracking_number = $prefix . str_pad($newNumber, 2, '0', STR_PAD_LEFT);
            }
        });
    }
}