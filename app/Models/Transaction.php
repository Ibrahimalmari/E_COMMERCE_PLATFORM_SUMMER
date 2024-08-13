<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'store_id',
        'delivery_worker_id',
        'total_amount',
        'delivery_fee',
        'company_percentage',
        'amount_paid',
        'transaction_type',
        'transaction_date',
        'order_id', 
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
    ];

    // علاقة مع نموذج Store
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // علاقة مع نموذج DeliveryWorker
    public function deliveryWorker()
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_worker_id');
    }


    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // إعداد نسبة الشركة بناءً على نوع المعاملة
    public static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if ($transaction->delivery_worker_id) {
                // إذا كان يوجد delivery_worker_id، نسبة الشركة تكون 10%
                $transaction->company_percentage = 10.00;
            } elseif ($transaction->store_id) {
                // إذا كان يوجد store_id، نسبة الشركة تكون 20%
                $transaction->company_percentage = 20.00;
            }
        });
    }
}
