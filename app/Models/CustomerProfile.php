<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_profiles'; // Explicitly set table name (optional)
    protected $primaryKey = 'id'; // Set the primary key to customer_id
    // public $incrementing = false; // Disable auto-increment if customer_id is not auto-incrementing
    // protected $keyType = 'int'; // Define the type of the primary key

    protected $fillable = [
        'customer_id',
        'points_balance',
        'birthday',
        'anniversary',
        'preferences',
        'total_rewards_earned',
        'total_rewards_redeemed',
        'preferred_notification_channel',
        'preferred_language',
        'qr_code_url',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'customer_id', 'user_id');
    }
}
