<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerBusinessRelationship extends Model
{
    use HasFactory;

    // Specify the table name
    protected $table = 'customer_business_relationships';

    // Allow mass assignment for these fields
    protected $fillable = [
        'customer_id',
        'business_id',
        'customer_tier',
        'last_interaction_date',
        'interaction_count',
        'total_points_earned',
        'total_points_redeemed',
    ];

    // Disable timestamps if they are not present in the table
    public $timestamps = false;

    // Relationships (Optional)
    public function customer()
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_id', 'customer_id');
    }

    public function business()
    {
        return $this->belongsTo(BusinessProfile::class, 'business_id', 'business_id');
    }
}
