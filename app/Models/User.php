<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Orders\Order;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'phone',
        'adresse',
        'active',
        'country_code',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
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
        'password' => 'hashed',
    ];

    // -------------------------------------------------------
    // Constantes de rôles
    // -------------------------------------------------------
    const ROLE_ADMIN       = 'ADMINUSER';
    const ROLE_CALL_CENTER = 'CALLCENTER';
    const ROLE_SALES       = 'VDS';
    const ROLE_MANAGER     = 'MNG';
    const ROLE_SECRETARY   = 'SCR';
    const ROLE_LIVREUR     = 'LVS';
    const ROLE_PARTNER     = 'PT';
    const ROLE_CASHIER     = 'CSA';
    const ROLE_ACCOUNTANT  = 'COMPTABLE';

    // -------------------------------------------------------
    // Helpers de rôle
    // -------------------------------------------------------
    public function isAdmin(): bool
    {
        return $this->user_type === self::ROLE_ADMIN;
    }

    public function isCallCenter(): bool
    {
        return $this->user_type === self::ROLE_CALL_CENTER;
    }

    public function isLivreur(): bool
    {
        return $this->user_type === self::ROLE_LIVREUR;
    }

    public function isSales(): bool
    {
        return $this->user_type === self::ROLE_SALES;
    }

    public function isManager(): bool
    {
        return $this->user_type === self::ROLE_MANAGER;
    }

    public function isSecretary(): bool
    {
        return $this->user_type === self::ROLE_SECRETARY;
    }

    public function isAccountant(): bool
    {
        return $this->user_type === self::ROLE_ACCOUNTANT;
    }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->user_type, (array) $roles, true);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function partnerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'partner_id');
    }

    public function courierDocuments(): HasMany
    {
        return $this->hasMany(CourierDocument::class);
    }

    public function createdPublicOrderLinks(): HasMany
    {
        return $this->hasMany(PublicOrderLink::class, 'created_by');
    }

    public function commissions(){
        return $this->hasMany(Commission::class);
    }
    public function wallets(){
        return $this->hasMany(Wallet::class);
    }

    public function responsibleStockLocations(): HasMany
    {
        return $this->hasMany(StockLocation::class, 'responsible_user_id');
    }

    public function createdStockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'created_by');
    }

    public function approvedStockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'approved_by');
    }

    public function requestedStockTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'requested_by');
    }

    public function createdStockCounts(): HasMany
    {
        return $this->hasMany(StockCount::class, 'created_by');
    }

    public function requestedStockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class, 'requested_by');
    }

    public function stockAuditLogs(): HasMany
    {
        return $this->hasMany(StockAuditLog::class);
    }

  
}
