<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'active', 'can_invoice', 'can_verify_payments'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_last_step'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'active' => 'boolean', 'can_invoice' => 'boolean', 'can_verify_payments' => 'boolean', 'two_factor_secret' => 'encrypted', 'two_factor_confirmed_at' => 'datetime'];
    }

    public function isAdmin(): bool
    {
        return $this->active && $this->role === 'admin';
    }

    public function mayInvoice(): bool
    {
        return $this->isAdmin() || ($this->active && $this->can_invoice);
    }

    public function mayVerifyPayments(): bool
    {
        return $this->isAdmin() || ($this->active && $this->can_verify_payments);
    }
}
