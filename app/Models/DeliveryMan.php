<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class DeliveryMan extends Authenticatable
{
    
    use HasApiTokens, HasFactory, Notifiable;

    const STATUS_ONLINE = 'متصل';
    const STATUS_OFFLINE = 'غير متصل';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'joining_date',
        'status',
        'PhotoOfPersonalID',
        'vehicle_image',
        'license_image',
        'vehicle_type',
        'vehicle_number',
        'NationalNumber',
        'role_id',
        'last_payment_date', 
        'account_status',     
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public static function getStatuses()
    {
        return [
            self::STATUS_ONLINE,
            self::STATUS_OFFLINE,
        ];
    }


    public function role(){
        return  $this->belongsTo(Role::class);
      }

      public function orders()
      {
          return $this->hasMany(Order::class, 'delivery_worker_id');
      }

      public function transactions()
      {
          return $this->hasMany(Transaction::class, 'delivery_worker_id');
      }
 
      

     
}
