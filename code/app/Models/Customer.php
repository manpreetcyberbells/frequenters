<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
class Customer extends Model
{
    public function profile()
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}