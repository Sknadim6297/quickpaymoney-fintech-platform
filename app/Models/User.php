<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'gender',
        'account_holder_name',
        'bank_name',
        'account_number',
        'ifsc_code',
        'branch_name',
        'account_type',
        'usdt_wallet_address',
        'password',
        'role',
        'account_status',
        'verification_status',
        'email_verified_at',
        'totp_secret',
        'totp_enabled',
        'totp_last_counter',
        'balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'totp_secret',
        'balance',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'totp_secret' => 'encrypted',
            'totp_enabled' => 'boolean',
            'totp_last_counter' => 'integer',
            'balance' => 'decimal:2',
            'account_holder_name' => 'encrypted',
            'bank_name' => 'encrypted',
            'account_number' => 'encrypted',
            'ifsc_code' => 'encrypted',
            'branch_name' => 'encrypted',
            'account_type' => 'encrypted',
            'usdt_wallet_address' => 'encrypted',
        ];
    }

    public function exchangeRequests(): HasMany
    {
        return $this->hasMany(ExchangeRequest::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function balanceLedgerEntries(): HasMany
    {
        return $this->hasMany(BalanceLedgerEntry::class);
    }
}
