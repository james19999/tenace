<?php

namespace App\Models;

use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
class Costumer extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::created(function (self $customer): void {
            try {
                app(\App\Services\CustomerLoyaltyService::class)->initializeCustomer((int) $customer->id);
            } catch (\Throwable $exception) {
                report($exception);
            }
        });
    }
    protected $guard = "companiecostumer";
    protected  $fillable=[
          'name',
          'phone',
          'email',
          'adresse',
          'user_id',

    ];

    public function getNameAttribute($value): string
    {
        $legacyAnonymousPrefix = 'Client anonyme · ';

        return is_string($value) && str_starts_with($value, $legacyAnonymousPrefix)
            ? substr($value, strlen($legacyAnonymousPrefix))
            : (string) $value;
    }

    public function isAnonymous(): bool
    {
        $storedName = (string) ($this->getRawOriginal('name') ?? $this->attributes['name'] ?? '');

        return str_starts_with($storedName, 'CL-')
            || str_starts_with($storedName, 'Client anonyme · CL-');
    }

    // protected $casts = [

    //     'phone' => 'integer',
    // ];
    public function orders(){
        return $this->hasMany(Order::class);
    }

    public function latestOrder(): HasOne
    {
        return $this->hasOne(Order::class, 'costumer_id')->latestOfMany();
    }

    public function contactHistories(): HasMany
    {
        return $this->hasMany(CostumerContactHistory::class, 'costumer_id');
    }

    public function latestContactHistory(): HasOne
    {
        return $this->hasOne(CostumerContactHistory::class, 'costumer_id')->latestOfMany('contacted_at');
    }

    public function contactPreference(): HasOne
    {
        return $this->hasOne(CostumerContactPreference::class, 'costumer_id');
    }
}
