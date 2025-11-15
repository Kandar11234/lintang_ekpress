<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_code',
        'user_id',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'customer_type',
        'company_name',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class, 'receiver_name', 'name');
    }

    // Generate customer code otomatis
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->customer_code)) {
                $year = date('y');
                $prefix = "CUST-{$year}-";
                
                $lastCustomer = self::where('customer_code', 'LIKE', $prefix . '%')
                                   ->orderBy('id', 'desc')
                                   ->first();
                
                if ($lastCustomer && preg_match('/CUST-\d{2}-(\d+)$/', $lastCustomer->customer_code, $matches)) {
                    $lastNumber = (int)$matches[1];
                    $newNumber = $lastNumber + 1;
                } else {
                    $newNumber = 1;
                }
                
                $customer->customer_code = $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}